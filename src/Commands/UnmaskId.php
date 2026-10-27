<?php

declare(strict_types=1);

namespace Kpcrudy\Commands;

use CodeIgniter\CLI\BaseCommand;

/**
 * Mostra l'identificador real d'un o més codis emmascarats (p. ex. el que
 * apareix en una URL o en un registre d'errors):
 *
 *     php spark kpcrudy:unmaskid invoices Xk3f9QaLm2PwE
 */
class UnmaskId extends BaseCommand
{
    protected $group       = 'Kpcrudy';
    protected $name        = 'kpcrudy:unmaskid';
    protected $description = 'Shows the real id of one or more masked codes taken from the URLs of a CRUD with maskIds().';
    protected $usage       = 'kpcrudy:unmaskid <context> <code> [<code>...]';
    protected $arguments   = [
        'context' => 'Table name, or the context passed to maskIds(context: ...)',
        'code'    => 'One or more masked codes',
    ];
    protected $options = [
        '--config' => 'Config class passed to the CRUD, if it is not app/Config/Kpcrudy.php (e.g. "Kpcrudy\Demo\Config\CrudConfig" for the demo)',
    ];

    public function run(array $params)
    {
        return MaskId::convert($params, 'unmask');
    }
}
