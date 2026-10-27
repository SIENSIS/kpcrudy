<?php

declare(strict_types=1);

namespace Kpcrudy\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use FilesystemIterator;
use Kpcrudy\Config\Kpcrudy as KpcrudyConfig;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use Throwable;

/**
 * Copia al projecte la configuració, un tema o els textos d'un idioma de la
 * llibreria, per adaptar-los sense tocar vendor/:
 *
 *     php spark kpcrudy:publish config
 *     php spark kpcrudy:publish theme bootstrap5 --as admin
 *     php spark kpcrudy:publish theme w3css --as intranet --only list,partials/pagination
 *     php spark kpcrudy:publish lang ca
 *
 * No sobreescriu mai res sense --force, i amb --dry-run només diu què faria.
 */
class Publish extends BaseCommand
{
    protected $group       = 'Kpcrudy';
    protected $name        = 'kpcrudy:publish';
    protected $description = 'Copies the configuration, a theme or the texts of a language into the project, to adapt them.';
    protected $usage       = 'kpcrudy:publish [config|theme|lang] [name] [options]';
    protected $arguments   = [
        'what' => 'config, theme or lang. If omitted, it is asked.',
        'name' => 'theme: the theme to copy (bootstrap5, w3css, tailwind or one of $themes). lang: the locale (ca, es, en, fr, pt, de).',
    ];
    protected $options = [
        '--as'      => 'theme: name of the new theme (default: <theme>-custom)',
        '--only'    => 'theme: copy only these templates, separated by commas (e.g. list,partials/pagination); the rest is taken from the original theme',
        '--path'    => 'theme: destination folder (default: app/Views/kpcrudy/<name>)',
        '--force'   => 'Overwrite the files that already exist',
        '--dry-run' => 'Show what would be done without writing anything',
    ];

    /**
     * Temes inclosos a la llibreria (vegeu Crud::renderer()).
     */
    protected const BUILTIN = ['bootstrap5', 'w3css', 'tailwind'];

    protected bool $force = false;
    protected bool $dryRun = false;

    /**
     * Fitxers creats o que es crearien, per al resum final.
     *
     * @var list<string>
     */
    protected array $written = [];

    public function run(array $params)
    {
        $this->force  = CLI::getOption('force') !== null;
        $this->dryRun = CLI::getOption('dry-run') !== null;
        $what         = $params[0] ?? CLI::prompt('What do you want to publish?', ['config', 'theme', 'lang'], 'required');

        try {
            $result = match ($what) {
                'config' => $this->publishConfig(true),
                'theme'  => $this->publishTheme($params[1] ?? null),
                'lang'   => $this->publishLang($params[1] ?? null),
                default  => throw new RuntimeException("Unknown option \"{$what}\": use config, theme or lang."),
            };
        } catch (Throwable $e) {
            CLI::error($e->getMessage());

            return EXIT_ERROR;
        }

        if ($this->dryRun) {
            CLI::write('Dry run: nothing has been written.', 'yellow');
        }

        return $result;
    }

    // ------------------------------------------------------------------
    // Configuració
    // ------------------------------------------------------------------

    /**
     * app/Config/Kpcrudy.php amb totes les opcions de la llibreria, comentades,
     * en una classe que l'estén: les opcions que s'hi afegeixin en versions
     * noves continuen tenint el valor per defecte.
     */
    protected function publishConfig(bool $explicit): int
    {
        $target = APPPATH . 'Config' . DIRECTORY_SEPARATOR . 'Kpcrudy.php';

        if (is_file($target) && ! $this->force) {
            if ($explicit) {
                CLI::write('app/Config/Kpcrudy.php already exists (use --force to replace it).', 'yellow');
            }

            return EXIT_SUCCESS;
        }

        $this->write($target, $this->configSource());

        if ($explicit) {
            $this->summary([
                'Edit app/Config/Kpcrudy.php: config(\'Kpcrudy\') now returns this class.',
                'It contains every option with its default value and comment; change only what you need.',
            ]);
        }

        return EXIT_SUCCESS;
    }

