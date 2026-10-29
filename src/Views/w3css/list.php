<?php
/**
 * Tema W3.CSS: llistat i paperera. Rep les mateixes variables que
 * bootstrap5/list.php (vegeu-ne la capçalera).
 *
 * @var \Kpcrudy\Renderer $this
 */
$hasBulk = $bulkActions !== [];
$colspan = count($columns) + 1 + ($hasBulk ? 1 : 0);
?>
<div class="kpcrudy">
    <?= $this->render('partials/assets') ?>

    <div class="w3-cell-row w3-margin-bottom">
        <?php if ($showTitle ?? true): ?><h2 class="w3-cell w3-cell-middle kp-m0"><?= esc($title) ?></h2><?php endif ?>
        <div class="w3-cell w3-cell-middle w3-right-align">
            <?php foreach ($headerActions as $action): ?>
                <?= $this->render('partials/button', ['action' => $action]) ?>
            <?php endforeach ?>
        </div>
    </div>

    <?= $this->render('partials/flash', ['flash' => $flash]) ?>

    <form method="get" action="<?= esc($listUrl, 'attr') ?>" class="w3-cell-row w3-margin-bottom">
        <?= $this->hidden($searchHidden) ?>
        <?php if ($canSearch): ?>
            <div class="w3-cell w3-cell-middle">
                <div class="kp-search">
                    <input type="search" name="q" class="w3-input w3-border w3-round kp-grow" value="<?= esc($state['q'], 'attr') ?>"
                           placeholder="<?= esc(lang('Kpcrudy.searchPlaceholder'), 'attr') ?>" aria-label="<?= esc(lang('Kpcrudy.search'), 'attr') ?>">
                    <button class="w3-button w3-white w3-border w3-round" type="submit" title="<?= esc(lang('Kpcrudy.search'), 'attr') ?>"><i class="bi bi-search"></i></button>
                </div>
            </div>
        <?php endif ?>
        <div class="w3-cell w3-cell-middle w3-right-align">
            <label class="w3-small w3-text-grey" for="kp-per-page"><?= esc(lang('Kpcrudy.perPage')) ?></label>
            <select id="kp-per-page" name="per_page" class="w3-select w3-border w3-round kp-wauto" data-kp-autosubmit>
                <?php foreach ($perPageOptions as $option): ?>
                    <option value="<?= (int) $option ?>"<?= (int) $option === $state['per_page'] ? ' selected' : '' ?>><?= (int) $option ?></option>
                <?php endforeach ?>
            </select>
        </div>
    </form>

    <?php if ($filters !== []): ?>
        <?= $this->render('partials/filters', compact('filters', 'filterHidden', 'listUrl', 'filtersReset', 'filtersActive')) ?>
    <?php endif ?>

    <form method="post" action="<?= esc($bulkUrl, 'attr') ?>" data-kp-bulk="<?= esc(lang('Kpcrudy.noSelection'), 'attr') ?>">
        <?= csrf_field() ?>
        <?php if ($trash): ?><input type="hidden" name="trash" value="1"><?php endif ?>

        <div class="w3-card w3-white">
            <div class="w3-responsive">
                <table class="w3-table w3-striped w3-hoverable w3-bordered">
                    <thead>
                        <tr class="w3-light-grey">
                            <?php if ($hasBulk): ?>
                                <th class="kp-check"><input type="checkbox" class="w3-check" data-kp-check-all aria-label="<?= esc(lang('Kpcrudy.selectAll'), 'attr') ?>"></th>
                            <?php endif ?>
                            <?php foreach ($columns as $column): ?>
                                <th>
                                    <?php if ($column['sortable']): ?>
                                        <a href="<?= esc($column['url'], 'attr') ?>" class="kp-nodeco">
                                            <?= esc($column['label']) ?>
                                            <?php if ($column['sorted'] === 'asc'): ?><i class="bi bi-sort-up"></i><?php endif ?>
                                            <?php if ($column['sorted'] === 'desc'): ?><i class="bi bi-sort-down"></i><?php endif ?>
                                        </a>
                                    <?php else: ?>
                                        <?= esc($column['label']) ?>
                                    <?php endif ?>
                                </th>
                            <?php endforeach ?>
                            <th class="w3-right-align"><?= esc(lang('Kpcrudy.actions')) ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $row): ?>
                            <tr>
                                <?php if ($hasBulk): ?>
                                    <td><input type="checkbox" class="w3-check" name="ids[]" value="<?= esc($row['id'], 'attr') ?>" aria-label="<?= esc(lang('Kpcrudy.select'), 'attr') ?>"></td>
                                <?php endif ?>
                                <?php foreach ($columns as $column): ?>
                                    <td><?= $row['cells'][$column['name']] ?></td>
                                <?php endforeach ?>
                                <td class="w3-right-align kp-actions">
                                    <?php foreach ($row['actions'] as $action): ?>
                                        <?= $this->render('partials/button', ['action' => $action, 'compact' => true]) ?>
                                    <?php endforeach ?>
                                </td>
                            </tr>
                        <?php endforeach ?>
                        <?php if ($rows === []): ?>
                            <tr><td colspan="<?= $colspan ?>" class="w3-center w3-text-grey w3-padding-16"><?= esc(lang('Kpcrudy.noRecords')) ?></td></tr>
                        <?php endif ?>
                    </tbody>
                </table>
            </div>

            <div class="w3-container w3-padding w3-light-grey">
                <?php if ($hasBulk && $rows !== []): ?>
                    <div class="w3-margin-bottom">
                        <select name="action" class="w3-select w3-border w3-round kp-wauto" aria-label="<?= esc(lang('Kpcrudy.bulkActions'), 'attr') ?>">
                            <option value=""><?= esc(lang('Kpcrudy.bulkActions')) ?></option>
                            <?php foreach ($bulkActions as $key => $bulk): ?>
                                <option<?= $this->attrs(['value' => $key, 'data-confirm' => $bulk['confirm'], 'data-url' => $bulk['url']]) ?>><?= esc($bulk['label']) ?></option>
                            <?php endforeach ?>
                        </select>
                        <button type="submit" class="w3-button w3-white w3-border w3-border-blue w3-text-blue w3-round"><?= esc(lang('Kpcrudy.apply')) ?></button>
                    </div>
                <?php endif ?>
                <?= $this->render('partials/pagination', ['pagination' => $pagination]) ?>
            </div>
        </div>
    </form>

    <form id="kp-post-form" method="post" hidden><?= csrf_field() ?></form>
</div>
