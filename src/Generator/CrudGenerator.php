<?php

declare(strict_types=1);

namespace Kpcrudy\Generator;

use CodeIgniter\Database\BaseConnection;
use RuntimeException;

/**
 * Genera el mètode de controlador, la ruta i, opcionalment, el model d'un
 * CRUD a partir de l'estructura d'una taula. El fa servir la comanda
 * "php spark kpcrudy:make".
 *
 * El codi generat és un punt de partida: fa servir els valors per defecte de
 * la llibreria i hi afegeix el que es pot deduir de la base de dades
 * (relacions, etiquetes, tipus de camp i regles úniques).
 */
class CrudGenerator
{
    /**
     * Camps que la llibreria gestiona sola i no surten als formularis.
     */
    private const AUTO_FIELDS = ['created_at', 'updated_at', 'deleted_at'];

    /**
     * Camps que, si existeixen, descriuen bé un registre en una relació.
     */
    private const DISPLAY_CANDIDATES = ['name', 'title', 'label', 'username', 'code', 'email', 'description'];

    /**
     * Noms de taula i de columna que es poden posar sense risc al codi generat.
     */
    public const NAME_PATTERN = '/^[A-Za-z_][A-Za-z0-9_]*$/';

    /**
     * Nom curt d'una classe (controlador, model).
     */
    public const CLASS_PATTERN = '/^[A-Z][A-Za-z0-9_]*$/';

    public function __construct(private BaseConnection $db)
    {
    }

    public static function isSafeName(string $name): bool
    {
        return (bool) preg_match(self::NAME_PATTERN, $name);
    }

    /**
     * @return list<string>
     */
    public function tables(): array
    {
        $tables = array_values(array_filter(
            $this->db->listTables(),
            static fn (string $table): bool => $table !== 'migrations',
        ));
        sort($tables);

        return $tables;
    }

    /**
     * Estructura de la taula i tot el que se'n pot deduir.
     *
     * @return array<string, mixed>
     */
    public function analyze(string $table): array
    {
        // Els noms acaben dins del codi PHP generat: abans de res, només els segurs
        if (! self::isSafeName($table)) {
            throw new RuntimeException("The table name \"{$table}\" cannot be used: only letters, numbers and _ are allowed (and it cannot start with a number).");
        }

        if (! $this->db->tableExists($table, false)) {
            throw new RuntimeException("The table \"{$table}\" does not exist.");
        }

        $fieldData = $this->db->getFieldData($table);
        $unsafe    = array_filter(
            array_map(static fn ($field): string => (string) $field->name, $fieldData),
            static fn (string $name): bool => ! self::isSafeName($name),
        );

        if ($unsafe !== []) {
            throw new RuntimeException('The table "' . $table . '" has columns whose names cannot be used in generated code: '
                . implode(', ', array_map(static fn (string $name): string => '"' . addcslashes($name, "\0..\37\"\\\177..\377") . '"', $unsafe))
                . '. Only letters, numbers and _ are allowed (and they cannot start with a number).');
        }

        $fields = [];
        $pk     = null;

        foreach ($fieldData as $field) {
            $fields[(string) $field->name] = [
                'type'       => strtolower((string) $field->type),
                'max_length' => isset($field->max_length) ? (int) $field->max_length : null,
                'nullable'   => (bool) ($field->nullable ?? true),
                'default'    => $field->default ?? null,
            ];

            if (! empty($field->primary_key) && $pk === null) {
                $pk = (string) $field->name;
            }
        }

        $pk ??= isset($fields['id']) ? 'id' : (string) array_key_first($fields);

        $unique = [];

        foreach ($this->db->getIndexData($table) as $index) {
            if ($index->type === 'UNIQUE' && count($index->fields) === 1 && isset($fields[(string) $index->fields[0]])) {
                $unique[] = (string) $index->fields[0];
            }
        }

        $relations = [];

        foreach ($this->foreignKeys($table) as $fk) {
            if (count($fk['columns']) === 1 && isset($fields[$fk['columns'][0]])) {
                $relations[$fk['columns'][0]] = [
                    'table'   => $fk['foreign_table'],
                    'pk'      => $fk['foreign_columns'][0],
                    'display' => $this->displayFor($fk['foreign_table']),
                ];
            }
        }

        return [
            'table'     => $table,
            'pk'        => $pk,
            'fields'    => $fields,
            'unique'    => $unique,
            'relations' => $relations,
            'nn'        => $this->junctions($table, $pk),
        ];
    }

