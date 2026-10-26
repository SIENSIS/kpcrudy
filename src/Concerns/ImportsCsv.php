<?php

declare(strict_types=1);

namespace Kpcrudy\Concerns;

use Closure;
use CodeIgniter\HTTP\ResponseInterface;
use DateTime;
use FilesystemIterator;
use Kpcrudy\Exceptions\ImportAbortedException;
use RuntimeException;
use Throwable;

/**
 * Importació de dades des d'un fitxer CSV, en tres passos:
 *
 *  1. base/import (GET)               formulari per pujar el fitxer
 *  2. base/import (POST, _kp_step=upload)  previsualització: columnes, files i errors
 *  3. base/import (POST, _kp_step=confirm) importació i resultat
 *
 * Cada fila passa per la mateixa conversió, validació, hooks i permisos que
 * el formulari. Les columnes es reconeixen pel nom del camp o per l'etiqueta,
 * i els valors de les relacions i dels desplegables, per l'id o pel text, de
 * manera que un CSV exportat es pot tornar a importar tal qual.
 *
 * S'utilitza des de Kpcrudy\Crud.
 */
trait ImportsCsv
{
    /**
     * Opcions de la importació, o null si no està activada.
     *
     * @var array<string, mixed>|null
     */
    protected ?array $import = null;

    /** @var array<string, Closure> */
    protected array $importCallbacks = [];

    /**
     * Activa la importació des de CSV.
     *
     * Opcions:
     *  - 'fields'  camps que es poden importar (per defecte, els del formulari d'alta)
     *  - 'key'     camp que identifica un registre existent per actualitzar-lo
     *              (per defecte, la clau primària)
     *  - 'maxRows' nombre màxim de files (per defecte, Config::$importMaxRows)
     *  - 'maxSize' mida màxima del fitxer en KB (per defecte, Config::$importMaxSize)
     *
     * @param array<string, mixed> $options
     */
    public function enableImport(array $options = []): static
    {
        $this->import = [
            'fields'  => isset($options['fields']) ? array_values($options['fields']) : null,
            'key'     => $options['key'] ?? null,
            'maxRows' => (int) ($options['maxRows'] ?? $this->config->importMaxRows),
            'maxSize' => (int) ($options['maxSize'] ?? $this->config->importMaxSize),
        ];

        return $this;
    }

    /**
     * Transforma el valor d'una columna abans de validar-lo:
     * fn (string $value, array $row): mixed. $row conté els valors de la fila
     * (camp => text del CSV). El que retorna es desa tal qual.
     */
    public function callbackImport(string $field, Closure $callback): static
    {
        $this->importCallbacks[$field] = $callback;

        return $this;
    }

    protected function importAllowed(): bool
    {
        return $this->import !== null && $this->can('import') && ($this->can('add') || $this->can('edit'));
    }

    // ------------------------------------------------------------------
    // Operació
    // ------------------------------------------------------------------

    protected function importOperation(): ResponseInterface|string
    {
        if (! $this->importAllowed()) {
            return $this->redirectToList('danger', lang('Kpcrudy.notAllowed'));
        }

        if ($this->request->getGet('template') !== null) {
            return $this->importTemplate();
        }

        if (! $this->isPost()) {
            // Tornar a la pujada anul·la la previsualització pendent
            $this->discardImportFile();

            return $this->importView('upload');
        }

        return $this->request->getPost('_kp_step') === 'confirm'
            ? $this->importConfirm()
            : $this->importUpload();
    }

