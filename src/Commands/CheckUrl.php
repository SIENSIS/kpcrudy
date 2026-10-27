<?php

declare(strict_types=1);

namespace Kpcrudy\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Kpcrudy\UrlSigner;
use Throwable;

/**
 * Comprova la signatura d'una URL (p. ex. la d'un usuari que diu que el seu
 * enllaç no funciona) i en mostra la caducitat.
 *
 *     php spark kpcrudy:checkurl "http://localhost:8080/shared/agenda?kp_exp=...&kp_sig=..."
 */
class CheckUrl extends BaseCommand
{
    protected $group       = 'Kpcrudy';
    protected $name        = 'kpcrudy:checkurl';
    protected $description = 'Checks the signature of a URL and shows when it expires.';
    protected $usage       = 'kpcrudy:checkurl <url> [options]';
    protected $arguments   = [
        'url' => 'Signed URL (absolute or relative to the project). Quote it: it contains "&".',
    ];
    protected $options = [
        '--config' => 'Config class passed to the CRUD, if it is not app/Config/Kpcrudy.php (e.g. "Kpcrudy\Demo\Config\CrudConfig" for the demo)',
    ];

    public function run(array $params)
    {
        $url = $params[0] ?? null;

        if (! is_string($url) || $url === '') {
            CLI::error('Usage: php spark kpcrudy:checkurl "<url>" [--config Class]');

            return EXIT_ERROR;
        }

        try {
            $config  = MaskId::config(CLI::getOption('config')) ?? config('Kpcrudy');
            $signer  = new UrlSigner($config);
            // Les URL que signa un CRUD mentre es navega tenen un abast propi ('crud', 'crud-trash')
            $status  = $signer->checkUrl($url, true, ['', 'crud', 'crud-trash']);
            $matched = $signer->matched();
        } catch (Throwable $e) {
            CLI::error($e->getMessage());

            return EXIT_ERROR;
        }

        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
        $expires = isset($query['kp_exp']) && is_string($query['kp_exp']) && ctype_digit($query['kp_exp']) ? (int) $query['kp_exp'] : null;

        $message = match ($status) {
            UrlSigner::VALID   => CLI::color('Valid signature', 'green') . ' (' . static::describe($matched) . ')',
            UrlSigner::EXPIRED => CLI::color('Expired', 'yellow') . ' (the signature is correct: ' . static::describe($matched) . ')',
            UrlSigner::MISSING => CLI::color('Not signed', 'red') . ' (no kp_sig parameter)',
            default            => CLI::color('Invalid signature', 'red') . ' (modified URL, another key or another project)',
        };

        CLI::write($message);

        if ($expires !== null && in_array($status, [UrlSigner::VALID, UrlSigner::EXPIRED], true)) {
            CLI::write(($status === UrlSigner::VALID ? 'Expires: ' : 'Expired: ') . date('Y-m-d H:i:s', $expires));
        } elseif ($status === UrlSigner::VALID) {
            CLI::write('It never expires.');
        }

        return $status === UrlSigner::VALID ? EXIT_SUCCESS : EXIT_ERROR;
    }

    /**
     * @param array{mode: string, scope: string}|null $matched
     */
    protected static function describe(?array $matched): string
    {
        $what = ($matched['mode'] ?? '') === 'full' ? 'path and parameters' : 'path only: the parameters can change';
        $who  = match ($matched['scope'] ?? '') {
            'crud'       => 'signed by a CRUD while browsing; only that CRUD accepts it',
            'crud-trash' => 'signed by a CRUD from its trash page; only that CRUD accepts it',
            default      => 'signed with signUrl() or signedUrl()',
        };

        return $what . '; ' . $who;
    }
}