    /**
     * Codi del mètode del controlador.
     *
     * @param array<string, mixed> $info    Resultat d'analyze()
     * @param array<string, mixed> $options method, subject, plural, model (nom de la classe com s'ha
     *                                      d'escriure al codi, o null) i crud (com s'ha d'escriure Crud: 'Crud'
     *                                      si s'importa, '\Kpcrudy\Crud' si no es pot importar)
     */
    public function methodCode(array $info, array $options): string
    {
        $table  = (string) $info['table'];
        $fields = $info['fields'];
        $nn     = $info['nn'];
        $crud   = $options['crud'] ?? 'Crud';

        // Tot el que no va entre cometes ha de ser un identificador vàlid
        if (! preg_match('/^[a-z][A-Za-z0-9_]*$/', (string) $options['method'])) {
            throw new RuntimeException("Invalid method name \"{$options['method']}\".");
        }

        foreach ([$crud, $options['model']] as $reference) {
            if ($reference !== null && ! preg_match('/^\\\\?[A-Za-z_][A-Za-z0-9_]*(\\\\[A-Za-z_][A-Za-z0-9_]*)*$/', (string) $reference)) {
                throw new RuntimeException("Invalid class name \"{$reference}\".");
            }
        }

        $columns = [];
        $form    = [];
        $labels  = [];
        $types   = [];

        foreach ($fields as $name => $meta) {
            // Una clau numèrica de PHP arriba com a int: amb strict_types cal tornar-la a string
            $name = (string) $name;
            $hint = $this->typeHint($name, $meta);

            if ($name !== 'deleted_at' && $hint !== 'password' && ! $this->isLongText($meta) && count($columns) < 8) {
                $columns[] = $name;
            }

            if ($name !== $info['pk'] && ! in_array($name, self::AUTO_FIELDS, true)) {
                $form[] = $name;
            }

            if ($name !== 'deleted_at') {
                $labels[$name] = $this->label($name, isset($info['relations'][$name]));
            }

            // Un camp que es diu exactament "password" ja el detecta la llibreria
            if ($hint !== null && ! ($hint === 'password' && $name === 'password')) {
                $types[$name] = $hint;
            }
        }

        foreach ($nn as $name => $relation) {
            $name          = (string) $name;
            $columns[]     = $name;
            $form[]        = $name;
            $labels[$name] = $this->label($name, false);
        }

        $lines   = [];
        $lines[] = $options['model'] !== null
            ? "->setModel({$options['model']}::class)"
            : '->setTable(' . $this->export($table) . ')';
        $lines[] = '->setSubject(' . $this->export($options['subject']) . ', ' . $this->export($options['plural']) . ')';
        $lines[] = '->columns(' . $this->exportList($columns) . ')';
        $lines[] = '->fields(' . $this->exportList($form) . ')';
        $lines[] = '->displayAs(' . $this->exportMap($labels, 12) . ')';

        foreach ($info['relations'] as $field => $relation) {
            $args = [$this->export((string) $field), $this->export($relation['table']), $this->export($relation['display'])];

            if ($relation['pk'] !== 'id') {
                $args[] = "['pk' => " . $this->export($relation['pk']) . ']';
            }

            $lines[] = '->setRelation(' . implode(', ', $args) . ')';
        }

        foreach ($nn as $name => $relation) {
            $lines[] = '->setRelationNN(' . implode(', ', array_map($this->export(...), [
                (string) $name, $relation['junction'], $relation['table'], $relation['thisKey'], $relation['otherKey'], $relation['display'],
            ])) . ')';
        }

        foreach ($types as $name => $type) {
            $args = $this->export((string) $name) . ', ' . $this->export($type);

            if (in_array($type, ['image', 'upload'], true)) {
                $dir = $this->export('uploads/' . $table);
                $args .= ", ['path' => FCPATH . {$dir}, 'url' => base_url({$dir})]";
            }

            $lines[] = '->fieldType(' . $args . ')';
        }

        // Les regles únicues no es dedueixen soles: s'hi afegeixen amb les altres que tindria el camp
        if ($options['model'] === null) {
            foreach ($info['unique'] as $name) {
                if ($name !== $info['pk'] && isset($fields[$name])) {
                    $lines[] = '->setRules(' . $this->export($name) . ', ' . $this->export(implode('|', $this->rulesFor($info, $name))) . ')';
                }
            }
        }

        // La primera crida va enganxada a $crud i la resta s'encadenen a sota
        $chain = '        $crud' . array_shift($lines);

        foreach ($lines as $line) {
            $chain .= "\n            " . $line;
        }

        return "    /**\n"
            . '     * CRUD for the "' . $this->comment($table) . "\" table (generated by \"php spark kpcrudy:make\").\n"
            . "     */\n"
            . "    public function {$options['method']}()\n"
            . "    {\n"
            . "        \$crud = new {$crud}();\n"
            . $chain . ";\n"
            . "\n"
            . "        return \$crud->render();\n"
            . "    }\n";
    }

