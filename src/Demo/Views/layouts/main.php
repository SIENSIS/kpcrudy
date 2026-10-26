<?php
/**
 * Demo layout. Every CRUD is rendered here (Config\Kpcrudy::$layout).
 *
 * @var string                    $kpcrudy   HTML of the CRUD or of the page
 * @var string                    $title
 * @var array<string, mixed>|null $showcase  Set by BaseController::showcase()
 */

use Kpcrudy\Demo\Demo;
use Kpcrudy\Demo\Libraries\Auth;

$user     = Auth::user();
$isAdmin  = Auth::isAdmin();
$showcase ??= null;
$path     = substr(uri_string(), strlen(Demo::PREFIX) + 1);
$active   = static fn (string $prefix): string => ($prefix === '' ? $path === '' : str_starts_with($path, $prefix)) ? ' active' : '';
$message  = session()->getFlashdata('message');
$error    = session()->getFlashdata('error');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc($title) ?> · kpcrudy demo</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/github.min.css">
    <style>
        .navbar-avatar { width: 28px; height: 28px; object-fit: cover; }
        #showcase { --bs-offcanvas-width: min(760px, 95vw); }
        #showcase pre { font-size: .8rem; max-height: 70vh; }
        main { min-height: calc(100vh - 120px); }
    </style>
</head>
<body class="bg-body-tertiary">
<nav class="navbar navbar-expand-lg bg-dark" data-bs-theme="dark">
    <div class="container-fluid px-lg-5">
        <a class="navbar-brand fw-semibold" href="<?= Demo::url() ?>"><i class="bi bi-grid-3x3-gap"></i> kpcrudy <span class="text-body-secondary fw-normal">demo</span></a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav" aria-label="Menu"><span class="navbar-toggler-icon"></span></button>

        <div class="collapse navbar-collapse" id="nav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link<?= $active('') ?>" href="<?= Demo::url() ?>">Home</a></li>
                <?php if ($isAdmin): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle<?= $active('admin') ?>" href="#" data-bs-toggle="dropdown">Administration</a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?= Demo::url('admin/users') ?>"><i class="bi bi-people"></i> Users</a></li>
                            <li><a class="dropdown-item" href="<?= Demo::url('admin/categories') ?>"><i class="bi bi-tags"></i> Categories</a></li>
                            <li><a class="dropdown-item" href="<?= Demo::url('admin/events') ?>"><i class="bi bi-calendar-event"></i> Events</a></li>
                            <li><a class="dropdown-item" href="<?= Demo::url('admin/attendances') ?>"><i class="bi bi-calendar-check"></i> Attendance</a></li>
                        </ul>
                    </li>
                <?php endif ?>
                <li class="nav-item"><a class="nav-link<?= $active('agenda') ?>" href="<?= Demo::url('agenda') ?>">Agenda</a></li>
                <li class="nav-item"><a class="nav-link<?= $active('my/') ?>" href="<?= Demo::url('my/attendances') ?>">My registrations</a></li>
            </ul>

            <div class="d-flex align-items-center gap-2">
                <?php if ($showcase !== null): ?>
                    <button class="btn btn-sm btn-outline-warning" type="button" data-bs-toggle="offcanvas" data-bs-target="#showcase">
                        <i class="bi bi-code-slash"></i> Code for this page
                    </button>
                <?php endif ?>

                <?php if ($user !== null): ?>
                    <div class="dropdown">
                        <a href="#" class="nav-link text-light dropdown-toggle d-flex align-items-center gap-2" data-bs-toggle="dropdown">
                            <?php if ($user['avatar']): ?>
                                <img src="<?= Demo::uploadUrl('avatars') . '/' . rawurlencode($user['avatar']) ?>" alt="" class="rounded-circle navbar-avatar">
                            <?php else: ?>
                                <i class="bi bi-person-circle fs-5"></i>
                            <?php endif ?>
                            <?= esc($user['first_name']) ?>
                            <?php if ($isAdmin): ?><span class="badge text-bg-warning">admin</span><?php endif ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="<?= Demo::url('profile') ?>"><i class="bi bi-person"></i> My profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="post" action="<?= Demo::url('logout') ?>">
                                    <?= csrf_field() ?>
                                    <button class="dropdown-item" type="submit"><i class="bi bi-box-arrow-right"></i> Log out</button>
                                </form>
                            </li>
                        </ul>
                    </div>
                <?php endif ?>
            </div>
        </div>
    </div>
</nav>

<main class="container-fluid py-4 px-lg-5">
    <?php if ($message): ?>
        <div class="alert alert-success alert-dismissible fade show"><?= esc($message) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif ?>
    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show"><?= esc($error) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif ?>

    <?= $kpcrudy ?>
</main>

<footer class="text-center text-body-secondary small py-3">
    <a href="https://github.com/SIENSIS/kpcrudy" class="link-secondary">kpcrudy</a> demo
    <?php if (ENVIRONMENT !== 'production'): // Exact versions only help attackers in production ?>
        · CodeIgniter <?= esc(\CodeIgniter\CodeIgniter::CI_VERSION) ?> · PHP <?= esc(PHP_VERSION) ?>
    <?php endif ?>
</footer>

<?php if ($showcase !== null): ?>
    <div class="offcanvas offcanvas-end" tabindex="-1" id="showcase" aria-labelledby="showcase-title">
        <div class="offcanvas-header border-bottom">
            <div>
                <h5 class="offcanvas-title mb-0" id="showcase-title"><code><?= esc($showcase['method']) ?></code></h5>
                <div class="small text-body-secondary"><?= esc($showcase['file']) ?></div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <ul class="nav nav-tabs mb-3" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#showcase-code" type="button" role="tab" aria-controls="showcase-code" aria-selected="true">
                        <i class="bi bi-code-slash"></i> Code
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#showcase-features" type="button" role="tab" aria-controls="showcase-features" aria-selected="false">
                        <i class="bi bi-list-check"></i> Features
                        <span class="badge rounded-pill text-bg-secondary"><?= count($showcase['features']) ?></span>
                    </button>
                </li>
            </ul>
            <div class="tab-content">
                <div class="tab-pane fade show active" id="showcase-code" role="tabpanel">
                    <pre class="border rounded"><code class="language-php"><?= esc($showcase['code']) ?></code></pre>
                </div>
                <div class="tab-pane fade" id="showcase-features" role="tabpanel">
                    <ul class="list-unstyled mb-0">
                        <?php foreach ($showcase['features'] as $feature): ?>
                            <li class="mb-2"><i class="bi bi-check2-circle text-success"></i> <?= esc($feature) ?></li>
                        <?php endforeach ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
<?php endif ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js"></script>
<script>hljs.highlightAll();</script>
</body>
</html>
