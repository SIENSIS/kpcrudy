<?php

declare(strict_types=1);

namespace Kpcrudy\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use FilesystemIterator;
use Kpcrudy\Demo\Demo as DemoModule;
use Kpcrudy\Demo\Installer;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Throwable;

/**
 * Instal·la la demo de kpcrudy al projecte on s'ha afegit la llibreria.
 *
 *     php spark kpcrudy:demo install
 *     php spark kpcrudy:demo status
 *     php spark kpcrudy:demo reset
 *     php spark kpcrudy:demo uninstall
 *
 * No copia ni modifica cap fitxer del projecte: la demo és un mòdul de la
 * llibreria (src/Demo). La instal·lació crea les taules kpdemo_*, les imatges
 * a public/uploads/kpcrudy-demo i un fitxer de marca a writable/ que activa
 * les rutes /kpcrudy-demo. La desinstal·lació ho esborra tot.
 *
 * La demo no té autenticació real: amb ENVIRONMENT = 'production', install i
 * reset es neguen a funcionar i les rutes no es registren, llevat que s'hagi
 * instal·lat amb --allow-production.
 *
 * Els missatges són en anglès, com la demo.
 */
class Demo extends BaseCommand
{
    protected $group       = 'Kpcrudy';
    protected $name        = 'kpcrudy:demo';
    protected $description = 'Installs, resets or removes the kpcrudy demo application.';
    protected $usage       = 'kpcrudy:demo <install|uninstall|reset|status> [options]';
    protected $arguments   = [
        'action' => 'install: creates tables, data and images | uninstall: removes everything | reset: back to the initial data | status: current state',
    ];
    protected $options = [
        '--force'            => 'install: drops the tables left by a previous installation and starts again',
        '--yes'              => 'Do not ask for confirmation (uninstall and reset)',
        '--allow-production' => 'install and reset: run even if ENVIRONMENT is "production"; install also serves the demo routes there (anyone can log in as an administrator!)',
    ];

    public function run(array $params)
    {
        $action = $params[0] ?? null;

        return match ($action) {
            'install'   => $this->install(),
            'uninstall' => $this->uninstall(),
            'reset'     => $this->reset(),
            'status'    => $this->status(),
            default     => $this->help(),
        };
    }

    private function install(): int
    {
        if (! $this->productionAllowed('install')) {
            return EXIT_ERROR;
        }

        $installer = new Installer();
        $existing  = $installer->existingTables();

        if (DemoModule::isInstalled() && ! $this->option('force')) {
            CLI::error('The demo is already installed.');
            CLI::write('Use "php spark kpcrudy:demo reset" to go back to the initial data.');

            return EXIT_ERROR;
        }

        if ($existing !== [] && ! $this->option('force')) {
            CLI::error('Some demo tables already exist: ' . implode(', ', $existing));
            CLI::write('Run it again with --force to drop them and start again.');

            return EXIT_ERROR;
        }

        foreach ([FCPATH => 'public/', WRITEPATH => 'writable/'] as $dir => $label) {
            if (! is_writable($dir)) {
                CLI::error("Cannot write to {$label}.");

                return EXIT_ERROR;
            }
        }

        if (! extension_loaded('gd')) {
            CLI::write('Warning: the GD extension is not available; the demo will be installed without sample images.', 'yellow');
        }

        CLI::write('Installing the kpcrudy demo...', 'yellow');

        try {
            if ($existing !== []) {
                $installer->uninstall();
            }

            $installer->install($this->option('allow-production'));
        } catch (Throwable $e) {
            CLI::error('The demo could not be installed: ' . $e->getMessage());
            CLI::write('Everything that had been created has been removed.');

            return EXIT_ERROR;
        }

        CLI::write('Done!', 'green');
        CLI::newLine();
        CLI::write('  Tables:   ' . implode(', ', DemoModule::TABLES));
        CLI::write('  Images:   ' . $this->relative(DemoModule::uploadPath()));
        CLI::write('  URL:      ' . CLI::color(DemoModule::url(), 'green'));
        CLI::newLine();

        if (! config('Modules')->shouldDiscover('routes')) {
            CLI::write('Warning: route discovery for modules is disabled in this project.', 'yellow');
            CLI::write('Add this line to app/Config/Routes.php to enable the demo:');
            CLI::write('    \Kpcrudy\Demo\Demo::routes($routes);', 'light_gray');
            CLI::write('It only registers routes while the demo is installed (and never in production');
            CLI::write('unless installed with --allow-production), but remove it if you uninstall the demo.');
            CLI::newLine();
        }

        if (ENVIRONMENT === 'production') {
            CLI::write('Warning: the demo routes are active in PRODUCTION. Anyone can log in as an administrator.', 'red');
            CLI::write('Uninstall it as soon as you are done: php spark kpcrudy:demo uninstall', 'red');
            CLI::newLine();
        }

        CLI::write('To remove it: php spark kpcrudy:demo uninstall');

        return EXIT_SUCCESS;
    }

