<?php

declare(strict_types=1);

namespace Kpcrudy\Commands;

use CodeIgniter\CLI\CLI;

/**
 * Opcions d'una ordre de spark tant si s'executa des de la línia d'ordres
 * (CLI::getOption()) com des del codi amb command('kpcrudy:... --opcio valor'),
 * que les passa a run($params).
 */
trait ReadsOptions
{
    /**
     * @var array<int|string, mixed>
     */
    protected array $params = [];

    /**
     * Valor d'una opció (null si no s'ha indicat o no té valor).
     */
    protected function option(string $name): mixed
    {
        if (array_key_exists($name, $this->params)) {
            return $this->params[$name];
        }

        return CLI::getOption($name);
    }

    /**
     * Si s'ha indicat una opció, amb valor o sense (p. ex. --force).
     */
    protected function hasOption(string $name): bool
    {
        return array_key_exists($name, $this->params) || array_key_exists($name, CLI::getOptions());
    }
}
