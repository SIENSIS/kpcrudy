<?php
/**
 * Tema Tailwind: llistat i paperera. Rep les mateixes variables que
 * bootstrap5/list.php (vegeu-ne la capçalera).
 *
 * @var \Kpcrudy\Renderer $this
 */
$hasBulk = $bulkActions !== [];
$colspan = count($columns) + 1 + ($hasBulk ? 1 : 0);
$input   = 'rounded-md border border-slate-300 bg-white px-3 py-1.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500';
?>
<div class="kpcrudy">
    <?= $this->render('partials/assets') ?>

    <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
        <?php if ($showTitle ?? true): ?><h1 class="text-2xl font-semibold"><?= esc($title) ?></h1><?php endif ?>
        <div class="flex flex-wrap gap-2">
            <?php foreach ($headerActions as $action): ?>
                <?= $this->render('partials/button', ['action' => $action]) ?>
            <?php endforeach ?>
        </div>
    </div>

    <?= $this->render('partials/flash', ['flash' => $flash]) ?>

    <form method="get" action="<?= esc($listUrl, 'attr') ?>" class="mb-4 flex flex-wrap items-center gap-2">
        <?= $this->hidden($searchHidden) ?>
        <?php if ($canSearch): ?>
            <div class="flex w-full max-w-md">
                <input type="search" name="q" class="<?= $input ?> w-full rounded-r-none" value="<?= esc($state['q'], 'attr') ?>"
                       placeholder="<?= esc(lang('Kpcrudy.searchPlaceholder'), 'attr') ?>" aria-label="<?= esc(lang('Kpcrudy.search'), 'attr') ?>">
                <button class="rounded-r-md border border-l-0 border-slate-300 bg-white px-3 text-slate-600 hover:bg-slate-50" type="submit" title="<?= esc(lang('Kpcrudy.search'), 'attr') ?>"><i class="bi bi-search"></i></button>
            </div>
        <?php endif ?>
        <div class="ml-auto flex items-center gap-2">
            <label class="text-sm text-slate-500" for="kp-per-page"><?= esc(lang('Kpcrudy.perPage')) ?></label>
            <select id="kp-per-page" name="per_page" class="<?= $input ?>" data-kp-autosubmit>
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

        <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-slate-600">
                        <tr>
                            <?php if ($hasBulk): ?>
                                <th class="w-px px-3 py-2"><input type="checkbox" class="accent-blue-600" data-kp-check-all aria-label="<?= esc(lang('Kpcrudy.selectAll'), 'attr') ?>"></th>
                            <?php endif ?>
                            <?php foreach ($columns as $column): ?>
                                <th class="whitespace-nowrap px-3 py-2 font-semibold">
                                    <?php if ($column['sortable']): ?>
                                        <a href="<?= esc($column['url'], 'attr') ?>" class="hover:underline">
                                            <?= esc($column['label']) ?>
                                            <?php if ($column['sorted'] === 'asc'): ?><i class="bi bi-sort-up"></i><?php endif ?>
                                            <?php if ($column['sorted'] === 'desc'): ?><i class="bi bi-sort-down"></i><?php endif ?>
                                        </a>
                                    <?php else: ?>
                                        <?= esc($column['label']) ?>
                                    <?php endif ?>
                                </th>
                            <?php endforeach ?>
                            <th class="px-3 py-2 text-right font-semibold"><?= esc(lang('Kpcrudy.actions')) ?></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($rows as $row): ?>
                            <tr class="hover:bg-slate-50">
                                <?php if ($hasBulk): ?>
                                    <td class="px-3 py-2"><input type="checkbox" class="accent-blue-600" name="ids[]" value="<?= esc($row['id'], 'attr') ?>" aria-label="<?= esc(lang('Kpcrudy.select'), 'attr') ?>"></td>
                                <?php endif ?>
                                <?php foreach ($columns as $column): ?>
                                    <td class="px-3 py-2"><?= $row['cells'][$column['name']] ?></td>
                                <?php endforeach ?>
                                <td class="whitespace-nowrap px-3 py-2 text-right">
                                    <div class="inline-flex gap-1">
                                        <?php foreach ($row['actions'] as $action): ?>
                                            <?= $this->render('partials/button', ['action' => $action, 'compact' => true]) ?>
                                        <?php endforeach ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach ?>
                        <?php if ($rows === []): ?>
                            <tr><td colspan="<?= $colspan ?>" class="px-3 py-8 text-center text-slate-500"><?= esc(lang('Kpcrudy.noRecords')) ?></td></tr>
                        <?php endif ?>
                    </tbody>
                </table>
            </div>

            <div class="flex flex-wrap items-center gap-3 border-t border-slate-200 bg-slate-50 px-3 py-2">
                <?php if ($hasBulk && $rows !== []): ?>
                    <div class="flex">
                        <select name="action" class="<?= $input ?> rounded-r-none" aria-label="<?= esc(lang('Kpcrudy.bulkActions'), 'attr') ?>">
                            <option value=""><?= esc(lang('Kpcrudy.bulkActions')) ?></option>
                            <?php foreach ($bulkActions as $key => $bulk): ?>
                                <option<?= $this->attrs(['value' => $key, 'data-confirm' => $bulk['confirm'], 'data-url' => $bulk['url']]) ?>><?= esc($bulk['label']) ?></option>
                            <?php endforeach ?>
                        </select>
                        <button type="submit" class="rounded-r-md border border-l-0 border-blue-600 bg-white px-3 text-sm text-blue-600 hover:bg-blue-50"><?= esc(lang('Kpcrudy.apply')) ?></button>
                    </div>
                <?php endif ?>
                <div class="flex-1">
                    <?= $this->render('partials/pagination', ['pagination' => $pagination]) ?>
                </div>
            </div>
        </div>
    </form>

    <form id="kp-post-form" method="post" hidden><?= csrf_field() ?></form>
</div>
