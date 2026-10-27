<?php

declare(strict_types=1);

namespace Kpcrudy\Demo\Config;

use Kpcrudy\Config\Kpcrudy;
use Kpcrudy\Demo\Demo;
use RuntimeException;

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

    /**
     * Every demo URL uses masked ids (admin/events/edit/Xk3f9... instead of edit/5).
     */
    public bool $maskIds = true;

    public function __construct()
    {
        parent::__construct();

        // The demo has its own masking key, stored in the install marker, so that
        // uninstalling it leaves no key behind in the host project
        $this->maskKey = Demo::maskKey();

        // Without the demo key, kpcrudy would fall back to the host project's key
        // (or create writable/kpcrudy/mask.key), which uninstall could not remove
        if ($this->maskKey === '') {
            throw new RuntimeException('The kpcrudy demo is not installed (php spark kpcrudy:demo install).');
        }
    }
}
