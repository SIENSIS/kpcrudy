<?php

namespace Tests\Kpcrudy;

use CodeIgniter\Config\Services;
use CodeIgniter\Controller;
use CodeIgniter\HTTP\CLIRequest;
use CodeIgniter\HTTP\Files\UploadedFile;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\HTTP\SiteURI;
use CodeIgniter\HTTP\UserAgent;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\StreamFilterTrait;
use Config\App;
use Kpcrudy\Config\Kpcrudy as KpcrudyConfig;
use Kpcrudy\Crud;
use Kpcrudy\Renderer;
use Kpcrudy\Storage\UploadHandler;
use Kpcrudy\Storage\UploadRejectedException;

/**
 * Features added in 1.1: custom search and filters, capture()/describe(), CLI
 * requests, kpcrudy:make options in $params, translation fallbacks, translated
 * CSV import/export, upload handlers, external assets, responsive lists, AJAX
 * relations and trees.
 *
 * @internal
 */
final class CrudExtensionsTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use StreamFilterTrait;

    protected $migrate = false;

    protected function setUp(): void
    {
        parent::setUp();

        $forge = \Config\Database::forge($this->DBGroup);

        foreach (['kx_item_translations', 'kx_notes', 'kx_items', 'kx_categories'] as $table) {
            $forge->dropTable($table, true);
        }

        $forge->addField([
            'id'   => ['type' => 'INTEGER', 'unsigned' => true, 'auto_increment' => true],
            'name' => ['type' => 'VARCHAR', 'constraint' => 50],
            'kind' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
        ]);
        $forge->addPrimaryKey('id');
        $forge->createTable('kx_categories');

        $forge->addField([
            'id'          => ['type' => 'INTEGER', 'unsigned' => true, 'auto_increment' => true],
            'name'        => ['type' => 'VARCHAR', 'constraint' => 50],
            'category_id' => ['type' => 'INTEGER', 'null' => true],
            'parent_id'   => ['type' => 'INTEGER', 'null' => true],
            'position'    => ['type' => 'INTEGER', 'null' => true],
            'photo'       => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true],
        ]);
        $forge->addPrimaryKey('id');
        $forge->createTable('kx_items');

        $forge->addField([
            'id'      => ['type' => 'INTEGER', 'unsigned' => true, 'auto_increment' => true],
            'item_id' => ['type' => 'INTEGER'],
            'text'    => ['type' => 'VARCHAR', 'constraint' => 100],
        ]);
        $forge->addPrimaryKey('id');
        $forge->createTable('kx_notes');

        $forge->addField([
            'id'      => ['type' => 'INTEGER', 'unsigned' => true, 'auto_increment' => true],
            'item_id' => ['type' => 'INTEGER', 'unsigned' => true],
            'locale'  => ['type' => 'VARCHAR', 'constraint' => 5],
            'title'   => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
        ]);
        $forge->addPrimaryKey('id');
        $forge->createTable('kx_item_translations');

        $db = $this->db;
        $db->table('kx_categories')->insertBatch([
            ['name' => 'Castles', 'kind' => 'public'],
            ['name' => 'Caves', 'kind' => 'public'],
            ['name' => 'Secret', 'kind' => 'private'],
        ]);
        $db->table('kx_items')->insertBatch([
            ['name' => 'Alpha', 'category_id' => 1, 'parent_id' => null, 'position' => 2],
            ['name' => 'Beta', 'category_id' => 2, 'parent_id' => null, 'position' => 1],
            ['name' => 'Gamma', 'category_id' => 1, 'parent_id' => 2, 'position' => 1],
            ['name' => 'Delta', 'category_id' => null, 'parent_id' => 3, 'position' => 1],
        ]);
        $db->table('kx_notes')->insert(['item_id' => 1, 'text' => 'needle in the note']);
        $db->table('kx_item_translations')->insertBatch([
            ['item_id' => 1, 'locale' => 'ca', 'title' => 'Títol A'],
            ['item_id' => 1, 'locale' => 'en', 'title' => 'Title A'],
            ['item_id' => 2, 'locale' => 'ca', 'title' => 'Només català'],
        ]);

        MemoryUploads::$stored  = [];
        MemoryUploads::$deleted = [];
    }

    /**
     * @param array<string, mixed> $post
     * @param array<string, mixed> $get
     */
    private function crud(string $path = 'items', string $method = 'GET', array $post = [], array $get = [], ?KpcrudyConfig $config = null, array $files = []): Crud
    {
        $app     = config(App::class);
        $request = new FakeFilesRequest($app, new SiteURI($app, $path), null, new UserAgent());
        $request->fakeFiles = $files;
        $request->setMethod($method);
        $request->setGlobal('post', $post);
        $request->setGlobal('get', $get);
        $_GET  = $get;
        $_POST = $post;
        Services::injectMock('request', $request);

        $config ??= new KpcrudyConfig();
        $config->layout         = false;
        $config->csrfProtection = false;
        $config->checkRoutes    = false;
        $config->flashKeys      = ['success' => 'ok', 'danger' => 'ko', 'warning' => 'ko'];

        return (new Crud($config))->setTable('kx_items')->setSubject('item', 'Items');
    }

    // ------------------------------------------------------------------
    // Custom search and filters
    // ------------------------------------------------------------------

    public function testSearchCallbackSearchesAnotherTable(): void
    {
        $note = static fn (\CodeIgniter\Database\BaseBuilder $b, string $text) => $b->orWhereIn(
            'kx_items.id',
            static fn (\CodeIgniter\Database\BaseBuilder $q) => $q->select('item_id')->from('kx_notes')->like('text', $text),
        );

        $html = $this->crud('items', 'GET', [], ['q' => 'needle'])->columns(['name'])->searchCallback($note)->render();
        $this->assertStringContainsString('Alpha', $html);
        $this->assertStringNotContainsString('Beta', $html);

        // Columns are still searched in the same OR group
        $html = $this->crud('items', 'GET', [], ['q' => 'Gam'])->columns(['name'])->searchCallback($note)->render();
        $this->assertStringContainsString('Gamma', $html);
        $this->assertStringNotContainsString('Alpha', $html);

        // Only callbacks: the search box is still shown
        $html = $this->crud()->columns(['name'])->searchFields([])->searchCallback($note)->render();
        $this->assertStringContainsString('name="q"', $html);
    }

    public function testAddFilterAppliesOnlyItsOptions(): void
    {
        $byKind = static fn (\CodeIgniter\Database\BaseBuilder $b, string $value) => $b->whereIn(
            'kx_items.category_id',
            static fn (\CodeIgniter\Database\BaseBuilder $q) => $q->select('id')->from('kx_categories')->where('kind', $value),
        );
        $filtered = fn (array $get): string => $this->crud('items', 'GET', [], $get)
            ->columns(['name'])
            ->filters([])
            ->addFilter('kind', 'Kind', $byKind, ['options' => ['public' => 'Public', 'private' => 'Private']])
            ->addFilter('note', 'Note', static fn ($b, string $value) => $b->whereIn('kx_items.id', static fn ($q) => $q->select('item_id')->from('kx_notes')->like('text', $value)))
            ->render();

        $html = $filtered(['f' => ['kind' => 'public']]);
        $this->assertStringContainsString('Gamma', $html);
        $this->assertStringNotContainsString('Delta', $html, 'no category: not public');
        $this->assertStringContainsString('name="f[kind]"', $html);

        // A value that is not an option is ignored (everything is listed)
        $this->assertStringContainsString('Delta', $filtered(['f' => ['kind' => "x' OR 1=1"]]));

        // Text filter
        $html = $filtered(['f' => ['note' => 'needle']]);
        $this->assertStringContainsString('Alpha', $html);
        $this->assertStringNotContainsString('Beta', $html);
    }

    public function testFilterKeysAreValidated(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->crud()->addFilter('Bad Key', 'x', static fn () => null);
    }

    // ------------------------------------------------------------------
    // capture(), describe(), CLI request
    // ------------------------------------------------------------------

    public function testCaptureStopsBeforeAnyOperation(): void
    {
        $crud = Crud::capture(fn () => $this->crud('items/delete/1', 'POST')->setResource('items')->addOperation('archive', static fn () => null)->render());

        $this->assertInstanceOf(Crud::class, $crud);
        $this->assertSame(1, $this->db->table('kx_items')->where('id', 1)->countAllResults(), 'nothing was deleted');

        $description = $crud->describe();
        $this->assertSame('items', $description['resource']);
        $this->assertSame('kx_items', $description['table']);
        $this->assertContains('archive', $description['permissions']);
        $this->assertArrayHasKey('archive', $description['customOperations']);
        $this->assertContains('name', $description['columns']);
        $this->assertSame('relation', $this->crud()->setRelation('category_id', 'kx_categories', 'name')->describe()['types']['category_id']);

        $this->assertNull(Crud::capture(static fn () => null), 'render() never called');

        // After capture() render() works again
        $this->assertIsString($this->crud()->render());
    }

    public function testCaptureControllerRunsAnAction(): void
    {
        $this->crud(); // injects a request
        $crud = Crud::captureController('\\' . CapturedItems::class . '::index');

        $this->assertInstanceOf(Crud::class, $crud);
        $this->assertSame('captured-items', $crud->getResource());
    }

    public function testACrudCanBeBuiltWithACliRequest(): void
    {
        Services::injectMock('request', new CLIRequest(new App()));

        $crud = (new Crud())->setTable('kx_items');

        $this->assertContains('list', $crud->permissionCatalogue());
        $this->assertSame('kx_items', $crud->describe()['table']);
    }

    public function testMakeCrudReadsOptionsFromParams(): void
    {
        // The tests database has a prefix: the command lists the tables with it
        $table = in_array('kx_items', $this->db->listTables(), true) ? 'kx_items' : $this->db->DBPrefix . 'kx_items';
        command("kpcrudy:make {$table} --route admin/kx --controller Admin/Kx --dry-run");
        $output = $this->getStreamFilterBuffer();

        $this->assertStringContainsString("'admin/kx'", $output);
        $this->assertStringContainsString('Controllers\\Admin', $output);
    }

    // ------------------------------------------------------------------
    // Translations: fallbacks, CSV import and export
    // ------------------------------------------------------------------

    private function translated(Crud $crud, array $options = []): Crud
    {
        return $crud->columns(['name', 'title'])->fields(['name', 'title'])
            ->setTranslations('kx_item_translations', 'item_id', 'locale', ['title'], ['en' => 'English', 'ca' => 'Català'], $options + ['listLocale' => 'en']);
    }

    public function testFallbackLocaleFillsMissingTranslations(): void
    {
        $plain = $this->translated($this->crud())->render();
        $this->assertStringContainsString('Title A', $plain);
        $this->assertStringNotContainsString('Només català', $plain);

        $html = $this->translated($this->crud(), ['fallbacks' => ['ca']])->render();
        $this->assertStringContainsString('Title A', $html, 'the list locale wins');
        $this->assertStringNotContainsString('Títol A', $html);
        $this->assertStringContainsString('Només català', $html, 'the fallback fills the gap');

        // Search and sort use the same value
        $found = $this->translated($this->crud('items', 'GET', [], ['q' => 'català']), ['fallbacks' => ['en' => ['ca']]])->render();
        $this->assertStringContainsString('Beta', $found);
        $this->assertStringNotContainsString('Alpha', $found);
        $this->assertStringNotContainsString('Alpha', $this->translated($this->crud('items', 'GET', [], ['q' => 'Títol']), ['fallbacks' => ['ca']])->render(), 'a hidden fallback does not match');

        $sorted = $this->translated($this->crud('items', 'GET', [], ['sort' => 'title', 'dir' => 'asc']), ['fallbacks' => ['ca']])->render();
        $this->assertLessThan(strpos($sorted, 'Title A'), strpos($sorted, 'Només català'));
    }

    public function testTranslatedFieldsAreImportedAndExportedPerLocale(): void
    {
        $crud = new TestableCrud($this->configForTests());
        $this->crud(); // request
        $crud->setTable('kx_items')->fields(['name', 'title'])->enableImport()
            ->setTranslations('kx_item_translations', 'item_id', 'locale', ['title'], ['en' => 'English', 'ca' => 'Català'], ['rules' => ['title' => 'required|max_length[80]']]);

        $file = tempnam(sys_get_temp_dir(), 'kx');
        file_put_contents($file, "name,title:en,Title (Català)\nOmega,Hello,Hola\nSigma,,Només\n");
        $result = $crud->import($file);

        $this->assertSame(1, $result['created'], 'Sigma has no English title (required locale)');
        $omega  = $this->db->table('kx_items')->where('name', 'Omega')->get()->getRow()->id;
        $titles = array_column($this->db->table('kx_item_translations')->where('item_id', $omega)->get()->getResultArray(), 'title', 'locale');
        $this->assertEqualsCanonicalizing(['ca' => 'Hola', 'en' => 'Hello'], $titles);

        // Export with one column per locale
        $csv = (string) $this->crud('items/export')->columns(['name', 'title'])
            ->setTranslations('kx_item_translations', 'item_id', 'locale', ['title'], ['en' => 'English', 'ca' => 'Català'])
            ->exportTranslations()->render()->getBody();
        $this->assertStringContainsString('"Title (English)";"Title (Català)"', $csv);
        $this->assertStringContainsString('Omega;Hello;Hola', $csv);
    }

    // ------------------------------------------------------------------
    // Upload handlers
    // ------------------------------------------------------------------

    public function testUploadHandlerStoresServesAndDeletes(): void
    {
        $png  = tempnam(sys_get_temp_dir(), 'kx');
        $data = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==', true);
        file_put_contents($png, $data);
        $file = new TestUploadedFile($png, 'me.png', 'image/png', strlen((string) $data), UPLOAD_ERR_OK);

        $config                = $this->configForTests();
        $config->uploadHandler = MemoryUploads::class;

        $response = $this->crud('items/edit/1', 'POST', ['name' => 'Alpha'], [], $config, ['photo' => $file])->fields(['name', 'photo'])->fieldType('photo', 'image')->render();

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('mem/photo/1', $this->db->table('kx_items')->where('id', 1)->get()->getRow()->photo);
        $this->assertSame([['photo', 'edit', 'kx_items']], MemoryUploads::$stored);

        $html = $this->crud('items', 'GET', [], [], $config)->columns(['name', 'photo'])->fieldType('photo', 'image')->render();
        $this->assertStringContainsString('https://cdn.example.com/mem/photo/1', html_entity_decode($html));

        $this->crud('items/delete/1', 'POST', [], [], $config)->fieldType('photo', 'image')->render();
        $this->assertSame(['mem/photo/1'], MemoryUploads::$deleted);
    }

    public function testUploadHandlerCanRejectAFile(): void
    {
        $png  = tempnam(sys_get_temp_dir(), 'kx');
        $data = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==', true);
        file_put_contents($png, $data);
        $file = new TestUploadedFile($png, 'me.png', 'image/png', strlen((string) $data), UPLOAD_ERR_OK);

        $html = $this->crud('items/edit/2', 'POST', ['name' => 'Beta'], [], null, ['photo' => $file])->fields(['name', 'photo'])
            ->fieldType('photo', 'image', ['handler' => new MemoryUploads(reject: true)])->render();

        $this->assertIsString($html);
        $this->assertStringContainsString('Too big for the bucket', $html);
        $this->assertNull($this->db->table('kx_items')->where('id', 2)->get()->getRow()->photo);
    }

    // ------------------------------------------------------------------
    // Assets, responsive lists
    // ------------------------------------------------------------------

    public function testAssetsInlineOrExternal(): void
    {
        $inline = (new Renderer([], new KpcrudyConfig()))->asset('kpcrudy.js');
        $this->assertStringStartsWith('<script', $inline);
        $this->assertStringContainsString('kpcrudyReady', $inline);

        $config             = new KpcrudyConfig();
        $config->assetsMode = 'external';
        $config->assetsUrl  = '/static/kpcrudy';
        $renderer           = new Renderer([], $config);

        $this->assertMatchesRegularExpression('#^<script[^>]* src="/static/kpcrudy/kpcrudy\.js\?v=[0-9a-f]{10}" defer></script>$#', html_entity_decode($renderer->asset('kpcrudy.js')));
        $this->assertMatchesRegularExpression('#^<link rel="stylesheet"[^>]* href="/static/kpcrudy/kpcrudy\.css\?v=[0-9a-f]{10}">$#', html_entity_decode($renderer->asset('kpcrudy.css')));

        $html = $this->crud('items', 'GET', [], [], $config)->columns(['name'])->render();
        $this->assertStringNotContainsString('kpcrudyReady', $html, 'no inline code');
        $this->assertStringContainsString('/static/kpcrudy/kpcrudy.js', html_entity_decode($html));

        $dir = sys_get_temp_dir() . '/kx-assets-' . bin2hex(random_bytes(3));
        command('kpcrudy:publish assets --path ' . $dir);
        $this->assertFileExists($dir . '/kpcrudy.js');
        $this->assertFileExists($dir . '/kpcrudy-tailwind.css');
    }

    public function testResponsiveListOptions(): void
    {
        $html = $this->crud()->columns(['name', 'position'])
            ->columnPriority(['position' => 3])
            ->rowActionsMenu()
            ->mobileCards()
            ->render();

        $this->assertMatchesRegularExpression('#<th scope="col" class="kp-p3">#', $html);
        $this->assertStringContainsString('data-label="Position"', $html);
        $this->assertStringContainsString('kp-cards', $html);
        $this->assertStringContainsString('<details class="kp-menu">', $html);

        // Only when there are more than N actions (show, edit, delete = 3)
        $this->assertStringNotContainsString('<details class="kp-menu">', $this->crud()->rowActionsMenu(3)->render());
        $this->assertStringContainsString('<details class="kp-menu">', $this->crud()->rowActionsMenu(2)->render());
    }

    // ------------------------------------------------------------------
    // AJAX relations
    // ------------------------------------------------------------------

    private function lookup(Crud $crud): Crud
    {
        return $crud->fields(['name', 'category_id'])->filters(['category_id'])
            ->setRelation('category_id', 'kx_categories', 'name', ['ajax' => true, 'minChars' => 2, 'where' => ['kind' => 'public']]);
    }

    public function testLookupSearchesTheRelation(): void
    {
        $response = $this->lookup($this->crud('items/lookup', 'GET', [], ['field' => 'category_id', 'q' => 'ca']))->render();

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame(['results' => [['id' => '1', 'text' => 'Castles'], ['id' => '2', 'text' => 'Caves']]], json_decode((string) $response->getBody(), true));

        // Too short, outside the relation's where
        $short = $this->lookup($this->crud('items/lookup', 'GET', [], ['field' => 'category_id', 'q' => 'c']))->render();
        $this->assertSame(['results' => []], json_decode((string) $short->getBody(), true));
        $secret = $this->lookup($this->crud('items/lookup', 'GET', [], ['field' => 'category_id', 'q' => 'Secret']))->render();
        $this->assertSame(['results' => []], json_decode((string) $secret->getBody(), true));
    }

    public function testLookupNeedsAFormOrFilterTheUserCanUse(): void
    {
        $this->expectException(\CodeIgniter\Exceptions\PageNotFoundException::class);
        $this->crud('items/lookup', 'GET', [], ['field' => 'category_id', 'q' => 'ca'])
            ->setRelation('category_id', 'kx_categories', 'name', ['ajax' => true])
            ->fields(['name', 'category_id'])->filters([])
            ->unsetAdd()->unsetEdit()
            ->render();
    }

    public function testLookupFieldsAreRenderedAndValidated(): void
    {
        $form = $this->lookup($this->crud('items/edit/1'))->render();
        $this->assertStringContainsString('data-kp-lookup=', $form);
        $this->assertStringContainsString('value="Castles"', $form, 'the current text');
        $this->assertStringNotContainsString('>Caves<', $form, 'no list of every option');

        // A value outside the relation (where) is refused
        $this->lookup($this->crud('items/edit/1', 'POST', ['name' => 'Alpha', 'category_id' => '3']))->render();
        $this->assertSame('1', (string) $this->db->table('kx_items')->where('id', 1)->get()->getRow()->category_id);

        $this->lookup($this->crud('items/edit/1', 'POST', ['name' => 'Alpha', 'category_id' => '2']))->render();
        $this->assertSame('2', (string) $this->db->table('kx_items')->where('id', 1)->get()->getRow()->category_id);

        // Filter
        $list = $this->lookup($this->crud('items', 'GET', [], ['f' => ['category_id' => '2']]))->columns(['name'])->render();
        $this->assertStringContainsString('Alpha', $list);
        $this->assertStringNotContainsString('Gamma', $list);
    }

    // ------------------------------------------------------------------
    // Trees
    // ------------------------------------------------------------------

    public function testTreeOrderIndentationAndCycles(): void
    {
        $tree = static fn (Crud $crud): Crud => $crud->columns(['name'])->fields(['name', 'parent_id'])
            ->setRelation('parent_id', 'kx_items', 'name')
            ->tree('parent_id', ['order' => 'position']);

        $html = $tree($this->crud())->render();
        $positions = array_map(static fn (string $name): int|false => strpos($html, $name), ['Beta', 'Gamma', 'Delta', 'Alpha']);
        $this->assertSame($positions, array_values(array_filter($positions)), 'all shown');
        $sorted = $positions;
        sort($sorted);
        $this->assertSame($sorted, $positions, 'Beta, its child Gamma, its grandchild Delta, then Alpha');
        $this->assertSame(3, substr_count($html, 'class="kp-tree-pad"'), 'Gamma 1 + Delta 2');

        // The parent options are in tree order
        $form = $tree($this->crud('items/add'))->render();
        $this->assertStringContainsString('— — Delta', $form);

        // Beta cannot hang from its grandchild Delta, nor from itself
        $tree($this->crud('items/edit/2', 'POST', ['name' => 'Beta', 'parent_id' => '4']))->render();
        $tree($this->crud('items/edit/2', 'POST', ['name' => 'Beta', 'parent_id' => '2']))->render();
        $this->assertNull($this->db->table('kx_items')->where('id', 2)->get()->getRow()->parent_id);

        // Searching shows a flat list
        $this->assertSame(0, substr_count($tree($this->crud('items', 'GET', [], ['q' => 'Delta']))->render(), 'class="kp-tree-pad"'));
    }

    private function configForTests(): KpcrudyConfig
    {
        $config                 = new KpcrudyConfig();
        $config->layout         = false;
        $config->csrfProtection = false;
        $config->checkRoutes    = false;

        return $config;
    }
}