    protected function configSource(): string
    {
        $source = (string) file_get_contents(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR . 'Kpcrudy.php');

        $header = <<<'TXT'
            /**
             * Configuració de kpcrudy per a aquest projecte (creada amb php spark kpcrudy:publish config).
             *
             * Estén la de la llibreria: aquí hi ha totes les opcions amb el valor per defecte
             * i el comentari, com a referència. Canvia només el que necessitis (o esborra les
             * opcions que no toquis: agafaran el valor de la llibreria, també en versions noves).
             */
            TXT;

        $replacements = [
            'namespace Kpcrudy\Config;'           => 'namespace Config;',
            "use CodeIgniter\\Config\\BaseConfig;\n\n" => '',
            'class Kpcrudy extends BaseConfig'    => 'class Kpcrudy extends \Kpcrudy\Config\Kpcrudy',
        ];

        foreach ($replacements as $search => $replace) {
            if (! str_contains($source, $search)) {
                throw new RuntimeException('The library configuration file has an unexpected format: cannot generate app/Config/Kpcrudy.php.');
            }

            $source = str_replace($search, $replace, $source);
        }

        // El comentari de la classe de la llibreria es canvia pel del projecte
        return (string) preg_replace('#/\*\*.*?\*/\s*(?=class Kpcrudy)#s', $header . "\n", $source, 1);
    }

    // ------------------------------------------------------------------
    // Temes
    // ------------------------------------------------------------------

    protected function publishTheme(?string $theme): int
    {
        $themes = $this->themes();
        $theme ??= CLI::prompt('Theme to copy', array_keys($themes), 'required');

        if (! isset($themes[$theme])) {
            throw new RuntimeException("The theme \"{$theme}\" does not exist. Themes: " . implode(', ', array_keys($themes)) . '.');
        }

        $as = (string) (CLI::getOption('as') ?? $theme . '-custom');

        if (! preg_match('/^[a-z][a-z0-9_-]*$/', $as)) {
            throw new RuntimeException("Invalid theme name \"{$as}\": use lowercase letters, numbers, - and _.");
        }

        if (in_array($as, self::BUILTIN, true)) {
            throw new RuntimeException("\"{$as}\" is a theme of the library: choose another name with --as.");
        }

        $source = $themes[$theme]['path'];
        $files  = $this->templates($source);
        $only   = CLI::getOption('only');

        if (is_string($only) && $only !== '') {
            $wanted = array_map(static fn (string $name): string => preg_replace('/\.php$/', '', trim($name, " /\\")), explode(',', $only));
            $unknown = array_diff($wanted, $files);

            if ($unknown !== []) {
                throw new RuntimeException('Unknown templates: ' . implode(', ', $unknown) . '. Templates of ' . $theme . ': ' . implode(', ', $files) . '.');
            }

            $files = array_values(array_intersect($files, $wanted));
        }

        $partial = count($files) < count($this->templates($source));
        $path    = rtrim((string) (CLI::getOption('path') ?? APPPATH . 'Views/kpcrudy/' . $as), '/\\');

        // Còpia sencera: el pare és el del tema original (les plantilles que amplien
        // el pare amb renderParent() continuen funcionant igual). Còpia parcial:
        // el pare és el tema original, que dona les plantilles que no s'han copiat.
        $parent = $partial ? $theme : ($themes[$theme]['parent'] ?? 'bootstrap5');

        foreach ($files as $file) {
            $content = (string) file_get_contents($source . DIRECTORY_SEPARATOR . $file . '.php');

            // En una còpia parcial, el pare d'aquesta plantilla és la mateixa plantilla del tema
            // original: en lloc de duplicar-la (i el seu CSS), se'n fa una ampliació
            if ($partial && str_contains($content, 'renderParent(')) {
                $content = $this->extensionStub($theme, $file);
            }

            $this->write($path . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $file) . '.php', $content);
        }

        $this->registerTheme($as, $path, $parent);

