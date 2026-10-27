<?php

declare(strict_types=1);

namespace Kpcrudy\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Kpcrudy\Config\Kpcrudy as KpcrudyConfig;
use Kpcrudy\Crud;
use RuntimeException;
use Throwable;

/**
 * Mostra el codi emmascarat d'un o més identificadors, tal com surt a les URL
 * d'un CRUD amb maskIds():
 *
 *     php spark kpcrudy:maskid invoices 5 6 7
 *
 * El context és el nom de la taula, o el que s'hagi passat a maskIds(context: ...).
 */
class MaskId extends BaseCommand
{
    protected $group       = 'Kpcrudy';
    protected $name        = 'kpcrudy:maskid';
    protected $description = 'Shows the masked code of one or more ids, as they appear in the URLs of a CRUD with maskIds().';
    protected $usage       = 'kpcrudy:maskid <context> <id> [<id>...]';
    protected $arguments   = [
        'context' => 'Table name, or the context passed to maskIds(context: ...)',
        'id'      => 'One or more real ids',
    ];
    protected $options = [
        '--config' => 'Config class passed to the CRUD, if it is not app/Config/Kpcrudy.php (e.g. "Kpcrudy\Demo\Config\CrudConfig" for the demo)',
    ];

    public function run(array $params)
    {
        return static::convert($params, 'mask');
    }

    /**
     * Comú a kpcrudy:maskid i kpcrudy:unmaskid.
     *
     * @param array<int|string, string> $params
     */
    public static function convert(array $params, string $direction): int
    {
        $values  = array_values(array_filter($params, static fn ($value, $key): bool => is_int($key), ARRAY_FILTER_USE_BOTH));
        $context = array_shift($values);

        if ($context === null || $values === []) {
            CLI::error('Usage: php spark kpcrudy:' . ($direction === 'mask' ? 'maskid <context> <id>...' : 'unmaskid <context> <code>...'));
            CLI::write('The context is the table name, or the value passed to maskIds(context: ...).');

            return EXIT_ERROR;
        }

        $rows  = [];
        $error = false;

        try {
            $config = static::config(CLI::getOption('config'));

            foreach ($values as $value) {
                $result = $direction === 'mask' ? Crud::maskId($value, $context, $config) : Crud::unmaskId($value, $context, $config);
                $error  = $error || $result === null;
                $rows[] = [$value, $result ?? CLI::color('invalid code for this context', 'red')];
            }
        } catch (Throwable $e) {
            CLI::error($e->getMessage());

            return EXIT_ERROR;
        }

        CLI::write('Context: ' . CLI::color($context, 'yellow'));
        CLI::table($rows, $direction === 'mask' ? ['Id', 'Masked code'] : ['Masked code', 'Id']);

        return $error ? EXIT_ERROR : EXIT_SUCCESS;
    }

    /**
     * Configuració indicada amb --config (null: la del projecte). També la fan servir kpcrudy:signurl i kpcrudy:checkurl.
     */
    public static function config(mixed $class): ?KpcrudyConfig
    {
        if (! is_string($class) || $class === '') {
            return null;
        }

        if (! is_a($class, KpcrudyConfig::class, true)) {
            throw new RuntimeException("The class \"{$class}\" does not exist or does not extend Kpcrudy\Config\Kpcrudy.");
        }

        return new $class();
    }
}
