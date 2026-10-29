<?php

namespace Tests\Kpcrudy;

use CodeIgniter\Test\CIUnitTestCase;
use Kpcrudy\Generator\CrudGenerator;
use Kpcrudy\Renderer;

/**
 * Code generation for apps and modules (kpcrudy:make), translated messages
 * and the CSP nonce helper of the templates.
 *
 * @internal
 */
final class GeneratorAndLanguageTest extends CIUnitTestCase
{
    private function generator(): CrudGenerator
    {
        return new CrudGenerator(db_connect());
    }

    public function testRouteLineWithFilterAndName(): void
    {
        $line = $this->generator()->routeLine('admin/posts', '\Blog\Controllers\Posts::index', 'auth', 'blog-posts');

        $this->assertSame("\\Kpcrudy\\Crud::routes(\$routes, 'admin/posts', '\\Blog\\Controllers\\Posts::index', ['filter' => 'auth', 'as' => 'blog-posts']);", $line);
        $this->assertStringEndsWith("'Posts::index');", $this->generator()->routeLine('posts', 'Posts::index', null));
    }

    public function testNewControllerExtendsTheGivenParent(): void
    {
        $file = WRITEPATH . 'kp-missing-' . bin2hex(random_bytes(4)) . '.php';
        $code = "    public function index()\n    {\n    }\n";

        [$status, $source] = $this->generator()->controllerSource($file, 'Blog\Controllers\Admin', 'Posts', 'index', $code, ['Kpcrudy\Crud'], false, 'Blog\Controllers\BaseController');
        $this->assertSame('created', $status);
        $this->assertStringContainsString("use Blog\\Controllers\\BaseController;\nuse Kpcrudy\\Crud;", $source);
        $this->assertStringContainsString('class Posts extends BaseController', $source);

        // Same namespace: no import
        [, $source] = $this->generator()->controllerSource($file, 'Blog\Controllers', 'Posts', 'index', $code, [], false, 'Blog\Controllers\BaseController');
        $this->assertStringNotContainsString('use Blog', $source);

        // A parent with the same short name as the class is written in full
        [, $source] = $this->generator()->controllerSource($file, 'Blog\Controllers', 'Controller', 'index', $code, [], false, 'CodeIgniter\Controller');
        $this->assertStringContainsString('class Controller extends \CodeIgniter\Controller', $source);
    }

    public function testRoutesFileIsCreatedWhenMissing(): void
    {
        $line   = "\\Kpcrudy\\Crud::routes(\$routes, 'posts', 'Posts::index');";
        $source = $this->generator()->routesSource(WRITEPATH . 'kp-no-such-dir/Routes.php', 'posts', $line);

        $this->assertStringStartsWith('<?php', (string) $source);
        $this->assertStringEndsWith($line . "\n", (string) $source);

        $file = WRITEPATH . 'kp-routes-' . bin2hex(random_bytes(4)) . '.php';
        file_put_contents($file, "<?php\n\n" . $line . "\n");

        try {
            $this->assertNull($this->generator()->routesSource($file, 'posts', $line), 'already there');
        } finally {
            unlink($file);
        }
    }

    public function testEveryLanguageHasTheSameKeys(): void
    {
        $dir  = dirname(__DIR__, 4) . '/src/Language/';
        $keys = static function (array $lines, string $prefix = '') use (&$keys): array {
            $all = [];

            foreach ($lines as $key => $value) {
                $all = [...$all, ...(is_array($value) ? $keys($value, $prefix . $key . '.') : [$prefix . $key])];
            }

            sort($all);

            return $all;
        };

        $english = $keys(require $dir . 'en/Kpcrudy.php');

        foreach (glob($dir . '*/Kpcrudy.php') as $file) {
            $this->assertSame($english, $keys(require $file), 'Keys of ' . basename(dirname($file)));
        }

        // Every lang('Kpcrudy.x') used by the library exists
        $used = [];

        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(dirname($dir), \FilesystemIterator::SKIP_DOTS)) as $source) {
            if ($source->getExtension() === 'php' && preg_match_all("/lang\\('Kpcrudy\\.([A-Za-z.]+)'/", (string) file_get_contents($source->getPathname()), $m)) {
                $used = [...$used, ...$m[1]];
            }
        }

        $this->assertSame([], array_values(array_diff(array_unique($used), $english)), 'Language keys used but not defined');
    }

    public function testExceptionMessagesAreTranslated(): void
    {
        service('language')->setLocale('en');
        $this->assertSame('Kpcrudy: table "foo" does not exist.', lang('Kpcrudy.exceptions.tableNotFound', ['foo']));

        service('language')->setLocale('ca');
        $this->assertSame('Kpcrudy: la taula "foo" no existeix.', lang('Kpcrudy.exceptions.tableNotFound', ['foo']));

        service('language')->setLocale('en');
    }

    public function testNonceFollowsTheContentSecurityPolicy(): void
    {
        $renderer = new Renderer([]);
        $enabled  = config('App')->CSPEnabled;

        if ($enabled) {
            $this->assertMatchesRegularExpression('/^ nonce="[^"]+"$/', $renderer->nonce());
            $this->assertMatchesRegularExpression('/^ nonce="[^"]+"$/', $renderer->nonce('style'));
        } else {
            $this->assertSame('', $renderer->nonce());
        }
    }
}
