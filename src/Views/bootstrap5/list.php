<?php
/**
 * Llistat i paperera.
 *
 * @var \Kpcrudy\Renderer                    $this
 * @var string                               $title
 * @var bool                                 $trash
 * @var list<array<string, mixed>>           $columns
 * @var list<array<string, mixed>>           $rows
 * @var array<string, mixed>                 $state
 * @var string                               $listUrl
 * @var string                               $bulkUrl
 * @var array<string, mixed>                 $searchHidden
 * @var list<array<string, mixed>>           $filters
 * @var array<string, mixed>                 $filterHidden
 * @var string                               $filtersReset
 * @var int                                  $filtersActive
 * @var bool                                 $canSearch
 * @var list<int>                            $perPageOptions
 * @var list<array<string, mixed>>           $headerActions
 * @var array<string, array<string, mixed>>  $bulkActions
 * @var array<string, mixed>                 $pagination
 * @var array<string, string>|null           $flash
 */
$hasBulk    = $bulkActions !== [];
$colspan    = count($columns) + 1 + ($hasBulk ? 1 : 0);
?>
<div class="kpcrudy">
    <?= $this->render('partials/assets') ?>

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <?php if ($showTitle ?? true): ?><h1 class="h3 mb-0"><?= esc($title) ?></h1><?php endif ?>
        <div class="d-flex flex-wrap gap-2">
            <?php foreach ($headerActions as $action): ?>
                <?= $this->render('partials/button', ['action' => $action]) ?>
            <?php endforeach ?>
        </div>
    </div>

    <?= $this->render('partials/flash', ['flash' => $flash]) ?>

    <form method="get" action="<?= esc($listUrl, 'attr') ?>" class="d-flex flex-wrap align-items-center gap-2 mb-3">
        <?= $this->hidden($searchHidden) ?>
        <?php if ($canSearch): ?>
            <div class="input-group kp-search">
                <input type="search" name="q" class="form-control" value="<?= esc($state['q'], 'attr') ?>"
                       placeholder="<?= esc(lang('Kpcrudy.searchPlaceholder'), 'attr') ?>" aria-label="<?= esc(lang('Kpcrudy.search'), 'attr') ?>">
                <button class="btn btn-outline-secondary" type="submit" title="<?= esc(lang('Kpcrudy.search'), 'attr') ?>"><i class="bi bi-search"></i></button>
            </div>
        <?php endif ?>

        <?php if ($filters !== []): ?>
            <button class="btn btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#kp-filters" aria-expanded="<?= $filtersActive > 0 ? 'true' : 'false' ?>">
                <i class="bi bi-funnel"></i> <?= esc(lang('Kpcrudy.filters')) ?>
                <?php if ($filtersActive > 0): ?><span class="badge text-bg-primary"><?= $filtersActive ?></span><?php endif ?>
            </button>
        <?php endif ?>

        <div class="ms-auto d-flex align-items-center gap-2">
            <label class="text-body-secondary small text-nowrap" for="kp-per-page"><?= esc(lang('Kpcrudy.perPage')) ?></label>
            <select id="kp-per-page" name="per_page" class="form-select form-select-sm w-auto" data-kp-autosubmit>
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

        <div class="card">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <?php if ($hasBulk): ?>
                                <th class="kp-check"><input type="checkbox" class="form-check-input" data-kp-check-all aria-label="<?= esc(lang('Kpcrudy.selectAll'), 'attr') ?>"></th>
                            <?php endif ?>
                            <?php foreach ($columns as $column): ?>
                                <th scope="col">
                                    <?php if ($column['sortable']): ?>
                                        <a href="<?= esc($column['url'], 'attr') ?>">
                                            <?= esc($column['label']) ?>
                                            <?php if ($column['sorted'] === 'asc'): ?><i class="bi bi-sort-up"></i><?php endif ?>
                                            <?php if ($column['sorted'] === 'desc'): ?><i class="bi bi-sort-down"></i><?php endif ?>
                                        </a>
                                    <?php else: ?>
                                        <?= esc($column['label']) ?>
                                    <?php endif ?>
                                </th>
                            <?php endforeach ?>
                            <th scope="col" class="text-end"><?= esc(lang('Kpcrudy.actions')) ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $row): ?>
                            <tr>
                                <?php if ($hasBulk): ?>
                                    <td><input type="checkbox" class="form-check-input" name="ids[]" value="<?= esc($row['id'], 'attr') ?>" aria-label="<?= esc(lang('Kpcrudy.select'), 'attr') ?>"></td>
                                <?php endif ?>
                                <?php foreach ($columns as $column): ?>
                                    <td><?= $row['cells'][$column['name']] ?></td>
                                <?php endforeach ?>
                                <td class="text-end kp-actions">
                                    <div class="btn-group">
                                        <?php foreach ($row['actions'] as $action): ?>
                                            <?= $this->render('partials/button', ['action' => $action, 'compact' => true]) ?>
                                        <?php endforeach ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach ?>
                        <?php if ($rows === []): ?>
                            <tr><td colspan="<?= $colspan ?>" class="text-center text-body-secondary py-4"><?= esc(lang('Kpcrudy.noRecords')) ?></td></tr>
                        <?php endif ?>
                    </tbody>
                </table>
            </div>

            <div class="card-footer d-flex flex-wrap align-items-center gap-3">
                <?php if ($hasBulk && $rows !== []): ?>
                    <div class="input-group input-group-sm w-auto">
                        <select name="action" class="form-select" aria-label="<?= esc(lang('Kpcrudy.bulkActions'), 'attr') ?>">
                            <option value=""><?= esc(lang('Kpcrudy.bulkActions')) ?></option>
                            <?php foreach ($bulkActions as $key => $bulk): ?>
                                <option<?= $this->attrs(['value' => $key, 'data-confirm' => $bulk['confirm'], 'data-url' => $bulk['url']]) ?>><?= esc($bulk['label']) ?></option>
                            <?php endforeach ?>
                        </select>
                        <button type="submit" class="btn btn-outline-primary"><?= esc(lang('Kpcrudy.apply')) ?></button>
                    </div>
                <?php endif ?>
                <div class="flex-grow-1">
                    <?= $this->render('partials/pagination', ['pagination' => $pagination]) ?>
                </div>
            </div>
        </div>
    </form>

    <form id="kp-post-form" method="post" class="d-none"><?= csrf_field() ?></form>
</div>
