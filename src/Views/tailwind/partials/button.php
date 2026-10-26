<?php
/**
 * Tema Tailwind: botó d'acció. Les accions porten classes de Bootstrap
 * ('btn-primary', 'btn-outline-danger'...), tant les de la llibreria com les
 * d'addAction(); aquí es tradueixen a utilitats de Tailwind. Les que no són
 * a la taula es deixen tal qual.
 *
 * @var \Kpcrudy\Renderer    $this
 * @var array<string, mixed> $action
 * @var bool                 $compact
 */
$compact ??= false;
$map = [
    'btn-primary'           => 'border-blue-600 bg-blue-600 text-white hover:bg-blue-700',
    'btn-secondary'         => 'border-slate-500 bg-slate-500 text-white hover:bg-slate-600',
    'btn-success'           => 'border-green-600 bg-green-600 text-white hover:bg-green-700',
    'btn-danger'            => 'border-red-600 bg-red-600 text-white hover:bg-red-700',
    'btn-warning'           => 'border-amber-400 bg-amber-400 text-black hover:bg-amber-500',
    'btn-info'              => 'border-cyan-500 bg-cyan-500 text-black hover:bg-cyan-600',
    'btn-outline-primary'   => 'border-blue-600 bg-white text-blue-600 hover:bg-blue-50',
    'btn-outline-secondary' => 'border-slate-300 bg-white text-slate-700 hover:bg-slate-50',
    'btn-outline-success'   => 'border-green-600 bg-white text-green-700 hover:bg-green-50',
    'btn-outline-danger'    => 'border-red-500 bg-white text-red-600 hover:bg-red-50',
    'btn-outline-warning'   => 'border-amber-500 bg-white text-amber-600 hover:bg-amber-50',
    'btn-outline-info'      => 'border-cyan-600 bg-white text-cyan-700 hover:bg-cyan-50',
];
$variant = implode(' ', array_map(static fn (string $class): string => $map[$class] ?? $class, preg_split('/\s+/', trim((string) ($action['class'] ?? 'btn-outline-secondary')))));
$classes = 'inline-flex items-center gap-1 rounded-md border text-sm font-medium transition-colors '
    . ($compact ? 'px-2 py-1 ' : 'px-3 py-1.5 ') . $variant;
$label = esc($action['label']);
$inner = ! empty($action['icon'])
    ? '<i class="' . esc($action['icon'], 'attr') . '"></i>' . ($compact ? '' : ' ' . $label)
    : $label;

if (isset($action['badge']) && $action['badge'] > 0) {
    $inner .= ' <span class="rounded-full bg-slate-500 px-1.5 text-xs text-white">' . (int) $action['badge'] . '</span>';
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
