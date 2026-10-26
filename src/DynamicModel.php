<?php

declare(strict_types=1);

namespace Kpcrudy;

use CodeIgniter\Database\ConnectionInterface;
use CodeIgniter\Model;

/**
 * Model creat en temps d'execució quan el CRUD treballa directament amb
 * un nom de taula. Així es reaprofiten els timestamps i els soft deletes
 * de CodeIgniter sense haver d'escriure cap classe.
 */
class DynamicModel extends Model
{
    /**
     * @param array<string, mixed> $settings Propietats del model (table, primaryKey, allowedFields...)
     */
    public function __construct(array $settings, ?ConnectionInterface $db = null)
    {
        foreach ($settings as $property => $value) {
            $this->{$property} = $value;
        }

        parent::__construct($db);
    }
}
