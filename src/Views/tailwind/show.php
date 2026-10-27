<?php
/**
 * Tema Tailwind: fitxa de detall. Rep les mateixes variables que bootstrap5/show.php.
 *
 * @var \Kpcrudy\Renderer $this
 */
?>
<div class="kpcrudy">
    <?= $this->render('partials/assets') ?>

    <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
        <h1 class="text-2xl font-semibold"><?= esc($title) ?></h1>
        <div class="flex flex-wrap gap-2">
            <?php if ($backUrl !== ''): ?>
            <a href="<?= esc($backUrl, 'attr') ?>" class="inline-flex items-center gap-1 rounded-md border border-slate-300 bg-white px-3 py-1.5 text-sm text-slate-700 hover:bg-slate-50"><i class="bi bi-arrow-left"></i> <?= esc(lang('Kpcrudy.backToList')) ?></a>
            <?php endif ?>
            <?php foreach ($actions as $action): ?>
                <?= $this->render('partials/button', ['action' => $action]) ?>
            <?php endforeach ?>
        </div>
    </div>

    <?= $this->render('partials/flash', ['flash' => $flash]) ?>

    <dl class="divide-y divide-slate-100 overflow-hidden rounded-lg border border-slate-200 bg-white text-sm shadow-sm">
        <?php foreach ($items as $item): ?>
            <div class="grid gap-1 px-4 py-2 sm:grid-cols-4 sm:gap-4">
                <dt class="font-medium text-slate-500"><?= esc($item['label']) ?></dt>
                <dd class="sm:col-span-3"><?= $item['html'] !== '' ? $item['html'] : '<span class="text-slate-300">—</span>' ?></dd>
            </div>
        <?php endforeach ?>
    </dl>

    <form id="kp-post-form" method="post" hidden><?= csrf_field() ?></form>
</div>
