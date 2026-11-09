<?php
/**
 * Accions d'una fila en un menú "⋯" (Crud::rowActionsMenu()). Fet amb <details>,
 * sense JavaScript ni dependències del tema: serveix per a tots els temes.
 *
 * @var \Kpcrudy\Renderer          $this
 * @var list<array<string, mixed>> $actions
 */
?>
<details class="kp-menu">
    <summary title="<?= esc(lang('Kpcrudy.moreActions'), 'attr') ?>" aria-label="<?= esc(lang('Kpcrudy.moreActions'), 'attr') ?>"><i class="bi bi-three-dots" aria-hidden="true"></i></summary>
    <div class="kp-menu-list">
        <?php foreach ($actions as $action): ?>
            <?= $this->render('partials/button', ['action' => $action, 'compact' => false]) ?>
        <?php endforeach ?>
    </div>
</details>
