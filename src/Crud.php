<?php

declare(strict_types=1);

namespace Kpcrudy;

use Closure;
use CodeIgniter\Database\BaseBuilder;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\Filters\CSRF;
use CodeIgniter\HTTP\Files\UploadedFile;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Model;
use CodeIgniter\Router\RouteCollection;
use Kpcrudy\Concerns\ImportsCsv;
use Kpcrudy\Config\Kpcrudy as KpcrudyConfig;
use RuntimeException;
use Throwable;

/**
 * Generador de CRUD automàtic per a CodeIgniter 4.
 *
 * Ús bàsic dins d'un controlador:
 *
 *     $crud = new \Kpcrudy\Crud();
 *     $crud->setTable('clients')->setSubject('Client', 'Clients');
 *     return $crud->render();
 *
 * Les operacions es resolen a partir de la URL:
 *     base               llistat
 *     base/trash         paperera
 *     base/add           formulari d'alta
 *     base/edit/{id}     formulari d'edició
 *     base/show/{id}     fitxa de detall
 *     base/export        exportació CSV (GET)
 *     base/delete/{id}, base/restore/{id}, base/purge/{id},
 *     base/emptytrash, base/bulk (POST)
 */
class Crud
{
    use ImportsCsv;

    /**
     * Operacions que reben un identificador: base/{operació}/{id}
     */
    public const ID_OPERATIONS = ['edit', 'show', 'delete', 'restore', 'purge'];

    /**
     * Operacions sense identificador: base/{operació}
     */
    public const OPERATIONS = ['add', 'trash', 'export', 'import', 'bulk', 'emptytrash'];

    /**
     * Tipus de camp que es mostren com a desplegable.
     */
    protected const SELECT_TYPES = ['relation', 'relation_nn', 'enum', 'select', 'boolean'];

    protected KpcrudyConfig $config;
    protected IncomingRequest $request;
    protected ?BaseConnection $db = null;
    protected ?Model $model       = null;
    protected ?string $table      = null;
    protected ?string $primaryKey = null;
    protected ?string $subject    = null;
    protected ?string $subjectPlural = null;

    /** @var list<string>|null */
    protected ?array $columns = null;

    /** @var list<string>|null */
    protected ?array $addFields = null;

    /** @var list<string>|null */
    protected ?array $editFields = null;

    /** @var list<string>|null */
    protected ?array $showFields = null;

    /** @var list<string>|null */
    protected ?array $searchFields = null;

    /** @var list<string>|null */
    protected ?array $filterFields = null;

    /** @var list<string>|null */
    protected ?array $exportColumns = null;

    /** @var list<string> */
    protected array $unsortable = [];

    /** @var array<string, string> */
    protected array $labels = [];

    /** @var array<string, array{type: string, options: array<string, mixed>}> */
    protected array $types = [];

    /** @var array<string, list<string>> */
    protected array $rules = [];

    /** @var list<string> */
    protected array $required = [];

    /** @var list<string> */
    protected array $readonly = [];

    /** @var array<string, mixed> */
    protected array $defaults = [];

    /** @var array<string, string> */
    protected array $help = [];

    /** @var array<string, array<string, mixed>> */
    protected array $relations = [];

    /** @var array<string, array<string, mixed>> */
    protected array $relationsNN = [];

    /** @var array<string, Closure> */
    protected array $columnCallbacks = [];

    /** @var array<string, Closure> */
    protected array $fieldCallbacks = [];

    /** @var array<string, Closure> */
    protected array $exportCallbacks = [];

    /** @var array<string, Closure> */
    protected array $hooks = [];

    /** @var list<array<string, mixed>> */
    protected array $actions = [];

    /** @var list<array<string, mixed>> */
    protected array $headerActions = [];

    /** @var array<string, array<string, mixed>> */
    protected array $bulkActions = [];

    /** @var array<string, bool|Closure> */
    protected array $permissions = [];

    /** @var array<string, mixed> */
    protected array $where = [];

    protected ?bool $softDeletes   = null;
    protected string $deletedField = 'deleted_at';
    protected ?string $createdField = null;
    protected ?string $updatedField = null;

    protected int $perPage;

    /** @var list<int> */
    protected array $perPageOptions;

    /** @var array{0: string, 1: string}|null */
    protected ?array $defaultOrder = null;

    protected ?string $baseUrl = null;
    protected ?string $backUrl = null;
    protected bool $maskIds;
    protected ?string $maskContext = null;
    protected ?IdMasker $masker    = null;

    /**
     * Operacions que només s'obren amb una URL signada (requireSignature()).
     *
     * @var list<string>
     */
    protected array $signedOperations = [];

    /**
     * Durada (segons) dels enllaços que el CRUD signa quan la petició actual no
     * és signada; null = no caduquen.
     */
    protected ?int $signTtl = null;

    /**
     * Caducitat dels enllaços que signa el CRUD en aquesta petició: la de la
     * URL signada amb què s'ha entrat (així navegar no allarga la validesa).
     */
    protected ?int $signExpires = null;

    protected bool $signatureChecked = false;

    /**
     * Si en aquesta petició el CRUD pot signar els enllaços que genera. Només ho
     * fa si s'hi ha entrat amb una URL signada d'un llistat, o per una operació
     * de $signFrom: compartir una fitxa no dona accés al llistat, i una petició
     * sense signatura no aconsegueix mai enllaços signats.
     */
    protected bool $signAllowed = false;

    /**
     * Operacions que, obertes sense signatura, poden repartir enllaços signats.
     *
     * @var list<string>
     */
    protected array $signFrom = [];
    protected ?UrlSigner $signer      = null;

    /**
     * Si ja s'han comprovat les rutes dels CRUD en aquesta petició (checkRoutes()).
     */
    protected static bool $routesChecked = false;
    protected string $theme;
    protected string|false $layout;

    /**
     * Estructura de la taula: nom de camp => metadades.
     *
     * @var array<string, array<string, mixed>>
     */
    protected array $meta = [];

    protected bool $booted = false;

    /** @var array<string, array<string, string>> */
    protected array $optionsCache = [];

    public function __construct(?KpcrudyConfig $config = null)
    {
        $config ??= config('Kpcrudy');

        $this->config         = $config instanceof KpcrudyConfig ? $config : new KpcrudyConfig();
        $this->request        = service('request');
        $this->perPage        = $this->config->perPage;
        $this->perPageOptions = $this->config->perPageOptions;
        $this->theme          = $this->config->theme;
        $this->layout         = $this->config->layout;
        $this->maskIds        = $this->config->maskIds;

        helper('url');
    }

    // ------------------------------------------------------------------
    // Configuració: origen de dades
    // ------------------------------------------------------------------

    /**
     * Treballa directament amb una taula (es crea un model dinàmic).
     */
    public function setTable(string $table): static
    {
        $this->table = $table;

        return $this;
    }

    /**
     * Treballa amb un model de CodeIgniter (instància o nom de classe).
     * Es reaprofiten la seva validació, timestamps, soft deletes i callbacks.
     */
    public function setModel(Model|string $model): static
    {
        if (is_string($model)) {
            $instance = model($model, false);

            if (! $instance instanceof Model) {
                throw new RuntimeException("Kpcrudy: no s'ha trobat el model '{$model}'.");
            }

            $model = $instance;
        }

        $this->model = $model;

        return $this;
    }

    /**
     * Connexió o grup de base de dades (només en mode taula).
     */
    public function setDatabase(BaseConnection|string $db): static
    {
        $this->db = is_string($db) ? db_connect($db) : $db;

        return $this;
    }

    public function setPrimaryKey(string $primaryKey): static
    {
        $this->primaryKey = $primaryKey;

        return $this;
    }

    public function setSubject(string $singular, ?string $plural = null): static
    {
        $this->subject       = $singular;
        $this->subjectPlural = $plural ?? $singular;

        return $this;
    }

    /**
     * Activa o desactiva la paperera (mode taula). Per defecte s'activa
     * sola si la taula té un camp 'deleted_at'. En mode model, mana el model.
     */
    public function softDeletes(bool $enabled = true, string $field = 'deleted_at'): static
    {
        $this->softDeletes  = $enabled;
        $this->deletedField = $field;

        return $this;
    }

    /**
     * Restringeix els registres que gestiona el CRUD (llistat, edició, esborrat...).
     * Accepta els mateixos formats que el where() del Query Builder.
     *
     * @param array<string, mixed>|string $field
     */
    public function where(array|string $field, mixed $value = null): static
    {
        if (is_array($field)) {
            $this->where = array_merge($this->where, $field);
        } else {
            $this->where[$field] = $value;
        }

        return $this;
    }

    // ------------------------------------------------------------------
    // Configuració: columnes i camps
    // ------------------------------------------------------------------

    /**
     * Columnes del llistat (i de l'exportació, si no se'n configuren d'altres).
     *
     * @param list<string> $columns
     */
    public function columns(array $columns): static
    {
        $this->columns = array_values($columns);

        return $this;
    }

    /**
     * Camps dels formularis d'alta i d'edició.
     *
     * @param list<string> $fields
     */
    public function fields(array $fields): static
    {
        $this->addFields  = array_values($fields);
        $this->editFields = array_values($fields);

        return $this;
    }

    /**
     * @param list<string> $fields
     */
    public function addFields(array $fields): static
    {
        $this->addFields = array_values($fields);

        return $this;
    }

    /**
     * @param list<string> $fields
     */
    public function editFields(array $fields): static
    {
        $this->editFields = array_values($fields);

        return $this;
    }

    /**
     * Camps de la fitxa de detall.
     *
     * @param list<string> $fields
     */
    public function showFields(array $fields): static
    {
        $this->showFields = array_values($fields);

        return $this;
    }

    /**
     * Camps on busca el cercador general.
     *
     * @param list<string> $fields
     */
    public function searchFields(array $fields): static
    {
        $this->searchFields = array_values($fields);

        return $this;
    }

    /**
     * Camps amb filtre. Per defecte: les columnes de tipus relació, enum,
     * select i booleà.
     *
     * @param list<string> $fields
     */
    public function filters(array $fields): static
    {
        $this->filterFields = array_values($fields);

        return $this;
    }

    /**
     * @param list<string> $columns
     */
    public function exportColumns(array $columns): static
    {
        $this->exportColumns = array_values($columns);

        return $this;
    }

    /**
     * Columnes que no es poden ordenar.
     *
     * @param list<string> $columns
     */
    public function unsortable(array $columns): static
    {
        $this->unsortable = array_values($columns);

        return $this;
    }

    /**
     * Etiqueta d'un camp, o array camp => etiqueta.
     *
     * @param array<string, string>|string $field
     */
    public function displayAs(array|string $field, ?string $label = null): static
    {
        if (is_array($field)) {
            $this->labels = array_merge($this->labels, $field);
        } else {
            $this->labels[$field] = (string) $label;
        }

        return $this;
    }

    /**
     * Força el tipus d'un camp.
     *
     * Tipus: text, textarea, number, decimal, email, url, password, date,
     * datetime, time, color, boolean, enum, select, hidden, readonly,
     * upload, image.
     *
     * Opcions habituals:
     *  - select/enum:  ['options' => [valor => etiqueta]]
     *  - hidden:       ['value' => valor] (es desa sempre aquest valor)
     *  - password:     ['hash' => true]
     *  - upload/image: ['path' => ..., 'url' => ..., 'allowed' => [...], 'maxSize' => KB,
     *                   'subdir' => 'any/{camp}' o fn (array $data, ?array $record): string]
     *  - qualsevol:    ['attrs' => [atribut => valor]] per a l'input
     *
     * @param array<string, mixed> $options
     */
    public function fieldType(string $field, string $type, array $options = []): static
    {
        $this->types[$field] = ['type' => $type, 'options' => $options];
        unset($this->optionsCache[$field]);

        return $this;
    }

    /**
     * Regles de validació de CodeIgniter per a un camp, o array camp => regles.
     * Es pot fer servir {id} per referir-se a l'identificador en edició
     * (p. ex. is_unique[clients.email,id,{id}]).
     *
     * @param array<string, list<string>|string>|string $field
     * @param list<string>|string|null                  $rules
     */
    public function setRules(array|string $field, array|string|null $rules = null): static
    {
        $map = is_array($field) ? $field : [$field => $rules ?? []];

        foreach ($map as $name => $fieldRules) {
            $this->rules[$name] = is_array($fieldRules) ? array_values($fieldRules) : explode('|', $fieldRules);
        }

        return $this;
    }

    /**
     * @param list<string> $fields
     */
    public function requiredFields(array $fields): static
    {
        $this->required = array_values(array_unique(array_merge($this->required, $fields)));

        return $this;
    }

    /**
     * Camps que es mostren però no es poden modificar en edició.
     *
     * @param list<string> $fields
     */
    public function readonlyFields(array $fields): static
    {
        $this->readonly = array_values(array_unique(array_merge($this->readonly, $fields)));

        return $this;
    }

    /**
     * Valor inicial d'un camp al formulari d'alta.
     */
    public function setDefault(string $field, mixed $value): static
    {
        $this->defaults[$field] = $value;

        return $this;
    }

    /**
     * Text d'ajuda que es mostra sota el camp al formulari.
     */
    public function setHelp(string $field, string $text): static
    {
        $this->help[$field] = $text;

        return $this;
    }

    // ------------------------------------------------------------------
    // Configuració: relacions
    // ------------------------------------------------------------------

    /**
     * Relació 1-N: el camp $field (clau forana) apunta a $table.
     *
     * $display pot ser un camp ('name') o una plantilla ('{surname}, {name}').
     *
     * Opcions: 'pk' (clau de la taula relacionada, per defecte 'id'),
     *          'where' (array de condicions), 'order' (ordre de les opcions).
     *
     * @param array<string, mixed> $options
     */
    public function setRelation(string $field, string $table, string $display, array $options = []): static
    {
        $this->relations[$field] = [
            'table'   => $table,
            'display' => $display,
            'fields'  => $this->templateFields($display),
            'pk'      => $options['pk'] ?? 'id',
            'where'   => $options['where'] ?? [],
            'order'   => $options['order'] ?? null,
        ];
        unset($this->optionsCache[$field]);

        return $this;
    }

    /**
     * Relació N-N a través d'una taula intermèdia. $name és el nom del camp
     * virtual que podràs posar a columns(), fields(), filters()...
     *
     * @param string               $junction   Taula intermèdia
     * @param string               $otherTable Taula relacionada
     * @param string               $thisKey    Camp de la intermèdia que apunta a aquesta taula
     * @param string               $otherKey   Camp de la intermèdia que apunta a la relacionada
     * @param string               $display    Camp o plantilla a mostrar de la relacionada
     * @param array<string, mixed> $options    'pk', 'where', 'order' (com setRelation)
     */
    public function setRelationNN(
        string $name,
        string $junction,
        string $otherTable,
        string $thisKey,
        string $otherKey,
        string $display,
        array $options = [],
    ): static {
        $this->relationsNN[$name] = [
            'junction' => $junction,
            'table'    => $otherTable,
            'thisKey'  => $thisKey,
            'otherKey' => $otherKey,
            'display'  => $display,
            'fields'   => $this->templateFields($display),
            'pk'       => $options['pk'] ?? 'id',
            'where'    => $options['where'] ?? [],
            'order'    => $options['order'] ?? null,
        ];
        unset($this->optionsCache[$name]);

        return $this;
    }

    // ------------------------------------------------------------------
    // Configuració: callbacks i hooks
    // ------------------------------------------------------------------

