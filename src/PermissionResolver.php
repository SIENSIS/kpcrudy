<?php

declare(strict_types=1);

namespace Kpcrudy;

/**
 * Decideix els permisos de tots els CRUD d'un projecte des d'un sol lloc
 * (p. ex. a partir dels rols o dels permisos de l'usuari a CodeIgniter Shield).
 *
 * Es configura a Config\Kpcrudy::$permissionResolver (per a tots els CRUD) o
 * amb Crud::setPermissionResolver() (per a un de sol).
 *
 * El resolutor té l'última paraula: si denega, setPermission() no pot
 * tornar a permetre; si permet o no opina (null), les regles de setPermission()
 * encara poden restringir.
 */
interface PermissionResolver
{
    /**
     * @param string                    $resource  Identificador del CRUD (setResource(); per defecte, el nom de la taula)
     * @param string                    $operation Operació (list, show, add, edit, delete, export, import, trash,
     *                                             restore, purge, search, filters, bulk) o el permís d'una acció
     *                                             o operació pròpia (opció 'permission')
     * @param array<string, mixed>|null $row       Registre afectat, o null si la comprovació és general
     *                                             (p. ex. per mostrar el botó "Afegeix")
     *
     * @return bool|null true permet, false denega, null no opina (decideixen setPermission()
     *                   i Config\Kpcrudy::$denyByDefault)
     */
    public function allows(string $resource, string $operation, ?array $row, Crud $crud): ?bool;
}