    protected function importUpload(): string
    {
        $mode    = $this->request->getPost('mode') === 'upsert' ? 'upsert' : 'insert';
        $onError = $this->request->getPost('on_error') === 'skip' ? 'skip' : 'stop';
        $file    = $this->request->getFile('file');
        $form    = ['mode' => $mode, 'onError' => $onError];

        if ($file === null || ! $file->isValid() || ! in_array(strtolower($file->getClientExtension()), ['csv', 'txt'], true)) {
            return $this->importView('upload', $form + ['error' => lang('Kpcrudy.importInvalidFile')]);
        }

        if (($file->getSize() / 1024) > $this->import['maxSize']) {
            return $this->importView('upload', $form + ['error' => lang('Kpcrudy.importTooBig', [$this->import['maxSize']])]);
        }

        // Una previsualització anterior que no s'ha confirmat queda substituïda per aquesta
        $this->discardImportFile();

        $token = bin2hex(random_bytes(16));
        $file->move($this->importDir(), $token . '.csv');

        // El fitxer pot contenir dades personals o contrasenyes: només el pot llegir el servidor
        @chmod($this->importDir() . '/' . $token . '.csv', 0600);

        $analysis = $this->analyzeImport($this->importDir() . '/' . $token . '.csv', $mode);

        if (isset($analysis['error'])) {
            @unlink($this->importDir() . '/' . $token . '.csv');

            return $this->importView('upload', $form + ['error' => $analysis['error']]);
        }

        session()->set($this->stateKey() . '_import', [
            'token'   => $token,
            'mode'    => $mode,
            'onError' => $onError,
            'name'    => $file->getClientName(),
        ]);

        $importable = $analysis['counts']['insert'] + $analysis['counts']['update'];

        return $this->importView('preview', $form + [
            'token'     => $token,
            'fileName'  => $file->getClientName(),
            'columns'   => $analysis['columns'],
            'counts'    => $analysis['counts'],
            'rows'      => array_slice($analysis['rows'], 0, 20),
            'total'     => count($analysis['rows']),
            'errors'    => array_slice(array_filter($analysis['rows'], static fn (array $row): bool => $row['errors'] !== []), 0, 200),
            'importable' => $importable,
            'canImport' => $importable > 0 && ($onError === 'skip' || $analysis['counts']['error'] === 0),
        ]);
    }

    protected function importConfirm(): ResponseInterface|string
    {
        $saved = session()->get($this->stateKey() . '_import');
        $token = $this->request->getPost('token');
        $token = is_string($token) ? $token : '';
        $path  = $this->importDir() . '/' . preg_replace('/[^a-f0-9]/', '', $token) . '.csv';

        if (! is_array($saved) || ! is_string($saved['token'] ?? null) || ! hash_equals($saved['token'], $token) || ! is_file($path)) {
            return $this->redirectToList('warning', lang('Kpcrudy.importExpired'));
        }

        $result = $this->runImport($path, $saved['mode'], $saved['onError']);

        @unlink($path);
        session()->remove($this->stateKey() . '_import');

        if ($result['errors'] === []) {
            return $this->redirectToList('success', lang('Kpcrudy.importDone', [$result['created'], $result['updated'], $result['skipped']]));
        }

        return $this->importView('report', ['result' => $result, 'fileName' => $saved['name']]);
    }

