<?php
/**
 * Tema W3.CSS: formulari d'alta i d'edició. Rep les mateixes variables que
 * bootstrap5/form.php.
 *
 * @var \Kpcrudy\Renderer $this
 */
?>
<div class="kpcrudy">
    <?= $this->render('partials/assets') ?>

    <div class="w3-cell-row w3-margin-bottom">
        <h2 class="w3-cell w3-cell-middle" style="margin: 0"><?= esc($title) ?></h2>
        <div class="w3-cell w3-cell-middle w3-right-align">
            <a href="<?= esc($backUrl, 'attr') ?>" class="w3-button w3-white w3-border w3-round"><i class="bi bi-arrow-left"></i> <?= esc(lang('Kpcrudy.backToList')) ?></a>
        </div>
    </div>

    <?= $this->render('partials/flash', ['flash' => $flash]) ?>

    <?php if ($formError): ?>
        <div class="w3-panel w3-leftbar w3-pale-red w3-border-red"><p><?= esc($formError) ?></p></div>
    <?php endif ?>

    <form method="post" action="<?= esc($action, 'attr') ?>" class="w3-card w3-white"<?= $multipart ? ' enctype="multipart/form-data"' : '' ?>>
        <?= csrf_field() ?>
        <div class="w3-container w3-padding-16">
            <?php foreach ($fields as $field): ?>
                <?= $this->render('partials/field', ['field' => $field]) ?>
            <?php endforeach ?>
        </div>
        <div class="w3-container w3-padding w3-light-grey">
            <button type="submit" name="_kp_after" value="list" class="w3-button w3-blue w3-round"><i class="bi bi-check-lg"></i> <?= esc(lang('Kpcrudy.save')) ?></button>
            <button type="submit" name="_kp_after" value="continue" class="w3-button w3-white w3-border w3-border-blue w3-text-blue w3-round"><?= esc(lang('Kpcrudy.saveAndContinue')) ?></button>
            <?php if ($mode === 'add'): ?>
                <button type="submit" name="_kp_after" value="new" class="w3-button w3-white w3-border w3-border-blue w3-text-blue w3-round"><?= esc(lang('Kpcrudy.saveAndNew')) ?></button>
            <?php endif ?>
            <a href="<?= esc($backUrl, 'attr') ?>" class="w3-button w3-right"><?= esc(lang('Kpcrudy.cancel')) ?></a>
        </div>
    </form>
</div>
