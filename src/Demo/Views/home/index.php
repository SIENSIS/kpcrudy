<?php

use Kpcrudy\Demo\Demo;

/**
 * @var array<string, mixed>       $me
 * @var bool                       $isAdmin
 * @var string                     $role
 * @var array<string, string>|null $change
 * @var array<string, int>         $stats
 * @var list<array<string, mixed>> $upcoming
 */

// Map of the demo: every page, who can open it and the library features it shows
$pages = [
    ['admin/users', 'bi-people', 'Users', ['admin'], 'Table mode with trash, image, password, editable N-N, per-record permissions and bulk actions.'],
    ['admin/categories', 'bi-tags', 'Categories', ['admin', 'editor'], 'Model mode, texts translated into three languages in a child table, colour field and a hook that prevents deleting.'],
    ['admin/events', 'bi-calendar-event', 'Events', ['admin', 'editor'], 'Relations, multiselect, range filters, custom operations, central permissions for editors and an afterWrite() hook.'],
    ['admin/attendances', 'bi-calendar-check', 'Attendance', ['admin'], 'Junction table with its own data, different add and edit fields, and capacity control.'],
    ['agenda', 'bi-calendar3', 'Agenda', [], 'Read-only with where(), columns computed for the user and a "Join" custom operation.'],
    ['my/attendances', 'bi-ticket-perforated', 'My registrations', [], 'Records limited to the user, conditional operation and safe bulk action.'],
    ['profile', 'bi-person-badge', 'My profile', [], 'One-record CRUD with a custom theme, setBackUrl() and N-N interests.'],
];
?>
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
    <div>
        <h1 class="h3 mb-1">Hello, <?= esc($me['first_name']) ?>!</h1>
        <p class="text-body-secondary mb-0">
            This application shows every feature of <strong>kpcrudy</strong>.
            On every page, the <span class="badge text-bg-warning"><i class="bi bi-code-slash"></i> Code</span> button in the top bar shows the controller code.
        </p>
    </div>
    <?php if ($isAdmin && Demo::canResetFromWeb()): ?>
        <form method="post" action="<?= Demo::url('admin/reset') ?>">
            <?= csrf_field() ?>
            <?php // data-kpdemo-confirm (see the layout script) instead of onsubmit="": works with a strict CSP ?>
            <button class="btn btn-outline-danger" type="submit" data-kpdemo-confirm="All changes will be lost and the sample data will be generated again. Continue?"><i class="bi bi-arrow-repeat"></i> Reset data</button>
        </form>
    <?php endif ?>
</div>

<?php if ($change !== null): ?>
    <div class="alert alert-light border small">
        <i class="bi bi-clock-history"></i> Last change (afterWrite hook): <?= esc($change['text']) ?>, by <?= esc($change['user']) ?> at <?= esc($change['at']) ?>
    </div>
<?php endif ?>

<div class="row g-3 mb-4">
    <?php foreach ([
        ['Active users', $stats['users'], 'bi-people', 'primary'],
        ['Categories', $stats['categories'], 'bi-tags', 'info'],
        ['Upcoming events', $stats['events'], 'bi-calendar-event', 'success'],
        ['My registrations', $stats['mine'], 'bi-ticket-perforated', 'warning'],
    ] as [$label, $value, $icon, $color]): ?>
        <div class="col-6 col-lg-3">
            <div class="card h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <i class="bi <?= $icon ?> fs-2 text-<?= $color ?>"></i>
                    <div>
                        <div class="fs-4 fw-semibold"><?= $value ?></div>
                        <div class="text-body-secondary small"><?= esc($label) ?></div>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach ?>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <h2 class="h5 mb-3">Demo map</h2>
        <div class="row g-3">
            <?php foreach ($pages as [$url, $icon, $name, $roles, $description]): ?>
                <?php if ($roles !== [] && ! in_array($role, $roles, true)) { continue; } ?>
                <div class="col-md-6">
                    <a href="<?= Demo::url($url) ?>" class="card h-100 text-decoration-none">
                        <div class="card-body">
                            <h3 class="h6 card-title">
                                <i class="bi <?= $icon ?>"></i> <?= esc($name) ?>
                                <?php foreach ($roles as $badge): ?><span class="badge text-bg-<?= $badge === 'admin' ? 'warning' : 'info' ?> ms-1"><?= $badge ?></span><?php endforeach ?>
                            </h3>
                            <p class="card-text small text-body-secondary mb-0"><?= esc($description) ?></p>
                        </div>
                    </a>
                </div>
            <?php endforeach ?>
        </div>
        <?php if (! $isAdmin): ?>
            <p class="small text-body-secondary mt-3">
                Log out and log in as an administrator to see every page<?= $role === 'user' ? ', or as the editor to see the same CRUDs with fewer permissions' : '' ?>.
            </p>
        <?php endif ?>
    </div>

    <div class="col-lg-4">
        <h2 class="h5 mb-3">Upcoming events</h2>
        <div class="list-group">
            <?php foreach ($upcoming as $event): ?>
                <a href="<?= Demo::url('agenda/show/' . Demo::maskId('kpdemo_events', $event['id'])) ?>" class="list-group-item list-group-item-action">
                    <div class="d-flex justify-content-between">
                        <strong><?= esc($event['title']) ?></strong>
                        <?php // Painted by the layout script: a style="" attribute would break a strict CSP ?>
                        <span class="badge" data-kpdemo-bg="<?= esc($event['color'], 'attr') ?>"><?= esc($event['category']) ?></span>
                    </div>
                    <div class="small text-body-secondary">
                        <i class="bi bi-clock"></i> <?= date('d/m/Y H:i', strtotime($event['starts_at'])) ?>
                        <?php if ($event['location']): ?> · <i class="bi bi-geo-alt"></i> <?= esc($event['location']) ?><?php endif ?>
                    </div>
                </a>
            <?php endforeach ?>
            <?php if ($upcoming === []): ?>
                <div class="list-group-item text-body-secondary">There are no upcoming events.</div>
            <?php endif ?>
        </div>
    </div>
</div>