    private function uninstall(): int
    {
        $installer = new Installer();
        $existing  = $installer->existingTables();

        if (! DemoModule::isInstalled() && $existing === [] && ! is_dir(DemoModule::uploadPath())) {
            CLI::write('The demo is not installed: there is nothing to remove.');

            return EXIT_SUCCESS;
        }

        CLI::write('The demo will be removed:', 'yellow');
        CLI::write('  - Tables: ' . ($existing === [] ? 'none' : implode(', ', $existing)));
        CLI::write('  - Files:  ' . $this->relative(DemoModule::uploadPath()));
        CLI::write('  - Routes: /' . DemoModule::PREFIX);

        if (! $this->confirm('Continue?')) {
            CLI::write('Cancelled.');

            return EXIT_SUCCESS;
        }

        try {
            $installer->uninstall();
        } catch (Throwable $e) {
            CLI::error('The demo could not be removed: ' . $e->getMessage());

            return EXIT_ERROR;
        }

        CLI::write('The demo has been removed. The project is back to how it was.', 'green');

        if (! config('Modules')->shouldDiscover('routes')) {
            CLI::write('If you added "\Kpcrudy\Demo\Demo::routes($routes);" to app/Config/Routes.php, you can remove it now', 'yellow');
            CLI::write('(it no longer registers any route).', 'yellow');
        }

        return EXIT_SUCCESS;
    }

    private function reset(): int
    {
        if (! DemoModule::isInstalled()) {
            CLI::error('The demo is not installed. Use "php spark kpcrudy:demo install".');

            return EXIT_ERROR;
        }

        if (! $this->productionAllowed('reset')) {
            return EXIT_ERROR;
        }

        if (! $this->confirm('All changes made in the demo will be lost. Continue?')) {
            CLI::write('Cancelled.');

            return EXIT_SUCCESS;
        }

        try {
            (new Installer())->reset();
        } catch (Throwable $e) {
            CLI::error('The demo could not be reset: ' . $e->getMessage());

            return EXIT_ERROR;
        }

        CLI::write('The demo data has been reset: ' . DemoModule::url(), 'green');

        return EXIT_SUCCESS;
    }

    private function status(): int
    {
        $installer = new Installer();
        $existing  = $installer->existingTables();
        $db        = db_connect();

        CLI::write('kpcrudy demo', 'yellow');
        CLI::write('  Installed: ' . (DemoModule::isInstalled() ? CLI::color('yes', 'green') : CLI::color('no', 'red')));

        if (DemoModule::isInstalled()) {
            $info = DemoModule::marker();
            CLI::write('  Date:      ' . ($info['installed_at'] ?? '?'));
            CLI::write('  URL:       ' . DemoModule::url());
            CLI::write('  Routes:    ' . (DemoModule::isEnabled()
                ? CLI::color('active', 'green') . (ENVIRONMENT === 'production' ? CLI::color(' (in production: --allow-production)', 'red') : '')
                : CLI::color('disabled in production (install with --allow-production to serve it)', 'yellow')));
        }

        CLI::write('  Tables:');

        foreach (DemoModule::TABLES as $table) {
            $exists = in_array($table, $existing, true);
            CLI::write('    ' . str_pad($table, 24) . ($exists ? $db->table($table)->countAllResults() . ' rows' : CLI::color('missing', 'light_gray')));
        }

        CLI::write('  Files:     ' . (is_dir(DemoModule::uploadPath()) ? $this->countFiles(DemoModule::uploadPath()) . ' in ' . $this->relative(DemoModule::uploadPath()) : 'none'));

        if (! DemoModule::isInstalled() && $existing !== []) {
            CLI::write('There are tables from an incomplete installation: "php spark kpcrudy:demo uninstall" removes them.', 'yellow');
        }

        return EXIT_SUCCESS;
    }

    private function help(): int
    {
        $this->showHelp();

        return EXIT_ERROR;
    }

    /**
     * La demo no té autenticació real: en producció cal demanar-ho explícitament.
     */
    private function productionAllowed(string $action): bool
    {
        if (ENVIRONMENT !== 'production') {
            return true;
        }

        if (! $this->option('allow-production')) {
            CLI::error("Refusing to {$action} the demo: ENVIRONMENT is \"production\".");
            CLI::write('The demo has no real authentication: anyone can log in as an administrator.', 'yellow');
            CLI::write('Install it in a development environment, or run it again with --allow-production', 'yellow');
            CLI::write('if you really want it in this environment.', 'yellow');

            return false;
        }

        CLI::write('Warning: running in PRODUCTION (--allow-production). The demo has no real authentication.', 'red');

        return true;
    }

    private function option(string $name): bool
    {
        return CLI::getOption($name) !== null || array_key_exists($name, CLI::getOptions());
    }

    private function confirm(string $question): bool
    {
        return $this->option('yes') || CLI::prompt($question, ['n', 'y']) === 'y';
    }

    private function relative(string $path): string
    {
        return str_replace([ROOTPATH, '\\'], ['', '/'], $path);
    }

    private function countFiles(string $dir): int
    {
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));

        return iterator_count($files);
    }
}