    /**
     * Codi d'un model de CodeIgniter per a la taula.
     *
     * @param array<string, mixed> $info
     */
    public function modelCode(array $info, string $class, string $namespace): string
    {
        if (! preg_match(self::CLASS_PATTERN, $class) || ! preg_match('/^[A-Z][A-Za-z0-9_]*(\\\\[A-Z][A-Za-z0-9_]*)*$/', $namespace)) {
            throw new RuntimeException("Invalid model class name \"{$namespace}\\{$class}\".");
        }

        $fields  = $info['fields'];
        $allowed = array_values(array_filter(
            array_map('strval', array_keys($fields)),
            static fn (string $name): bool => $name !== $info['pk'] && ! in_array($name, self::AUTO_FIELDS, true),
        ));

        $rules = [];

        if ($info['unique'] !== []) {
            // La clau primària necessita una regla perquè les altres puguin fer servir {id}
            $rules[$info['pk']] = "'permit_empty|is_natural_no_zero'";
        }

        foreach ($allowed as $name) {
            $fieldRules = $this->rulesFor($info, $name);

            if ($fieldRules !== ['permit_empty']) {
                // Amb l'etiqueta, els errors diuen "The First name field..." i no "The first_name field..."
                $rules[$name] = "['label' => " . $this->export($this->label($name, isset($info['relations'][$name])))
                    . ", 'rules' => " . $this->export(implode('|', $fieldRules)) . ']';
            }
        }

        $rulesCode = '[]';

        if ($rules !== []) {
            $keys      = array_map('strval', array_keys($rules));
            $width     = max(array_map(static fn (string $key): int => strlen($key) + 2, $keys));
            $rulesCode = "[\n" . implode("\n", array_map(
                fn (string $key, string $value): string => '        ' . str_pad($this->export($key), $width) . ' => ' . $value . ',',
                $keys,
                $rules,
            )) . "\n    ]";
        }

        $softDeletes = isset($fields['deleted_at']) ? 'true' : 'false';
        $timestamps  = isset($fields['created_at']) || isset($fields['updated_at']) ? 'true' : 'false';
        $created     = isset($fields['created_at']) ? "'created_at'" : "''";
        $updated     = isset($fields['updated_at']) ? "'updated_at'" : "''";
        $comment     = $this->comment((string) $info['table']);

        return <<<PHP
            <?php

            namespace {$namespace};

            use CodeIgniter\\Model;

            /**
             * Model for the "{$comment}" table (generated by "php spark kpcrudy:make").
             */
            class {$class} extends Model
            {
                protected \$table          = {$this->export((string) $info['table'])};
                protected \$primaryKey     = {$this->export((string) $info['pk'])};
                protected \$returnType     = 'array';
                protected \$useSoftDeletes = {$softDeletes};
                protected \$allowedFields  = {$this->exportList($allowed)};

                protected \$useTimestamps = {$timestamps};
                protected \$createdField  = {$created};
                protected \$updatedField  = {$updated};

                protected \$validationRules = {$rulesCode};
            }

            PHP;
    }

    /**
     * Línia que s'afegeix al fitxer de rutes (app/Config/Routes.php o el del mòdul).
     *
     * @param string|null $as Nom de la ruta (route_to(), url_to())
     */
    public function routeLine(string $route, string $handler, ?string $filter, ?string $as = null): string
    {
        $options = [];

        if ($filter !== null && $filter !== '') {
            $options[] = "'filter' => " . $this->export($filter);
        }

        if ($as !== null && $as !== '') {
            $options[] = "'as' => " . $this->export($as);
        }

        $options = $options === [] ? '' : ', [' . implode(', ', $options) . ']';

        return '\\Kpcrudy\\Crud::routes($routes, ' . $this->export($route) . ', ' . $this->export($handler) . $options . ');';
    }

