<?php

declare(strict_types=1);

namespace Kpcrudy\Demo\Config;

use Kpcrudy\Config\Kpcrudy;

/**
 * kpcrudy settings for the demo CRUDs, independent of the host project's
 * app/Config/Kpcrudy.php. Every demo controller passes it to the constructor:
 *
 *     $crud = new Crud(new CrudConfig());
 */
class CrudConfig extends Kpcrudy
{
    /**
     * Every CRUD is rendered inside the demo layout (navbar + code panel).
     */
    public string|false $layout = 'Kpcrudy\Demo\Views\layouts\main';

    /**
     * 'profile' only overrides show.php; the other templates fall back to bootstrap5.
     */
    public array $themes = [
        'profile' => __DIR__ . '/../Views/kpcrudy/profile',
    ];

    public int $perPage          = 10;
    public array $perPageOptions = [5, 10, 25, 50];
}