    /**
     * CSV amb la capçalera de les columnes que es poden importar.
     */
    protected function importTemplate(): ResponseInterface
    {
        $columns = $this->importFieldList();
        $key     = $this->importKey();

        if (! in_array($key, $columns, true)) {
            array_unshift($columns, $key);
        }

        $handle = fopen('php://temp', 'r+b');

        if ($this->config->csvBom) {
            fwrite($handle, "\xEF\xBB\xBF");
        }

        fputcsv($handle, array_map($this->label(...), $columns), $this->config->csvDelimiter, '"', '');
        rewind($handle);
        $csv = (string) stream_get_contents($handle);
        fclose($handle);

        $filename = (url_title((string) $this->subjectPlural, '-', true) ?: 'import') . '-template.csv';

        return service('response')
            ->setHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($csv);
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function importView(string $step, array $data = []): string
    {
        $key = $this->importKey();

        return $this->output('import', $data + [
            'step'        => $step,
            'action'      => $this->url('import'),
            'templateUrl' => $this->withQuery($this->url('import'), ['template' => 1]),
            'backUrl'     => $this->backUrl(),
            'mode'        => 'insert',
            'onError'     => 'stop',
            'error'       => null,
            'keyLabel'    => $this->label($key),
            'canInsert'   => $this->can('add'),
            'canUpdate'   => $this->can('edit'),
            'maxRows'     => $this->import['maxRows'],
            'maxSize'     => $this->import['maxSize'],
            'fieldsHelp'  => array_map(fn (string $name): array => [
                'name'     => $name,
                'label'    => $this->label($name),
                'required' => in_array('required', $this->fieldRules($name, 'add', null), true),
            ], $this->importFieldList()),
        ], lang('Kpcrudy.importTitle', [$this->subjectPlural]));
    }

    // ------------------------------------------------------------------
    // Anàlisi i importació
    // ------------------------------------------------------------------

    /**
     * @return array<string, mixed>
     */
    protected function analyzeImport(string $path, string $mode): array
    {
        $csv = $this->readImportCsv($path);

        if (isset($csv['error'])) {
            return $csv;
        }

        $mapping = $this->mapImportHeader($csv['header']);

        if ($mapping['fields'] === [] && $mapping['key'] === null) {
            return ['error' => lang('Kpcrudy.importNoColumns')];
        }

        $rows   = [];
        $counts = ['insert' => 0, 'update' => 0, 'error' => 0];

        foreach ($csv['rows'] as [$line, $cells]) {
            $row    = $this->prepareImportRow($cells, $mapping, $mode);
            $action = $row['errors'] === [] ? $row['action'] : 'error';
            $counts[$action]++;

            // Les contrasenyes i els altres camps secrets no es mostren mai a la previsualització
            $values = $row['raw'];

            foreach ($values as $field => $value) {
                if ($value !== '' && $this->isSensitive($field)) {
                    $values[$field] = '••••••';
                }
            }

            $rows[] = [
                'line'   => $line,
                'action' => $action,
                'values' => $values,
                'errors' => $row['errors'],
            ];
        }

        return ['columns' => $mapping['columns'], 'rows' => $rows, 'counts' => $counts];
    }

    /**
     * Importa el fitxer. Amb $onError 'stop' tot va en una transacció i, si
     * alguna fila falla, no es desa res; amb 'skip' cada fila és independent.
     *
     * @return array{created: int, updated: int, skipped: int, aborted: bool, errors: list<array{line: int, errors: list<string>}>}
     */
    protected function runImport(string $path, string $mode, string $onError): array
    {
        $result = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'aborted' => false, 'errors' => []];
        $csv    = $this->readImportCsv($path);

        if (isset($csv['error'])) {
            $result['errors'][] = ['line' => 0, 'errors' => [$csv['error']]];

            return $result;
        }

        $mapping = $this->mapImportHeader($csv['header']);

        // Importa una fila. Retorna l'acció feta ('insert' o 'update') o un error amb
        // la línia. Els hooks posteriors (afterInsert...) s'afegeixen a $after i
        // s'executen només quan la transacció s'ha confirmat, com en el formulari.
        $importRow = function (int $line, array $cells, array &$after) use ($mapping, $mode): array|string {
            $row = $this->prepareImportRow($cells, $mapping, $mode);

            if ($row['errors'] !== []) {
                return ['line' => $line, 'errors' => array_values($row['errors']), 'exception' => false];
            }

            try {
                $error = $this->commitImportRow($row, $after);
            } catch (Throwable $e) {
                return ['line' => $line, 'errors' => [$this->errorMessage($e)], 'exception' => true];
            }

            return $error === null ? $row['action'] : ['line' => $line, 'errors' => [$error], 'exception' => false];
        };

        $count = static function (array &$result, array|string $outcome): void {
            if (is_string($outcome)) {
                $result[$outcome === 'insert' ? 'created' : 'updated']++;
            } else {
                unset($outcome['exception']);
                $result['errors'][] = $outcome;
            }
        };

        if ($onError === 'stop') {
            $after = [];
            $done  = ['created' => 0, 'updated' => 0, 'errors' => []];

            try {
                $this->transaction(function () use ($csv, $importRow, $count, &$done, &$after): void {
                    foreach ($csv['rows'] as [$line, $cells]) {
                        $outcome = $importRow($line, $cells, $after);
                        $count($done, $outcome);

                        // Després d'un error de base de dades la transacció ja no és fiable
                        if (is_array($outcome) && $outcome['exception']) {
                            break;
                        }
                    }

                    if ($done['errors'] !== []) {
                        throw new ImportAbortedException();
                    }
                });
            } catch (Throwable $e) {
                if (! $e instanceof ImportAbortedException) {
                    $done['errors'][] = ['line' => 0, 'errors' => [$this->errorMessage($e)]];
                }

                $result['aborted'] = true;
                $result['skipped'] = count($csv['rows']);
                $result['errors']  = $done['errors'];

                return $result;
            }

            array_map(static fn (Closure $hook) => $hook(), $after);

            return array_merge($result, ['created' => $done['created'], 'updated' => $done['updated']]);
        }

        foreach ($csv['rows'] as [$line, $cells]) {
            $after   = [];
            $outcome = null;

            try {
                $this->transaction(static function () use ($importRow, $line, $cells, &$after, &$outcome): void {
                    $outcome = $importRow($line, $cells, $after);

                    // Una fila amb errors no ha de deixar res escrit
                    if (is_array($outcome)) {
                        throw new ImportAbortedException();
                    }
                });
            } catch (ImportAbortedException) {
                // $outcome ja conté l'error de la fila
            } catch (Throwable $e) {
                $outcome = ['line' => $line, 'errors' => [$this->errorMessage($e)], 'exception' => true];
            }

            $count($result, $outcome);

            if (is_array($outcome)) {
                $result['skipped']++;
            } else {
                array_map(static fn (Closure $hook) => $hook(), $after);
            }
        }

        return $result;
    }

