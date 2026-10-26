<?php
/**
 * @var \Kpcrudy\Renderer                     $this
 * @var array{type: string, text: string}|null $flash
 */
?>
<?php if (! empty($flash['text'])): ?>
    <div class="alert alert-<?= esc($flash['type'] ?? 'info', 'attr') ?> alert-dismissible fade show" role="alert">
        <?= esc($flash['text']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="<?= esc(lang('Kpcrudy.close'), 'attr') ?>"></button>
    </div>
<?php endif ?>