    // ------------------------------------------------------------------
    // Escriure fitxers
    // ------------------------------------------------------------------

    // No s'escriu res fins que tot està comprovat: aquests mètodes només
    // calculen el contingut nou i llancen una excepció si alguna cosa no quadra.

    /**
     * Com s'ha d'escriure una classe al codi del controlador: el nom curt si
     * es pot importar (o ja hi és importada) o el nom complet (\X\Y) si el nom
     * curt ja l'ocupa una altra classe.
     *
     * @param string|null $source Codi actual del controlador (null si encara no existeix)
     */
    public function classReference(?string $source, string $ownClass, string $fqcn): string
    {
        $fqcn  = ltrim($fqcn, '\\');
        $short = substr($fqcn, (int) strrpos('\\' . $fqcn, '\\'));

        if (strcasecmp($short, $ownClass) === 0) {
            return '\\' . $fqcn;
        }

        if ($source === null) {
            return $short;
        }

        $imported = $this->imports($source);

        if (isset($imported[strtolower($short)])) {
            return strcasecmp($imported[strtolower($short)], $fqcn) === 0 ? $short : '\\' . $fqcn;
        }

        // Sense namespace ni declare, la importació no té un lloc clar on anar
        return $this->usePosition($source) === null ? '\\' . $fqcn : $short;
    }

    /**
     * Codi nou del controlador (creat o amb el mètode afegit o substituït).
     *
     * @param list<string> $imports Classes que el controlador ha d'importar
     * @param string       $extends Classe pare (FQCN) si s'ha de crear el controlador
     *
     * @return array{0: string, 1: string} [estat ('created', 'added' o 'replaced'), codi]
     */
    public function controllerSource(string $path, string $namespace, string $class, string $method, string $methodCode, array $imports, bool $force, string $extends = 'App\Controllers\BaseController'): array
    {
        if (! is_file($path)) {
            $extends   = ltrim($extends, '\\');
            $parentNs  = (string) substr($extends, 0, max(0, (int) strrpos($extends, '\\')));
            $parentRef = $this->classReference(null, $class, $extends);
            $uses      = $imports;

            // La classe pare s'importa si és d'un altre namespace i el nom curt no xoca
            if ($parentRef[0] !== '\\' && strcasecmp($parentNs, $namespace) !== 0) {
                $uses[] = $extends;
            }

            $uses = array_unique($uses);
            sort($uses);

            $code = "<?php\n\nnamespace {$namespace};\n\n"
                . implode('', array_map(static fn (string $use): string => "use {$use};\n", $uses))
                . "\nclass {$class} extends {$parentRef}\n{\n" . $methodCode . "}\n";

            return ['created', $code];
        }

        $source = (string) file_get_contents($path);
        $tokens = $this->tokens($source, $path);
        $body   = $this->classBody($tokens, $class);

        if ($body === null) {
            throw new RuntimeException("The file {$path} does not declare the class {$class}.");
        }

        $range = $this->methodRange($tokens, $body[0], $body[1], $method);

        if ($range !== null && ! $force) {
            throw new RuntimeException("The method {$method}() already exists in {$class}. Use --force to replace it.");
        }

        if ($range !== null) {
            $source = substr($source, 0, $range[0]) . trim($methodCode) . substr($source, $range[1] + 1);
            $status = 'replaced';
        } else {
            // Just abans de la clau que tanca la classe (no l'última del fitxer)
            $end    = $tokens[$body[1]]['offset'];
            $before = rtrim(substr($source, 0, $end));
            $source = $before . (str_ends_with($before, '{') ? "\n" : "\n\n") . $methodCode . substr($source, $end);
            $status = 'added';
        }

        foreach ($imports as $use) {
            $source = $this->addUse($source, $use);
        }

        // Comprovació final: el fitxer resultant ha de ser PHP vàlid
        $this->tokens($source, $path);

        return [$status, $source];
    }

    /**
     * Codi nou del fitxer de rutes amb la ruta, o null si la ruta ja hi era.
     * Si el fitxer no existeix (p. ex. el Config/Routes.php d'un mòdul nou),
     * es crea amb la ruta.
     */
    public function routesSource(string $routesFile, string $route, string $line): ?string
    {
        if (! is_file($routesFile)) {
            return "<?php\n\n/**\n * @var \\CodeIgniter\\Router\\RouteCollection \$routes\n */\n\n" . $line . "\n";
        }

        $source = (string) file_get_contents($routesFile);

        if (preg_match('/Crud::routes\(\s*\$routes\s*,\s*[\'"]\/?' . preg_quote($route, '/') . '\/?[\'"]/', $source)) {
            return null;
        }

        $source = rtrim($source);

        // Si el fitxer acaba amb l'etiqueta de tancament de PHP, la ruta ha d'anar abans
        if (str_ends_with($source, '?>')) {
            return rtrim(substr($source, 0, -2)) . "\n\n" . $line . "\n?>\n";
        }

        return $source . "\n\n" . $line . "\n";
    }

