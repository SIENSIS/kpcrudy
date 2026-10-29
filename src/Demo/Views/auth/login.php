<?php

use Kpcrudy\Demo\Demo;

/**
 * Demo login: click a user to log in as that user (no password).
 *
 * @var list<array<string, mixed>> $users
 */
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Log in · kpcrudy demo</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style<?= ($nonce = csp_style_nonce()) === '' ? '' : ' ' . $nonce ?>>
        .user-avatar { width: 44px; height: 44px; object-fit: cover; }
        .user-avatar-empty { font-size: 44px; line-height: 1; }
        .user-card:hover { border-color: var(--bs-primary); }
        .login-box { max-width: 960px; }
    </style>
</head>
<body class="bg-body-tertiary">
<div class="container py-5 login-box">
    <h1 class="h3 text-center mb-1"><i class="bi bi-grid-3x3-gap"></i> kpcrudy demo</h1>
    <p class="text-center text-body-secondary mb-4">
        Event management built with automatic CRUDs.<br>
        Choose a user to log in as: no password needed.
    </p>

    <?php if ($error = session()->getFlashdata('error')): ?>
        <div class="alert alert-danger"><?= esc($error) ?></div>
    <?php endif ?>
    <?php if ($message = session()->getFlashdata('message')): ?>
        <div class="alert alert-success"><?= esc($message) ?></div>
    <?php endif ?>

    <form method="post" action="<?= Demo::url('login') ?>">
        <?= csrf_field() ?>

        <?php foreach ([
            'admin'  => ['Administrators', 'Access to every page, including administration.'],
            'editor' => ['Editors', 'Events and categories with fewer permissions (central permission resolver), plus the user pages.'],
            'user'   => ['Users', 'Agenda, own registrations and profile.'],
        ] as $role => [$heading, $description]): ?>
            <h2 class="h6 text-uppercase text-body-secondary mt-4 mb-1"><?= esc($heading) ?></h2>
            <p class="small text-body-secondary mb-2"><?= esc($description) ?></p>
            <div class="row g-2">
                <?php foreach ($users as $user): ?>
                    <?php if ($user['role'] !== $role) { continue; } ?>
                    <div class="col-sm-6 col-lg-4">
                        <button type="submit" name="user_id" value="<?= (int) $user['id'] ?>" class="card card-body user-card w-100 text-start flex-row align-items-center gap-3 py-2">
                            <?php if ($user['avatar']): ?>
                                <img src="<?= Demo::uploadUrl('avatars') . '/' . rawurlencode($user['avatar']) ?>" alt="" class="rounded-circle user-avatar">
                            <?php else: ?>
                                <i class="bi bi-person-circle text-body-tertiary user-avatar-empty"></i>
                            <?php endif ?>
                            <span>
                                <span class="d-block fw-semibold"><?= esc($user['first_name'] . ' ' . $user['last_name']) ?></span>
                                <span class="d-block small text-body-secondary">@<?= esc($user['username']) ?></span>
                            </span>
                            <?php if ($role !== 'user'): ?><span class="badge text-bg-<?= $role === 'admin' ? 'warning' : 'info' ?> ms-auto"><?= $role ?></span><?php endif ?>
                        </button>
                    </div>
                <?php endforeach ?>
            </div>
        <?php endforeach ?>
    </form>

    <p class="small text-body-secondary text-center mt-4 mb-0">
        Deactivated users and users in the trash are not listed and cannot log in.
        Try deactivating one from <em>Administration › Users</em>.
    </p>
</div>
</body>
</html>