    /**
     * Converteix i valida una fila. No escriu res.
     *
     * @param list<string|null>    $cells
     * @param array<string, mixed> $mapping
     *
     * @return array<string, mixed>
     */
    protected function prepareImportRow(array $cells, array $mapping, string $mode): array
    {
        $raw = [];

        foreach ($mapping['fields'] as $index => $field) {
            $raw[$field] = $this->importCell($cells[$index] ?? '');
        }

        $keyField = $this->importKey();
        $keyValue = $mapping['key'] !== null ? $this->importCell($cells[$mapping['key']] ?? '') : '';
        $record   = null;
        $row      = ['action' => 'insert', 'id' => null, 'record' => null, 'raw' => $raw, 'data' => [], 'nn' => [], 'errors' => []];

        // Si falten columnes, les cel·les buides esborrarien dades sense voler-ho
        $indexes = array_keys($mapping['fields'] + ($mapping['key'] !== null ? [$mapping['key'] => $keyField] : []));

        if ($indexes !== [] && count($cells) <= max($indexes)) {
            $row['errors'][] = lang('Kpcrudy.importShortRow');

            return $row;
        }

        if ($mode === 'upsert' && $keyValue !== '') {
            $matches = $keyField === $this->primaryKey && ! $this->isValidId($keyValue)
                ? []
                : $this->baseBuilder(false)->where("{$this->table}.{$keyField}", $keyValue)->limit(2)->get()->getResultArray();

            if (count($matches) > 1) {
                $row['errors'][] = lang('Kpcrudy.importAmbiguous', [$this->label($keyField), $keyValue]);

                return $row;
            }

            $record = $matches[0] ?? null;
        }

        $action = $record === null ? 'insert' : 'update';
        $id     = $record === null ? null : (string) $record[$this->primaryKey];
        $row    = ['action' => $action, 'id' => $id, 'record' => $record] + $row;

        if (! ($action === 'insert' ? $this->can('add') : $this->can('edit', $record))) {
            $row['errors'][] = lang('Kpcrudy.importRowNotAllowed');

            return $row;
        }

        // En una actualització només es canvien els camps del formulari d'edició,
        // i els de l'abast (where) sempre tenen el valor de l'abast
        $scope    = $this->scopeValues();
        $formMode = $action === 'insert' ? 'add' : 'edit';
        $fields   = $action === 'insert' ? $this->importFieldList() : array_values(array_intersect(array_keys($raw), $this->editFieldList()));
        $fields   = array_values(array_diff($fields, array_keys($scope)));
        $values   = $raw;

        // Valors per defecte (setDefault) per a les columnes que no hi són
        if ($action === 'insert') {
            foreach ($this->defaults as $field => $default) {
                if (in_array($field, $fields, true) && ! array_key_exists($field, $values)) {
                    $values[$field] = is_array($default) ? implode(',', $default) : (string) $default;
                }
            }
        }

        $input = [];
        $rules = [];

        // Els camps ocults amb valor fix es desen sempre, encara que no siguin al fitxer
        foreach ($action === 'insert' ? $this->addFieldList() : $this->editFieldList() as $name) {
            $options = $this->types[$name]['options'] ?? [];

            if ($this->typeOf($name) === 'hidden' && array_key_exists('value', $options)) {
                $row['data'][$name] = $options['value'];
            }
        }

        foreach ($fields as $name) {
            $type    = $this->typeOf($name);
            $options = $this->types[$name]['options'] ?? [];

            if (! $this->isEditable($name, $formMode)) {
                continue;
            }

            $fieldRules = $this->fieldRules($name, $formMode, $id);

            if ($fieldRules !== []) {
                $rules[$name] = ['label' => $this->label($name), 'rules' => $fieldRules];
            }

            if (! array_key_exists($name, $values)) {
                continue;
            }

            [$value, $error] = $this->convertImportValue($name, $values[$name], $raw);

            if ($error !== null) {
                $row['errors'][$name] = $error;
                unset($rules[$name]);

                continue;
            }

            if ($type === 'relation_nn') {
                $input[$name]     = $value;
                $row['nn'][$name] = $value;

                continue;
            }

            if ($type === 'password') {
                $input[$name] = (string) $value;

                if ($value !== '' && $value !== null) {
                    $row['data'][$name] = ($options['hash'] ?? true) ? password_hash((string) $value, PASSWORD_DEFAULT) : $value;
                }

                continue;
            }

            $input[$name]       = $value;
            $row['data'][$name] = $value;
        }

        if ($rules !== []) {
            $validation = service('validation', null, false);

            if (! $validation->setRules($rules)->run($input)) {
                $row['errors'] += $validation->getErrors();
            }
        }

        $row['data'] = array_merge($row['data'], $scope);

        // El registre, tal com quedaria, ha de continuar sent editable per l'usuari
        if ($row['errors'] === [] && $action === 'update' && ! $this->can('edit', array_merge($record, $row['data']))) {
            $row['errors'][] = lang('Kpcrudy.importRowNotAllowed');
        }

        return $row;
    }

