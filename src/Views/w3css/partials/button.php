<?php
/**
 * Tema W3.CSS: botó d'acció. Les accions porten classes de Bootstrap
 * ('btn-primary', 'btn-outline-danger'...), tant les de la llibreria com les
 * d'addAction(); aquí es tradueixen a W3.CSS. Les que no són a la taula es
 * deixen tal qual, per si el projecte hi posa classes pròpies.
 *
 * @var \Kpcrudy\Renderer    $this
 * @var array<string, mixed> $action
 * @var bool                 $compact
 */
$compact ??= false;
$map = [
    'btn-primary'           => 'w3-blue',
    'btn-secondary'         => 'w3-grey',
    'btn-success'           => 'w3-green',
    'btn-danger'            => 'w3-red',
    'btn-warning'           => 'w3-amber',
    'btn-info'              => 'w3-cyan',
    'btn-outline-primary'   => 'w3-white w3-border w3-border-blue w3-text-blue',
    'btn-outline-secondary' => 'w3-white w3-border',
    'btn-outline-success'   => 'w3-white w3-border w3-border-green w3-text-green',
    'btn-outline-danger'    => 'w3-white w3-border w3-border-red w3-text-red',
    'btn-outline-warning'   => 'w3-white w3-border w3-border-amber w3-text-amber',
    'btn-outline-info'      => 'w3-white w3-border w3-border-cyan w3-text-cyan',
];
$variant = implode(' ', array_map(static fn (string $class): string => $map[$class] ?? $class, preg_split('/\s+/', trim((string) ($action['class'] ?? 'btn-outline-secondary')))));
$classes = 'w3-button w3-round ' . ($compact ? 'w3-small w3-padding-small ' : '') . $variant;
$label   = esc($action['label']);
$inner   = ! empty($action['icon'])
    ? '<i class="' . esc($action['icon'], 'attr') . '"></i>' . ($compact ? '' : ' ' . $label)
    : $label;

if (isset($action['badge']) && $action['badge'] > 0) {
    $inner .= ' <span class="w3-tag w3-round w3-small w3-grey">' . (int) $action['badge'] . '</span>';
}
?>
<?php if (($action['method'] ?? 'get') === 'post'): ?>
    <button type="submit" form="kp-post-form"<?= $this->attrs([
        'formaction'      => $action['url'],
        'class'           => $classes,
        'title'           => $action['label'],
        'aria-label'      => $compact ? $action['label'] : null,
        'data-kp-confirm' => $action['confirm'] ?? null,
    ]) ?>><?= $inner ?></button>
<?php else: ?>
    <a<?= $this->attrs([
        'href'            => $action['url'],
        'class'           => $classes,
        'title'           => $action['label'],
        'aria-label'      => $compact ? $action['label'] : null,
        'target'          => $action['target'] ?? null,
        'rel'             => empty($action['target']) ? null : 'noopener noreferrer',
        'data-kp-confirm' => $action['confirm'] ?? null,
    ]) ?>><?= $inner ?></a>
<?php endif ?>
