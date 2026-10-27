<?php

declare(strict_types=1);

namespace Kpcrudy\Demo;

use CodeIgniter\Router\RouteCollection;
use Kpcrudy\Crud;
use Kpcrudy\Demo\Config\CrudConfig;
use Kpcrudy\Demo\Filters\AdminFilter;
use Kpcrudy\Demo\Filters\AuthFilter;
use Kpcrudy\Demo\Filters\CsrfFilter;
use RuntimeException;

/**
 * Names and paths of the demo module, chosen so that they never collide with
 * the host project: URLs under /kpcrudy-demo, tables prefixed with kpdemo_,
 * uploads in public/uploads/kpcrudy-demo and its own session key.
 */
final class Demo
{
    public const PREFIX = 'kpcrudy-demo';

    public const SESSION_KEY = 'kpdemo_user_id';

    /**
     * Demo tables, in creation order.
     */
    public const TABLES = ['kpdemo_categories', 'kpdemo_users', 'kpdemo_user_interests', 'kpdemo_events', 'kpdemo_attendances'];

    public static function url(string $path = ''): string
    {
        return site_url(rtrim(self::PREFIX . '/' . ltrim($path, '/'), '/'));
    }

    public static function uploadPath(string $folder = ''): string
    {
        return rtrim(FCPATH . 'uploads/' . self::PREFIX . '/' . $folder, '/');
    }

    public static function uploadUrl(string $folder = ''): string
    {
        return rtrim(base_url('uploads/' . self::PREFIX . '/' . $folder), '/');
    }

    /**
     * Written by "php spark kpcrudy:demo install". While it exists, the demo
     * routes are active (outside production, see isEnabled()).
     */
    public static function markerFile(): string
    {
        return WRITEPATH . 'kpcrudy-demo.json';
    }

    public static function isInstalled(): bool
    {
        return is_file(self::markerFile());
    }

    /**
     * Contents of the marker file ([] if the demo is not installed).
     *
     * @return array<string, mixed>
     */
    public static function marker(): array
    {
        // The marker is replaced atomically (writeMarker), but a read can still
        // fail while another process renames it on some systems: try again
        for ($try = 0; $try < 5; $try++) {
            if (! self::isInstalled()) {
                return [];
            }

            $marker = json_decode((string) @file_get_contents(self::markerFile()), true);

            if (is_array($marker) && isset($marker['installed_at'])) {
                return $marker;
            }

            usleep(20000);
        }

        return [];
    }

    /**
     * Writes the marker through a temporary file, so that readers never see
     * a half-written one.
     *
     * @param array<string, mixed> $data
     */
    public static function writeMarker(array $data): void
    {
        $temp = self::markerFile() . '.' . bin2hex(random_bytes(4)) . '.tmp';

        file_put_contents($temp, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        if (! @rename($temp, self::markerFile())) {
            @unlink($temp);

            throw new RuntimeException('Cannot write the demo marker ' . self::markerFile());
        }
    }

    /**
     * The demo has no real authentication (anyone can log in as any user,
     * administrators included), so it is never served in production unless
     * it was installed explicitly with --allow-production.
     */
    public static function isEnabled(): bool
    {
        return self::isInstalled()
            && (ENVIRONMENT !== 'production' || (self::marker()['allow_production'] ?? false) === true);
    }

    /**
     * Key used to mask the demo ids (see Config\CrudConfig). It is stored in the
     * install marker; older installs get one the first time it is needed.
     */
    public static function maskKey(): string
    {
        static $key = null;

        if ($key !== null) {
            return $key;
        }

        $marker = self::marker();

        // Not installed, or the marker cannot be read: never write a partial marker back
        if (! isset($marker['installed_at'])) {
            return '';
        }

        if (! is_string($marker['mask_key'] ?? null) || strlen($marker['mask_key']) !== 64) {
            $marker['mask_key'] = bin2hex(random_bytes(32));
            self::writeMarker($marker);
        }

        return $key = $marker['mask_key'];
    }

    /**
     * Demo settings, built once per request (masking many ids is then cheap).
     */
    public static function config(): CrudConfig
    {
        static $config = null;

        return $config ??= new CrudConfig();
    }

    /**
     * Masked id as it appears in the demo URLs. The context is the table name,
     * the same one the CRUD of that table uses.
     */
    public static function maskId(string $table, int|string $id): string
    {
        return Crud::maskId($id, $table, self::config());
    }

    /**
     * Real id of a masked code from a demo URL, or null if it is not valid.
     */
    public static function unmaskId(string $table, string $code): ?string
    {
        return Crud::unmaskId($code, $table, self::config());
    }

    /**
     * Resetting from the web drops and recreates tables: never in production.
     */
    public static function canResetFromWeb(): bool
    {
        return ENVIRONMENT !== 'production';
    }

    /**
     * Registers the demo routes. Called from src/Config/Routes.php, which
     * CodeIgniter discovers automatically in the Kpcrudy namespace, or from
     * the project's app/Config/Routes.php when route discovery is disabled.
     *
     * It does nothing if the demo is not installed or not enabled (see
     * isEnabled()), so the call is harmless after an uninstall.
     *
     * Every route has exactly one filter (multiple filters per route are
     * optional in CodeIgniter 4.4) and all of them extend CsrfFilter, so POST
     * requests are CSRF-protected even if the host project has not enabled
     * the 'csrf' filter globally.
     */
    public static function routes(RouteCollection $routes): void
    {
        if (! self::isEnabled()) {
            return;
        }

        $routes->group(self::PREFIX, ['namespace' => 'Kpcrudy\Demo\Controllers'], static function (RouteCollection $routes): void {
            // Public pages (CSRF only)
            $routes->group('', ['filter' => CsrfFilter::class], static function (RouteCollection $routes): void {
                $routes->get('login', 'Auth::login');
                $routes->post('login', 'Auth::attempt');
                $routes->post('logout', 'Auth::logout');

                // No login: the shared agenda only opens with a signed link (see Shared)
                Crud::routes($routes, 'shared/agenda', 'Shared::agenda');
            });

            // Any logged-in demo user
            $routes->group('', ['filter' => AuthFilter::class], static function (RouteCollection $routes): void {
                $routes->get('/', 'Home::index');

                Crud::routes($routes, 'agenda', 'Agenda::index');
                Crud::routes($routes, 'my/attendances', 'MyAttendances::index');
                Crud::routes($routes, 'profile', 'Profile::index');

                $routes->post('agenda-join/(:segment)', 'Agenda::join/$1');
                $routes->post('my/cancel/(:segment)', 'MyAttendances::cancel/$1');
            });

            // Demo administrators only
            $routes->group('', ['filter' => AdminFilter::class], static function (RouteCollection $routes): void {
                Crud::routes($routes, 'admin/users', 'Admin\Users::index');
                Crud::routes($routes, 'admin/categories', 'Admin\Categories::index');
                Crud::routes($routes, 'admin/events', 'Admin\Events::index');
                Crud::routes($routes, 'admin/attendances', 'Admin\Attendances::index');

                $routes->post('admin/events-duplicate/(:segment)', 'Admin\Events::duplicate/$1');

                if (self::canResetFromWeb()) {
                    $routes->post('admin/reset', 'Home::reset');
                }
            });
        });
    }
}
