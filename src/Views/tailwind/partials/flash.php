<?php
/**
 * @var \Kpcrudy\Renderer                     $this
 * @var array{type: string, text: string}|null $flash
 */
$colors = [
    'success' => 'border-green-300 bg-green-50 text-green-800',
    'danger'  => 'border-red-300 bg-red-50 text-red-800',
    'warning' => 'border-amber-300 bg-amber-50 text-amber-800',
    'info'    => 'border-blue-300 bg-blue-50 text-blue-800',
];
?>
<?php if (! empty($flash['text'])): ?>
    <div data-kp-alert class="mb-4 flex items-start justify-between gap-4 rounded-lg border px-4 py-3 text-sm <?= $colors[$flash['type'] ?? 'info'] ?? $colors['info'] ?>" role="alert">
        <span><?= esc($flash['text']) ?></span>
        <button type="button" data-kp-dismiss class="opacity-60 hover:opacity-100" aria-label="<?= esc(lang('Kpcrudy.close'), 'attr') ?>">&times;</button>
    </div>
<?php endif ?>
