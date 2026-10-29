<?php
/**
 * Formulari d'alta i d'edició.
 *
 * @var \Kpcrudy\Renderer          $this
 * @var string                     $title
 * @var string                     $mode      'add' o 'edit'
 * @var string                     $action
 * @var string                     $backUrl
 * @var list<array<string, mixed>> $fields
 * @var bool                       $multipart
 * @var string|null                $formError
 * @var array<string, string>|null $flash
 */
?>
<div class="kpcrudy">
    <?= $this->render('partials/assets') ?>

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <?php if ($showTitle ?? true): ?><h1 class="h3 mb-0"><?= esc($title) ?></h1><?php endif ?>
        <a href="<?= esc($backUrl, 'attr') ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> <?= esc(lang('Kpcrudy.backToList')) ?></a>
    </div>

    <?= $this->render('partials/flash', ['flash' => $flash]) ?>

    <?php if ($formError): ?>
        <div class="alert alert-danger"><?= esc($formError) ?></div>
    <?php endif ?>

    <form method="post" action="<?= esc($action, 'attr') ?>"<?= $multipart ? ' enctype="multipart/form-data"' : '' ?>>
        <?= csrf_field() ?>
        <div class="card">
            <div class="card-body">
                <?php foreach ($fields as $field): ?>
                    <?= $this->render('partials/field', ['field' => $field]) ?>
                <?php endforeach ?>
            </div>
            <div class="card-footer d-flex flex-wrap gap-2">
                <button type="submit" name="_kp_after" value="list" class="btn btn-primary"><i class="bi bi-check-lg"></i> <?= esc(lang('Kpcrudy.save')) ?></button>
                <button type="submit" name="_kp_after" value="continue" class="btn btn-outline-primary"><?= esc(lang('Kpcrudy.saveAndContinue')) ?></button>
                <?php if ($mode === 'add'): ?>
                    <button type="submit" name="_kp_after" value="new" class="btn btn-outline-primary"><?= esc(lang('Kpcrudy.saveAndNew')) ?></button>
                <?php endif ?>
                <a href="<?= esc($backUrl, 'attr') ?>" class="btn btn-link ms-auto"><?= esc(lang('Kpcrudy.cancel')) ?></a>
            </div>
        </div>
    </form>
</div>
