<?php

declare(strict_types=1);

namespace Kpcrudy\Demo;

use CodeIgniter\Router\RouteCollection;
use Kpcrudy\Crud;
use Kpcrudy\Demo\Filters\AdminFilter;
use Kpcrudy\Demo\Filters\AuthFilter;
use Kpcrudy\Demo\Filters\CsrfFilter;

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
        return self::isInstalled()
            ? (json_decode((string) file_get_contents(self::markerFile()), true) ?: [])
            : [];
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
            });

            // Any logged-in demo user
            $routes->group('', ['filter' => AuthFilter::class], static function (RouteCollection $routes): void {
                $routes->get('/', 'Home::index');

                Crud::routes($routes, 'agenda', 'Agenda::index');
                Crud::routes($routes, 'my/attendances', 'MyAttendances::index');
                Crud::routes($routes, 'profile', 'Profile::index');

                $routes->post('agenda-join/(:num)', 'Agenda::join/$1');
                $routes->post('my/cancel/(:num)', 'MyAttendances::cancel/$1');
            });

            // Demo administrators only
            $routes->group('', ['filter' => AdminFilter::class], static function (RouteCollection $routes): void {
                Crud::routes($routes, 'admin/users', 'Admin\Users::index');
                Crud::routes($routes, 'admin/categories', 'Admin\Categories::index');
                Crud::routes($routes, 'admin/events', 'Admin\Events::index');
                Crud::routes($routes, 'admin/attendances', 'Admin\Attendances::index');

                $routes->post('admin/events-duplicate/(:num)', 'Admin\Events::duplicate/$1');

                if (self::canResetFromWeb()) {
                    $routes->post('admin/reset', 'Home::reset');
                }
            });
        });
    }
}
