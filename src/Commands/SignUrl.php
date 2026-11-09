<?php

declare(strict_types=1);

namespace Kpcrudy\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Kpcrudy\Crud;
use Throwable;

/**
 * Genera una URL signada, p. ex. per enviar-la per correu:
 *
 *     php spark kpcrudy:signurl shared/agenda --ttl 604800 --free-query
 *     php spark kpcrudy:signurl "invoices/download?file=2026-001.pdf" --ttl 3600
 */
class SignUrl extends BaseCommand
{
    use ReadsOptions;

    protected $group       = 'Kpcrudy';
    protected $name        = 'kpcrudy:signurl';
    protected $description = 'Signs a URL so that it can be opened without changes (and, optionally, only until it expires).';
    protected $usage       = 'kpcrudy:signurl <url> [options]';
    protected $arguments   = [
        'url' => 'Absolute URL or path relative to the project (e.g. "shared/agenda/show/Xk3f9QaLm2PwE7")',
    ];
    protected $options = [
        '--ttl'        => 'Seconds until the link expires (default: 86400 = 1 day; 0 = never)',
        '--free-query' => 'Sign only the path: the query string (?q=, ?page=...) can change. Use it for CRUD pages with requireSignature()',
        '--config'     => 'Config class passed to the CRUD, if it is not app/Config/Kpcrudy.php (e.g. "Kpcrudy\Demo\Config\CrudConfig" for the demo)',
    ];

    public function run(array $params)
    {
        $this->params = $params;
        $url = $params[0] ?? null;
        $ttl = $this->option('ttl') ?? '86400';

        if (! is_string($url) || $url === '') {
            CLI::error('Usage: php spark kpcrudy:signurl <url> [--ttl seconds] [--free-query] [--config Class]');

            return EXIT_ERROR;
        }

        if (! is_string($ttl) || ! ctype_digit($ttl)) {
            CLI::error('--ttl must be a number of seconds (0 = the link never expires).');

            return EXIT_ERROR;
        }

        try {
            $signed = Crud::signUrl($url, $ttl === '0' ? null : (int) $ttl, ! $this->hasOption('free-query'), MaskId::config($this->option('config')));
        } catch (Throwable $e) {
            CLI::error($e->getMessage());

            return EXIT_ERROR;
        }

        CLI::write($signed, 'green');
        CLI::write($ttl === '0' ? 'It never expires.' : 'Valid until ' . date('Y-m-d H:i:s', time() + (int) $ttl) . '.');

        if (! $this->hasOption('free-query')) {
            CLI::write('The whole URL is signed: changing any parameter makes it invalid.');
        }

        return EXIT_SUCCESS;
    }
}
