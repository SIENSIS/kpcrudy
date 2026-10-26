<?php

declare(strict_types=1);

namespace Kpcrudy\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use CodeIgniter\Controller;
use Kpcrudy\Crud;
use Kpcrudy\Generator\CrudGenerator;
use ReflectionClass;
use RuntimeException;
use Throwable;

/**
 * Genera la funció de controlador i la ruta d'un CRUD a partir d'una taula.
 *
 *     php spark kpcrudy:make                      (pregunta les dades)
 *     php spark kpcrudy:make clients              (amb els valors per defecte)
 *     php spark kpcrudy:make clients --controller Admin/Clients --route admin/clients --filter auth --model
 *
 * Els missatges són en anglès, com la resta de comandes de la llibreria.
 */
class MakeCrud extends BaseCommand
{
    protected $group       = 'Kpcrudy';
    protected $name        = 'kpcrudy:make';
    protected $description = 'Generates the controller method and route of a CRUD from a database table.';
    protected $usage       = 'kpcrudy:make [table] [options]';
    protected $arguments   = [
        'table' => 'Database table. If omitted, every value is asked interactively.',
    ];
    protected $options = [
        '--controller' => 'Controller class, relative to App\Controllers (e.g. Admin/Clients). Created if it does not exist.',
        '--method'     => 'Controller method (default: index)',
        '--route'      => 'Route path (e.g. admin/clients)',
        '--filter'     => 'Route filter (e.g. auth, session, auth:admin)',
        '--model'      => 'Also generate a model in App\Models and use setModel()',
        '--dry-run'    => 'Show the generated code without writing any file',
        '--force'      => 'Replace the method (and the model) if they already exist',
    ];

