<?php
/**
 * Fitxa de detall d'un registre.
 *
 * @var \Kpcrudy\Renderer          $this
 * @var string                     $title
 * @var list<array<string, mixed>> $items
 * @var list<array<string, mixed>> $actions
 * @var string                     $backUrl ('' si no s'ha de mostrar: fitxa compartida amb un enllaç signat)
 * @var array<string, string>|null $flash
 */
?>
<div class="kpcrudy">
    <?= $this->render('partials/assets') ?>

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <?php if ($showTitle ?? true): ?><h1 class="h3 mb-0"><?= esc($title) ?></h1><?php endif ?>
        <div class="d-flex flex-wrap gap-2">
            <?php if ($backUrl !== ''): ?>
            <a href="<?= esc($backUrl, 'attr') ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> <?= esc(lang('Kpcrudy.backToList')) ?></a>
            <?php endif ?>
            <?php foreach ($actions as $action): ?>
                <?= $this->render('partials/button', ['action' => $action]) ?>
            <?php endforeach ?>
        </div>
    </div>

    <?= $this->render('partials/flash', ['flash' => $flash]) ?>

    <div class="card">
        <div class="card-body">
            <dl class="row mb-0">
                <?php foreach ($items as $item): ?>
                    <dt class="col-md-3 text-body-secondary"><?= esc($item['label']) ?></dt>
                    <dd class="col-md-9"><?= $item['html'] !== '' ? $item['html'] : '<span class="text-body-tertiary">—</span>' ?></dd>
                <?php endforeach ?>
            </dl>
        </div>
    </div>

    <form id="kp-post-form" method="post" class="d-none"><?= csrf_field() ?></form>
</div>
