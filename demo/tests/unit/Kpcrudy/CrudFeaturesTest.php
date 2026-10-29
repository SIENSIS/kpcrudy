<?php

namespace Tests\Kpcrudy;

use CodeIgniter\Config\Services;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\SiteURI;
use CodeIgniter\HTTP\UserAgent;
use CodeIgniter\Router\RouteCollection;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use Config\App;
use Config\Modules;
use Kpcrudy\Config\Kpcrudy as KpcrudyConfig;
use Kpcrudy\Crud;
use Kpcrudy\PermissionResolver;

/**
 * Library features that are not specific to the demo: route names, central
 * permissions, custom operations, multiselect, translations and afterWrite().
 * Runs on the "tests" database group (SQLite in memory by default).
 *
 * @internal
 */
final class CrudFeaturesTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate = false;

    protected function setUp(): void
    {
        parent::setUp();

        $forge = \Config\Database::forge($this->DBGroup);
        $forge->dropTable('kp_item_translations', true);
        $forge->dropTable('kp_items', true);

        $forge->addField([
            'id'         => ['type' => 'INTEGER', 'unsigned' => true, 'auto_increment' => true],
            'name'       => ['type' => 'VARCHAR', 'constraint' => 50],
            'tags'       => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'owner'      => ['type' => 'INTEGER', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $forge->addPrimaryKey('id');
        $forge->createTable('kp_items');

        $forge->addField([
            'id'      => ['type' => 'INTEGER', 'unsigned' => true, 'auto_increment' => true],
            'item_id' => ['type' => 'INTEGER', 'unsigned' => true],
            'locale'  => ['type' => 'VARCHAR', 'constraint' => 5],
            'title'   => ['type' => 'VARCHAR', 'constraint' => 80],
            'body'    => ['type' => 'TEXT', 'null' => true],
        ]);
        $forge->addPrimaryKey('id');
        $forge->createTable('kp_item_translations');

        $this->db->table('kp_items')->insert(['name' => 'First', 'owner' => 1]);
        $this->db->table('kp_items')->insert(['name' => 'Second', 'owner' => 2]);
    }

    /**
     * CRUD on kp_items for a request to $path (as if routed by Crud::routes('items', ...)).
     *
     * @param array<string, mixed> $post
     */
    private function crud(string $path = 'items', string $method = 'GET', array $post = []): Crud
    {
        $app     = config(App::class);
        $request = new IncomingRequest($app, new SiteURI($app, $path), null, new UserAgent());
        $request->setMethod($method);
        $request->setGlobal('post', $post);
        $_POST = $post;
        Services::injectMock('request', $request);

        $config                 = new KpcrudyConfig();
        $config->layout         = false;
        $config->csrfProtection = false;
        $config->checkRoutes    = false;
        $config->flashKeys      = ['success' => 'ok', 'danger' => 'ko', 'warning' => 'ko'];

        return (new Crud($config))->setTable('kp_items')->setSubject('item', 'Items');
    }

    public function testRoutesNameOnlyTheBaseRoute(): void
    {
        $routes = new RouteCollection(Services::locator(), new Modules(), new \Config\Routing());
        Crud::routes($routes, 'admin/items', 'Items::index', ['as' => 'items']);

        $get = array_keys($routes->getRoutes('GET'));

        $this->assertContains('admin/items', $get);
        $this->assertContains('admin/items/(.*)', $get);
        $this->assertSame('/admin/items', $routes->reverseRoute('items'));
    }

    public function testResolverHasTheLastWord(): void
    {
        $crud = $this->crud()
            ->setResource('things')
            ->setPermission('delete', true)
            ->setPermissionResolver(static function (string $resource, string $operation, ?array $row): ?bool {
                if ($resource !== 'things') {
                    return false;
                }

                return match ($operation) {
                    'delete' => false,
                    'edit'   => $row === null || (int) $row['owner'] === 1,
                    default  => null,
                };
            });

        $this->assertSame('things', $crud->getResource());
        $this->assertFalse($crud->isAllowed('delete'), 'setPermission() cannot allow what the resolver denies');
        $this->assertTrue($crud->isAllowed('edit', ['owner' => 1]));
        $this->assertFalse($crud->isAllowed('edit', ['owner' => 2]));
        $this->assertTrue($crud->isAllowed('show'), 'null: no opinion, allowed by default');
    }

    public function testResourceDefaultsToTheTableAndDenyByDefault(): void
    {
        $crud = $this->crud();
        $this->assertSame('kp_items', $crud->getResource());

        $config                = new KpcrudyConfig();
        $config->denyByDefault = true;
        $config->permissionResolver = DenyAllButList::class;

        $crud = (new Crud($config))->setTable('kp_items')->setPermission('add', true);

        $this->assertTrue($crud->isAllowed('list'));
        $this->assertTrue($crud->isAllowed('add'), 'allowed by setPermission()');
        $this->assertFalse($crud->isAllowed('edit'), 'nobody says anything: denied');
    }

    public function testCustomOperationRunsOnTheCrudRoute(): void
    {
        $calls = [];
        $crud  = $this->crud('items/archive/1', 'POST')
            ->addOperation('archive', static function (string $id, array $row) use (&$calls) {
                $calls[] = [$id, $row['name']];

                return 'Archived ' . $row['name'];
            });

        $response = $crud->render();

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame([['1', 'First']], $calls);
        $this->assertSame('Archived First', session('ok'));
    }

    public function testCustomOperationChecksMethodAndPermission(): void
    {
        $called = false;
        $op     = static function () use (&$called) {
            $called = true;
        };

        // POST operation reached with GET
        $this->crud('items/archive/1')->addOperation('archive', $op)->render();
        $this->assertFalse($called);
        $this->assertNotNull(session('ko'));

        // Denied by its permission (the key, by default)
        $this->crud('items/archive/1', 'POST')->addOperation('archive', $op)->setPermission('archive', false)->render();
        $this->assertFalse($called);

        // General operation (no record), GET, with an error message
        $response = $this->crud('items/report')
            ->addOperation('report', static fn ($id, $row, Crud $crud) => $crud->backToList('danger', 'Nothing to report'), ['row' => false, 'method' => 'get'])
            ->render();
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('Nothing to report', session('ko'));
    }

    public function testOperationNamesAreValidated(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->crud()->addOperation('edit', static fn () => null);
    }

    public function testMultiselectStoresOnlyValidOptions(): void
    {
        $writes = [];
        $this->crud('items/add', 'POST', ['name' => 'Third', 'tags' => ['a', 'zzz', 'c']])
            ->fields(['name', 'tags'])
            ->fieldType('tags', 'multiselect', ['options' => ['a' => 'A', 'b' => 'B', 'c' => 'C']])
            ->afterWrite(static function (string $operation, string $id, array $data) use (&$writes) {
                $writes[] = [$operation, $data['name'] ?? null];
            })
            ->render();

        $row = $this->db->table('kp_items')->where('name', 'Third')->get()->getRowArray();

        $this->assertSame('["a","c"]', $row['tags']);
        $this->assertSame([['insert', 'Third']], $writes);

        // CSV storage
        $this->crud('items/add', 'POST', ['name' => 'Fourth', 'tags' => ['b', 'c']])
            ->fields(['name', 'tags'])
            ->fieldType('tags', 'multiselect', ['options' => ['a' => 'A', 'b' => 'B', 'c' => 'C'], 'storage' => 'csv'])
            ->render();

        $this->assertSame('b,c', $this->db->table('kp_items')->where('name', 'Fourth')->get()->getRow()->tags);
    }

    private function translated(Crud $crud): Crud
    {
        return $crud->fields(['name', 'title', 'body'])
            ->setTranslations('kp_item_translations', 'item_id', 'locale', ['title', 'body'], ['en' => 'English', 'ca' => 'Català']);
    }

    public function testTranslationsAreSavedPerLocale(): void
    {
        $response = $this->translated($this->crud('items/add', 'POST', [
            'name'  => 'Third',
            'kp_tr' => ['en' => ['title' => 'Hello', 'body' => 'Text'], 'ca' => ['title' => '', 'body' => '']],
        ]))->render();

        $this->assertInstanceOf(RedirectResponse::class, $response);

        $id   = $this->db->table('kp_items')->where('name', 'Third')->get()->getRow()->id;
        $rows = $this->db->table('kp_item_translations')->where('item_id', $id)->get()->getResultArray();

        $this->assertCount(1, $rows, 'an empty optional locale is not stored');
        $this->assertSame(['en', 'Hello'], [$rows[0]['locale'], $rows[0]['title']]);

        // Edit: the optional locale is added, the required one updated
        $this->translated($this->crud('items/edit/' . $id, 'POST', [
            'name'  => 'Third',
            'kp_tr' => ['en' => ['title' => 'Hi', 'body' => ''], 'ca' => ['title' => 'Hola', 'body' => '']],
        ]))->render();

        $titles = array_column($this->db->table('kp_item_translations')->where('item_id', $id)->orderBy('locale')->get()->getResultArray(), 'title', 'locale');
        $this->assertSame(['ca' => 'Hola', 'en' => 'Hi'], $titles);

        // The list shows the list locale (the first one by default)
        $html = $this->translated($this->crud())->columns(['name', 'title'])->render();
        $this->assertStringContainsString('Hi', $html);
        $this->assertStringNotContainsString('Hola', $html);
    }

    public function testRequiredLocaleIsValidated(): void
    {
        $result = $this->translated($this->crud('items/add', 'POST', [
            'name'  => 'Third',
            'kp_tr' => ['en' => ['title' => '', 'body' => ''], 'ca' => ['title' => 'Hola', 'body' => '']],
        ]))->render();

        $this->assertIsString($result, 'the form is shown again');
        $this->assertSame(0, $this->db->table('kp_items')->where('name', 'Third')->countAllResults());
        $this->assertStringContainsString('The Title (English) field is required.', $result);
    }
}

/**
 * Allows only the list: everything else is left to setPermission() and denyByDefault.
 */
final class DenyAllButList implements PermissionResolver
{
    public function allows(string $resource, string $operation, ?array $row, Crud $crud): ?bool
    {
        return $operation === 'list' ? true : null;
    }
}