    /**
     * Personalitza com es mostra una columna (llistat i fitxa).
     * fn (mixed $value, array $row): string  -> HTML (cal escapar-lo tu)
     * També serveix per crear columnes virtuals que no existeixen a la taula.
     */
    public function callbackColumn(string $column, Closure $callback): static
    {
        $this->columnCallbacks[$column] = $callback;

        return $this;
    }

    /**
     * Personalitza l'input d'un camp del formulari.
     * fn (mixed $value, ?array $row, array $field): string  -> HTML
     * L'input ha de fer servir name="{nom del camp}".
     */
    public function callbackField(string $field, Closure $callback): static
    {
        $this->fieldCallbacks[$field] = $callback;

        return $this;
    }

    /**
     * Valor d'una columna a l'exportació CSV.
     * fn (mixed $value, array $row): string
     */
    public function callbackExport(string $column, Closure $callback): static
    {
        $this->exportCallbacks[$column] = $callback;

        return $this;
    }

    /**
     * fn (array $data): array|string|false|null
     * Retorna les dades modificades, null per deixar-les igual,
     * un string amb un missatge d'error o false per cancel·lar.
     */
    public function beforeInsert(Closure $callback): static
    {
        return $this->setHook('beforeInsert', $callback);
    }

    /**
     * fn (string $id, array $data): void
     */
    public function afterInsert(Closure $callback): static
    {
        return $this->setHook('afterInsert', $callback);
    }

    /**
     * fn (array $data, string $id, array $old): array|string|false|null
     */
    public function beforeUpdate(Closure $callback): static
    {
        return $this->setHook('beforeUpdate', $callback);
    }

    /**
     * fn (string $id, array $data, array $old): void
     */
    public function afterUpdate(Closure $callback): static
    {
        return $this->setHook('afterUpdate', $callback);
    }

    /**
     * fn (string $id, array $row, bool $purge): string|false|null
     */
    public function beforeDelete(Closure $callback): static
    {
        return $this->setHook('beforeDelete', $callback);
    }

    /**
     * fn (string $id, array $row, bool $purge): void
     */
    public function afterDelete(Closure $callback): static
    {
        return $this->setHook('afterDelete', $callback);
    }

    /**
     * fn (string $id, array $row): void
     */
    public function afterRestore(Closure $callback): static
    {
        return $this->setHook('afterRestore', $callback);
    }

    protected function setHook(string $name, Closure $callback): static
    {
        $this->hooks[$name] = $callback;

        return $this;
    }

    // ------------------------------------------------------------------
    // Configuració: accions
    // ------------------------------------------------------------------

    /**
     * Afegeix un enllaç a cada fila del llistat (i a la fitxa).
     *
     * $url pot contenir {id} i {nom_de_camp}, o ser fn (array $row): string.
     * Les URL relatives es passen per site_url().
     *
     * Opcions: 'icon' (p. ex. 'bi bi-receipt'), 'class' (p. ex. 'btn-outline-info'),
     *          'target', 'confirm' (missatge), 'visible' (fn (array $row): bool),
     *          'method' ('get' o 'post'; amb 'post' s'envia un formulari amb el token CSRF).
     *
     * @param array<string, mixed> $options
     */
    public function addAction(string $label, Closure|string $url, array $options = []): static
    {
        $this->actions[] = $this->normalizeAction($label, $url, $options);

        return $this;
    }

    /**
     * Afegeix un botó a la capçalera del llistat.
     *
     * @param array<string, mixed> $options Igual que addAction()
     */
    public function addHeaderAction(string $label, string $url, array $options = []): static
    {
        $this->headerActions[] = $this->normalizeAction($label, $url, $options);

        return $this;
    }