/**
 * Upload handler that keeps the files in memory.
 */
final class MemoryUploads implements UploadHandler
{
    /** @var list<array{0: string, 1: string, 2: string}> */
    public static array $stored = [];

    /** @var list<string> */
    public static array $deleted = [];

    public function __construct(private readonly bool $reject = false)
    {
    }

    public function store(UploadedFile $file, string $field, array $context): string
    {
        if ($this->reject) {
            throw new UploadRejectedException('Too big for the bucket');
        }

        self::$stored[] = [$field, $context['operation'], $context['table']];

        return 'mem/' . $field . '/' . ($context['record']['id'] ?? 'new');
    }

    public function delete(string $stored, string $field, array $context): void
    {
        self::$deleted[] = $stored;
    }

    public function url(string $stored, string $field, array $context): ?string
    {
        return 'https://cdn.example.com/' . $stored;
    }
}

/**
 * Exposes the CSV import without the upload/confirm screens.
 */
final class TestableCrud extends Crud
{
    /**
     * @return array<string, mixed>
     */
    public function import(string $path): array
    {
        $this->boot();

        return $this->runImport($path, 'insert', 'skip');
    }
}

/**
 * A controller whose action builds and renders a CRUD.
 */
final class CapturedItems extends Controller
{
    public function index(): ResponseInterface|string
    {
        $config         = new KpcrudyConfig();
        $config->layout = false;

        return (new Crud($config))->setTable('kx_items')->setResource('captured-items')->render();
    }
}

/**
 * A request whose uploaded files are given by the test.
 */
final class FakeFilesRequest extends IncomingRequest
{
    /** @var array<string, UploadedFile> */
    public array $fakeFiles = [];

    public function getFile(string $fileID)
    {
        return $this->fakeFiles[$fileID] ?? null;
    }
}

/**
 * An uploaded file that is valid outside a real HTTP upload (is_uploaded_file()).
 */
final class TestUploadedFile extends UploadedFile
{
    public function isValid(): bool
    {
        return true;
    }
}
