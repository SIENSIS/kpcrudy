<?php
/**
 * @var \Kpcrudy\Renderer    $this
 * @var array<string, mixed> $pagination
 */
?>
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
    <div class="text-body-secondary small">
        <?= esc(lang('Kpcrudy.showing', [$pagination['from'], $pagination['to'], $pagination['total']])) ?>
    </div>
    <?php if ($pagination['links'] !== []): ?>
        <nav aria-label="<?= esc(lang('Kpcrudy.pagination'), 'attr') ?>">
            <ul class="pagination pagination-sm mb-0">
                <?php foreach ($pagination['links'] as $link): ?>
                    <li class="page-item<?= $link['active'] ? ' active' : '' ?><?= $link['url'] === null ? ' disabled' : '' ?>">
                        <?php if ($link['url'] === null || $link['active']): ?>
                            <span class="page-link"><?= $link['label'] ?></span>
                        <?php else: ?>
                            <a class="page-link" href="<?= esc($link['url'], 'attr') ?>"><?= $link['label'] ?></a>
                        <?php endif ?>
                    </li>
                <?php endforeach ?>
            </ul>
        </nav>
    <?php endif ?>
</div>
