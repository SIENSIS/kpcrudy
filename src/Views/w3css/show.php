<?php
/**
 * Tema W3.CSS: fitxa de detall. Rep les mateixes variables que bootstrap5/show.php.
 *
 * @var \Kpcrudy\Renderer $this
 */
?>
<div class="kpcrudy">
    <?= $this->render('partials/assets') ?>

    <div class="w3-cell-row w3-margin-bottom">
        <h2 class="w3-cell w3-cell-middle" style="margin: 0"><?= esc($title) ?></h2>
        <div class="w3-cell w3-cell-middle w3-right-align">
            <?php if ($backUrl !== ''): ?>
            <a href="<?= esc($backUrl, 'attr') ?>" class="w3-button w3-white w3-border w3-round"><i class="bi bi-arrow-left"></i> <?= esc(lang('Kpcrudy.backToList')) ?></a>
            <?php endif ?>
            <?php foreach ($actions as $action): ?>
                <?= $this->render('partials/button', ['action' => $action]) ?>
            <?php endforeach ?>
        </div>
    </div>

    <?= $this->render('partials/flash', ['flash' => $flash]) ?>

    <div class="w3-card w3-white">
        <table class="w3-table w3-bordered">
            <?php foreach ($items as $item): ?>
                <tr>
                    <th class="w3-text-grey" style="width: 25%"><?= esc($item['label']) ?></th>
                    <td><?= $item['html'] !== '' ? $item['html'] : '<span class="w3-text-light-grey">—</span>' ?></td>
                </tr>
            <?php endforeach ?>
        </table>
    </div>

    <form id="kp-post-form" method="post" hidden><?= csrf_field() ?></form>
</div>
