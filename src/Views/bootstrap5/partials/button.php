<?php
/**
 * Botó d'acció. Les accions POST envien el formulari ocult #kp-post-form.
 *
 * @var \Kpcrudy\Renderer    $this
 * @var array<string, mixed> $action
 * @var bool                 $compact Només icona (amb el text com a títol)
 */
$compact ??= false;
$classes = 'btn ' . ($compact ? 'btn-sm ' : '') . ($action['class'] ?? 'btn-outline-secondary');
$label   = esc($action['label']);
$inner   = ! empty($action['icon'])
    ? '<i class="' . esc($action['icon'], 'attr') . '"></i>' . ($compact ? '<span class="visually-hidden">' . $label . '</span>' : ' ' . $label)
    : $label;

if (isset($action['badge']) && $action['badge'] > 0) {
    $inner .= ' <span class="badge text-bg-secondary">' . (int) $action['badge'] . '</span>';
}
?>
<?php if (($action['method'] ?? 'get') === 'post'): ?>
    <button type="submit" form="kp-post-form"<?= $this->attrs([
        'formaction'      => $action['url'],
        'class'           => $classes,
        'title'           => $action['label'],
        'data-kp-confirm' => $action['confirm'] ?? null,
    ]) ?>><?= $inner ?></button>
<?php else: ?>
    <a<?= $this->attrs([
        'href'            => $action['url'],
        'class'           => $classes,
        'title'           => $action['label'],
        'target'          => $action['target'] ?? null,
        'rel'             => empty($action['target']) ? null : 'noopener noreferrer',
        'data-kp-confirm' => $action['confirm'] ?? null,
    ]) ?>><?= $inner ?></a>
<?php endif ?>
