<?php

declare(strict_types=1);

namespace Kpcrudy\Demo;

use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\Migration;
use Config\Database;
use FilesystemIterator;
use Kpcrudy\Demo\Database\Seeds\DemoSeeder;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Throwable;

/**
 * Installs and removes the demo in the host project.
 *
 * It does not use CodeIgniter's migration history on purpose: "migrate:rollback"
 * rolls back every namespace, so the demo migrations are run here directly and
 * the project's migrations are never touched.
 */
final class Installer
{
    private BaseConnection $db;

    public function __construct()
    {
        $this->db = db_connect();
    }

    /**
     * Demo tables that already exist in the database.
     *
     * @return list<string>
     */
    public function existingTables(): array
    {
        // Without cache, tableExists() does not add the DBPrefix: add it here
        return array_values(array_filter(Demo::TABLES, fn (string $table): bool => $this->db->tableExists($this->db->prefixTable($table), false)));
    }

    /**
     * Creates the tables, seeds the data and images, and writes the marker
     * that enables the routes. On failure, everything created is removed.
     *
     * @param bool $allowProduction Serve the demo routes even when ENVIRONMENT is 'production'
     */
    public function install(bool $allowProduction = false): void
    {
        // public/uploads may not exist yet: if the demo creates it, uninstall removes it
        $createdUploadsRoot = $this->marker()['created_uploads_root'] ?? ! is_dir(dirname(Demo::uploadPath()));

        try {
            $this->createAndSeed();

            $this->writeMarker([
                'installed_at'         => date('c'),
                'url'                  => Demo::url(),
                'tables'               => Demo::TABLES,
                'uploads'              => str_replace('\\', '/', Demo::uploadPath()),
                'created_uploads_root' => $createdUploadsRoot,
                'allow_production'     => $allowProduction,
            ]);
        } catch (Throwable $e) {
            $this->uninstall();

            throw $e;
        }
    }

    /**
     * Drops the demo tables, deletes the uploaded files and the marker.
     * It works even if the installation was left half done.
     *
     * @param bool $keepMarker Keep the marker (the demo stays installed), used by reset()
     */
    public function uninstall(bool $keepMarker = false): void
    {
        $marker = $this->marker();

        foreach (array_reverse($this->migrations()) as $migration) {
            $migration->down();
        }

        $this->deleteDirectory(Demo::uploadPath());

        $uploadsRoot = dirname(Demo::uploadPath());

        if (($marker['created_uploads_root'] ?? false) && is_dir($uploadsRoot) && (scandir($uploadsRoot) ?: []) === ['.', '..']) {
            rmdir($uploadsRoot);
        }

        if (! $keepMarker && is_file(Demo::markerFile())) {
            unlink(Demo::markerFile());
        }
    }

    /**
     * Back to the initial data.
     *
     * The marker is kept all the time, also when it fails: the demo stays
     * installed (with its routes and its options) and the reset can be retried.
     */
    public function reset(): void
    {
        $marker = $this->marker();

        $this->uninstall(true);

        try {
            $this->createAndSeed();
        } catch (Throwable $e) {
            // Remove what was half created, but not the marker
            $this->uninstall(true);

            throw $e;
        }

        $this->writeMarker(array_merge($marker, ['reset_at' => date('c')]));
    }

    private function createAndSeed(): void
    {
        foreach ($this->migrations() as $migration) {
            $migration->up();
        }

        Database::seeder()->setSilent(true)->call(DemoSeeder::class);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function writeMarker(array $data): void
    {
        file_put_contents(Demo::markerFile(), json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    /**
     * @return array<string, mixed>
     */
    private function marker(): array
    {
        return Demo::marker();
    }

    /**
     * @return list<Migration>
     */
    private function migrations(): array
    {
        $files = glob(__DIR__ . '/Database/Migrations/*.php') ?: [];
        sort($files);

        $migrations = [];

        foreach ($files as $file) {
            require_once $file;

            // 2026-10-22-000001_CreateCategories.php => CreateCategories
            $class        = __NAMESPACE__ . '\\Database\\Migrations\\' . preg_replace('/^[\d-]+_/', '', basename($file, '.php'));
            $migrations[] = new $class();
        }

        return $migrations;
    }

    private function deleteDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        $items = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($items as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }

        rmdir($dir);
    }
}
