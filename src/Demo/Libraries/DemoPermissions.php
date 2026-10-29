<?php

namespace Kpcrudy\Demo\Libraries;

use Kpcrudy\Crud;
use Kpcrudy\PermissionResolver;

/**
 * Central permissions of the demo CRUDs (Config\CrudConfig::$permissionResolver).
 *
 * Every CRUD names itself with setResource() and kpcrudy asks this class
 * before each operation, button and bulk action. In a real project this is
 * where you would ask CodeIgniter Shield: auth()->user()->can("{$resource}.{$operation}").
 *
 *  - administrators: no opinion (null), so each CRUD's own setPermission()
 *    rules and hooks still apply;
 *  - editors: only the operations listed below, and they can only change
 *    the events they created;
 *  - users: nothing on these resources.
 *
 * CRUDs with another resource (agenda, profile...) are not affected.
 */
class DemoPermissions implements PermissionResolver
{
    /**
     * resource => operations an editor can do. Custom operations, actions and
     * bulk actions are checked with their 'permission' option (by default, their key).
     */
    private const EDITOR = [
        'events'     => ['list', 'show', 'add', 'edit', 'search', 'filters', 'export', 'bulk', 'duplicate', 'publish'],
        'categories' => ['list', 'show', 'edit', 'search', 'filters'],
    ];

    public function allows(string $resource, string $operation, ?array $row, Crud $crud): ?bool
    {
        if (! isset(self::EDITOR[$resource])) {
            return null;
        }

        return match (Auth::role()) {
            'admin'  => null,
            'editor' => in_array($operation, self::EDITOR[$resource], true) && $this->ownsEvent($resource, $operation, $row),
            default  => false,
        };
    }

    /**
     * Editors can see every event, but only edit (or publish) their own.
     */
    private function ownsEvent(string $resource, string $operation, ?array $row): bool
    {
        if ($resource !== 'events' || $row === null || ! in_array($operation, ['edit', 'publish'], true)) {
            return true;
        }

        return (int) ($row['created_by'] ?? 0) === Auth::id();
    }
}