    /**
     * Acció massiva sobre els registres seleccionats.
     * fn (list<string> $ids, Crud $crud): string|ResponseInterface|null
     * Si retorna un string es mostra com a missatge.
     *
     * Opcions: 'confirm' (missatge), 'trash' (true per oferir-la a la paperera).
     *
     * @param array<string, mixed> $options
     */
    public function addBulkAction(string $key, string $label, Closure $callback, array $options = []): static
    {
        $this->bulkActions[$key] = [
            'label'    => $label,
            'callback' => $callback,
            'confirm'  => $options['confirm'] ?? null,
            'trash'    => (bool) ($options['trash'] ?? false),
        ];

        return $this;
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     */
    protected function normalizeAction(string $label, Closure|string $url, array $options): array
    {
        return [
            'label'   => $label,
            'url'     => $url,
            'icon'    => $options['icon'] ?? null,
            'class'   => $options['class'] ?? 'btn-outline-secondary',
            'target'  => $options['target'] ?? null,
            'confirm' => $options['confirm'] ?? null,
            'visible' => $options['visible'] ?? null,
            'method'  => strtolower($options['method'] ?? 'get') === 'post' ? 'post' : 'get',
        ];
    }

    // ------------------------------------------------------------------
    // Configuració: permisos
    // ------------------------------------------------------------------

    /**
     * Permet o denega una operació: list, add, edit, show, delete, export,
     * import, trash, restore, purge, search, filters, bulk.
     *
     * Amb una closure fn (?array $row, string $operation): bool es pot decidir
     * registre a registre ($row és null quan no hi ha cap registre concret).
     */
    public function setPermission(string $operation, bool|Closure $allowed): static
    {
        $this->permissions[$operation] = $allowed;

        return $this;
    }

    public function unsetAdd(): static
    {
        return $this->setPermission('add', false);
    }

    public function unsetEdit(): static
    {
        return $this->setPermission('edit', false);
    }

    public function unsetDelete(): static
    {
        return $this->setPermission('delete', false);
    }

    public function unsetShow(): static
    {
        return $this->setPermission('show', false);
    }

    public function unsetExport(): static
    {
        return $this->setPermission('export', false);
    }

    public function unsetTrash(): static
    {
        return $this->setPermission('trash', false);
    }

    public function unsetPurge(): static
    {
        return $this->setPermission('purge', false);
    }

    public function unsetSearch(): static
    {
        return $this->setPermission('search', false);
    }

    public function unsetFilters(): static
    {
        return $this->setPermission('filters', false);
    }

    public function unsetBulk(): static
    {
        return $this->setPermission('bulk', false);
    }

    /**
     * Només consulta: sense alta, edició, esborrat, restauració ni buidatge
     * de la paperera.
     */
    public function readOnly(): static
    {
        return $this->unsetAdd()->unsetEdit()->unsetDelete()->unsetPurge()->setPermission('restore', false);
    }

    // ------------------------------------------------------------------
    // Configuració: presentació
    // ------------------------------------------------------------------

    /**
     * @param list<int>|null $options
     */
    public function setPerPage(int $perPage, ?array $options = null): static
    {
        $this->perPage = max(1, $perPage);

        if ($options !== null) {
            $this->perPageOptions = array_values($options);
        }

        return $this;
    }

    public function defaultOrder(string $field, string $direction = 'asc'): static
    {
        $this->defaultOrder = [$field, strtolower($direction) === 'desc' ? 'desc' : 'asc'];

        return $this;
    }

    /**
     * URL base del CRUD. Normalment es dedueix sola de la URL actual.
     */
    public function setBaseUrl(string $url): static
    {
        $this->baseUrl = rtrim($url, '/');

        return $this;
    }

    /**
     * URL on es torna després de desar, esborrar o cancel·lar. Per defecte
     * és el llistat amb l'estat que l'usuari hi havia deixat.
     */
    public function setBackUrl(string $url): static
    {
        $this->backUrl = $url;

        return $this;
    }

    public function setTheme(string $theme): static
    {
        $this->theme = $theme;

        return $this;
    }

    /**
     * 'default', false o el nom d'una vista de CodeIgniter (vegeu Config\Kpcrudy::$layout).
     */
    public function setLayout(string|false $layout): static
    {
        $this->layout = $layout;

        return $this;
    }

    /**
     * Registra les dues rutes que necessita un CRUD:
     *
     *     Crud::routes($routes, 'admin/clients', 'Admin::clients');
     *
     * @param array<string, mixed> $options Opcions de ruta (filter, as...)
     */
    public static function routes(RouteCollection $routes, string $path, string $handler, array $options = []): void
    {
        $path = trim($path, '/');

        // La ruta pot tenir paràmetres (p. ex. "clients/(:num)/notes"): els segments
        // de l'operació són el grup següent
        $next = preg_match_all('/\((?!\?)/', $path) + 1;

        // La marca 'kpcrudy' permet trobar aquestes rutes a checkRoutes()
        $options['kpcrudy'] = true;

        $routes->match(['GET', 'POST'], $path, $handler, $options);
        $routes->match(['GET', 'POST'], $path . '/(:any)', $handler . '/$' . $next, $options);
    }

    /**
     * En desenvolupament, avisa (amb una excepció) de les rutes que xoquen amb
     * les d'un CRUD:
     *  - definides DESPRÉS de Crud::routes() amb el mateix prefix: no s'hi arriba
     *    mai, perquè "prefix/(:any)" les captura abans;
     *  - definides ABANS que capturen URL del CRUD (add, edit/5...): el CRUD
     *    deixa de funcionar per a aquestes URL.
     * Una ruta pròpia (p. ex. per a un enllaç públic) ha d'anar amb un altre prefix.
     */
    public static function checkRoutes(): void
    {
        if (static::$routesChecked) {
            return;
        }

        static::$routesChecked = true;
        $collection            = service('routes');
        $samples               = ['add', 'trash', 'export', 'import', 'bulk', 'emptytrash', 'edit/1', 'show/1', 'delete/1', 'restore/1', 'purge/1'];

        // CodeIgniter 4.4 desa els verbs en minúscules; a partir de la 4.5, en majúscules
        foreach (['GET', 'POST', 'get', 'post'] as $verb) {
            $keys = array_map('strval', array_keys($collection->getRoutes($verb)));
            $own  = array_values(array_filter($keys, static fn (string $key): bool => ! empty($collection->getRoutesOptions($key, $verb)['kpcrudy'])));

            foreach ($own as $key) {
                // Si "prefix/(:any)" ja existia, CodeIgniter ignora la del CRUD
                if (! str_ends_with($key, '/(.*)')) {
                    if (in_array($key . '/(.*)', $keys, true) && ! in_array($key . '/(.*)', $own, true)) {
                        throw new RuntimeException("Kpcrudy: la ruta {$verb} '{$key}/(.*)' s'ha definit abans de Crud::routes() i substitueix la del CRUD '{$key}'. Fes-la servir amb un altre prefix.");
                    }

                    continue;
                }

                $base     = substr($key, 0, -5);
                $position = array_search($key, $keys, true);

                foreach ($keys as $index => $other) {
                    if (in_array($other, $own, true)) {
                        // Un altre CRUD definit després dins d'aquest prefix (p. ex. "admin" i "admin/users")
                        if ($index > $position && $other !== $key && str_starts_with($other, $base . '/')) {
                            throw new RuntimeException("Kpcrudy: el CRUD '{$other}' no s'executarà mai: el CRUD '{$key}' es defineix abans i el captura. Defineix primer el CRUD amb el prefix més llarg.");
                        }

                        continue;
                    }

                    // Una ruta posterior amb el mateix prefix no s'executarà mai
                    if ($index > $position && str_starts_with($other, $base . '/')) {
                        throw new RuntimeException("Kpcrudy: la ruta {$verb} '{$other}' no s'executarà mai: la ruta del CRUD '{$key}' es defineix abans i la captura. Fes-la servir amb un altre prefix (p. ex. per a enllaços públics amb l'id emmascarat).");
                    }

                    // Una ruta anterior que captura URL del CRUD
                    if ($index < $position && ! str_contains($base, '(')) {
                        foreach ($samples as $sample) {
                            if (@preg_match('#^' . $other . '$#u', $base . '/' . $sample) === 1) {
                                throw new RuntimeException("Kpcrudy: la ruta {$verb} '{$other}' captura '{$base}/{$sample}', que és una URL del CRUD '{$base}'. Fes-la servir amb un altre prefix.");
                            }
                        }
                    }
                }
            }
        }
    }

    // ------------------------------------------------------------------
    // Identificadors emmascarats
    // ------------------------------------------------------------------

    /**
     * Emmascara els identificadors de les URL del CRUD: edit/Xk3f9Qa... en lloc
     * d'edit/5. Els hooks, els permisos i els callbacks continuen rebent l'id real.
     *
     * $context: per defecte, el nom de la taula (el mateix id dona codis diferents
     * a cada taula). Cal fer servir el mateix context per desfer el codi fora del CRUD.
     * $masker: una instància o classe de Kpcrudy\IdMasker (per defecte, Config::$idMasker).
     */
    public function maskIds(bool $enabled = true, ?string $context = null, IdMasker|string|null $masker = null): static
    {
        $this->maskIds     = $enabled;
        $this->maskContext = $context;

        if ($masker !== null) {
            $this->masker = is_string($masker) ? new $masker($this->config) : $masker;
        }

        return $this;
    }

    /**
     * Id tal com surt a les URL d'aquest CRUD: emmascarat si maskIds() és actiu.
     */
    public function encodeId(int|string $id): string
    {
        return $this->maskIds ? $this->masker()->encode((string) $id, $this->maskContextName()) : (string) $id;
    }

    /**
     * Id real a partir del que surt a les URL, o null si el codi no és vàlid.
     */
    public function decodeId(string $value): ?string
    {
        return $this->maskIds ? $this->masker()->decode($value, $this->maskContextName()) : $value;
    }

    /**
     * Emmascara un id fora d'un CRUD (p. ex. per fer un enllaç públic). El
     * context ha de ser el mateix que el del CRUD: per defecte, el nom de la taula.
     */
    public static function maskId(int|string $id, string $context, ?KpcrudyConfig $config = null): string
    {
        return static::defaultMasker($config)->encode((string) $id, $context);
    }

    /**
     * Id real d'un codi fet amb maskId() o per un CRUD, o null si no és vàlid.
     */
    public static function unmaskId(string $code, string $context, ?KpcrudyConfig $config = null): ?string
    {
        return static::defaultMasker($config)->decode($code, $context);
    }

    /**
     * Id real a partir del valor d'una URL pròpia (p. ex. una ruta amb (:segment)
     * que rep {kp_id}), tant si els ids estan emmascarats com si no: amb
     * emmascarament desfà el codi; sense, retorna el valor tal qual. Null si no és vàlid.
     *
     * $masked: null = segons Config::$maskIds; true/false si el CRUD ho decideix amb maskIds().
     */
    public static function idFromUrl(string $value, string $context, ?bool $masked = null, ?KpcrudyConfig $config = null): ?string
    {
        $config = static::resolveConfig($config);

        if ($masked ?? $config->maskIds) {
            return static::unmaskId($value, $context, $config);
        }

        return $value === '' ? null : $value;
    }

    /**
     * Valor que va a la URL per a un id real: el codi si els ids estan
     * emmascarats, l'id tal qual si no. És el que posa {kp_id} a les accions.
     *
     * $masked: null = segons Config::$maskIds; true/false si el CRUD ho decideix amb maskIds().
     */
    public static function idForUrl(int|string $id, string $context, ?bool $masked = null, ?KpcrudyConfig $config = null): string
    {
        $config = static::resolveConfig($config);

        return ($masked ?? $config->maskIds) ? static::maskId($id, $context, $config) : (string) $id;
    }

    protected static function defaultMasker(?KpcrudyConfig $config): IdMasker
    {
        $config = static::resolveConfig($config);
        $class  = $config->idMasker;
        $masker = new $class($config);

        if (! $masker instanceof IdMasker) {
            throw new RuntimeException("Kpcrudy: la classe '{$class}' no implementa Kpcrudy\\IdMasker.");
        }

        return $masker;
    }

    protected function masker(): IdMasker
    {
        return $this->masker ??= static::defaultMasker($this->config);
    }

    protected function maskContextName(): string
    {
        if ($this->maskContext === null) {
            $this->boot();
        }

        return $this->maskContext ?? $this->table;
    }

    // ------------------------------------------------------------------
    // URL signades
    // ------------------------------------------------------------------

    /**
     * Les operacions indicades només s'obren amb una URL signada (p. ex. per
     * compartir una consulta sense haver d'iniciar sessió). Els enllaços que
     * genera el CRUD (paginació, fitxes, exportació...) se signen sols i
     * caduquen quan caduca la URL amb què s'ha entrat.
     *
     * La signatura cobreix el camí (operació i id), no els paràmetres (?q=,
     * ?page=, ?f[]...): el que l'usuari pot veure ho ha de limitar where().
     *
     * $operations: '*' (totes, el valor per defecte i el més segur) o una llista
     *              ('list', 'show', 'export'...). Les que no hi són queden obertes:
     *              protegeix-les amb un filtre d'autenticació o amb unset*().
     * $ttl:        durada, en segons, dels enllaços que signen les operacions de $signFrom; null = no caduquen.
     * $signFrom:   operacions que NO necessiten signatura i que reparteixen enllaços
     *              signats (p. ex. ['list'] per a un llistat intern, protegit amb login,
     *              que dona enllaços signats a les fitxes). Per defecte, cap.
     *
     * @param list<string>|string $operations
     * @param list<string>|string $signFrom
     */
    public function requireSignature(array|string $operations = '*', ?int $ttl = null, array|string $signFrom = []): static
    {
        $all = ['list', ...self::ID_OPERATIONS, ...self::OPERATIONS];

        $operations = $operations === '*' ? $all : array_values((array) $operations);
        $signFrom   = array_values((array) $signFrom);

        // Un nom mal escrit deixaria l'operació oberta sense avisar
        foreach ([...$operations, ...$signFrom] as $operation) {
            if (! in_array($operation, $all, true)) {
                throw new RuntimeException("Kpcrudy: l'operació '{$operation}' no existeix. Operacions: " . implode(', ', $all) . '.');
            }
        }

        if (array_intersect($operations, $signFrom) !== []) {
            throw new RuntimeException('Kpcrudy: una operació no pot necessitar signatura i alhora repartir-ne ($signFrom).');
        }

        $this->signedOperations = $operations;
        $this->signFrom         = $signFrom;
        $this->signTtl          = $ttl;

        return $this;
    }

    /**
     * URL signada d'una operació d'aquest CRUD, per compartir-la (correu,
     * missatge...). L'id és el real (s'emmascara si cal). $ttl en segons; null = no caduca.
     *
     * @param array<string, mixed> $query
     */
    public function signedUrl(string $operation = 'list', int|string|null $id = null, ?int $ttl = 86400, array $query = []): string
    {
        $url = $this->withQuery($this->rawUrl($operation, $id === null ? null : (string) $id), $query);

        return $this->signer()->sign($url, $ttl === null ? null : time() + $ttl, false);
    }

    /**
     * Si una petició sense signatura arriba a una operació que no la necessita,
     * el CRUD no signa res, excepte per a les operacions de $signFrom.
     */
    protected function canSign(): bool
    {
        // Abans de render() (p. ex. url() al controlador) qui demana l'enllaç és el desenvolupador
        return ! $this->signatureChecked || $this->signAllowed;
    }

    /**
     * Signa qualsevol URL (absoluta o relativa al projecte), p. ex. d'una ruta
     * pròpia. Per defecte la signatura cobreix també els paràmetres, que no es
     * poden canviar. $ttl en segons; null = no caduca.
     */
    public static function signUrl(string $url, ?int $ttl = 3600, bool $lockQuery = true, ?KpcrudyConfig $config = null): string
    {
        if (! preg_match('#^([a-z][a-z0-9+.-]*:|/)#i', $url)) {
            $url = site_url($url);
        }

        return (new UrlSigner(static::resolveConfig($config)))->sign($url, $ttl === null ? null : time() + $ttl, $lockQuery);
    }

    /**
     * Estat de la signatura de la petició: 'valid', 'missing', 'invalid' o 'expired'
     * (constants de Kpcrudy\UrlSigner). Per a controladors propis: només accepta
     * les URL signades amb signUrl() o signedUrl(), mai les que signa un CRUD navegant.
     */
    public static function checkSignature(?IncomingRequest $request = null, bool $allowFreeQuery = false, ?KpcrudyConfig $config = null): string
    {
        return (new UrlSigner(static::resolveConfig($config)))->check($request ?? service('request'), $allowFreeQuery);
    }

    public static function hasValidSignature(?IncomingRequest $request = null, bool $allowFreeQuery = false, ?KpcrudyConfig $config = null): bool
    {
        return static::checkSignature($request, $allowFreeQuery, $config) === UrlSigner::VALID;
    }

    protected function signer(): UrlSigner
    {
        return $this->signer ??= new UrlSigner($this->config);
    }

    protected function signatureRequired(string $operation): bool
    {
        return in_array($operation, $this->signedOperations, true);
    }

    /**
     * Atura la petició si l'operació necessita signatura i no en té una de vàlida.
     * També fixa la caducitat dels enllaços que se signaran en aquesta petició.
     */
    protected function verifySignature(string $operation): void
    {
        if ($this->signedOperations === []) {
            return;
        }

        $this->signatureChecked = true;
        $status                 = $this->signer()->check($this->request, true, ['', 'crud', 'crud-trash']);
        $matched                = $this->signer()->matched();

        // Exportar la paperera demana una signatura feta des de la paperera (o una URL signada sencera)
        if ($status === UrlSigner::VALID && $operation === 'export' && $this->request->getGet('trash') && $this->signatureRequired('trash')
            && $matched['scope'] !== 'crud-trash' && $matched['mode'] !== 'full') {
            $status = UrlSigner::INVALID;
        }

        if ($status === UrlSigner::VALID) {
            $this->signExpires = $this->signer()->expires($this->request);
            $this->signAllowed = in_array($operation, ['list', 'trash'], true);
        } elseif ($this->signatureRequired($operation)) {
            throw PageNotFoundException::forPageNotFound(lang($status === UrlSigner::EXPIRED ? 'Kpcrudy.linkExpired' : 'Kpcrudy.linkInvalid'));
        } else {
            $this->signExpires = $this->signTtl === null ? null : time() + $this->signTtl;
            $this->signAllowed = in_array($operation, $this->signFrom, true);
        }
    }

    protected function signIfRequired(string $url, string $operation, string $scope = 'crud'): string
    {
        if (! $this->signatureRequired($operation) || ! $this->canSign()) {
            return $url;
        }

        $expires = $this->signatureChecked ? $this->signExpires : ($this->signTtl === null ? null : time() + $this->signTtl);

        return $this->signer()->sign($url, $expires, false, $scope);
    }

    /**
     * kp_exp i kp_sig d'una operació sense id, per als camps ocults dels formularis GET.
     *
     * @return array<string, string>
     */
    protected function signatureParams(string $operation): array
    {
        if (! $this->signatureRequired($operation)) {
            return [];
        }

        parse_str((string) parse_url($this->signIfRequired($this->rawUrl($operation), $operation), PHP_URL_QUERY), $params);

        return array_intersect_key($params, array_flip(UrlSigner::PARAMS));
    }

    protected static function resolveConfig(?KpcrudyConfig $config): KpcrudyConfig
    {
        $config ??= config('Kpcrudy');

        return $config instanceof KpcrudyConfig ? $config : new KpcrudyConfig();
    }

    // ------------------------------------------------------------------
    // Accés públic per a callbacks
    // ------------------------------------------------------------------

    public function getModel(): Model
    {
        $this->boot();

        return $this->model;
    }

    public function getTable(): string
    {
        $this->boot();

        return $this->table;
    }

    public function getPrimaryKey(): string
    {
        $this->boot();

        return $this->primaryKey;
    }

    public function getBaseUrl(): string
    {
        $this->boot();

        if ($this->baseUrl === null) {
            $this->resolveOperation();
        }

        return $this->baseUrl;
    }

    // ------------------------------------------------------------------
    // Execució
    // ------------------------------------------------------------------

    /**
     * Executa l'operació que correspon a la URL actual i en retorna el
     * resultat: HTML o una resposta (redirecció, descàrrega...).
     */
    public function render(): ResponseInterface|string
    {
        if (ENVIRONMENT === 'development' && $this->config->checkRoutes) {
            static::checkRoutes();
        }

        $this->boot();
        [$operation, $id] = $this->resolveOperation();

        // Amb els identificadors emmascarats, un codi que no és vàlid no coincideix amb cap registre
        if ($id !== null) {
            $id = $this->decodeId($id) ?? '';
        }

        if ($this->isPost()) {
            $this->verifyCsrf();
        }

        $this->verifySignature($operation);

        return match ($operation) {
            'list'       => $this->listOperation(false),
            'trash'      => $this->listOperation(true),
            'add'        => $this->formOperation(null),
            'edit'       => $this->formOperation($id),
            'show'       => $this->showOperation($id),
            'delete'     => $this->deleteOperation($id),
            'restore'    => $this->restoreOperation($id),
            'purge'      => $this->purgeOperation($id),
            'emptytrash' => $this->emptyTrashOperation(),
            'export'     => $this->exportOperation(),
            'import'     => $this->importOperation(),
            'bulk'       => $this->bulkOperation(),
        };
    }

    /**
     * Comprova el token CSRF si el filtre 'csrf' de CodeIgniter no ho ha fet
     * ja: el filtre treu el token de la petició i una segona verificació fallaria.
     */
    protected function verifyCsrf(): void
    {
        if (! $this->config->csrfProtection) {
            return;
        }

        $filters = service('filters');
        $aliases = config('Filters')->aliases ?? [];
        $names   = method_exists($filters, 'getFiltersClass')
            ? array_map(static fn ($entry): string => is_array($entry) ? (string) $entry[0] : (string) $entry, $filters->getFiltersClass()['before'] ?? [])
            : [];

        foreach ($filters->getFilters()['before'] ?? [] as $name) {
            $name = explode(':', (string) $name, 2)[0];
            array_push($names, ...(array) ($aliases[$name] ?? $name));
        }

        foreach ($names as $class) {
            if (is_a($class, CSRF::class, true)) {
                return;
            }
        }

        // Llança una SecurityException (error 403) si el token no és vàlid
        service('security')->verify($this->request);
    }

    protected function boot(): void
    {
        if ($this->booted) {
            return;
        }

        if ($this->model === null && $this->table === null) {
            throw new RuntimeException('Kpcrudy: cal cridar setTable() o setModel() abans de render().');
        }

        if ($this->model !== null) {
            $model = $this->model;

            $this->table ??= $model->table;
            $this->primaryKey ??= $model->primaryKey;
            $this->db           = $model->db;
            $this->softDeletes  = (bool) $model->useSoftDeletes;
            $this->deletedField = $model->deletedField;

            if ($model->useTimestamps) {
                $this->createdField = $model->createdField ?: null;
                $this->updatedField = $model->updatedField ?: null;
            }

            $this->loadMeta();
        } else {
            $this->db ??= db_connect();
            $this->loadMeta();

            if ($this->primaryKey === null) {
                foreach ($this->meta as $name => $meta) {
                    if ($meta['primary']) {
                        $this->primaryKey = $name;
                        break;
                    }
                }
            }

            $this->softDeletes ??= isset($this->meta[$this->deletedField]);

            if ($this->softDeletes && ! isset($this->meta[$this->deletedField])) {
                throw new RuntimeException("Kpcrudy: la taula '{$this->table}' no té el camp '{$this->deletedField}'.");
            }

            $this->createdField = isset($this->meta['created_at']) ? 'created_at' : null;
            $this->updatedField = isset($this->meta['updated_at']) ? 'updated_at' : null;
        }

        $this->primaryKey ??= 'id';

        if ($this->model === null) {
            $this->model = new DynamicModel([
                'table'          => $this->table,
                'primaryKey'     => $this->primaryKey,
                'allowedFields'  => array_keys($this->meta),
                'useSoftDeletes' => $this->softDeletes,
                'deletedField'   => $this->deletedField,
                'useTimestamps'  => $this->createdField !== null || $this->updatedField !== null,
                'createdField'   => $this->createdField ?? '',
                'updatedField'   => $this->updatedField ?? '',
                'returnType'     => 'array',
            ], $this->db);
        }

        $this->subject ??= ucfirst(str_replace('_', ' ', $this->table));
        $this->subjectPlural ??= $this->subject;

        if (! in_array($this->perPage, $this->perPageOptions, true)) {
            $this->perPageOptions[] = $this->perPage;
            sort($this->perPageOptions);
        }

        $this->booted = true;
    }

    protected function loadMeta(): void
    {
        if (! $this->db->tableExists($this->table)) {
            throw new RuntimeException("Kpcrudy: la taula '{$this->table}' no existeix.");
        }

        foreach ($this->db->getFieldData($this->table) as $field) {
            $this->meta[$field->name] = [
                'type'       => strtolower((string) ($field->type ?? '')),
                'max_length' => isset($field->max_length) ? (int) $field->max_length : null,
                'nullable'   => (bool) ($field->nullable ?? true),
                'default'    => $field->default ?? null,
                'primary'    => ! empty($field->primary_key),
                'enum'       => null,
            ];
        }

        // MySQL no informa dels valors dels ENUM a getFieldData()
        if ($this->db->DBDriver === 'MySQLi') {
            $sql = 'SHOW COLUMNS FROM ' . $this->db->escapeIdentifiers($this->db->prefixTable($this->table));

            foreach ($this->db->query($sql)->getResultArray() as $column) {
                if (isset($this->meta[$column['Field']]) && preg_match('/^enum\((.*)\)$/i', (string) $column['Type'], $m)) {
                    $this->meta[$column['Field']]['enum'] = str_getcsv($m[1], ',', "'", '');
                }
            }
        }
    }

    /**
     * Dedueix l'operació i l'identificador dels darrers segments de la URL.
     *
     * @return array{0: string, 1: string|null}
     */
    protected function resolveOperation(): array
    {
        // getPath() ja està descodificat: els segments no s'han de tornar a descodificar
        $path = trim($this->request->getPath(), '/');
        $base = $this->routeBase($path);

        if ($base !== null) {
            // Amb una ruta de Crud::routes() la base és coneguda: després només hi pot haver
            // "operació" o "operació/id". Qualsevol altra cosa és una pàgina que no existeix.
            $rest     = trim(substr($path, strlen($base)), '/');
            $segments = $rest === '' ? [] : explode('/', $rest);

            [$operation, $id] = match (true) {
                $segments === []                                                        => ['list', null],
                count($segments) === 1 && in_array($segments[0], self::OPERATIONS, true)  => [$segments[0], null],
                count($segments) === 2 && in_array($segments[0], self::ID_OPERATIONS, true) => [$segments[0], $segments[1]],
                default                                                                 => throw PageNotFoundException::forPageNotFound(),
            };

            $this->baseUrl ??= rtrim(site_url($base), '/');

            return [$operation, $id];
        }

        // Rutes definides a mà: es dedueix dels darrers segments
        $segments = $path === '' ? [] : explode('/', $path);
        $count    = count($segments);

        $operation = 'list';
        $id        = null;

        if ($count >= 2 && in_array($segments[$count - 2], self::ID_OPERATIONS, true)) {
            $operation = $segments[$count - 2];
            $id        = $segments[$count - 1];
            $segments  = array_slice($segments, 0, $count - 2);
        } elseif ($count >= 1 && in_array($segments[$count - 1], self::OPERATIONS, true)) {
            $operation = $segments[$count - 1];
            $segments  = array_slice($segments, 0, $count - 1);
        }

        $this->baseUrl ??= rtrim(site_url(implode('/', $segments)), '/');

        return [$operation, $id];
    }

    /**
     * Part de la URL que correspon a la base del CRUD, si la petició ha entrat per
     * una ruta de Crud::routes(); null si la ruta s'ha definit d'una altra manera.
     */
    protected function routeBase(string $path): ?string
    {
        $router = service('router');

        if (! method_exists($router, 'getMatchedRoute') || ! method_exists($router, 'getMatchedRouteOptions')) {
            return null;
        }

        $matched = $router->getMatchedRoute();
        $options = $router->getMatchedRouteOptions();

        if (! is_array($matched) || ! is_string($matched[0] ?? null) || empty($options['kpcrudy'])) {
            return null;
        }

        $key   = trim($matched[0], '/');
        $regex = str_replace('{locale}', '[^/]+', str_ends_with($key, '/(.*)') ? substr($key, 0, -5) : $key);

        return @preg_match('#^' . $regex . '(?=/|$)#u', $path, $m) === 1 ? $m[0] : null;
    }

    // ------------------------------------------------------------------
    // Operacions
    // ------------------------------------------------------------------

    protected function listOperation(bool $trash): string
    {
        if (! $this->can('list') || ($trash && ! $this->can('trash'))) {
            throw PageNotFoundException::forPageNotFound(lang('Kpcrudy.notAllowed'));
        }

        $state   = $this->readState();
        $builder = $this->selectBuilder($trash);
        $this->applySearchAndFilters($builder, $state);

        $total  = $builder->countAllResults(false);
        $pages  = max(1, (int) ceil($total / $state['per_page']));
        $page   = $state['page'] = min($state['page'], $pages);
        $offset = ($page - 1) * $state['per_page'];

        // Es recorda l'estat per tornar-hi després d'editar, esborrar...
        session()->set($this->stateKey(), ['query' => $this->stateQuery($state), 'trash' => $trash]);

        $this->applyOrder($builder, $state);
        $rows = $this->attachNN($builder->limit($state['per_page'], $offset)->get()->getResultArray());

        $sortable = $this->sortableColumns();
        $columns  = [];

        foreach ($this->columnList() as $name) {
            $sorted    = $state['sort'] === $name ? $state['dir'] : null;
            $columns[] = [
                'name'     => $name,
                'label'    => $this->label($name),
                'sortable' => in_array($name, $sortable, true),
                'sorted'   => $sorted,
                'url'      => $this->listUrl($trash, $this->stateQuery($state, [
                    'sort' => $name,
                    'dir'  => $sorted === 'asc' ? 'desc' : 'asc',
                    'page' => 1,
                ])),
            ];
        }

        $items = [];

        foreach ($rows as $row) {
            $cells = [];

            foreach ($this->columnList() as $name) {
                $cells[$name] = $this->renderValue($name, $row, 'list');
            }

            $items[] = [
                'id'      => $this->encodeId((string) $row[$this->primaryKey]),
                'cells'   => $cells,
                'actions' => $this->rowActions($row, $trash, 'list'),
            ];
        }

        $trashCount = (! $trash && $this->can('trash')) ? $this->baseBuilder(true)->countAllResults() : 0;
        $title      = $trash ? lang('Kpcrudy.trashTitle', [$this->subjectPlural]) : $this->subjectPlural;

        return $this->output('list', [
            'trash'          => $trash,
            'columns'        => $columns,
            'rows'           => $items,
            'state'          => $state,
            'listUrl'        => $this->listUrl($trash),
            'bulkUrl'        => $this->url('bulk'),
            // Els formularis GET no envien els paràmetres de l'action: la signatura va en camps ocults
            'searchHidden'   => $this->stateQuery($state, ['q' => '', 'page' => 1, 'per_page' => null]) + $this->signatureParams($trash ? 'trash' : 'list'),
            'filterHidden'   => array_intersect_key($this->stateQuery($state), array_flip(['q', 'per_page', 'sort', 'dir'])) + $this->signatureParams($trash ? 'trash' : 'list'),
            'filters'        => $this->can('filters') ? $this->filterData($state) : [],
            'filtersActive'  => count($this->stateQuery($state)['f'] ?? []),
            'filtersReset'   => $this->listUrl($trash, $this->stateQuery($state, ['f' => [], 'page' => 1])),
            'canSearch'      => $this->can('search') && $this->searchFieldList() !== [],
            'perPageOptions' => $this->perPageOptions,
            'headerActions'  => $this->headerActionList($trash, $state, $trashCount),
            'bulkActions'    => $this->bulkActionList($trash),
            'pagination'     => [
                'total' => $total,
                'page'  => $page,
                'pages' => $pages,
                'from'  => $total === 0 ? 0 : $offset + 1,
                'to'    => $offset + count($rows),
                'links' => $this->paginationLinks($page, $pages, fn (int $p): string => $this->listUrl($trash, $this->stateQuery($state, ['page' => $p]))),
            ],
        ], $title);
    }

    protected function formOperation(?string $id): ResponseInterface|string
    {
        $mode   = $id === null ? 'add' : 'edit';
        $record = null;

        if ($mode === 'edit') {
            $record = $this->findRecord($id);

            if ($record === null) {
                return $this->redirectToList('danger', lang('Kpcrudy.notFound'));
            }

            // A partir d'aquí, l'identificador real (no el text de la URL)
            $id = (string) $record[$this->primaryKey];
        }

        if (! $this->can($mode, $record)) {
            return $this->redirectToList('danger', lang('Kpcrudy.notAllowed'));
        }

        $fields    = $mode === 'add' ? $this->addFieldList() : $this->editFieldList();
        $values    = $mode === 'add' ? $this->defaultValues($fields) : $this->recordValues($record, $fields);
        $errors    = [];
        $formError = null;

        if ($this->isPost()) {
            $result = $this->save($mode, $id, $record, $fields);

            if ($result instanceof ResponseInterface) {
                return $result;
            }

            [$values, $errors, $formError] = $result;
        }

        $title = lang($mode === 'add' ? 'Kpcrudy.addTitle' : 'Kpcrudy.editTitle', [$this->subject]);

        return $this->output('form', [
            'mode'      => $mode,
            'id'        => $id === null ? null : $this->encodeId($id),
            'action'    => $mode === 'add' ? $this->url('add') : $this->url('edit', $id),
            'backUrl'   => $this->backUrl(),
            'fields'    => $this->formFields($fields, $values, $errors, $mode, $record),
            'multipart' => $this->hasUploads($fields),
            'errors'    => $errors,
            'formError' => $formError,
        ], $title);
    }

    protected function showOperation(?string $id): ResponseInterface|string
    {
        $record = $this->findDisplayRecord((string) $id);

        if ($record === null) {
            return $this->redirectToList('danger', lang('Kpcrudy.notFound'));
        }

        if (! $this->can('show', $record)) {
            return $this->redirectToList('danger', lang('Kpcrudy.notAllowed'));
        }

        $items = [];

        foreach ($this->showFieldList() as $name) {
            $items[] = [
                'name'  => $name,
                'label' => $this->label($name),
                'html'  => $this->renderValue($name, $record, 'show'),
            ];
        }

        return $this->output('show', [
            'id'      => $this->encodeId((string) $record[$this->primaryKey]),
            'items'   => $items,
            'actions' => $this->rowActions($record, false, 'show'),
            // Amb l'enllaç signat d'una sola fitxa no es pot tornar a un llistat que també demana signatura
            'backUrl' => ! $this->canSign() && $this->signatureRequired('list') ? '' : $this->backUrl(),
        ], lang('Kpcrudy.showTitle', [$this->subject]));
    }

    protected function deleteOperation(?string $id): ResponseInterface
    {
        if (! $this->isPost()) {
            return $this->redirectToList('danger', lang('Kpcrudy.invalidRequest'));
        }

        $record = $this->findRecord((string) $id);

        if ($record === null) {
            return $this->redirectToList('danger', lang('Kpcrudy.notFound'));
        }

        if (! $this->can('delete', $record)) {
            return $this->redirectToList('danger', lang('Kpcrudy.notAllowed'));
        }

        $result = $this->deleteRecord((string) $record[$this->primaryKey], $record, false);

        if ($result !== true) {
            return $this->redirectToList('danger', $result);
        }

        return $this->redirectToList('success', lang($this->softDeletes ? 'Kpcrudy.trashed' : 'Kpcrudy.deleted', [$this->subject]));
    }

    protected function restoreOperation(?string $id): ResponseInterface
    {
        if (! $this->isPost()) {
            return $this->redirectToList('danger', lang('Kpcrudy.invalidRequest'));
        }

        $record = $this->findRecord((string) $id, true);

        if ($record === null) {
            return $this->redirectToList('danger', lang('Kpcrudy.notFound'));
        }

        if (! $this->can('restore', $record)) {
            return $this->redirectToList('danger', lang('Kpcrudy.notAllowed'));
        }

        $result = $this->restoreRecord((string) $record[$this->primaryKey], $record);

        if ($result !== true) {
            return $this->redirectToList('danger', $result);
        }

        return $this->redirectToList('success', lang('Kpcrudy.restored', [$this->subject]));
    }

    protected function purgeOperation(?string $id): ResponseInterface
    {
        if (! $this->isPost()) {
            return $this->redirectToList('danger', lang('Kpcrudy.invalidRequest'));
        }

        $record = $this->findRecord((string) $id, true);

        if ($record === null) {
            return $this->redirectToList('danger', lang('Kpcrudy.notFound'));
        }

        if (! $this->can('purge', $record)) {
            return $this->redirectToList('danger', lang('Kpcrudy.notAllowed'));
        }

        $result = $this->deleteRecord((string) $record[$this->primaryKey], $record, true);

        if ($result !== true) {
            return $this->redirectToList('danger', $result);
        }

        return $this->redirectToList('success', lang('Kpcrudy.purged', [$this->subject]));
    }

    protected function emptyTrashOperation(): ResponseInterface
    {
        if (! $this->isPost()) {
            return $this->redirectToList('danger', lang('Kpcrudy.invalidRequest'));
        }

        if (! $this->can('purge')) {
            return $this->redirectToList('danger', lang('Kpcrudy.notAllowed'));
        }

        $rows  = $this->baseBuilder(true)->get()->getResultArray();
        $count = 0;

        foreach ($rows as $row) {
            $id = (string) $row[$this->primaryKey];

            if ($this->can('purge', $row) && $this->deleteRecord($id, $row, true) === true) {
                $count++;
            }
        }

        return $this->redirectToList('success', lang('Kpcrudy.bulkDone', [$count]));
    }

    protected function exportOperation(): ResponseInterface
    {
        // Exportar és una altra manera de llegir el llistat
        if (! $this->can('export') || ! $this->can('list')) {
            return $this->redirectToList('danger', lang('Kpcrudy.notAllowed'));
        }

        $trash = (bool) $this->request->getGet('trash') && $this->can('trash');
        $ids   = $this->request->getGet('ids');

        // Exportació de la selecció: es fa per GET perquè la descàrrega no
        // recarrega la pàgina i un POST invalidaria el token CSRF del formulari
        if (is_array($ids)) {
            $ids = $this->scopedIds($ids, $trash);

            if ($ids === []) {
                return $this->redirectToList('warning', lang('Kpcrudy.noSelection'));
            }
        }

        return $this->exportResponse($trash, is_array($ids) ? $ids : null);
    }

    protected function bulkOperation(): ResponseInterface
    {
        if (! $this->isPost() || ! $this->can('bulk')) {
            return $this->redirectToList('danger', lang('Kpcrudy.notAllowed'));
        }

        $trash  = (bool) $this->request->getPost('trash') && $this->can('trash');
        $action = (string) $this->request->getPost('action');
        $ids    = $this->scopedIds((array) $this->request->getPost('ids'), $trash);

        if (! array_key_exists($action, $this->bulkActionList($trash))) {
            return $this->redirectToList('danger', lang('Kpcrudy.invalidRequest'));
        }

        if ($ids === []) {
            return $this->redirectToList('warning', lang('Kpcrudy.noSelection'));
        }

        if ($action === 'export') {
            return $this->can('list') ? $this->exportResponse($trash, $ids) : $this->redirectToList('danger', lang('Kpcrudy.notAllowed'));
        }

        if (isset($this->bulkActions[$action])) {
            $result = ($this->bulkActions[$action]['callback'])($ids, $this);

            if ($result instanceof ResponseInterface) {
                return $result;
            }

            return $this->redirectToList('success', is_string($result) ? $result : lang('Kpcrudy.bulkDone', [count($ids)]));
        }

        $count = 0;

        foreach ($ids as $id) {
            $record = $this->findRecord($id, $trash);

            if ($record === null || ! $this->can($action, $record)) {
                continue;
            }

            $result = match ($action) {
                'delete'  => $this->deleteRecord($id, $record, false),
                'restore' => $this->restoreRecord($id, $record),
                'purge'   => $this->deleteRecord($id, $record, true),
            };

            if ($result === true) {
                $count++;
            }
        }

        return $this->redirectToList($count === count($ids) ? 'success' : 'warning', lang('Kpcrudy.bulkDone', [$count]));
    }

    // ------------------------------------------------------------------
    // Desar, esborrar i restaurar
    // ------------------------------------------------------------------

    /**
     * Valida i desa el formulari. Retorna una redirecció si tot ha anat bé
     * o [valors, errors, errorGeneral] per tornar a mostrar el formulari.
     *
     * @param list<string> $fields
     *
     * @return array{0: array<string, mixed>, 1: array<string, string>, 2: string|null}|ResponseInterface
     */
    protected function save(string $mode, ?string $id, ?array $record, array $fields): array|ResponseInterface
    {
        $input = [];
        $data  = [];
        $nn    = [];
        $files = [];
        $scope = $this->scopeValues();

        foreach ($fields as $name) {
            if (! $this->isEditable($name, $mode) || array_key_exists($name, $scope)) {
                continue;
            }

            $type    = $this->typeOf($name);
            $options = $this->types[$name]['options'] ?? [];

            switch ($type) {
                case 'relation_nn':
                    $posted = array_map('strval', (array) $this->request->getPost($name));
                    $valid  = array_map('strval', array_keys($this->optionsFor($name)));

                    $input[$name] = array_values(array_intersect(array_unique($posted), $valid));
                    $nn[$name]    = $input[$name];
                    break;

                case 'upload':
                case 'image':
                    $files[$name] = $this->request->getFile($name);
                    break;

                case 'boolean':
                    $input[$name] = $this->request->getPost($name) ? 1 : 0;
                    $data[$name]  = $input[$name];
                    break;

                case 'hidden':
                    if (array_key_exists('value', $options)) {
                        $data[$name] = $options['value'];
                        break;
                    }
                    // Sense valor fix: es tracta com un camp de text

                    // no break
                default:
                    $value = $this->request->getPost($name);
                    $value = is_array($value) ? implode(',', $value) : (string) ($value ?? '');

                    if ($type === 'datetime' && $value !== '') {
                        $value = str_replace('T', ' ', $value);
                        $value .= strlen($value) === 16 ? ':00' : '';
                    }

                    $input[$name] = $value;

                    if ($type === 'password') {
                        if ($value !== '') {
                            $data[$name] = ($options['hash'] ?? true) ? password_hash($value, PASSWORD_DEFAULT) : $value;
                        }
                        break;
                    }

                    $data[$name] = ($value === '' && $this->isNullable($name)) ? null : $value;
            }
        }

        // Els camps de l'abast (where) sempre tenen el valor de l'abast
        $data = array_merge($data, $scope);

        // Valors a mostrar si cal tornar a pintar el formulari
        $values = array_merge($record ?? [], $input);

        foreach (array_keys($files) as $name) {
            $values[$name] = $record[$name] ?? null;
        }

        // Validació
        $errors     = [];
        $validation = service('validation', null, false);
        $rules      = [];

        foreach (array_keys($input) as $name) {
            $fieldRules = $this->fieldRules($name, $mode, $id);

            if ($fieldRules !== []) {
                $rules[$name] = ['label' => $this->label($name), 'rules' => $fieldRules];
            }
        }

        if ($rules !== [] && ! $validation->setRules($rules)->run($input)) {
            $errors = $validation->getErrors();
        }

        foreach ($input as $name => $value) {
            $type = $this->typeOf($name);

            if (! isset($errors[$name]) && in_array($type, ['relation', 'enum', 'select'], true) && $value !== '' && $value !== null
                && ! array_key_exists((string) $value, $this->optionsFor($name))) {
                $errors[$name] = lang('Kpcrudy.invalidOption', [$this->label($name)]);
            }
        }

        foreach ($files as $name => $file) {
            $error = $this->checkUpload($name, $file, $mode, $record);

            if ($error !== null) {
                $errors[$name] = $error;
            }
        }

        if ($errors !== []) {
            return [$values, $errors, lang('Kpcrudy.validationErrors')];
        }

        // El registre, tal com quedaria, ha de continuar sent editable per l'usuari
        if ($mode === 'edit' && ! $this->can('edit', array_merge($record ?? [], $data))) {
            return [$values, [], lang('Kpcrudy.notAllowed')];
        }

        // Hooks previs
        $hook = $mode === 'add'
            ? $this->callHook('beforeInsert', $data)
            : $this->callHook('beforeUpdate', $data, (string) $id, $record);

        if ($hook === false || is_string($hook)) {
            return [$values, [], is_string($hook) ? $hook : lang('Kpcrudy.cancelled')];
        }

        if (is_array($hook)) {
            $data = array_merge($hook, $scope);
        }

        // Fitxers
        $moved    = [];
        $obsolete = [];

        try {
            foreach ($files as $name => $file) {
                $dir = $this->uploadDir($name);

                if ($file !== null && $file->isValid() && ! $file->hasMoved()) {
                    // El camp desa la ruta relativa al directori base: "subcarpeta/nom.ext"
                    $relative = ltrim($this->uploadSubdir($name, $data, $record) . '/' . $file->getRandomName(), '/');
                    $file->move(dirname($dir . '/' . $relative), basename($relative));
                    $moved[]     = $dir . DIRECTORY_SEPARATOR . $relative;
                    $data[$name] = $relative;
                } elseif ($this->request->getPost($name . '__remove')) {
                    $data[$name] = null;
                } else {
                    continue;
                }

                if (! empty($record[$name])) {
                    $obsolete[] = $this->uploadFilePath($name, (string) $record[$name]);
                }
            }

            // Retorna true si la validació del model falla (no s'ha escrit res)
            $failed = $this->transaction(function () use ($mode, &$id, $data, $nn): bool {
                if ($mode === 'add') {
                    $result = $this->model->insert($data, true);

                    if ($result === false) {
                        return true;
                    }

                    $id = (string) (is_int($result) && $result > 0 ? $result : ($data[$this->primaryKey] ?? $this->db->insertID()));
                } elseif ($data !== [] && ! $this->model->update($id, $this->withKeyForRules($data, (string) $id))) {
                    return true;
                }

                $this->syncNN((string) $id, $nn);
                $this->assertInScope((string) $id);

                return false;
            });

            if ($failed) {
                $this->deleteFiles($moved);

                return [$values, $this->model->errors(), lang('Kpcrudy.validationErrors')];
            }
        } catch (Throwable $e) {
            $this->deleteFiles($moved);

            return [$values, [], $this->errorMessage($e)];
        }

        $this->deleteFiles($obsolete);

        if ($mode === 'add') {
            $this->callHook('afterInsert', $id, $data);
        } else {
            $this->callHook('afterUpdate', $id, $data, $record);
        }

        session()->setFlashdata($this->stateKey() . '_flash', [
            'type' => 'success',
            'text' => lang($mode === 'add' ? 'Kpcrudy.created' : 'Kpcrudy.updated', [$this->subject]),
        ]);

        $next = match ($this->request->getPost('_kp_after')) {
            'continue' => $this->url('edit', $id),
            'new'      => $this->url('add'),
            default    => $this->backUrl(),
        };

        return redirect()->to($next);
    }

    /**
     * Si el model té una regla per a la clau primària (necessària per fer
     * servir {id} a les seves regles), hi afegeix l'identificador.
     *
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    protected function withKeyForRules(array $data, string $id): array
    {
        $rules = $this->model->getValidationRules();

        return isset($rules[$this->primaryKey]) ? $data + [$this->primaryKey => $id] : $data;
    }

    /**
     * Executa $work dins d'una transacció. Qualsevol error de base de dades
     * llança una excepció i desfà els canvis.
     */
    protected function transaction(Closure $work): mixed
    {
        $this->db->transException(true);
        $this->db->transBegin();

        try {
            $result = $work();

            if ($this->db->transStatus() === false) {
                throw new RuntimeException($this->db->error()['message'] ?? lang('Kpcrudy.saveError'));
            }

            $this->db->transCommit();

            return $result;
        } catch (Throwable $e) {
            while ($this->db->transDepth > 0) {
                $this->db->transRollback();
            }

            throw $e;
        } finally {
            $this->db->transException(false);
        }
    }

    /**
     * @param array<string, mixed> $record
     */
    protected function deleteRecord(string $id, array $record, bool $purge): string|true
    {
        $hard = $purge || ! $this->softDeletes;
        $hook = $this->callHook('beforeDelete', $id, $record, $hard);

        if ($hook === false || is_string($hook)) {
            return is_string($hook) ? $hook : lang('Kpcrudy.cancelled');
        }

        try {
            $this->transaction(function () use ($id, $hard): void {
                if ($hard) {
                    foreach ($this->relationsNN as $relation) {
                        $this->db->table($relation['junction'])->where($relation['thisKey'], $id)->delete();
                    }
                }

                if ($this->model->delete($id, $hard) === false) {
                    throw new RuntimeException(lang('Kpcrudy.saveError'));
                }
            });
        } catch (Throwable $e) {
            return $this->errorMessage($e);
        }

        if ($hard) {
            foreach (array_keys($this->types) as $name) {
                if ($this->isUpload($name) && ! empty($record[$name]) && ($this->types[$name]['options']['deleteFiles'] ?? true)) {
                    $this->deleteFiles([$this->uploadFilePath($name, (string) $record[$name])]);
                }
            }
        }

        $this->callHook('afterDelete', $id, $record, $hard);

        return true;
    }

    /**
     * @param array<string, mixed> $record
     */
    protected function restoreRecord(string $id, array $record): string|true
    {
        try {
            $this->db->table($this->table)
                ->where($this->primaryKey, $id)
                ->update([$this->deletedField => null]);
        } catch (Throwable $e) {
            return $this->errorMessage($e);
        }

        $this->callHook('afterRestore', $id, $record);

        return true;
    }

    /**
     * @param array<string, list<string>> $values
     */
    protected function syncNN(string $id, array $values): void
    {
        foreach ($values as $name => $ids) {
            $relation = $this->relationsNN[$name];
            $junction = $this->db->table($relation['junction']);
            $allowed  = array_map('strval', array_keys($this->optionsFor($name)));

            // Només es reescriuen els vincles que l'usuari podia triar: els que
            // queden fora de les opcions (p. ex. per un 'where' de la relació) es mantenen
            if ($allowed !== []) {
                $junction->where($relation['thisKey'], $id)->whereIn($relation['otherKey'], $allowed)->delete();
            }

            $ids = array_values(array_intersect($ids, $allowed));

            if ($ids !== []) {
                $junction->insertBatch(array_map(
                    static fn (string $other): array => [$relation['thisKey'] => $id, $relation['otherKey'] => $other],
                    $ids,
                ));
            }
        }
    }

    protected function callHook(string $name, mixed ...$args): mixed
    {
        return isset($this->hooks[$name]) ? ($this->hooks[$name])(...$args) : null;
    }

    protected function errorMessage(Throwable $e): string
    {
        log_message('error', 'Kpcrudy: ' . $e->getMessage());

        return ENVIRONMENT === 'production'
            ? lang('Kpcrudy.saveError')
            : lang('Kpcrudy.saveError') . ' ' . $e->getMessage();
    }

    // ------------------------------------------------------------------
    // Fitxers
    // ------------------------------------------------------------------

    protected function isUpload(string $name): bool
    {
        return in_array($this->typeOf($name), ['upload', 'image'], true);
    }

    /**
     * @param list<string> $fields
     */
    protected function hasUploads(array $fields): bool
    {
        foreach ($fields as $name) {
            if ($this->isUpload($name)) {
                return true;
            }
        }

        return false;
    }

    protected function uploadDir(string $name): string
    {
        $path = $this->types[$name]['options']['path'] ?? $this->config->uploadPath . DIRECTORY_SEPARATOR . $this->table;

        return rtrim($path, '/\\');
    }

    protected function uploadUrl(string $name, string $file): string
    {
        $url = $this->types[$name]['options']['url'] ?? base_url(trim($this->config->uploadUrl, '/') . '/' . $this->table);

        return rtrim($url, '/') . '/' . implode('/', array_map('rawurlencode', explode('/', $file)));
    }

    /**
     * Subcarpeta on es desa un fitxer nou, segons l'opció 'subdir' del camp:
     * un string (pot contenir {camp}) o fn (array $data, ?array $record): string.
     * Només s'admeten lletres, números, guions i barres; mai "..".
     *
     * @param array<string, mixed>      $data
     * @param array<string, mixed>|null $record
     */
    protected function uploadSubdir(string $name, array $data, ?array $record): string
    {
        $subdir = $this->types[$name]['options']['subdir'] ?? '';

        if ($subdir instanceof Closure) {
            $subdir = (string) $subdir($data, $record);
        } else {
            $values = array_merge($record ?? [], $data);
            $subdir = preg_replace_callback('/\{(\w+)\}/', static fn (array $m): string => (string) ($values[$m[1]] ?? ''), (string) $subdir);
        }

        $segments = array_filter(
            explode('/', str_replace('\\', '/', $subdir)),
            static fn (string $segment): bool => $segment !== '' && $segment !== '.' && $segment !== '..',
        );

        return implode('/', array_map(static fn (string $segment): string => preg_replace('/[^A-Za-z0-9_-]/', '-', $segment), $segments));
    }

    /**
     * @param array<string, mixed>|null $record
     */
    protected function checkUpload(string $name, ?UploadedFile $file, string $mode, ?array $record): ?string
    {
        $options  = $this->types[$name]['options'] ?? [];
        $required = in_array('required', $this->fieldRules($name, $mode, null), true);
        $label    = $this->label($name);

        if ($file === null || $file->getError() === UPLOAD_ERR_NO_FILE) {
            $current = $record[$name] ?? null;
            $removed = (bool) $this->request->getPost($name . '__remove');

            return ($required && ($current === null || $current === '' || $removed))
                ? lang('Kpcrudy.uploadRequired', [$label])
                : null;
        }

        if (! $file->isValid()) {
            return $file->getErrorString();
        }

        $isImage = $this->typeOf($name) === 'image';
        $allowed = $options['allowed'] ?? ($isImage ? $this->config->imageAllowed : $this->config->uploadAllowed);
        $allowed = array_map('strtolower', is_array($allowed) ? $allowed : explode(',', $allowed));
        $ext     = strtolower((string) ($file->guessExtension() ?: $file->getClientExtension()));

        if (! in_array($ext, $allowed, true) || ! in_array(strtolower($file->getClientExtension()), $allowed, true)) {
            return lang('Kpcrudy.uploadType', [$label, implode(', ', $allowed)]);
        }

        if ($isImage && ! str_starts_with((string) $file->getMimeType(), 'image/')) {
            return lang('Kpcrudy.uploadType', [$label, implode(', ', $allowed)]);
        }

        $maxSize = (int) ($options['maxSize'] ?? $this->config->uploadMaxSize);

        if (($file->getSize() / 1024) > $maxSize) {
            return lang('Kpcrudy.uploadSize', [$label, $maxSize]);
        }

        return null;
    }

    /**
     * Ruta absoluta d'un fitxer desat en un camp, o null si no existeix o
     * queda fora del directori del camp (p. ex. un valor amb "../").
     */
    protected function uploadFilePath(string $name, string $relative): ?string
    {
        $dir  = realpath($this->uploadDir($name));
        $path = realpath($this->uploadDir($name) . DIRECTORY_SEPARATOR . $relative);

        if ($dir === false || $path === false || ! str_starts_with($path, $dir . DIRECTORY_SEPARATOR)) {
            return null;
        }

        return $path;
    }

    /**
     * @param list<string|null> $files
     */
    protected function deleteFiles(array $files): void
    {
        foreach ($files as $file) {
            if ($file !== null && is_file($file)) {
                @unlink($file);
            }
        }
    }

    // ------------------------------------------------------------------
    // Consultes
    // ------------------------------------------------------------------

    /**
     * Consulta base amb l'abast del CRUD. $trash: false = actius,
     * true = a la paperera, null = tots.
     */
    protected function baseBuilder(?bool $trash): BaseBuilder
    {
        $table   = $this->table;
        $builder = $this->db->table($table);

        foreach ($this->where as $key => $value) {
            if (is_int($key)) {
                $builder->where($value);

                continue;
            }

            // Es qualifica amb la taula ("camp >=" => "taula.camp >=") per evitar ambigüitats amb els JOIN
            $key = trim($key);
            $builder->where(preg_match('/^(\w+)/', $key, $m) && isset($this->meta[$m[1]]) && ! str_contains($key, '.') ? "{$table}.{$key}" : $key, $value);
        }

        if ($this->softDeletes && $trash !== null) {
            if ($trash) {
                $builder->where("{$table}.{$this->deletedField} IS NOT NULL");
            } else {
                $builder->where("{$table}.{$this->deletedField}", null);
            }
        }

        return $builder;
    }

    /**
     * Condicions d'igualtat simples de where() (camp => valor): els registres
     * nous o editats les han de complir, així que es desen sempre amb aquest valor.
     *
     * @return array<string, mixed>
     */
    protected function scopeValues(): array
    {
        $values = [];

        foreach ($this->where as $key => $value) {
            if (is_int($key) || ! (is_scalar($value) || $value === null)
                || ! preg_match('/^(?:(\w+)\.)?(\w+)$/', trim($key), $m)
                || ($m[1] !== '' && $m[1] !== $this->table)
                || ! isset($this->meta[$m[2]])) {
                continue;
            }

            $values[$m[2]] = $value;
        }

        return $values;
    }

    /**
     * Comprova que un registre acabat de desar continua dins l'abast del
     * CRUD; si no, llança una excepció perquè es desfaci la transacció.
     */
    protected function assertInScope(string $id): void
    {
        if ($this->where === []) {
            return;
        }

        if ($this->baseBuilder(false)->where("{$this->table}.{$this->primaryKey}", $id)->countAllResults() === 0) {
            throw new RuntimeException(lang('Kpcrudy.notAllowed'));
        }
    }

    /**
     * Consulta amb els camps de les relacions 1-N afegits.
     */
    protected function selectBuilder(?bool $trash): BaseBuilder
    {
        $builder = $this->baseBuilder($trash);
        $builder->select("{$this->table}.*");

        foreach ($this->relations as $field => $relation) {
            if (! isset($this->meta[$field])) {
                continue;
            }

            $alias = 'kr_' . $field;
            $builder->join(
                "{$relation['table']} {$alias}",
                "{$alias}.{$relation['pk']} = {$this->table}.{$field}",
                'left',
            );

            foreach ($relation['fields'] as $relatedField) {
                $builder->select("{$alias}.{$relatedField} AS {$alias}__{$relatedField}");
            }
        }

        return $builder;
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function findRecord(string $id, bool $trash = false): ?array
    {
        if (! $this->isValidId($id)) {
            return null;
        }

        return $this->baseBuilder($trash)
            ->where("{$this->table}.{$this->primaryKey}", $id)
            ->get()
            ->getRowArray();
    }

    /**
     * Registre amb les relacions resoltes, per mostrar-lo.
     *
     * @return array<string, mixed>|null
     */
    protected function findDisplayRecord(string $id): ?array
    {
        if (! $this->isValidId($id)) {
            return null;
        }

        $row = $this->selectBuilder(false)
            ->where("{$this->table}.{$this->primaryKey}", $id)
            ->get()
            ->getRowArray();

        return $row === null ? null : $this->attachNN([$row])[0];
    }

    /**
     * Filtra els identificadors rebuts i deixa només els que existeixen
     * dins l'abast del CRUD.
     *
     * @param array<mixed> $ids
     *
     * @return list<string>
     */
    protected function scopedIds(array $ids, bool $trash): array
    {
        // Els identificadors arriben del formulari tal com es mostren (emmascarats o no)
        $ids = array_map(fn ($id) => is_scalar($id) ? ($this->decodeId((string) $id) ?? '') : '', $ids);

        $ids = array_values(array_unique(array_filter(
            array_map('strval', array_filter($ids, 'is_scalar')),
            fn (string $id): bool => $id !== '' && $this->isValidId($id),
        )));

        if ($ids === []) {
            return [];
        }

        $rows = $this->baseBuilder($trash)
            ->select("{$this->table}.{$this->primaryKey} AS kp_id")
            ->whereIn("{$this->table}.{$this->primaryKey}", $ids)
            ->get()
            ->getResultArray();

        return array_map(static fn (array $row): string => (string) $row['kp_id'], $rows);
    }

    /**
     * @param array<string, mixed> $state
     */
    protected function applySearchAndFilters(BaseBuilder $builder, array $state): void
    {
        $table = $this->table;

        if ($state['q'] !== '' && $this->can('search')) {
            $targets = [];

            foreach ($this->searchFieldList() as $name) {
                if (isset($this->relations[$name])) {
                    foreach ($this->relations[$name]['fields'] as $relatedField) {
                        $targets[] = "kr_{$name}.{$relatedField}";
                    }
                } elseif (isset($this->meta[$name])) {
                    $targets[] = "{$table}.{$name}";
                }
            }

            if ($targets !== []) {
                $builder->groupStart();

                foreach ($targets as $target) {
                    $builder->orLike($target, $this->escapeLike($state['q']), 'both', true);
                }

                $builder->groupEnd();
            }
        }

        if (! $this->can('filters')) {
            return;
        }

        foreach ($state['f'] as $name => $value) {
            $kind   = $this->filterKind($name);
            $column = "{$table}.{$name}";

            if ($kind === 'range') {
                if (! is_array($value)) {
                    continue;
                }

                $from = is_scalar($value['from'] ?? null) ? trim((string) $value['from']) : '';
                $to   = is_scalar($value['to'] ?? null) ? trim((string) $value['to']) : '';

                // Valors que no són números o dates fallarien en alguns motors de base de dades
                $type  = $this->typeOf($name);
                $valid = static fn (string $v): bool => in_array($type, ['number', 'decimal'], true) ? is_numeric($v) : (bool) preg_match('/^\d{4}-\d{2}-\d{2}( \d{2}:\d{2}(:\d{2})?)?$/', $v);
                $from  = $valid($from) ? $from : '';
                $to    = $valid($to) ? $to : '';

                if ($from !== '') {
                    $builder->where("{$column} >=", $from);
                }

                if ($to !== '') {
                    $builder->where("{$column} <=", $this->typeOf($name) === 'datetime' && strlen($to) === 10 ? $to . ' 23:59:59' : $to);
                }

                continue;
            }

            if (is_array($value) || $value === null || $value === '') {
                continue;
            }

            // Els desplegables només admeten les seves opcions
            if ($kind === 'select' && ! array_key_exists((string) $value, $this->optionsFor($name))) {
                continue;
            }

            if (isset($this->relationsNN[$name])) {
                $relation = $this->relationsNN[$name];
                $builder->whereIn(
                    "{$table}.{$this->primaryKey}",
                    static fn (BaseBuilder $sub): BaseBuilder => $sub->select($relation['thisKey'])
                        ->from($relation['junction'])
                        ->where($relation['otherKey'], $value),
                );
            } elseif ($kind === 'select') {
                $builder->where($column, $value);
            } else {
                $builder->like($column, $this->escapeLike((string) $value), 'both', true);
            }
        }
    }

    /**
     * @param array<string, mixed> $state
     */
    protected function applyOrder(BaseBuilder $builder, array $state): void
    {
        [$sort, $dir] = $state['sort'] !== ''
            ? [$state['sort'], $state['dir']]
            : ($this->defaultOrder ?? [$this->primaryKey, 'asc']);

        if (isset($this->relations[$sort])) {
            $builder->orderBy("kr_{$sort}.{$this->relations[$sort]['fields'][0]}", $dir);
        } elseif (isset($this->meta[$sort])) {
            $builder->orderBy("{$this->table}.{$sort}", $dir);
        }

        if ($sort !== $this->primaryKey) {
            $builder->orderBy("{$this->table}.{$this->primaryKey}", 'asc');
        }
    }

    /**
     * Afegeix a cada fila els valors de les relacions N-N a $row['__nn'][nom].
     *
     * @param list<array<string, mixed>> $rows
     *
     * @return list<array<string, mixed>>
     */
    protected function attachNN(array $rows): array
    {
        if ($rows === [] || $this->relationsNN === []) {
            return $rows;
        }

        $ids = array_map(fn (array $row): string => (string) $row[$this->primaryKey], $rows);

        foreach ($this->relationsNN as $name => $relation) {
            $builder = $this->db->table("{$relation['junction']} j")
                ->select("j.{$relation['thisKey']} AS kp_owner")
                ->join("{$relation['table']} o", "o.{$relation['pk']} = j.{$relation['otherKey']}")
                ->whereIn("j.{$relation['thisKey']}", $ids)
                ->orderBy("o.{$relation['fields'][0]}");

            foreach ($relation['fields'] as $field) {
                $builder->select("o.{$field} AS kp_f_{$field}");
            }

            $map = [];

            foreach ($builder->get()->getResultArray() as $result) {
                $values = [];

                foreach ($relation['fields'] as $field) {
                    $values[$field] = $result['kp_f_' . $field];
                }

                $map[(string) $result['kp_owner']][] = $this->formatTemplate($relation['display'], $values);
            }

            foreach ($rows as $i => $row) {
                $rows[$i]['__nn'][$name] = $map[(string) $row[$this->primaryKey]] ?? [];
            }
        }

        return $rows;
    }

    /**
     * @return list<string>
     */
    protected function selectedNN(string $id, string $name): array
    {
        $relation = $this->relationsNN[$name];

        $rows = $this->db->table($relation['junction'])
            ->select($relation['otherKey'])
            ->where($relation['thisKey'], $id)
            ->get()
            ->getResultArray();

        return array_map(static fn (array $row): string => (string) $row[$relation['otherKey']], $rows);
    }

    /**
     * Opcions d'un camp de tipus desplegable: valor => etiqueta.
     *
     * @return array<string, string>
     */
    protected function optionsFor(string $name): array
    {
        if (isset($this->optionsCache[$name])) {
            return $this->optionsCache[$name];
        }

        $type     = $this->typeOf($name);
        $relation = $this->relations[$name] ?? $this->relationsNN[$name] ?? null;
        $options  = [];

        if ($relation !== null && in_array($type, ['relation', 'relation_nn'], true)) {
            $builder = $this->db->table($relation['table'])->select($relation['pk'] . ' AS kp_value');

            foreach ($relation['fields'] as $field) {
                $builder->select($field);
            }

            if ($relation['where'] !== []) {
                $builder->where($relation['where']);
            }

            $builder->orderBy($relation['order'] ?? $relation['fields'][0]);

            foreach ($builder->get()->getResultArray() as $row) {
                $options[(string) $row['kp_value']] = $this->formatTemplate($relation['display'], $row);
            }
        } elseif ($type === 'boolean') {
            $options = ['1' => lang('Kpcrudy.yes'), '0' => lang('Kpcrudy.no')];
        } elseif (in_array($type, ['enum', 'select'], true)) {
            $defined = $this->types[$name]['options']['options'] ?? null;

            if ($defined === null) {
                $values  = $this->meta[$name]['enum'] ?? [];
                $defined = array_combine($values, $values);
            }

            foreach ($defined as $value => $label) {
                $options[(string) $value] = (string) $label;
            }
        }

        return $this->optionsCache[$name] = $options;
    }

    // ------------------------------------------------------------------
    // Camps: tipus, etiquetes i regles
    // ------------------------------------------------------------------

    protected function typeOf(string $name): string
    {
        if (isset($this->types[$name])) {
            return $this->types[$name]['type'];
        }

        if (isset($this->relations[$name])) {
            return 'relation';
        }

        if (isset($this->relationsNN[$name])) {
            return 'relation_nn';
        }

        $meta = $this->meta[$name] ?? null;

        if ($meta === null) {
            return 'virtual';
        }

        if ($meta['enum'] !== null) {
            return 'enum';
        }

        $type  = $meta['type'];
        $lower = strtolower($name);

        if (in_array($type, ['bool', 'boolean'], true) || ($type === 'tinyint' && $meta['max_length'] === 1)) {
            return 'boolean';
        }

        if (str_contains($type, 'int')) {
            return 'number';
        }

        if (preg_match('/decimal|numeric|float|double|real|money/', $type)) {
            return 'decimal';
        }

        if ($type === 'date') {
            return 'date';
        }

        if (str_contains($type, 'datetime') || str_contains($type, 'timestamp')) {
            return 'datetime';
        }

        if (str_starts_with($type, 'time')) {
            return 'time';
        }

        if (str_contains($lower, 'email')) {
            return 'email';
        }

        if (in_array($lower, ['password', 'contrasenya', 'contrasena'], true)) {
            return 'password';
        }

        if (in_array($lower, ['url', 'web', 'website'], true)) {
            return 'url';
        }

        if (preg_match('/text|clob|json/', $type)) {
            return 'textarea';
        }

        return 'text';
    }

    protected function label(string $name): string
    {
        if (isset($this->labels[$name])) {
            return $this->labels[$name];
        }

        if (isset($this->relations[$name]) && str_ends_with($name, '_id')) {
            $name = substr($name, 0, -3);
        }

        return ucfirst(str_replace('_', ' ', $name));
    }

    protected function isNullable(string $name): bool
    {
        return (bool) ($this->meta[$name]['nullable'] ?? true);
    }

    protected function isEditable(string $name, string $mode): bool
    {
        $type = $this->typeOf($name);

        if ($type === 'readonly' || ($mode === 'edit' && in_array($name, $this->readonly, true))) {
            return false;
        }

        // Un camp virtual només té sentit si té un input personalitzat que el desi
        return $type !== 'virtual';
    }

    /**
     * Regles de validació finals d'un camp.
     *
     * @return list<string>
     */
    protected function fieldRules(string $name, string $mode, ?string $id): array
    {
        $type  = $this->typeOf($name);
        $rules = $this->rules[$name] ?? [];
        $meta  = $this->meta[$name] ?? null;

        if (in_array($name, $this->required, true)) {
            $rules[] = 'required';
        }

        if ($this->config->inferRules && $meta !== null && ! isset($this->rules[$name])) {
            if (! $meta['nullable'] && $meta['default'] === null && ! $meta['primary']
                && ! in_array($type, ['boolean', 'upload', 'image', 'hidden', 'relation_nn'], true)) {
                $rules[] = 'required';
            }

            if ($meta['max_length'] && in_array($type, ['text', 'email', 'url'], true)) {
                $rules[] = "max_length[{$meta['max_length']}]";
            }

            $typeRule = match ($type) {
                'email'    => 'valid_email',
                'url'      => 'valid_url_strict[http,https]',
                'number'   => 'integer',
                'decimal'  => 'decimal',
                'date'     => 'valid_date[Y-m-d]',
                'datetime' => 'valid_date[Y-m-d H:i:s]',
                'time'     => 'regex_match[/^[0-2]\d:[0-5]\d(:[0-5]\d)?$/]',
                'color'    => 'regex_match[/^#[0-9a-fA-F]{6}$/]',
                default    => null,
            };

            if ($typeRule !== null) {
                $rules[] = $typeRule;
            }
        }

        if ($type === 'password' && $mode === 'edit') {
            $rules = array_filter($rules, static fn (string $rule): bool => $rule !== 'required');
        }

        $rules = array_values(array_unique(array_map(
            static fn (string $rule): string => str_replace('{id}', (string) $id, trim($rule)),
            array_filter($rules, static fn (string $rule): bool => trim($rule) !== ''),
        )));

        if ($rules !== [] && ! in_array('required', $rules, true) && ! in_array('permit_empty', $rules, true)) {
            array_unshift($rules, 'permit_empty');
        }

        return $rules;
    }

    // ------------------------------------------------------------------
    // Llistes de camps
    // ------------------------------------------------------------------

    /**
     * @return list<string>
     */
    protected function columnList(): array
    {
        return $this->columns ?? array_values(array_filter(
            array_keys($this->meta),
            // Amb maskIds() l'id real no es mostra per defecte (es veuria al costat del codi de la URL)
            fn (string $name): bool => ! $this->isDeletedField($name) && ! $this->isSensitive($name) && ! ($this->maskIds && $name === $this->primaryKey),
        ));
    }

    /**
     * @return list<string>
     */
    protected function defaultFormFields(): array
    {
        $exclude = [$this->primaryKey, $this->createdField, $this->updatedField, $this->softDeletes ? $this->deletedField : null];
        $fields  = array_filter(
            array_keys($this->meta),
            // Les contrasenyes sí que es poden canviar (es desen amb hash); la resta de camps secrets, no
            fn (string $name): bool => ! in_array($name, $exclude, true) && (! $this->isSensitive($name) || $this->typeOf($name) === 'password'),
        );

        return array_values(array_merge($fields, array_keys($this->relationsNN)));
    }

    /**
     * @return list<string>
     */
    protected function addFieldList(): array
    {
        return $this->addFields ?? $this->defaultFormFields();
    }

    /**
     * @return list<string>
     */
    protected function editFieldList(): array
    {
        return $this->editFields ?? $this->defaultFormFields();
    }

    /**
     * @return list<string>
     */
    protected function showFieldList(): array
    {
        if ($this->showFields !== null) {
            return $this->showFields;
        }

        $fields = array_filter(
            array_keys($this->meta),
            // Amb maskIds() l'id real no es mostra per defecte (es veuria al costat del codi de la URL)
            fn (string $name): bool => ! $this->isDeletedField($name) && ! $this->isSensitive($name) && ! ($this->maskIds && $name === $this->primaryKey),
        );

        return array_values(array_merge($fields, array_keys($this->relationsNN)));
    }

    /**
     * @return list<string>
     */
    protected function searchFieldList(): array
    {
        return $this->searchFields ?? array_values(array_filter(
            $this->columnList(),
            // Una columna amb callbackColumn pot amagar el valor real (p. ex. ****): no es cerca si no es demana
            fn (string $name): bool => (isset($this->meta[$name]) || isset($this->relations[$name]))
                && ! isset($this->columnCallbacks[$name]) && ! $this->isSensitive($name)
                && ! in_array($this->typeOf($name), ['boolean', 'upload', 'image', 'password', 'date', 'datetime', 'time', 'relation_nn'], true),
        ));
    }

    /**
     * @return list<string>
     */
    protected function filterFieldList(): array
    {
        return $this->filterFields ?? array_values(array_filter(
            $this->columnList(),
            fn (string $name): bool => in_array($this->typeOf($name), self::SELECT_TYPES, true),
        ));
    }

    /**
     * @return list<string>
     */
    protected function sortableColumns(): array
    {
        return array_values(array_filter(
            $this->columnList(),
            fn (string $name): bool => (isset($this->meta[$name]) || isset($this->relations[$name]))
                && ! in_array($name, $this->unsortable, true),
        ));
    }

    /**
     * Camp secret (contrasenyes, tokens...) segons Config\Kpcrudy::$sensitiveFields.
     */
    protected function isSensitive(string $name): bool
    {
        if ($this->typeOf($name) === 'password') {
            return true;
        }

        $lower = strtolower($name);

        if (in_array(str_replace('_', '', $lower), $this->config->sensitiveFields, true)) {
            return true;
        }

        return array_intersect(explode('_', $lower), $this->config->sensitiveFields) !== [];
    }

    protected function isDeletedField(string $name): bool
    {
        return $this->softDeletes && $name === $this->deletedField;
    }

    protected function filterKind(string $name): string
    {
        $type = $this->typeOf($name);

        if (in_array($type, self::SELECT_TYPES, true)) {
            return 'select';
        }

        return in_array($type, ['date', 'datetime', 'number', 'decimal'], true) ? 'range' : 'text';
    }

    // ------------------------------------------------------------------
    // Presentació de valors
    // ------------------------------------------------------------------

    /**
     * Valor d'un camp preparat per a un context: 'list' i 'show' (HTML) o 'export' (text).
     *
     * @param array<string, mixed> $row
     */
    protected function renderValue(string $name, array $row, string $context): string
    {
        $value = $row[$name] ?? null;

        if ($context === 'export' && isset($this->exportCallbacks[$name])) {
            return (string) ($this->exportCallbacks[$name])($value, $row);
        }

        if (isset($this->columnCallbacks[$name])) {
            $html = (string) ($this->columnCallbacks[$name])($value, $row);

            return $context === 'export' ? trim(html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8')) : $html;
        }

        $text = $this->displayText($name, $row);

        if ($context === 'export' || $text === '') {
            return $text;
        }

        switch ($this->typeOf($name)) {
            case 'boolean':
                // kp-badge, kp-yes i kp-no permeten donar-hi estil des de qualsevol tema
                return $value
                    ? '<span class="kp-badge kp-yes badge text-bg-success">' . esc($text) . '</span>'
                    : '<span class="kp-badge kp-no badge text-bg-secondary">' . esc($text) . '</span>';

            case 'image':
                $url = esc($this->uploadUrl($name, (string) $value), 'attr');

                return '<a href="' . $url . '" target="_blank"><img src="' . $url . '" alt="" class="img-thumbnail kp-thumb"></a>';

            case 'upload':
                return '<a href="' . esc($this->uploadUrl($name, (string) $value), 'attr') . '" target="_blank"><i class="bi bi-paperclip"></i> ' . esc(basename($text)) . '</a>';

            case 'url':
                // Només es fa enllaç d'adreces http(s): mai javascript:, data:...
                if (! in_array($this->urlScheme($text), ['http', 'https'], true)) {
                    return esc($this->truncate($text, $context));
                }

                return '<a href="' . esc($text, 'attr') . '" target="_blank" rel="noopener noreferrer">' . esc($this->truncate($text, $context)) . '</a>';

            case 'email':
                return '<a href="mailto:' . esc($text, 'attr') . '">' . esc($text) . '</a>';

            case 'color':
                // El valor va dins d'un style: només s'hi accepta un color hexadecimal
                if (! preg_match('/^#[0-9a-f]{3,8}$/i', $text)) {
                    return esc($text);
                }

                return '<span class="kp-swatch" style="background:' . esc($text, 'attr') . '"></span> ' . esc($text);

            case 'textarea':
                return $context === 'show' ? nl2br(esc($text)) : esc($this->truncate($text, $context));

            default:
                return esc($this->truncate($text, $context));
        }
    }

    /**
     * Valor d'un camp com a text pla.
     *
     * @param array<string, mixed> $row
     */
    protected function displayText(string $name, array $row): string
    {
        $value = $row[$name] ?? null;

        switch ($this->typeOf($name)) {
            case 'relation':
                if ($value === null || $value === '') {
                    return '';
                }

                $relation = $this->relations[$name];
                $values   = [];

                foreach ($relation['fields'] as $field) {
                    $values[$field] = $row["kr_{$name}__{$field}"] ?? '';
                }

                $text = $this->formatTemplate($relation['display'], $values);

                return $text !== '' ? $text : (string) $value;

            case 'relation_nn':
                return implode(', ', $row['__nn'][$name] ?? []);

            case 'boolean':
                return $value === null || $value === '' ? '' : lang($value ? 'Kpcrudy.yes' : 'Kpcrudy.no');

            case 'enum':
            case 'select':
                return $value === null ? '' : ($this->optionsFor($name)[(string) $value] ?? (string) $value);

            case 'date':
                return $this->formatDate($value, $this->config->dateFormat);

            case 'datetime':
                return $this->formatDate($value, $this->config->datetimeFormat);

            case 'password':
                return $value ? '••••••' : '';

            default:
                return (string) ($value ?? '');
        }
    }

    protected function formatDate(mixed $value, string $format): string
    {
        if ($value === null || $value === '' || str_starts_with((string) $value, '0000')) {
            return '';
        }

        $time = strtotime((string) $value);

        return $time === false ? (string) $value : date($format, $time);
    }

    protected function truncate(string $text, string $context): string
    {
        $max = $this->config->listTruncate;

        if ($context !== 'list' || $max <= 0 || mb_strlen($text) <= $max) {
            return $text;
        }

        return rtrim(mb_substr($text, 0, $max)) . '…';
    }

    /**
     * @return list<string>
     */
    protected function templateFields(string $display): array
    {
        if (! str_contains($display, '{')) {
            return [$display];
        }

        preg_match_all('/\{(\w+)\}/', $display, $matches);

        return array_values(array_unique($matches[1]));
    }

    /**
     * @param array<string, mixed> $values
     */
    protected function formatTemplate(string $display, array $values): string
    {
        if (! str_contains($display, '{')) {
            return (string) ($values[$display] ?? '');
        }

        return trim(preg_replace_callback('/\{(\w+)\}/', static fn (array $m): string => (string) ($values[$m[1]] ?? ''), $display));
    }

    // ------------------------------------------------------------------
    // Dades per a les plantilles
    // ------------------------------------------------------------------

    /**
     * @param list<string> $fields
     *
     * @return array<string, mixed>
     */
    protected function defaultValues(array $fields): array
    {
        $values = [];

        foreach ($fields as $name) {
            if (array_key_exists($name, $this->defaults)) {
                $values[$name] = $this->defaults[$name];
            } elseif (isset($this->relationsNN[$name])) {
                $values[$name] = [];
            } elseif (isset($this->meta[$name]['default']) && ! preg_match('/^(current_timestamp|now)/i', (string) $this->meta[$name]['default'])) {
                $values[$name] = $this->meta[$name]['default'];
            }
        }

        return $values;
    }

    /**
     * @param array<string, mixed> $record
     * @param list<string>         $fields
     *
     * @return array<string, mixed>
     */
    protected function recordValues(array $record, array $fields): array
    {
        $values = $record;
        $id     = (string) $record[$this->primaryKey];

        foreach ($fields as $name) {
            if (isset($this->relationsNN[$name])) {
                $values[$name] = $this->selectedNN($id, $name);
            }
        }

        return $values;
    }

    /**
     * @param list<string>          $fields
     * @param array<string, mixed>  $values
     * @param array<string, string> $errors
     * @param array<string, mixed>|null $record
     *
     * @return list<array<string, mixed>>
     */
    protected function formFields(array $fields, array $values, array $errors, string $mode, ?array $record): array
    {
        $items   = [];
        $display = null;
        $scope   = $this->scopeValues();

        foreach ($fields as $name) {
            $type    = $this->typeOf($name);
            $options = $this->types[$name]['options'] ?? [];

            if (($type === 'hidden' && array_key_exists('value', $options)) || ($type === 'virtual' && ! isset($this->fieldCallbacks[$name]))
                || array_key_exists($name, $scope)) {
                continue;
            }

            $value    = $values[$name] ?? null;
            $readonly = $type === 'readonly' || ($mode === 'edit' && in_array($name, $this->readonly, true));
            $item     = [
                'name'      => $name,
                'label'     => $this->label($name),
                'type'      => $readonly ? 'readonly' : $type,
                'value'     => $this->formValue($type, $value),
                'options'   => in_array($type, self::SELECT_TYPES, true) ? $this->optionsFor($name) : [],
                'required'  => in_array('required', $this->fieldRules($name, $mode, null), true),
                'error'     => $errors[$name] ?? null,
                'help'      => $this->help[$name] ?? ($type === 'password' && $mode === 'edit' ? lang('Kpcrudy.passwordKeep') : null),
                'attrs'     => $options['attrs'] ?? [],
                'maxlength' => in_array($type, ['text', 'email', 'url'], true) ? ($this->meta[$name]['max_length'] ?? null) : null,
                'display'   => null,
                'file'      => null,
                'html'      => null,
            ];

            if ($readonly) {
                if ($record !== null) {
                    $display ??= $this->findDisplayRecord((string) $record[$this->primaryKey]) ?? $record;
                }

                $item['display'] = $this->renderValue($name, $display ?? $values, 'show');
            }

            if (in_array($type, ['upload', 'image'], true) && is_string($value) && $value !== '') {
                $item['file'] = [
                    'name'    => basename($value),
                    'url'     => $this->uploadUrl($name, $value),
                    'isImage' => $type === 'image',
                ];
            }

            if (isset($this->fieldCallbacks[$name])) {
                $item['html'] = (string) ($this->fieldCallbacks[$name])($value, $record, $item);
            }

            $items[] = $item;
        }

        return $items;
    }

    protected function formValue(string $type, mixed $value): mixed
    {
        if ($value === null || is_array($value)) {
            return $value;
        }

        $value = (string) $value;

        return match ($type) {
            'datetime' => $value === '' ? '' : str_replace(' ', 'T', substr($value, 0, 16)),
            'date'     => substr($value, 0, 10),
            'password' => '',
            default    => $value,
        };
    }

    /**
     * @param array<string, mixed> $state
     *
     * @return list<array<string, mixed>>
     */
    protected function filterData(array $state): array
    {
        $filters = [];

        foreach ($this->filterFieldList() as $name) {
            $kind  = $this->filterKind($name);
            $type  = $this->typeOf($name);
            $value = $state['f'][$name] ?? ($kind === 'range' ? [] : '');

            $filters[] = [
                'name'    => $name,
                'label'   => $this->label($name),
                'kind'    => $kind,
                'input'   => match ($type) {
                    'date', 'datetime' => 'date',
                    'number', 'decimal' => 'number',
                    default => 'text',
                },
                'options' => $kind === 'select' ? $this->optionsFor($name) : [],
                'value'   => $kind === 'range'
                    ? ['from' => is_scalar($value['from'] ?? null) ? (string) $value['from'] : '', 'to' => is_scalar($value['to'] ?? null) ? (string) $value['to'] : '']
                    : (is_array($value) ? '' : (string) $value),
            ];
        }

        return $filters;
    }

    /**
     * Accions d'un registre (llistat o fitxa).
     *
     * @param array<string, mixed> $row
     *
     * @return list<array<string, mixed>>
     */
    protected function rowActions(array $row, bool $trash, string $context): array
    {
        $id      = (string) $row[$this->primaryKey];
        $actions = [];

        if ($trash) {
            if ($this->can('restore', $row)) {
                $actions[] = $this->builtinAction('restore', $this->url('restore', $id), 'bi bi-arrow-counterclockwise', 'btn-outline-success', 'post');
            }

            if ($this->can('purge', $row)) {
                $actions[] = $this->builtinAction('purge', $this->url('purge', $id), 'bi bi-x-octagon', 'btn-outline-danger', 'post', lang('Kpcrudy.confirmPurge'));
            }

            return $actions;
        }

        if ($context === 'list' && $this->can('show', $row)) {
            $actions[] = $this->builtinAction('show', $this->url('show', $id), 'bi bi-eye', 'btn-outline-primary');
        }

        if ($this->can('edit', $row)) {
            $actions[] = $this->builtinAction('edit', $this->url('edit', $id), 'bi bi-pencil', 'btn-outline-primary');
        }

        foreach ($this->actions as $action) {
            if ($action['visible'] instanceof Closure && ! ($action['visible'])($row)) {
                continue;
            }

            $action['url'] = $this->actionUrl($action['url'], $row);
            $actions[]     = $action;
        }

        if ($this->can('delete', $row)) {
            $actions[] = $this->builtinAction(
                'delete',
                $this->url('delete', $id),
                'bi bi-trash',
                'btn-outline-danger',
                'post',
                lang($this->softDeletes ? 'Kpcrudy.confirmTrash' : 'Kpcrudy.confirmDelete'),
            );
        }

        return $actions;
    }

    /**
     * @return array<string, mixed>
     */
    protected function builtinAction(string $key, string $url, string $icon, string $class, string $method = 'get', ?string $confirm = null): array
    {
        return [
            'key'     => $key,
            'label'   => lang('Kpcrudy.' . $key),
            'url'     => $url,
            'icon'    => $icon,
            'class'   => $class,
            'method'  => $method,
            'confirm' => $confirm,
            'target'  => null,
        ];
    }

    /**
     * @param array<string, mixed> $row
     */
    protected function actionUrl(Closure|string $url, array $row): string
    {
        if ($url instanceof Closure) {
            return $this->safeUrl((string) $url($row));
        }

        $url = preg_replace_callback('/\{(\w+)\}/', function (array $m) use ($row): string {
            $value = match ($m[1]) {
                'id'    => $row[$this->primaryKey],
                'kp_id' => (string) $row[$this->primaryKey] === '' ? '' : $this->encodeId((string) $row[$this->primaryKey]),
                default => $row[$m[1]] ?? '',
            };

            return rawurlencode((string) $value);
        }, $url);

        return preg_match('#^([a-z][a-z0-9+.-]*:|//|/|\#)#i', $url) ? $url : site_url($url);
    }

    /**
     * Esquema d'una URL ("http", "javascript"...) en minúscules, o '' si és relativa.
     * Els navegadors ignoren els espais i caràcters de control: també s'ignoren aquí.
     */
    protected function urlScheme(string $url): string
    {
        $url = preg_replace('/[\x00-\x20\x7f]+/', '', $url);

        return preg_match('/^([a-z][a-z0-9+.-]*):/i', $url, $m) ? strtolower($m[1]) : '';
    }

    /**
     * Deixa passar URL relatives, http(s), mailto i tel; la resta (javascript:...) es canvia per "#".
     */
    protected function safeUrl(string $url): string
    {
        return in_array($this->urlScheme($url), ['', 'http', 'https', 'mailto', 'tel'], true) ? $url : '#';
    }

    /**
     * @param array<string, mixed> $state
     *
     * @return list<array<string, mixed>>
     */
    protected function headerActionList(bool $trash, array $state, int $trashCount): array
    {
        $actions = [];

        if ($trash) {
            $actions[] = $this->builtinAction('backToList', $this->listUrl(false), 'bi bi-arrow-left', 'btn-outline-secondary');

            if ($this->can('purge')) {
                $actions[] = $this->builtinAction('emptyTrash', $this->url('emptytrash'), 'bi bi-trash3', 'btn-outline-danger', 'post', lang('Kpcrudy.confirmEmptyTrash'));
            }
        } else {
            if ($this->can('add')) {
                $actions[] = $this->builtinAction('add', $this->url('add'), 'bi bi-plus-lg', 'btn-primary');
            }

            foreach ($this->headerActions as $action) {
                if ($action['visible'] instanceof Closure && ! ($action['visible'])(null)) {
                    continue;
                }

                $action['url'] = $this->actionUrl($action['url'], [$this->primaryKey => '']);
                $actions[]     = $action;
            }

            if ($this->can('trash')) {
                $action          = $this->builtinAction('trash', $this->url('trash'), 'bi bi-trash', 'btn-outline-secondary');
                $action['badge'] = $trashCount;
                $actions[]       = $action;
            }
        }

        if ($this->can('export')) {
            $query = $this->stateQuery($state, ['page' => null]);

            if ($trash) {
                $query['trash'] = 1;
            }

            // L'exportació de la paperera es signa amb un abast propi: un enllaç d'exportació
            // del llistat no serveix per exportar la paperera
            $export    = $trash ? $this->signIfRequired($this->rawUrl('export'), 'export', 'crud-trash') : $this->url('export');
            $actions[] = $this->builtinAction('export', $this->withQuery($export, $query), 'bi bi-filetype-csv', 'btn-outline-success');
        }

        if (! $trash && $this->importAllowed()) {
            $actions[] = $this->builtinAction('import', $this->url('import'), 'bi bi-upload', 'btn-outline-success');
        }

        return $actions;
    }

    /**
     * Accions massives disponibles: clau => [label, confirm].
     *
     * @return array<string, array{label: string, confirm: string|null, url: string|null}>
     */
    protected function bulkActionList(bool $trash): array
    {
        if (! $this->can('bulk')) {
            return [];
        }

        $list = [];

        if ($trash) {
            if ($this->can('restore')) {
                $list['restore'] = ['label' => lang('Kpcrudy.restore'), 'confirm' => null, 'url' => null];
            }

            if ($this->can('purge')) {
                $list['purge'] = ['label' => lang('Kpcrudy.purge'), 'confirm' => lang('Kpcrudy.confirmBulk'), 'url' => null];
            }
        } elseif ($this->can('delete')) {
            $list['delete'] = ['label' => lang('Kpcrudy.delete'), 'confirm' => lang('Kpcrudy.confirmBulk'), 'url' => null];
        }

        if ($this->can('export')) {
            $list['export'] = [
                'label'   => lang('Kpcrudy.exportSelected'),
                'confirm' => null,
                'url'     => $trash ? $this->signIfRequired($this->rawUrl('export'), 'export', 'crud-trash') : $this->url('export'),
            ];
        }

        foreach ($this->bulkActions as $key => $action) {
            if ($action['trash'] === $trash) {
                $list[$key] = ['label' => $action['label'], 'confirm' => $action['confirm'], 'url' => null];
            }
        }

        return $list;
    }

    /**
     * @param Closure(int): string $url
     *
     * @return list<array{label: string, url: string|null, active: bool}>
     */
    protected function paginationLinks(int $page, int $pages, Closure $url): array
    {
        if ($pages <= 1) {
            return [];
        }

        $link  = static fn (string $label, ?string $href, bool $active = false): array => ['label' => $label, 'url' => $href, 'active' => $active];
        $start = max(1, $page - 2);
        $end   = min($pages, $page + 2);
        $links = [$link('&laquo;', $page > 1 ? $url($page - 1) : null)];

        if ($start > 1) {
            $links[] = $link('1', $url(1));

            if ($start > 2) {
                $links[] = $link('&hellip;', null);
            }
        }

        for ($i = $start; $i <= $end; $i++) {
            $links[] = $link((string) $i, $url($i), $i === $page);
        }

        if ($end < $pages) {
            if ($end < $pages - 1) {
                $links[] = $link('&hellip;', null);
            }

            $links[] = $link((string) $pages, $url($pages));
        }

        $links[] = $link('&raquo;', $page < $pages ? $url($page + 1) : null);

        return $links;
    }

    // ------------------------------------------------------------------
    // Estat del llistat i URL
    // ------------------------------------------------------------------

    /**
     * @return array{page: int, per_page: int, q: string, sort: string, dir: string, f: array<string, mixed>}
     */
    protected function readState(): array
    {
        $get     = $this->request->getGet();
        $perPage = (int) ($get['per_page'] ?? $this->perPage);
        $sort    = is_string($get['sort'] ?? null) ? $get['sort'] : '';
        $filters = is_array($get['f'] ?? null) ? $get['f'] : [];

        return [
            'page'     => max(1, (int) ($get['page'] ?? 1)),
            'per_page' => in_array($perPage, $this->perPageOptions, true) ? $perPage : $this->perPage,
            'q'        => is_string($get['q'] ?? null) ? trim($get['q']) : '',
            'sort'     => in_array($sort, $this->sortableColumns(), true) ? $sort : '',
            'dir'      => ($get['dir'] ?? '') === 'desc' ? 'desc' : 'asc',
            'f'        => array_intersect_key($filters, array_flip($this->filterFieldList())),
        ];
    }

    /**
     * Converteix l'estat en paràmetres d'URL, sense els valors per defecte.
     *
     * @param array<string, mixed> $state
     * @param array<string, mixed> $override
     *
     * @return array<string, mixed>
     */
    protected function stateQuery(array $state, array $override = []): array
    {
        $state = array_merge($state, $override);
        $query = [];

        if (! empty($state['page']) && (int) $state['page'] > 1) {
            $query['page'] = (int) $state['page'];
        }

        if (! empty($state['per_page']) && (int) $state['per_page'] !== $this->perPage) {
            $query['per_page'] = (int) $state['per_page'];
        }

        if (($state['q'] ?? '') !== '') {
            $query['q'] = $state['q'];
        }

        if (($state['sort'] ?? '') !== '') {
            $query['sort'] = $state['sort'];
            $query['dir']  = $state['dir'];
        }

        $filters = $this->cleanFilters($state['f'] ?? []);

        if ($filters !== []) {
            $query['f'] = $filters;
        }

        return $query;
    }

    /**
     * @param array<array-key, mixed> $filters
     *
     * @return array<array-key, mixed>
     */
    protected function cleanFilters(array $filters): array
    {
        foreach ($filters as $key => $value) {
            if (is_array($value)) {
                $value = $this->cleanFilters($value);
            }

            if ($value === '' || $value === null || $value === []) {
                unset($filters[$key]);
            } else {
                $filters[$key] = $value;
            }
        }

        return $filters;
    }

    protected function stateKey(): string
    {
        return 'kpcrudy_' . md5((string) $this->baseUrl);
    }

    /**
     * URL d'una operació del CRUD amb l'id real (s'emmascara i se signa si cal).
     */
    public function url(string $operation, ?string $id = null): string
    {
        return $this->signIfRequired($this->rawUrl($operation, $id), $operation);
    }

    protected function rawUrl(string $operation, ?string $id = null): string
    {
        if ($this->baseUrl === null) {
            $this->resolveOperation();
        }

        if ($operation === 'list') {
            return (string) $this->baseUrl;
        }

        return $this->baseUrl . '/' . $operation . ($id !== null ? '/' . rawurlencode($this->encodeId($id)) : '');
    }

    /**
     * @param array<string, mixed> $query
     */
    protected function listUrl(bool $trash, array $query = []): string
    {
        return $this->withQuery($trash ? $this->url('trash') : $this->signIfRequired((string) $this->baseUrl, 'list'), $query);
    }

    /**
     * @param array<string, mixed> $query
     */
    protected function withQuery(string $url, array $query): string
    {
        $string = http_build_query($query);

        if ($string === '') {
            return $url;
        }

        // La URL pot portar ja la signatura (?kp_exp=...&kp_sig=...)
        return $url . (str_contains($url, '?') ? '&' : '?') . $string;
    }

    /**
     * URL del llistat tal com l'havia deixat l'usuari (pàgina, filtres...).
     */
    protected function backUrl(): string
    {
        if ($this->backUrl !== null) {
            return $this->backUrl;
        }

        $saved = session()->get($this->stateKey());

        if (! is_array($saved)) {
            return $this->listUrl(false);
        }

        return $this->listUrl((bool) ($saved['trash'] ?? false) && $this->can('trash'), (array) ($saved['query'] ?? []));
    }

    protected function redirectToList(string $type, string $text): ResponseInterface
    {
        // Si el llistat necessita signatura i aquesta petició no en pot donar, redirigir-hi
        // portaria a "enllaç no vàlid": es mostra el missatge directament
        if ($this->backUrl === null && $this->signatureRequired('list') && ! $this->canSign()) {
            throw PageNotFoundException::forPageNotFound($text);
        }

        session()->setFlashdata($this->stateKey() . '_flash', ['type' => $type, 'text' => $text]);

        return redirect()->to($this->backUrl());
    }

    // ------------------------------------------------------------------
    // Utilitats
    // ------------------------------------------------------------------

    protected function can(string $operation, ?array $row = null): bool
    {
        if (in_array($operation, ['trash', 'restore', 'purge'], true)) {
            if (! $this->softDeletes) {
                return false;
            }

            if ($operation !== 'trash' && ! $this->can('trash', $row)) {
                return false;
            }
        }

        $rule = $this->permissions[$operation] ?? true;

        return $rule instanceof Closure ? (bool) $rule($row, $operation) : $rule;
    }

    /**
     * Un identificador que no pot ser una clau primària vàlida (p. ex. "5abc"
     * per a una clau numèrica) no ha de coincidir amb cap registre.
     */
    protected function isValidId(string $id): bool
    {
        if ($id === '' || strlen($id) > 255) {
            return false;
        }

        $type = (string) ($this->meta[$this->primaryKey]['type'] ?? '');

        return ! str_contains($type, 'int') || (bool) preg_match('/^-?\d+$/', $id);
    }

    /**
     * Escapa els comodins de LIKE (% i _) perquè la cerca sigui literal.
     */
    protected function escapeLike(string $value): string
    {
        $char = $this->db->likeEscapeChar;

        return str_replace([$char, '%', '_'], [$char . $char, $char . '%', $char . '_'], $value);
    }

    protected function isPost(): bool
    {
        return strtolower($this->request->getMethod()) === 'post';
    }

    protected function exportResponse(bool $trash, ?array $ids): ResponseInterface
    {
        $state   = $this->readState();
        $builder = $this->selectBuilder($trash);

        if ($ids === null) {
            $this->applySearchAndFilters($builder, $state);
        } else {
            $builder->whereIn("{$this->table}.{$this->primaryKey}", $ids);
        }

        $max = $this->config->exportMaxRows;

        if ($max > 0 && $builder->countAllResults(false) > $max) {
            return $this->redirectToList('warning', lang('Kpcrudy.exportTooMany', [$max]));
        }

        $this->applyOrder($builder, $state);

        $columns   = $this->exportColumns ?? $this->columnList();
        $delimiter = $this->config->csvDelimiter;
        $handle    = fopen('php://temp', 'r+b');

        if ($this->config->csvBom) {
            fwrite($handle, "\xEF\xBB\xBF");
        }

        fputcsv($handle, array_map($this->label(...), $columns), $delimiter, '"', '');

        // Es llegeix per blocs per no tenir tot el resultat a la memòria
        for ($offset = 0; ($chunk = $builder->get(500, $offset, false)->getResultArray()) !== []; $offset += 500) {
            foreach ($this->attachNN($chunk) as $row) {
                $line = [];

                foreach ($columns as $name) {
                    $line[] = $this->csvSafe($this->renderValue($name, $row, 'export'));
                }

                fputcsv($handle, $line, $delimiter, '"', '');
            }
        }

        rewind($handle);
        $csv = (string) stream_get_contents($handle);
        fclose($handle);

        $filename = (url_title($this->subjectPlural, '-', true) ?: 'export') . '-' . date('Ymd-His') . '.csv';

        return service('response')
            ->setHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($csv);
    }

    /**
     * Evita que el full de càlcul interpreti un valor com una fórmula: hi
     * afegeix un apòstrof si comença (encara que sigui després d'espais)
     * per =, +, -, @ (també en amplada completa) o per un tabulador o salt de línia.
     */
    protected function csvSafe(string $value): string
    {
        if ($value === '' || is_numeric($value)) {
            return $value;
        }

        $first = mb_substr(ltrim($value, ' '), 0, 1);

        return in_array($first, ['=', '+', '-', '@', '＝', '＋', '－', '＠', "\t", "\r", "\n"], true) ? "'" . $value : $value;
    }

    protected function renderer(): Renderer
    {
        // Temes inclosos a la llibreria; els de la configuració poden substituir w3css i tailwind
        $themes = array_merge(
            ['w3css' => __DIR__ . '/Views/w3css', 'tailwind' => __DIR__ . '/Views/tailwind'],
            $this->config->themes,
            ['bootstrap5' => __DIR__ . '/Views/bootstrap5'],
        );

        // Cadena de temes: el tema, el seu pare (si en té) i, al final, bootstrap5.
        // Un tema és un directori o ['path' => directori, 'parent' => nom del tema pare].
        $paths = [];
        $name  = $this->theme;
        $seen  = [];

        while (! isset($seen[$name])) {
            if (! isset($themes[$name])) {
                throw new RuntimeException("Kpcrudy: el tema '{$name}' no existeix.");
            }

            $seen[$name] = true;
            $entry       = $themes[$name];
            $paths[]     = is_array($entry) ? $entry['path'] : $entry;
            $name        = is_array($entry) ? ($entry['parent'] ?? 'bootstrap5') : 'bootstrap5';
        }

        $paths[] = $themes['bootstrap5'];

        return new Renderer(array_values(array_unique($paths)));
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function output(string $view, array $data, string $title): string
    {
        $renderer = $this->renderer();
        $html     = $renderer->render($view, $data + [
            'title'         => $title,
            'subject'       => $this->subject,
            'subjectPlural' => $this->subjectPlural,
            'flash'         => session()->getFlashdata($this->stateKey() . '_flash'),
        ]);

        if ($this->layout === false) {
            return $html;
        }

        if ($this->layout === 'default') {
            return $renderer->render('layout', ['title' => $title, 'content' => $html, 'config' => $this->config]);
        }

        return view($this->layout, ['kpcrudy' => $html, 'title' => $title]);
    }
}
