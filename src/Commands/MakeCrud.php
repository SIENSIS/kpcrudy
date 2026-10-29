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
 *     php spark kpcrudy:make posts --namespace Blog --route admin/posts --as blog-posts
 *
 * Per defecte genera al namespace App (app/Controllers, app/Models,
 * app/Config/Routes.php). Amb --namespace genera dins d'un mòdul o paquet:
 * <ns>\Controllers, <ns>\Models i <directori del ns>/Config/Routes.php.
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
        '--namespace'  => 'Base namespace of the app or module (default: App). Controllers go to <ns>\Controllers, models to <ns>\Models',
        '--path'       => 'Directory of the base namespace (default: taken from the autoloader, e.g. app/ for App)',
        '--routes'     => 'Routes file (default: <path>/Config/Routes.php). Created if it does not exist',
        '--extends'    => 'Parent class of a new controller (default: <ns>\Controllers\BaseController, App\Controllers\BaseController or CodeIgniter\Controller)',
        '--controller' => 'Controller class, relative to <ns>\Controllers (e.g. Admin/Clients). Created if it does not exist.',
        '--method'     => 'Controller method (default: index)',
        '--route'      => 'Route path (e.g. admin/clients)',
        '--filter'     => 'Route filter (e.g. auth, session, auth:admin)',
        '--as'         => 'Route name, for route_to() and url_to() (e.g. admin-clients)',
        '--model'      => 'Also generate a model in <ns>\Models and use setModel()',
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

        // Namespace i directori de destinació (l'aplicació o un mòdul)
        $baseNs = trim($this->value('namespace', false, '', 'App'), '\\');

        if (! preg_match('/^[A-Z][A-Za-z0-9_]*(\\\\[A-Z][A-Za-z0-9_]*)*$/', $baseNs)) {
            CLI::error("Invalid namespace \"{$baseNs}\".");

            return EXIT_ERROR;
        }

        $basePath = $this->basePath($baseNs);

        if ($basePath === null) {
            CLI::error("Cannot find the directory of the namespace {$baseNs}. Register it in Config\\Autoload::\$psr4 or use --path.");

            return EXIT_ERROR;
        }

        $routesFile = $this->value('routes', false, '', '');
        $routesFile = $routesFile !== '' ? $this->absolute($routesFile) : $basePath . 'Config' . DIRECTORY_SEPARATOR . 'Routes.php';
        $extends    = ltrim($this->value('extends', false, '', $this->defaultParent($baseNs)), '\\');

        if (! preg_match('/^[A-Za-z_][A-Za-z0-9_]*(\\\\[A-Za-z_][A-Za-z0-9_]*)*$/', $extends)) {
            CLI::error("Invalid parent class \"{$extends}\".");

            return EXIT_ERROR;
        }

        $controller = $this->value('controller', $interactive, "Controller (relative to {$baseNs}\\Controllers)", $this->className($table));
        $controller = implode('/', array_map('ucfirst', preg_split('#[/\\\\]+#', trim($controller, '/\\'))));
        $method     = $this->value('method', $interactive, 'Method', 'index');
        $route      = trim($this->value('route', $interactive, 'Route', $this->routeFor($controller, $method)), '/');
        $filter     = $this->value('filter', $interactive, 'Route filter (empty for none)', '');
        $as         = $this->value('as', $interactive, 'Route name (empty for none)', '');
        $withModel  = $this->flag('model') || ($interactive && CLI::prompt('Generate a model too?', ['n', 'y']) === 'y');
        $dryRun     = $this->flag('dry-run');
        $force      = $this->flag('force');

        if ($error = $this->validate($controller, $method, $route, $as, $extends)) {
            CLI::error($error);

            return EXIT_ERROR;
        }

        // 2. Codi (analyze() rebutja les columnes amb noms que no es poden posar al codi)
        $segments   = explode('/', $controller);
        $class      = array_pop($segments);
        $namespace  = rtrim($baseNs . '\\Controllers\\' . implode('\\', $segments), '\\');
        $path       = $basePath . 'Controllers' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $controller) . '.php';
        $modelNs    = $baseNs . '\\Models';
        $modelClass = $this->modelClass($table);
        // Fora de App, el handler porta el nom complet: el namespace per defecte de les rutes és App\Controllers
        $handler = $baseNs === 'App'
            ? str_replace('/', '\\', $controller) . '::' . $method
            : '\\' . $namespace . '\\' . $class . '::' . $method;

        try {
            $info                = $generator->analyze($table);
            [$singular, $plural] = $generator->subjectFor($table);

            // Si el controlador ja importa una altra classe Crud (o el model), s'escriu el nom complet
            $source   = is_file($path) ? (string) file_get_contents($path) : null;
            $crudRef  = $generator->classReference($source, $class, 'Kpcrudy\Crud');
            $modelRef = $withModel ? $generator->classReference($source, $class, $modelNs . '\\' . $modelClass) : null;
            $imports  = [];

            if ($crudRef[0] !== '\\') {
                $imports[] = 'Kpcrudy\Crud';
            }

            if ($modelRef !== null && $modelRef[0] !== '\\') {
                $imports[] = $modelNs . '\\' . $modelClass;
            }

            $methodCode = $generator->methodCode($info, [
                'method'  => $method,
                'subject' => $singular,
                'plural'  => $plural,
                'model'   => $modelRef,
                'crud'    => $crudRef,
            ]);
            $routeLine = $generator->routeLine($route, $handler, $filter, $as);
            $modelCode = $withModel ? $generator->modelCode($info, $modelClass, $modelNs) : null;
        } catch (Throwable $e) {
            CLI::error($e->getMessage());

            return EXIT_ERROR;
        }

        $this->summary($info);

        if ($dryRun) {
            $this->show('Controller ' . $namespace . '\\' . $class, $methodCode);

            if ($modelCode !== null) {
                $this->show('Model ' . $modelNs . '\\' . $modelClass, $modelCode);
            }

            $this->show($this->relative($routesFile), $routeLine);
        }

        // 3. Comprovacions: tot abans d'escriure cap fitxer, perquè un error no deixi feina a mitges
        $writes   = [];
        $messages = [];

        try {
            if ($modelCode !== null) {
                $modelsDir = $basePath . 'Models';
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
                    $messages[]         = ['Model:      ' . CLI::color($this->relative($modelPath), 'green'), null];
                }
            }

            [$status, $controllerCode] = $generator->controllerSource($path, $namespace, $class, $method, $methodCode, $imports, $force, $extends);
            $writes[$path]             = $controllerCode;
            $messages[]                = ['Controller: ' . CLI::color($this->relative($path), 'green') . " ({$status} {$method}())", null];

            $existing = $this->existingRoute($route);

            if ($existing !== null && ! $this->sameHandler($existing, $handler)) {
                throw new RuntimeException("The route \"{$route}\" already exists and points to {$existing}. Use --route to choose another path.");
            }

            $routesCode = $existing === null ? $generator->routesSource($routesFile, $route, $routeLine) : null;

            if ($routesCode !== null) {
                $writes[$routesFile] = $routesCode;
                $messages[]          = ['Route:      ' . CLI::color($routeLine, 'green'), null];
            } else {
                $messages[] = ["Route:      \"{$route}\" was already in " . $this->relative($routesFile), 'yellow'];
            }

            if ($routesCode !== null && ! is_file($routesFile) && $baseNs !== 'App') {
                $messages[] = ['Created ' . $this->relative($routesFile) . ': CodeIgniter loads it if route discovery is enabled (Config\\Modules) or if app/Config/Routes.php includes it.', 'yellow'];
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

    private function validate(string $controller, string $method, string $route, string $as, string $extends): ?string
    {
        if (! preg_match('#^[A-Z][A-Za-z0-9_]*(/[A-Z][A-Za-z0-9_]*)*$#', $controller)) {
            return "Invalid controller name \"{$controller}\".";
        }

        if (strcasecmp(basename($controller), 'BaseController') === 0) {
            return 'The controller cannot be BaseController.';
        }

        if ($as !== '' && ! preg_match('/^[A-Za-z0-9][A-Za-z0-9_.\-]*$/', $as)) {
            return "Invalid route name \"{$as}\": use letters, numbers, -, _ and .";
        }

        // Sense "_" al davant (CodeIgniter no els enruta: _remap...) ni noms de mètodes del controlador base
        if (! preg_match('/^[a-z][A-Za-z0-9_]*$/', $method)) {
            return "Invalid method name \"{$method}\": it must start with a lowercase letter and contain only letters, numbers and _.";
        }

        if (in_array(strtolower($method), $this->reservedMethods($extends), true)) {
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
     * Mètodes (en minúscules) de CodeIgniter\Controller i de la classe pare del controlador.
     *
     * @return list<string>
     */
    private function reservedMethods(string $extends): array
    {
        $names = [];

        foreach ([Controller::class, $extends] as $parent) {
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
        $handler  = strtolower(ltrim($handler, '\\'));

        return $existing === $handler || str_ends_with($existing, '\\' . $handler);
    }

    /**
     * Directori (amb / final) del namespace base: --path, el de l'autoloader
     * o, per a App, APPPATH. null si no es troba.
     */
    private function basePath(string $namespace): ?string
    {
        $path = $this->value('path', false, '', '');

        if ($path === '') {
            $path = $namespace === 'App' ? APPPATH : '';

            // El namespace registrat més llarg que el conté (Acme\Blog dins d'Acme => src/Blog)
            try {
                $registered = service('autoloader')->getNamespace();
            } catch (Throwable) {
                $registered = [];
            }

            $parts = explode('\\', $namespace);

            for ($i = count($parts); $i > 0 && $namespace !== 'App'; $i--) {
                $prefix = implode('\\', array_slice($parts, 0, $i));

                if (isset($registered[$prefix][0])) {
                    $path = rtrim($registered[$prefix][0], '/\\') . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, array_slice($parts, $i));
                    break;
                }
            }
        } else {
            $path = $this->absolute($path);
        }

        if ($path === '') {
            return null;
        }

        // Sense ../ (els missatges mostren el camí relatiu al projecte)
        $path = is_dir($path) ? (string) realpath($path) : $path;

        return rtrim($path, '/\\') . DIRECTORY_SEPARATOR;
    }

    /**
     * Classe pare per defecte dels controladors nous: el BaseController del
     * namespace, el de l'aplicació o CodeIgniter\Controller.
     */
    private function defaultParent(string $namespace): string
    {
        foreach ([$namespace . '\\Controllers\\BaseController', 'App\Controllers\BaseController'] as $class) {
            if (class_exists($class)) {
                return $class;
            }
        }

        return Controller::class;
    }

    /**
     * Camí absolut: els relatius ho són al directori del projecte (ROOTPATH).
     */
    private function absolute(string $path): string
    {
        return preg_match('#^([A-Za-z]:)?[/\\\\]#', $path) ? $path : ROOTPATH . $path;
    }

    /**
     * Camí relatiu al directori del projecte si n'és a dins (per als missatges).
     */
    private function relative(string $path): string
    {
        $root = rtrim(ROOTPATH, '/\\') . DIRECTORY_SEPARATOR;

        return str_starts_with($path, $root) ? str_replace('\\', '/', substr($path, strlen($root))) : $path;
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
