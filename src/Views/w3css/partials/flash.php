<?php
/**
 * @var \Kpcrudy\Renderer                     $this
 * @var array{type: string, text: string}|null $flash
 */
$colors = [
    'success' => 'w3-pale-green w3-border-green',
    'danger'  => 'w3-pale-red w3-border-red',
    'warning' => 'w3-pale-yellow w3-border-amber',
    'info'    => 'w3-pale-blue w3-border-blue',
];
?>
<?php if (! empty($flash['text'])): ?>
    <div class="w3-panel w3-leftbar <?= $colors[$flash['type'] ?? 'info'] ?? $colors['info'] ?> w3-display-container">
        <span onclick="this.parentElement.remove()" class="w3-button w3-display-topright" title="<?= esc(lang('Kpcrudy.close'), 'attr') ?>">&times;</span>
        <p><?= esc($flash['text']) ?></p>
    </div>
<?php endif ?>
