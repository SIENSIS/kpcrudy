<?php
/**
 * Tema Tailwind: formulari d'alta i d'edició. Rep les mateixes variables que
 * bootstrap5/form.php.
 *
 * @var \Kpcrudy\Renderer $this
 */
?>
<div class="kpcrudy">
    <?= $this->render('partials/assets') ?>

    <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
        <?php if ($showTitle ?? true): ?><h1 class="text-2xl font-semibold"><?= esc($title) ?></h1><?php endif ?>
        <a href="<?= esc($backUrl, 'attr') ?>" class="inline-flex items-center gap-1 rounded-md border border-slate-300 bg-white px-3 py-1.5 text-sm text-slate-700 hover:bg-slate-50"><i class="bi bi-arrow-left"></i> <?= esc(lang('Kpcrudy.backToList')) ?></a>
    </div>

    <?= $this->render('partials/flash', ['flash' => $flash]) ?>

    <?php if ($formError): ?>
        <div class="mb-4 rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-800"><?= esc($formError) ?></div>
    <?php endif ?>

    <form method="post" action="<?= esc($action, 'attr') ?>" class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm"<?= $multipart ? ' enctype="multipart/form-data"' : '' ?>>
        <?= csrf_field() ?>
        <div class="divide-y divide-slate-100 px-4">
            <?php foreach ($fields as $field): ?>
                <?= $this->render('partials/field', ['field' => $field]) ?>
            <?php endforeach ?>
        </div>
        <div class="flex flex-wrap items-center gap-2 border-t border-slate-200 bg-slate-50 px-4 py-3">
            <button type="submit" name="_kp_after" value="list" class="inline-flex items-center gap-1 rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700"><i class="bi bi-check-lg"></i> <?= esc(lang('Kpcrudy.save')) ?></button>
            <button type="submit" name="_kp_after" value="continue" class="rounded-md border border-blue-600 bg-white px-4 py-2 text-sm font-medium text-blue-600 hover:bg-blue-50"><?= esc(lang('Kpcrudy.saveAndContinue')) ?></button>
            <?php if ($mode === 'add'): ?>
                <button type="submit" name="_kp_after" value="new" class="rounded-md border border-blue-600 bg-white px-4 py-2 text-sm font-medium text-blue-600 hover:bg-blue-50"><?= esc(lang('Kpcrudy.saveAndNew')) ?></button>
            <?php endif ?>
            <a href="<?= esc($backUrl, 'attr') ?>" class="ml-auto text-sm text-slate-600 underline"><?= esc(lang('Kpcrudy.cancel')) ?></a>
        </div>
    </form>
</div>
