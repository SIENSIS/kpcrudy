<?php

namespace Kpcrudy\Demo\Libraries;

use Kpcrudy\Crud;
use Kpcrudy\PermissionResolver;
use RuntimeException;

/**
 * Central permissions of the demo CRUDs (Config\CrudConfig::$permissionResolver).
 *
 * Every CRUD names itself with setResource() and kpcrudy asks this class
 * before each operation, button and bulk action. In a real project this is
 * where you would ask CodeIgniter Shield: auth()->user()->can("{$resource}.{$operation}").
 *
 *  - administrators: no opinion (null), so each CRUD's own setPermission()
 *    rules and hooks still apply;
 *  - editors: only the operations granted below;
 *  - users: nothing on these resources.
 *
 * CRUDs with another resource (agenda, profile...) are not affected.
 */
class DemoPermissions implements PermissionResolver
{
    /**
     * Permission catalogue: every operation each resource can ask about
     * (Crud::permissionCatalogue()), with the ones granted to editors.
     * Outside production, a CRUD that asks about an operation missing here
     * fails loudly (see check()), so the catalogue cannot get out of date.
     */
    private const RESOURCES = [
        'events' => [
            'catalogue' => ['list', 'show', 'add', 'edit', 'delete', 'export', 'import', 'search', 'filters', 'bulk',
                'trash', 'restore', 'purge', 'duplicate', 'attendees', 'publish', 'cancel'],
            'editor' => ['list', 'show', 'add', 'edit', 'search', 'filters', 'export', 'bulk', 'duplicate', 'publish'],
        ],
        'categories' => [
            'catalogue' => ['list', 'show', 'add', 'edit', 'delete', 'search', 'filters'],
            'editor'    => ['list', 'show', 'edit', 'search', 'filters'],
        ],
    ];

    /**
     * Resources already checked in this request.
     *
     * @var array<string, true>
     */
    private static array $checked = [];

    public function allows(string $resource, string $operation, ?array $row, Crud $crud): ?bool
    {
        if (! isset(self::RESOURCES[$resource])) {
            return null;
        }

        $this->check($resource, $crud);

        return match (Auth::role()) {
            'admin'  => null,
            'editor' => in_array($operation, self::RESOURCES[$resource]['editor'], true) && $this->ownsEvent($resource, $operation, $row),
            default  => false,
        };
    }

    /**
     * "Sees every record, but only changes their own": a decision per record,
     * which where() cannot express. (When users must only SEE their own
     * records, use where() instead: see MyAttendances.)
     */
    private function ownsEvent(string $resource, string $operation, ?array $row): bool
    {
        if ($resource !== 'events' || $row === null || ! in_array($operation, ['edit', 'publish'], true)) {
            return true;
        }

        return (int) ($row['created_by'] ?? 0) === Auth::id();
    }

    /**
     * Outside production: every operation the CRUD can ask about must be in the catalogue.
     */
    private function check(string $resource, Crud $crud): void
    {
        if (ENVIRONMENT === 'production' || isset(self::$checked[$resource])) {
            return;
        }

        self::$checked[$resource] = true;

        $missing = array_diff($crud->permissionCatalogue(), self::RESOURCES[$resource]['catalogue']);

        if ($missing !== []) {
            throw new RuntimeException("DemoPermissions: add '" . implode("', '", $missing) . "' to the catalogue of \"{$resource}\".");
        }
    }
}
