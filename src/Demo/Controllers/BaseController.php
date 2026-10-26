<?php

namespace Kpcrudy\Demo\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\CLIRequest;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Kpcrudy\Demo\Config\Showcase;
use Kpcrudy\Demo\Libraries\Auth;
use Psr\Log\LoggerInterface;
use ReflectionMethod;

/**
 * @property CLIRequest|IncomingRequest $request
 */
abstract class BaseController extends Controller
{
    protected $helpers = ['url', 'form'];

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        // The demo is written in English, whatever the host project's locale is
        service('language')->setLocale('en');

        $this->publishShowcase();
    }

    /**
     * Logged-in user (the auth filter guarantees there is one).
     */
    protected function user(): array
    {
        return Auth::user() ?? [];
    }

    /**
     * Renders a regular view inside the demo layout.
     *
     * @param array<string, mixed> $data
     */
    protected function page(string $view, array $data, string $title): string
    {
        return view('Kpcrudy\Demo\Views\layouts\main', ['kpcrudy' => view($view, $data), 'title' => $title]);
    }

    /**
     * Demo only: if the current controller method is listed in
     * Config\Showcase, publishes its source code and features so that the
     * layout can show them in the "Code" panel.
     */
    private function publishShowcase(): void
    {
        $router   = service('router');
        $method   = $router->methodName();
        $features = config(Showcase::class)->pages[ltrim($router->controllerName(), '\\') . '::' . $method] ?? null;

        if ($features === null || ! method_exists($this, $method)) {
            return;
        }

        $reflection = new ReflectionMethod($this, $method);
        $lines      = file($reflection->getFileName());
        $start      = $reflection->getStartLine() - 1;

        // Include the docblock above the method, if any
        $doc = $reflection->getDocComment();
        if ($doc !== false) {
            $start -= substr_count($doc, "\n") + 1;
        }

        $code    = array_slice($lines, $start, $reflection->getEndLine() - $start);
        $imports = array_filter($lines, static fn (string $line): bool => str_starts_with($line, 'use ') && ! str_contains($line, 'BaseController'));

        // Path relative to the kpcrudy package: src/Demo/Controllers/...
        $package = str_replace('\\', '/', dirname(__DIR__, 3)) . '/';

        service('renderer')->setVar('showcase', [
            'file'     => str_replace($package, '', str_replace('\\', '/', $reflection->getFileName())),
            'method'   => $reflection->getDeclaringClass()->getShortName() . '::' . $method . '()',
            'features' => $features,
            'code'     => rtrim(implode('', $imports)) . "\n\n// ...\n\n" . $this->dedent(implode('', $code)),
        ]);
    }

    private function dedent(string $code): string
    {
        $lines  = explode("\n", rtrim($code));
        $indent = PHP_INT_MAX;

        foreach ($lines as $line) {
            if (trim($line) !== '') {
                $indent = min($indent, strlen($line) - strlen(ltrim($line)));
            }
        }

        return implode("\n", array_map(static fn (string $line): string => substr($line, min($indent, strlen($line))), $lines));
    }
}