    /**
     * Text d'una cel·la, sense espais ni l'apòstrof que l'exportació afegeix
     * davant dels valors que el full de càlcul prendria per fórmules.
     */
    protected function importCell(mixed $cell): string
    {
        $value = trim((string) $cell);

        if (str_starts_with($value, "'") && preg_match("/^'\\s*[=+\\-@＝＋－＠]/u", $value)) {
            $value = substr($value, 1);
        }

        return $value;
    }

    /**
     * Desa una fila ja validada. Retorna un missatge d'error o null.
     *
     * @param array<string, mixed> $row
     */
    protected function commitImportRow(array $row, array &$after): ?string
    {
        $data = $row['data'];
        $id   = $row['id'];

        $hook = $row['action'] === 'insert'
            ? $this->callHook('beforeInsert', $data)
            : $this->callHook('beforeUpdate', $data, (string) $id, $row['record']);

        if ($hook === false || is_string($hook)) {
            return is_string($hook) ? $hook : lang('Kpcrudy.cancelled');
        }

        if (is_array($hook)) {
            $data = array_merge($hook, $this->scopeValues());
        }

        if ($row['action'] === 'insert') {
            $result = $this->model->insert($data, true);

            if ($result === false) {
                return implode(' ', $this->model->errors());
            }

            $id = (string) (is_int($result) && $result > 0 ? $result : ($data[$this->primaryKey] ?? $this->db->insertID()));
        } elseif ($data !== [] && ! $this->model->update($id, $this->withKeyForRules($data, (string) $id))) {
            return implode(' ', $this->model->errors());
        }

        $this->syncNN((string) $id, $row['nn']);
        $this->assertInScope((string) $id);

        $after[] = $row['action'] === 'insert'
            ? fn () => $this->callHook('afterInsert', $id, $data)
            : fn () => $this->callHook('afterUpdate', $id, $data, $row['record']);

        return null;
    }