    public function run(array $params)
    {
        $generator   = new CrudGenerator(db_connect());
        $interactive = ! isset($params[0]) || $params[0] === '';

        try {
            $tables = $generator->tables();
        } catch (Throwable $e) {
            CLI::error('Cannot read the database: ' . $e->getMessage());

            return EXIT_ERROR;
        }

        if ($tables === []) {
            CLI::error('The database has no tables.');

            return EXIT_ERROR;
        }

        // 1. Dades: arguments, opcions o preguntes
        $table = $interactive ? $this->askTable($tables) : $params[0];

        if (! in_array($table, $tables, true)) {
            CLI::error("The table \"{$table}\" does not exist.");

            return EXIT_ERROR;
        }

        // El nom de la taula acaba dins del codi generat: només lletres, números i _
        if (! CrudGenerator::isSafeName($table)) {
            CLI::error("The table name \"{$table}\" cannot be used: only letters, numbers and _ are allowed (and it cannot start with a number).");

            return EXIT_ERROR;
        }

        $controller = $this->value('controller', $interactive, 'Controller (relative to App\Controllers)', $this->className($table));
        $controller = implode('/', array_map('ucfirst', preg_split('#[/\\\\]+#', trim($controller, '/\\'))));
        $method     = $this->value('method', $interactive, 'Method', 'index');
        $route      = trim($this->value('route', $interactive, 'Route', $this->routeFor($controller, $method)), '/');
        $filter     = $this->value('filter', $interactive, 'Route filter (empty for none)', '');
        $withModel  = $this->flag('model') || ($interactive && CLI::prompt('Generate a model too?', ['n', 'y']) === 'y');
        $dryRun     = $this->flag('dry-run');
        $force      = $this->flag('force');

        if ($error = $this->validate($controller, $method, $route)) {
            CLI::error($error);

            return EXIT_ERROR;
        }

        // 2. Codi (analyze() rebutja les columnes amb noms que no es poden posar al codi)
        $segments   = explode('/', $controller);
        $class      = array_pop($segments);
        $namespace  = rtrim('App\Controllers\\' . implode('\\', $segments), '\\');
        $path       = APPPATH . 'Controllers/' . $controller . '.php';
        $modelClass = $this->modelClass($table);
        $handler    = str_replace('/', '\\', $controller) . '::' . $method;

        try {
            $info                = $generator->analyze($table);
            [$singular, $plural] = $generator->subjectFor($table);

            // Si el controlador ja importa una altra classe Crud (o el model), s'escriu el nom complet
            $source   = is_file($path) ? (string) file_get_contents($path) : null;
            $crudRef  = $generator->classReference($source, $class, 'Kpcrudy\Crud');
            $modelRef = $withModel ? $generator->classReference($source, $class, 'App\Models\\' . $modelClass) : null;
            $imports  = [];

            if ($crudRef[0] !== '\\') {
                $imports[] = 'Kpcrudy\Crud';
            }

            if ($modelRef !== null && $modelRef[0] !== '\\') {
                $imports[] = 'App\Models\\' . $modelClass;
            }

            $methodCode = $generator->methodCode($info, [
                'method'  => $method,
                'subject' => $singular,
                'plural'  => $plural,
                'model'   => $modelRef,
                'crud'    => $crudRef,
            ]);
            $routeLine = $generator->routeLine($route, $handler, $filter);
            $modelCode = $withModel ? $generator->modelCode($info, $modelClass, 'App\Models') : null;
        } catch (Throwable $e) {
            CLI::error($e->getMessage());

            return EXIT_ERROR;
        }

        $this->summary($info);

        if ($dryRun) {
            $this->show('Controller ' . $namespace . '\\' . $class, $methodCode);

            if ($modelCode !== null) {
                $this->show('Model App\Models\\' . $modelClass, $modelCode);
            }

            $this->show('app/Config/Routes.php', $routeLine);
        }

        // 3. Comprovacions: tot abans d'escriure cap fitxer, perquè un error no deixi feina a mitges
        $writes   = [];
        $messages = [];

        try {
            if ($modelCode !== null) {
                $modelsDir = APPPATH . 'Models';
                $modelPath = $modelsDir . DIRECTORY_SEPARATOR . $modelClass . '.php';

                if (is_dir($modelsDir) && realpath(dirname($modelPath)) !== realpath($modelsDir)) {
                    throw new RuntimeException("Invalid model path {$modelPath}.");
                }

                if (is_file($modelPath) && ! $force) {
                    $modelTable = $generator->modelTable($modelPath);

                    if ($modelTable !== $table) {
                        throw new RuntimeException("The model {$modelClass} already exists and is not for the table \"{$table}\""
                            . ($modelTable !== null ? " (it uses \"{$modelTable}\")" : '') . '. Use --force to replace it, or generate the CRUD without --model.');
                    }

                    $messages[] = ["The model {$modelClass} already exists: it has not been changed (use --force to replace it).", 'yellow'];
                } else {
                    $writes[$modelPath] = $modelCode;
                    $messages[]         = ['Model:      ' . CLI::color('app/Models/' . $modelClass . '.php', 'green'), null];
                }
            }

            [$status, $controllerCode] = $generator->controllerSource($path, $namespace, $class, $method, $methodCode, $imports, $force);
            $writes[$path]             = $controllerCode;
            $messages[]                = ['Controller: ' . CLI::color('app/Controllers/' . $controller . '.php', 'green') . " ({$status} {$method}())", null];

            $routesFile = APPPATH . 'Config/Routes.php';
            $existing   = $this->existingRoute($route);

            if ($existing !== null && ! $this->sameHandler($existing, $handler)) {
                throw new RuntimeException("The route \"{$route}\" already exists and points to {$existing}. Use --route to choose another path.");
            }

            $routesCode = $existing === null ? $generator->routesSource($routesFile, $route, $routeLine) : null;

            if ($routesCode !== null) {
                $writes[$routesFile] = $routesCode;
                $messages[]          = ['Route:      ' . CLI::color($routeLine, 'green'), null];
            } else {
                $messages[] = ["Route:      \"{$route}\" was already in app/Config/Routes.php", 'yellow'];
            }
        } catch (Throwable $e) {
            CLI::error($e->getMessage());

            return EXIT_ERROR;
        }

        if ($dryRun) {
            CLI::write('Dry run: no files have been written.', 'yellow');

            return EXIT_SUCCESS;
        }

        // 4. Fitxers
        try {
            foreach ($writes as $file => $code) {
                $generator->writeFile($file, $code);
            }
        } catch (Throwable $e) {
            CLI::error($e->getMessage());

            return EXIT_ERROR;
        }

        foreach ($messages as [$message, $color]) {
            CLI::write($message, $color);
        }

        CLI::newLine();
        CLI::write('Open ' . CLI::color(site_url($route), 'green') . ' and adapt the generated code to your needs.');

        return EXIT_SUCCESS;
    }

    // ------------------------------------------------------------------
    // Dades d'entrada
    // ------------------------------------------------------------------

    /**
     * @param list<string> $tables
     */
    private function askTable(array $tables): string
    {
        CLI::write('Tables:', 'yellow');

        foreach ($tables as $i => $table) {
            CLI::write('  [' . ($i + 1) . '] ' . $table);
        }

        $answer = trim(CLI::prompt('Table (number or name)'));

        return ctype_digit($answer) ? ($tables[(int) $answer - 1] ?? '') : $answer;
    }

    private function value(string $option, bool $interactive, string $question, string $default): string
    {
        $value = CLI::getOption($option);

        if (is_string($value) && $value !== '') {
            return $value;
        }

        return $interactive ? trim((string) CLI::prompt($question, $default !== '' ? $default : null)) : $default;
    }

    private function flag(string $name): bool
    {
        return array_key_exists($name, CLI::getOptions());
    }