        $this->summary([
            "Theme \"{$as}\": " . count($files) . ' template(s) in ' . $this->relative($path) . ($partial ? " (the rest comes from \"{$theme}\")" : '') . '.',
            "Use it in a CRUD with ->setTheme('{$as}'), or for every CRUD with \$theme = '{$as}' in app/Config/Kpcrudy.php.",
            'Each template documents at the top the variables it receives. See docs/new_theme_CA.md / new_theme_EN.md.',
        ]);

        return EXIT_SUCCESS;
    }

    /**
     * Temes disponibles: els de la llibreria i els de la configuració del projecte.
     *
     * @return array<string, array{path: string, parent: string|null}>
     */
    protected function themes(): array
    {
        $views  = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Views' . DIRECTORY_SEPARATOR;
        $themes = [];

        foreach (self::BUILTIN as $name) {
            $themes[$name] = ['path' => $views . $name, 'parent' => $name === 'bootstrap5' ? null : 'bootstrap5'];
        }

        $config = config('Kpcrudy');

        if ($config instanceof KpcrudyConfig) {
            foreach ($config->themes as $name => $entry) {
                $themes[$name] = is_array($entry)
                    ? ['path' => (string) $entry['path'], 'parent' => $entry['parent'] ?? 'bootstrap5']
                    : ['path' => (string) $entry, 'parent' => 'bootstrap5'];
            }
        }

        return $themes;
    }

    /**
     * Plantilles d'un tema, sense ".php" i amb "/" (p. ex. "list", "partials/pagination").
     *
     * @return list<string>
     */
    protected function templates(string $dir): array
    {
        if (! is_dir($dir)) {
            throw new RuntimeException("The theme folder \"{$dir}\" does not exist.");
        }

        $files = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $relative = substr($file->getPathname(), strlen(rtrim($dir, '/\\')) + 1);
                $files[]  = str_replace('\\', '/', substr($relative, 0, -4));
            }
        }

        sort($files);

        return $files;
    }

    protected function extensionStub(string $theme, string $file): string
    {
        return <<<PHP
            <?php
            /**
             * Amplia la plantilla "{$file}" del tema "{$theme}" (creada amb php spark kpcrudy:publish).
             * L'original es manté a la llibreria; aquí només s'hi afegeix el que vulguis.
             *
             * @var \\Kpcrudy\\Renderer \$this
             */
            ?>
            <?= \$this->renderParent('{$file}', get_defined_vars()) ?>
            <style>
                /* Els teus estils */
            </style>

            PHP;
    }

    /**
     * Afegeix el tema a $themes d'app/Config/Kpcrudy.php (i crea aquest fitxer si no hi és).
     */
    protected function registerTheme(string $name, string $path, string $parent): void
    {
        $target = APPPATH . 'Config' . DIRECTORY_SEPARATOR . 'Kpcrudy.php';
        $entry  = "'{$name}' => ['path' => " . $this->pathExpression($path) . ", 'parent' => '{$parent}'],";

        // Si encara no hi ha configuració al projecte, es crea ja amb el tema registrat
        $source  = is_file($target) ? (string) file_get_contents($target) : $this->configSource();
        $pattern = '/(public\s+array\s+\$themes\s*=\s*\[)(.*?)(\]\s*;)/s';

        // Només es mira dins de l'array (el comentari de la propietat també té exemples de temes)
        if (preg_match($pattern, $source, $m) && preg_match('/[\'"]' . preg_quote($name, '/') . '[\'"]\s*=>/', $m[2])) {
            CLI::write("The theme \"{$name}\" is already registered in app/Config/Kpcrudy.php: check its path and parent.", 'yellow');

            return;
        }

        $updated = preg_replace_callback(
            $pattern,
            static fn (array $m): string => $m[1] . rtrim($m[2]) . "\n        " . $entry . "\n    " . $m[3],
            $source,
            1,
            $count,
        );

        if ($updated === null || $count !== 1) {
            CLI::write('Add this line to $themes in app/Config/Kpcrudy.php:', 'yellow');
            CLI::write('    ' . $entry);

            return;
        }

        $this->write($target, $updated, true);
    }

    protected function pathExpression(string $path): string
    {
        $app  = rtrim(str_replace('\\', '/', APPPATH), '/') . '/';
        $norm = str_replace('\\', '/', $path);

        return str_starts_with($norm, $app)
            ? "APPPATH . '" . substr($norm, strlen($app)) . "'"
            : var_export($norm, true);
    }

    // ------------------------------------------------------------------
    // Idiomes
    // ------------------------------------------------------------------

    protected function publishLang(?string $locale): int
    {
        $dir     = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Language';
        $locales = array_values(array_filter(scandir($dir) ?: [], static fn (string $name): bool => $name[0] !== '.' && is_dir($dir . DIRECTORY_SEPARATOR . $name)));
        $locale ??= CLI::prompt('Language', $locales, 'required');

        if (! in_array($locale, $locales, true)) {
            throw new RuntimeException("The language \"{$locale}\" does not exist. Languages: " . implode(', ', $locales) . '.');
        }

        $content = (string) file_get_contents($dir . DIRECTORY_SEPARATOR . $locale . DIRECTORY_SEPARATOR . 'Kpcrudy.php');
        $content = preg_replace(
            '/^<\?php\s*/',
            "<?php\n\n// Textos de kpcrudy per a aquest projecte (creats amb php spark kpcrudy:publish lang {$locale}).\n"
            . "// Tenen preferència sobre els de la llibreria. Pots esborrar les línies que no canviïs:\n"
            . "// es faran servir les de la llibreria, també els textos nous de versions futures.\n\n",
            $content,
            1,
        );

        $target = APPPATH . 'Language' . DIRECTORY_SEPARATOR . $locale . DIRECTORY_SEPARATOR . 'Kpcrudy.php';

        if (is_file($target) && ! $this->force) {
            CLI::write($this->relative($target) . ' already exists (use --force to replace it).', 'yellow');

            return EXIT_SUCCESS;
        }

        $this->write($target, (string) $content);

        $this->summary([
            'Change the texts in ' . $this->relative($target) . ': they take precedence over the library ones.',
        ]);

        return EXIT_SUCCESS;
    }

    // ------------------------------------------------------------------
    // Utilitats
    // ------------------------------------------------------------------

    /**
     * Escriu un fitxer (o diu que l'escriuria, amb --dry-run). Sense --force no
     * sobreescriu cap fitxer existent, excepte la configuració en registrar un tema.
     */
    protected function write(string $file, string $content, bool $update = false): void
    {
        $exists = is_file($file);

        if ($exists && ! $this->force && ! $update) {
            CLI::write('  skip      ' . $this->relative($file) . ' (it exists; use --force to replace it)', 'yellow');

            return;
        }

        CLI::write('  ' . str_pad($exists ? 'update' : 'create', 10) . $this->relative($file), 'green');
        $this->written[] = $file;

        if ($this->dryRun) {
            return;
        }

        if (! is_dir(dirname($file)) && ! @mkdir(dirname($file), 0777, true) && ! is_dir(dirname($file))) {
            throw new RuntimeException('Cannot create the folder ' . dirname($file));
        }

        // Es desa a part i es reanomena: un error a mig camí no deixa el fitxer a mitges
        $temp = $file . '.' . bin2hex(random_bytes(4)) . '.tmp';

        if (file_put_contents($temp, $content) === false || ! @rename($temp, $file)) {
            @unlink($temp);

            throw new RuntimeException('Cannot write ' . $file);
        }
    }

    /**
     * @param list<string> $lines
     */
    protected function summary(array $lines): void
    {
        CLI::newLine();

        foreach ($lines as $line) {
            CLI::write($line);
        }
    }

    protected function relative(string $path): string
    {
        $root = rtrim(str_replace('\\', '/', ROOTPATH), '/') . '/';
        $norm = str_replace('\\', '/', $path);

        return str_starts_with($norm, $root) ? substr($norm, strlen($root)) : $norm;
    }
}