    /**
     * Converteix el text d'una cel·la al valor que espera el camp.
     *
     * @param array<string, string> $row
     *
     * @return array{0: mixed, 1: string|null} [valor, error]
     */
    protected function convertImportValue(string $name, string $value, array $row): array
    {
        $type    = $this->typeOf($name);
        $invalid = lang('Kpcrudy.importInvalidValue', [$this->label($name), $value]);

        if (isset($this->importCallbacks[$name])) {
            $converted = ($this->importCallbacks[$name])($value, $row);

            // El valor que retorna el callback també ha de ser una de les opcions permeses
            if ($type === 'relation_nn') {
                $ids = array_map('strval', (array) $converted);

                return array_diff($ids, array_map('strval', array_keys($this->optionsFor($name)))) === []
                    ? [array_values(array_unique($ids)), null]
                    : [null, $invalid];
            }

            if (in_array($type, ['relation', 'enum', 'select'], true) && $converted !== null && $converted !== ''
                && ! array_key_exists((string) $converted, $this->optionsFor($name))) {
                return [null, $invalid];
            }

            return [$converted, null];
        }

        if ($value === '') {
            return match ($type) {
                'relation_nn' => [[], null],
                'boolean'     => [0, null],
                'password'    => ['', null],
                default       => [$this->isNullable($name) ? null : '', null],
            };
        }

        switch ($type) {
            case 'relation':
            case 'enum':
            case 'select':
                $match = $this->matchImportOption($name, $value);

                return match ($match) {
                    null    => [null, $invalid],
                    false   => [null, lang('Kpcrudy.importAmbiguous', [$this->label($name), $value])],
                    default => [$match, null],
                };

            case 'relation_nn':
                $ids = [];

                foreach (preg_split('/\s*[,|]\s*/', $value) as $item) {
                    if ($item === '') {
                        continue;
                    }

                    $match = $this->matchImportOption($name, $item);

                    if ($match === null) {
                        return [null, lang('Kpcrudy.importInvalidValue', [$this->label($name), $item])];
                    }

                    if ($match === false) {
                        return [null, lang('Kpcrudy.importAmbiguous', [$this->label($name), $item])];
                    }

                    $ids[] = $match;
                }

                return [array_values(array_unique($ids)), null];

            case 'boolean':
                $lower = mb_strtolower($value);
                $true  = ['1', 'true', 'yes', 'y', 'on', 'x', 'si', 'sí', 's', mb_strtolower(lang('Kpcrudy.yes'))];
                $false = ['0', 'false', 'no', 'n', 'off', mb_strtolower(lang('Kpcrudy.no'))];

                return match (true) {
                    in_array($lower, $true, true)  => [1, null],
                    in_array($lower, $false, true) => [0, null],
                    default                        => [null, $invalid],
                };

            case 'date':
            case 'datetime':
                $date = $this->parseImportDate($value, $type === 'datetime');

                return $date === null ? [null, $invalid] : [$date, null];

            case 'number':
            case 'decimal':
                $number = $this->parseImportNumber($value);

                return $number === null ? [null, $invalid] : [$number, null];

            default:
                return [$value, null];
        }
    }