    /**
     * Valor de $table d'un model existent, o null si no es troba.
     */
    public function modelTable(string $path): ?string
    {
        $source = (string) file_get_contents($path);

        return preg_match('/\$table\s*=\s*([\'"])([^\'"]*)\1\s*;/', $source, $match) ? $match[2] : null;
    }

    /**
     * Escriu un fitxer de manera atòmica: primer a un temporal del mateix
     * directori i després el reanomena, perquè mai no quedi a mitges.
     */
    public function writeFile(string $path, string $code): void
    {
        $dir = dirname($path);

        if (! is_dir($dir) && ! mkdir($dir, 0775, true) && ! is_dir($dir)) {
            throw new RuntimeException("Cannot create the directory {$dir}.");
        }

        $temp = $dir . DIRECTORY_SEPARATOR . '.' . basename($path) . '.' . bin2hex(random_bytes(4)) . '.tmp';

        if (file_put_contents($temp, $code) !== strlen($code)) {
            @unlink($temp);

            throw new RuntimeException("Cannot write {$path}.");
        }

        if (is_file($path)) {
            @chmod($temp, fileperms($path) & 0777);
        }

        if (! rename($temp, $path)) {
            @unlink($temp);

            throw new RuntimeException("Cannot write {$path}.");
        }
    }

    // ------------------------------------------------------------------
    // Deduccions
    // ------------------------------------------------------------------

    /**
     * Regles de validació d'un camp, com les que dedueix la llibreria més
     * is_unique si el camp té un índex únic.
     *
     * @param array<string, mixed> $info
     *
     * @return list<string>
     */
    public function rulesFor(array $info, string $name): array
    {
        $meta  = $info['fields'][$name];
        $hint  = $this->typeHint($name, $meta);
        $rules = [];

        // Els fitxers els valida la llibreria en pujar-los
        if (in_array($hint, ['image', 'upload'], true)) {
            return ['permit_empty'];
        }

        $rules[] = ! $meta['nullable'] && $meta['default'] === null && ! in_array($hint, ['boolean', 'image', 'upload'], true)
            ? 'required'
            : 'permit_empty';

        if (str_contains($name, 'email')) {
            $rules[] = 'valid_email';
        }

        // A la columna d'una contrasenya s'hi desa el hash: la llargada no té sentit
        if ($meta['max_length'] && preg_match('/char/', $meta['type']) && $hint !== 'password') {
            $rules[] = "max_length[{$meta['max_length']}]";
        }

        if (in_array($name, $info['unique'], true)) {
            $rules[] = "is_unique[{$info['table']}.{$name},{$info['pk']},{id}]";
        }

        return $rules;
    }

    /**
     * Tipus de camp que convé forçar a partir del nom.
     *
     * @param array<string, mixed> $meta
     */
    public function typeHint(string $name, array $meta): ?string
    {
        $lower = strtolower($name);

        return match (true) {
            (bool) preg_match('/(^|_)(photo|avatar|image|picture|logo|poster|thumbnail|cover)($|_)/', $lower) => 'image',
            (bool) preg_match('/(^|_)(file|document|attachment|pdf|contract|cv)($|_)/', $lower)                => 'upload',
            (bool) preg_match('/(^|_)(password|pass|pwd|passwd)($|_)/', $lower)                               => 'password',
            in_array($lower, ['color', 'colour'], true) && str_contains($meta['type'], 'char')                  => 'color',
            default                                                                                             => null,
        };
    }

    /**
     * Etiqueta llegible: "first_name" => "First name", "city_id" => "City".
     */
    public function label(string $name, bool $isRelation): string
    {
        if ($isRelation && str_ends_with($name, '_id')) {
            $name = substr($name, 0, -3);
        }

        return ucfirst(str_replace('_', ' ', $name));
    }

