<?php
/**
 * @var \Kpcrudy\Renderer    $this
 * @var array<string, mixed> $pagination
 */
?>
<div class="w3-cell-row">
    <div class="w3-cell w3-cell-middle w3-small w3-text-grey">
        <?= esc(lang('Kpcrudy.showing', [$pagination['from'], $pagination['to'], $pagination['total']])) ?>
    </div>
    <?php if ($pagination['links'] !== []): ?>
        <nav class="w3-cell w3-cell-middle w3-right-align kp-pages" aria-label="<?= esc(lang('Kpcrudy.pagination'), 'attr') ?>">
            <div class="w3-bar w3-border w3-round w3-white">
                <?php foreach ($pagination['links'] as $link): ?>
                    <?php if ($link['url'] === null || $link['active']): ?>
                        <span class="w3-bar-item w3-button w3-small<?= $link['active'] ? ' w3-blue' : ' w3-disabled' ?>"><?= $link['label'] ?></span>
                    <?php else: ?>
                        <a class="w3-bar-item w3-button w3-small" href="<?= esc($link['url'], 'attr') ?>"><?= $link['label'] ?></a>
                    <?php endif ?>
                <?php endforeach ?>
            </div>
        </nav>
    <?php endif ?>
</div>