    /**
     * Valor d'una opció a partir de l'id o del text que es mostra. Retorna
     * null si no n'hi ha cap i false si el text coincideix amb més d'una opció.
     */
    protected function matchImportOption(string $name, string $value): string|false|null
    {
        $options = $this->optionsFor($name);

        if (array_key_exists($value, $options)) {
            return $value;
        }

        $lower   = mb_strtolower($value);
        $matches = array_keys(array_filter($options, static fn (string $label): bool => mb_strtolower($label) === $lower));

        return match (count($matches)) {
            0       => null,
            1       => (string) $matches[0],
            default => false,
        };
    }

    protected function parseImportDate(string $value, bool $withTime): ?string
    {
        $formats = $withTime
            ? ['Y-m-d H:i:s', 'Y-m-d H:i', 'Y-m-d\TH:i', $this->config->datetimeFormat, $this->config->datetimeFormat . ':s', 'Y-m-d', $this->config->dateFormat]
            : ['Y-m-d', $this->config->dateFormat];

        foreach (array_unique($formats) as $format) {
            $date = DateTime::createFromFormat('!' . $format, $value);

            if ($date !== false && $date->format($format) === $value) {
                return $date->format($withTime ? 'Y-m-d H:i:s' : 'Y-m-d');
            }
        }

        return null;
    }

    /**
     * Accepta "1234.5", "1234,5", "1.234,50", "1,234.50", "€ 12.00", "-3"...
     * Qualsevol altre caràcter (lletres, parèntesis...) fa que el valor no sigui vàlid.
     */
    protected function parseImportNumber(string $value): ?string
    {
        // Només s'ignoren els espais (també els no separables) i els símbols de moneda
        $clean = (string) preg_replace('/[\s\x{00A0}\x{202F}€$£]/u', '', $value);

        if (! preg_match('/^-?\d[\d.,]*$/', $clean) || preg_match('/[.,]{2}|[.,]$/', $clean)) {
            return null;
        }

        $comma = strrpos($clean, ',');
        $dot   = strrpos($clean, '.');

        if ($comma !== false && ($dot === false || $comma > $dot)) {
            // La coma és el separador decimal
            $clean = str_replace(['.', ','], ['', '.'], $clean);
        } else {
            $clean = str_replace(',', '', $clean);
        }

        return is_numeric($clean) ? $clean : null;
    }

    // ------------------------------------------------------------------
    // Fitxer CSV
    // ------------------------------------------------------------------

    /**
     * Llegeix el CSV: detecta la codificació (UTF-8 o Windows-1252) i el
     * separador (punt i coma, coma o tabulador).
     *
     * @return array<string, mixed> ['header' => [...], 'rows' => [[línia, cel·les], ...]] o ['error' => ...]
     */
    protected function readImportCsv(string $path): array
    {
        $content = (string) file_get_contents($path);
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);

        if (! mb_check_encoding($content, 'UTF-8')) {
            $content = mb_convert_encoding($content, 'UTF-8', 'Windows-1252');
        }

        $content   = str_replace(["\r\n", "\r"], "\n", $content);
        $firstLine = strtok($content, "\n") ?: '';
        $counts    = [';' => substr_count($firstLine, ';'), ',' => substr_count($firstLine, ','), "\t" => substr_count($firstLine, "\t")];
        arsort($counts);
        $delimiter = (string) array_key_first($counts);

        $handle = fopen('php://temp', 'r+');
        fwrite($handle, $content);
        unset($content);
        rewind($handle);

        $header = null;
        $rows   = [];
        $line   = 0;
        $error  = null;