    /**
     * Noms per al títol: "kpdemo_events" => ["Event", "Events"].
     *
     * @return array{0: string, 1: string}
     */
    public function subjectFor(string $table): array
    {
        $words  = explode('_', $table);
        $last   = array_pop($words);
        $plural = ucfirst(str_replace('_', ' ', $last));

        $singular = match (true) {
            str_ends_with($last, 'ies')                  => substr($last, 0, -3) . 'y',
            (bool) preg_match('/(ss|sh|ch|x)es$/', $last) => substr($last, 0, -2),
            str_ends_with($last, 's') && ! str_ends_with($last, 'ss') => substr($last, 0, -1),
            default                                      => $last,
        };

        return [ucfirst($singular), $plural];
    }

    /**
     * Camp o plantilla per mostrar els registres d'una taula relacionada.
     */
    public function displayFor(string $table): string
    {
        $fields = [];

        foreach ($this->db->getFieldData($table) as $field) {
            $fields[$field->name] = strtolower((string) $field->type);
        }

        if (isset($fields['first_name'], $fields['last_name'])) {
            return '{first_name} {last_name}';
        }

        foreach (self::DISPLAY_CANDIDATES as $candidate) {
            if (isset($fields[$candidate])) {
                return $candidate;
            }
        }

        foreach ($fields as $name => $type) {
            if (str_contains($type, 'char')) {
                return (string) $name;
            }
        }

        return (string) array_key_first($fields);
    }

    /**
     * Taules intermèdies "pures" (només les dues claus, i potser id i timestamps)
     * que relacionen aquesta taula amb una altra: es generen com a setRelationNN().
     *
     * @return array<string, array<string, string>>
     */
    private function junctions(string $table, string $pk): array
    {
        $result = [];
        $prefix = str_contains($table, '_') ? substr($table, 0, strpos($table, '_') + 1) : '';

        foreach ($this->tables() as $candidate) {
            if ($candidate === $table) {
                continue;
            }

            $fks = $this->foreignKeys($candidate);

            if (count($fks) !== 2) {
                continue;
            }

            $mine  = array_values(array_filter($fks, static fn (array $fk): bool => $fk['foreign_table'] === $table && $fk['foreign_columns'] === [$pk]));
            $other = array_values(array_filter($fks, static fn (array $fk): bool => $fk['foreign_table'] !== $table));

            if (count($mine) !== 1 || count($other) !== 1 || count($mine[0]['columns']) !== 1 || count($other[0]['columns']) !== 1) {
                continue;
            }

            $columns = array_map(static fn ($field): string => $field->name, $this->db->getFieldData($candidate));
            $extra   = array_diff($columns, [$mine[0]['columns'][0], $other[0]['columns'][0], 'id', ...self::AUTO_FIELDS]);

            if ($extra !== []) {
                continue;
            }

            $otherTable = $other[0]['foreign_table'];
            $name       = $prefix !== '' && str_starts_with($otherTable, $prefix) ? substr($otherTable, strlen($prefix)) : $otherTable;

            $result[$name] = [
                'junction' => $candidate,
                'table'    => $otherTable,
                'thisKey'  => $mine[0]['columns'][0],
                'otherKey' => $other[0]['columns'][0],
                'display'  => $this->displayFor($otherTable),
            ];
        }

        return $result;
    }

