<?php
/**
 * @var \Kpcrudy\Renderer    $this
 * @var array<string, mixed> $pagination
 */
?>
<div class="flex flex-wrap items-center justify-between gap-2">
    <div class="text-sm text-slate-500">
        <?= esc(lang('Kpcrudy.showing', [$pagination['from'], $pagination['to'], $pagination['total']])) ?>
    </div>
    <?php if ($pagination['links'] !== []): ?>
        <nav class="inline-flex overflow-hidden rounded-md border border-slate-300 bg-white text-sm" aria-label="<?= esc(lang('Kpcrudy.pagination'), 'attr') ?>">
            <?php foreach ($pagination['links'] as $link): ?>
                <?php if ($link['active']): ?>
                    <span class="border-l border-slate-300 bg-blue-600 px-3 py-1 text-white first:border-l-0"><?= $link['label'] ?></span>
                <?php elseif ($link['url'] === null): ?>
                    <span class="border-l border-slate-300 px-3 py-1 text-slate-400 first:border-l-0"><?= $link['label'] ?></span>
                <?php else: ?>
                    <a class="border-l border-slate-300 px-3 py-1 text-blue-600 hover:bg-slate-50 first:border-l-0" href="<?= esc($link['url'], 'attr') ?>"><?= $link['label'] ?></a>
                <?php endif ?>
            <?php endforeach ?>
        </nav>
    <?php endif ?>
</div>