    private function validate(string $controller, string $method, string $route): ?string
    {
        if (! preg_match('#^[A-Z][A-Za-z0-9_]*(/[A-Z][A-Za-z0-9_]*)*$#', $controller)) {
            return "Invalid controller name \"{$controller}\".";
        }

        if (strcasecmp(basename($controller), 'BaseController') === 0) {
            return 'The controller cannot be BaseController.';
        }

        // Sense "_" al davant (CodeIgniter no els enruta: _remap...) ni noms de mètodes del controlador base
        if (! preg_match('/^[a-z][A-Za-z0-9_]*$/', $method)) {
            return "Invalid method name \"{$method}\": it must start with a lowercase letter and contain only letters, numbers and _.";
        }

        if (in_array(strtolower($method), $this->reservedMethods(), true)) {
            return "Invalid method name \"{$method}\": the base controller already has a method with that name.";
        }

        if (! preg_match('#^[a-z0-9][a-z0-9_\-/]*$#', $route)) {
            return "Invalid route \"{$route}\": use lowercase letters, numbers, - , _ and /.";
        }

        $last = basename($route);

        if (in_array($last, [...Crud::OPERATIONS, ...Crud::ID_OPERATIONS], true)) {
            return "The route cannot end with \"{$last}\": the CRUD uses that word as an operation.";
        }

        return null;
    }

    /**
     * Mètodes (en minúscules) de CodeIgniter\Controller i de BaseController.
     *
     * @return list<string>
     */
    private function reservedMethods(): array
    {
        $names = [];

        foreach ([Controller::class, 'App\Controllers\BaseController'] as $parent) {
            if (! class_exists($parent)) {
                continue;
            }

            foreach ((new ReflectionClass($parent))->getMethods() as $reflection) {
                $names[] = strtolower($reflection->getName());
            }
        }

        return array_values(array_unique($names));
    }

    /**
     * Handler de la ruta GET que ja existeix per a aquest camí (també les que
     * defineixen altres fitxers o mòduls), o null si no n'hi ha cap o no es poden carregar.
     */
    private function existingRoute(string $route): ?string
    {
        try {
            $routes = service('routes')->loadRoutes();

            foreach (['GET', 'get'] as $verb) {
                foreach ($routes->getRoutes($verb) as $key => $handler) {
                    if (trim((string) $key, '/') === $route) {
                        return is_string($handler) ? $handler : 'a closure';
                    }
                }
            }
        } catch (Throwable) {
            // Si les rutes no es poden carregar, només es fa la comprovació del fitxer
        }

        return null;
    }

    private function sameHandler(string $existing, string $handler): bool
    {
        $existing = strtolower(ltrim($existing, '\\'));
        $handler  = strtolower($handler);

        return $existing === $handler || str_ends_with($existing, '\\' . $handler);
    }

    // ------------------------------------------------------------------
    // Sortida
    // ------------------------------------------------------------------

    /**
     * @param array<string, mixed> $info
     */
    private function summary(array $info): void
    {
        CLI::write("Table {$info['table']}: " . count($info['fields']) . ' fields, primary key ' . $info['pk'], 'yellow');

        foreach ($info['relations'] as $field => $relation) {
            CLI::write("  1-N  {$field} -> {$relation['table']} ({$relation['display']})");
        }

        foreach ($info['nn'] as $name => $relation) {
            CLI::write("  N-N  {$name} -> {$relation['table']} through {$relation['junction']}");
        }

        foreach ($info['unique'] as $field) {
            CLI::write("  unique  {$field}");
        }

        CLI::newLine();
    }

    private function show(string $title, string $code): void
    {
        CLI::write('--- ' . $title . ' ---', 'yellow');
        CLI::write(rtrim($code));
        CLI::newLine();
    }

    // ------------------------------------------------------------------
    // Noms
    // ------------------------------------------------------------------

    private function studly(string $text): string
    {
        return str_replace(' ', '', ucwords(str_replace(['_', '-'], ' ', strtolower($text))));
    }

    /**
     * Nom del model: "kpdemo_events" => "KpdemoEventModel". Un nom de classe
     * no pot començar amb un número: "_2024_sales" => "T2024SaleModel".
     */
    private function modelClass(string $table): string
    {
        return $this->className($this->singularTable($table)) . 'Model';
    }

    private function className(string $text): string
    {
        $class = $this->studly($text);

        return preg_match('/^[A-Z]/', $class) ? $class : 'T' . $class;
    }

    private function singularTable(string $table): string
    {
        return (string) preg_replace(['/ies$/', '/(ss|sh|ch|x)es$/', '/(?<!s)s$/'], ['y', '$1', ''], $table, 1);
    }

    private function routeFor(string $controller, string $method): string
    {
        $path = strtolower((string) preg_replace('/(?<!^|\/)[A-Z]/', '-$0', $controller));

        return $method === 'index' ? $path : $path . '/' . str_replace('_', '-', strtolower($method));
    }
}