    /**
     * @return list<array{columns: list<string>, foreign_table: string, foreign_columns: list<string>}>
     */
    private function foreignKeys(string $table): array
    {
        $result = [];

        foreach ($this->db->getForeignKeyData($table) as $fk) {
            $result[] = [
                'columns'         => array_values((array) $fk->column_name),
                'foreign_table'   => (string) $fk->foreign_table_name,
                'foreign_columns' => array_values((array) $fk->foreign_column_name),
            ];
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $meta
     */
    private function isLongText(array $meta): bool
    {
        return (bool) preg_match('/text|blob|json/', $meta['type']);
    }

    // ------------------------------------------------------------------
    // Codi PHP
    // ------------------------------------------------------------------

    /**
     * Tokens del codi amb la posició de cadascun. Llança una excepció si el
     * codi no és PHP vàlid: no es modifica un fitxer que no s'entén.
     *
     * @return list<array{id: int|null, text: string, offset: int}>
     */
    private function tokens(string $source, string $path): array
    {
        try {
            $raw = token_get_all($source, TOKEN_PARSE);
        } catch (\ParseError $e) {
            throw new RuntimeException("Cannot parse {$path}: {$e->getMessage()} (line {$e->getLine()}).");
        }

        $tokens = [];
        $offset = 0;

        foreach ($raw as $token) {
            $text     = is_array($token) ? $token[1] : $token;
            $tokens[] = ['id' => is_array($token) ? $token[0] : null, 'text' => $text, 'offset' => $offset];
            $offset += strlen($text);
        }

        return $tokens;
    }

    /**
     * Índex del següent token significatiu (sense espais ni comentaris) a partir de $i.
     *
     * @param list<array{id: int|null, text: string, offset: int}> $tokens
     * @param list<string>                                         $skip   Altres textos que s'han de saltar
     */
    private function nextToken(array $tokens, int $i, array $skip = []): ?int
    {
        for ($i++; isset($tokens[$i]); $i++) {
            if (! in_array($tokens[$i]['id'], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true) && ! in_array($tokens[$i]['text'], $skip, true)) {
                return $i;
            }
        }

        return null;
    }

    private function opens(array $token): bool
    {
        return $token['text'] === '{' || in_array($token['id'], [T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES], true);
    }

    /**
     * Índex de la clau que tanca la que s'obre a $open.
     *
     * @param list<array{id: int|null, text: string, offset: int}> $tokens
     */
    private function closingBrace(array $tokens, int $open): ?int
    {
        $depth = 0;

        for ($i = $open; isset($tokens[$i]); $i++) {
            if ($this->opens($tokens[$i])) {
                $depth++;
            } elseif ($tokens[$i]['text'] === '}' && --$depth === 0) {
                return $i;
            }
        }

        return null;
    }

    /**
     * Índexs [clau d'obertura, clau de tancament] del cos de la classe, o null.
     *
     * @param list<array{id: int|null, text: string, offset: int}> $tokens
     *
     * @return array{0: int, 1: int}|null
     */
    private function classBody(array $tokens, string $class): ?array
    {
        foreach ($tokens as $i => $token) {
            if ($token['id'] !== T_CLASS) {
                continue;
            }

            // "Foo::class" i les classes anònimes no tenen el nom just després
            $name = $this->nextToken($tokens, $i);

            if ($name === null || $tokens[$name]['id'] !== T_STRING || strcasecmp($tokens[$name]['text'], $class) !== 0) {
                continue;
            }

            for ($j = $name + 1; isset($tokens[$j]); $j++) {
                if ($tokens[$j]['text'] === '{') {
                    $close = $this->closingBrace($tokens, $j);

                    return $close === null ? null : [$j, $close];
                }
            }
        }

        return null;
    }

    /**
     * Posició [inici, final] d'un mètode (amb el docblock i els modificadors)
     * dins del cos d'una classe, o null. El final és l'últim caràcter ("}" o ";").
     *
     * @param list<array{id: int|null, text: string, offset: int}> $tokens
     *
     * @return array{0: int, 1: int}|null
     */
    private function methodRange(array $tokens, int $open, int $close, string $method): ?array
    {
        $depth       = 0;
        $memberStart = null;

        for ($i = $open + 1; $i < $close; $i++) {
            $token = $tokens[$i];

            // Només els membres de la classe (no les funcions ni closures de dins dels mètodes)
            if ($depth === 0) {
                if ($memberStart === null && ! in_array($token['id'], [T_WHITESPACE, T_COMMENT], true) && ! in_array($token['text'], [';', '}'], true)) {
                    $memberStart = $token['offset'];
                }

                if ($token['id'] === T_FUNCTION) {
                    // El nom ha de venir just després (potser amb &): si no, és una closure
                    $name = $this->nextToken($tokens, $i, ['&']);

                    if ($name !== null && $tokens[$name]['id'] === T_STRING && strcasecmp($tokens[$name]['text'], $method) === 0) {
                        // El cos comença al primer "{"; un mètode abstracte acaba al ";"
                        for ($j = $name + 1; $j < $close; $j++) {
                            if ($tokens[$j]['text'] === ';') {
                                return [$memberStart ?? $token['offset'], $tokens[$j]['offset']];
                            }

                            if ($tokens[$j]['text'] === '{') {
                                $end = $this->closingBrace($tokens, $j);

                                return $end === null ? null : [$memberStart ?? $token['offset'], $tokens[$end]['offset']];
                            }
                        }

                        return null;
                    }
                }
            }

            if ($this->opens($token)) {
                $depth++;
            } elseif ($token['text'] === '}') {
                $depth--;
            }

            if ($depth === 0 && in_array($token['text'], [';', '}'], true)) {
                $memberStart = null;
            }
        }

        return null;
    }

    /**
     * Classes importades amb "use" al nivell del fitxer: [àlies en minúscules => nom complet].
     *
     * @return array<string, string>
     */
    private function imports(string $source): array
    {
        $result = [];

        preg_match_all('/^use\s+(?!function\s|const\s)([^;]+);/mi', $source, $matches);

        foreach ($matches[1] as $statement) {
            $statement = trim((string) preg_replace('/\s+/', ' ', $statement));
            $prefix    = '';

            // use X\{A, B as C};
            if (preg_match('/^([^{]*)\{(.*)\}$/', $statement, $group)) {
                $prefix    = trim($group[1]);
                $statement = $group[2];
            }

            foreach (explode(',', $statement) as $part) {
                if (! preg_match('/^\s*\\\\?([\w\\\\]+)(?:\s+as\s+(\w+))?\s*$/i', $part, $m)) {
                    continue;
                }

                $full  = ltrim($prefix . $m[1], '\\');
                $alias = $m[2] ?? '';
                $alias = $alias !== '' ? $alias : substr($full, (int) strrpos('\\' . $full, '\\'));

                $result[strtolower($alias)] = $full;
            }
        }

        return $result;
    }

    /**
     * Posició on es pot afegir un "use" quan encara no n'hi ha cap: després
     * del namespace o, si no n'hi ha, del declare(). Null si no hi ha cap lloc segur.
     */
    private function usePosition(string $source): ?int
    {
        foreach (['/^namespace\s+[^;{]+;/m', '/^declare\s*\([^)]*\)\s*;/m'] as $pattern) {
            if (preg_match($pattern, $source, $match, PREG_OFFSET_CAPTURE)) {
                return $match[0][1] + strlen($match[0][0]);
            }
        }

        // Fitxer sense namespace (classe global): just després de l'etiqueta d'obertura
        if (preg_match('/^<\?php\b/', $source)) {
            return 5;
        }

        return null;
    }

    private function addUse(string $source, string $class): string
    {
        $imported = $this->imports($source);
        $short    = substr($class, (int) strrpos('\\' . $class, '\\'));

        if (isset($imported[strtolower($short)])) {
            // Ja importada; si és una altra classe amb el mateix nom, classReference() ja ho ha evitat
            return $source;
        }

        if (preg_match_all('/^use\s+[^;]+;[ \t]*$/m', $source, $matches, PREG_OFFSET_CAPTURE)) {
            $last = end($matches[0]);
            $pos  = $last[1] + strlen(rtrim($last[0]));

            return substr($source, 0, $pos) . "\nuse {$class};" . substr($source, $pos);
        }

        $pos = $this->usePosition($source);

        if ($pos === null) {
            throw new RuntimeException("Cannot add \"use {$class};\" to the controller.");
        }

        $after = substr($source, $pos);

        return substr($source, 0, $pos) . "\n\nuse {$class};" . (preg_match('/^[ \t]*\r?\n[ \t]*\r?\n/', $after) ? '' : "\n") . $after;
    }

    /**
     * Text segur dins d'un comentari /* ... *\/ d'una sola línia.
     */
    private function comment(string $text): string
    {
        return str_replace(['*/', "\r", "\n"], ['* /', ' ', ' '], $text);
    }

    private function export(string $value): string
    {
        // Entre cometes simples, una barra invertida només s'ha de doblar davant d'una altra, d'una cometa o al final
        $value = (string) preg_replace('/\\\\(?=[\\\\\']|$)/', '\\\\\\\\', $value);

        return "'" . str_replace("'", "\\'", $value) . "'";
    }

    /**
     * @param list<string> $values
     */
    private function exportList(array $values): string
    {
        return '[' . implode(', ', array_map($this->export(...), $values)) . ']';
    }

    /**
     * @param array<string, string> $map
     */
    private function exportMap(array $map, int $indent): string
    {
        if ($map === []) {
            return '[]';
        }

        $width = max(array_map(static fn (string $key): int => strlen($key) + 2, array_keys($map)));
        $pad   = str_repeat(' ', $indent + 4);
        $lines = [];

        foreach ($map as $key => $value) {
            $lines[] = $pad . str_pad($this->export((string) $key), $width) . ' => ' . $this->export($value) . ',';
        }

        return "[\n" . implode("\n", $lines) . "\n" . str_repeat(' ', $indent) . ']';
    }
}