        while (($cells = fgetcsv($handle, null, $delimiter, '"', '')) !== false) {
            $line++;

            if (trim(implode('', array_map('strval', $cells))) === '') {
                continue;
            }

            if ($header === null) {
                $header = array_map(static fn ($cell): string => trim((string) $cell), $cells);

                if (count($header) > 1000) {
                    $error = lang('Kpcrudy.importInvalidFile');
                    break;
                }

                continue;
            }

            // Es para de llegir tan bon punt se superen els límits, per no omplir la memòria
            if (count($cells) > count($header) + 100) {
                $error = lang('Kpcrudy.importInvalidFile');
                break;
            }

            if (count($rows) >= $this->import['maxRows']) {
                $error = lang('Kpcrudy.importTooManyRows', [$this->import['maxRows']]);
                break;
            }

            $rows[] = [$line, $cells];
        }

        fclose($handle);

        if ($error !== null) {
            return ['error' => $error];
        }

        if ($header === null || $rows === []) {
            return ['error' => lang('Kpcrudy.importEmpty')];
        }

        return ['header' => $header, 'rows' => $rows];
    }

    /**
     * Associa les columnes del CSV als camps, pel nom o per l'etiqueta.
     *
     * @param list<string> $header
     *
     * @return array{fields: array<int, string>, key: int|null, columns: list<array<string, mixed>>}
     */
    protected function mapImportHeader(array $header): array
    {
        $fields     = $this->importFieldList();
        $key        = $this->importKey();
        $normalize  = static fn (string $text): string => preg_replace('/\s+/', ' ', mb_strtolower(trim($text)));
        $candidates = [];

        foreach (array_unique([...$fields, $key]) as $field) {
            $candidates[$normalize($field)] ??= $field;
            $candidates[$normalize($this->label($field))] ??= $field;
        }

        $map     = ['fields' => [], 'key' => null, 'columns' => []];
        $used    = [];

        foreach ($header as $index => $title) {
            $field = $candidates[$normalize($title)] ?? null;

            if ($field !== null && isset($used[$field])) {
                $field = null;
            }

            if ($field !== null) {
                $used[$field] = true;

                if ($field === $key) {
                    $map['key'] = $index;
                }

                if (in_array($field, $fields, true)) {
                    $map['fields'][$index] = $field;
                }
            }

            $map['columns'][] = [
                'header' => $title,
                'field'  => $field,
                'label'  => $field !== null ? $this->label($field) : null,
                'key'    => $field !== null && $field === $key,
            ];
        }

        return $map;
    }

    /**
     * Camps que es poden importar: els configurats o els del formulari d'alta,
     * sense fitxers, camps virtuals ni de només lectura.
     *
     * @return list<string>
     */
    protected function importFieldList(): array
    {
        $fields = $this->import['fields'] ?? $this->addFieldList();

        return array_values(array_filter($fields, function (string $name): bool {
            $type    = $this->typeOf($name);
            $options = $this->types[$name]['options'] ?? [];

            return ! in_array($type, ['upload', 'image', 'virtual', 'readonly'], true)
                && ! ($type === 'hidden' && array_key_exists('value', $options));
        }));
    }

    protected function importKey(): string
    {
        return $this->import['key'] ?? $this->primaryKey;
    }

    protected function importDir(): string
    {
        $dir = WRITEPATH . 'uploads/kpcrudy-import';

        if (! is_dir($dir) && ! @mkdir($dir, 0700, true) && ! is_dir($dir)) {
            throw new RuntimeException("Kpcrudy: no es pot crear el directori '{$dir}'.");
        }

        return $dir;
    }

    /**
     * Esborra el fitxer de la previsualització pendent de l'usuari (si n'hi ha)
     * i els d'importacions abandonades fa més d'una hora.
     */
    protected function discardImportFile(): void
    {
        $previous = session()->get($this->stateKey() . '_import');

        if (is_array($previous) && is_string($previous['token'] ?? null) && preg_match('/^[a-f0-9]{32}$/', $previous['token'])) {
            @unlink($this->importDir() . '/' . $previous['token'] . '.csv');
        }

        session()->remove($this->stateKey() . '_import');

        foreach (new FilesystemIterator($this->importDir()) as $file) {
            if ($file->isFile() && $file->getExtension() === 'csv' && $file->getMTime() < time() - 3600) {
                @unlink($file->getPathname());
            }
        }
    }
}
