<?php
/**
 * Tema Tailwind: panell de filtres amb <details>, sense JavaScript.
 *
 * @var \Kpcrudy\Renderer          $this
 * @var list<array<string, mixed>> $filters
 * @var array<string, mixed>       $filterHidden
 * @var string                     $listUrl
 * @var string                     $filtersReset
 * @var int                        $filtersActive
 */
$input = 'block w-full rounded-md border border-slate-300 bg-white px-2 py-1.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500';
?>
<details class="mb-4"<?= $filtersActive > 0 ? ' open' : '' ?>>
    <summary class="inline-flex cursor-pointer items-center gap-1 rounded-md border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50">
        <i class="bi bi-funnel"></i> <?= esc(lang('Kpcrudy.filters')) ?>
        <?php if ($filtersActive > 0): ?><span class="rounded-full bg-blue-600 px-1.5 text-xs text-white"><?= $filtersActive ?></span><?php endif ?>
    </summary>

    <form method="get" action="<?= esc($listUrl, 'attr') ?>" class="mt-3 rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
        <?= $this->hidden($filterHidden) ?>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <?php foreach ($filters as $filter): ?>
                <?php $id = 'kp-f-' . $filter['name']; ?>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600" for="<?= esc($id, 'attr') ?>"><?= esc($filter['label']) ?></label>

                    <?php if ($filter['kind'] === 'select'): ?>
                        <select class="<?= $input ?>" id="<?= esc($id, 'attr') ?>" name="f[<?= esc($filter['name'], 'attr') ?>]">
                            <option value=""><?= esc(lang('Kpcrudy.all')) ?></option>
                            <?php foreach ($filter['options'] as $value => $label): ?>
                                <option value="<?= esc((string) $value, 'attr') ?>"<?= (string) $value === $filter['value'] ? ' selected' : '' ?>><?= esc($label) ?></option>
                            <?php endforeach ?>
                        </select>

                    <?php elseif ($filter['kind'] === 'range'): ?>
                        <div class="flex gap-2">
                            <input type="<?= esc($filter['input'], 'attr') ?>" class="<?= $input ?>" id="<?= esc($id, 'attr') ?>" step="any"
                                   name="f[<?= esc($filter['name'], 'attr') ?>][from]" value="<?= esc($filter['value']['from'], 'attr') ?>" placeholder="<?= esc(lang('Kpcrudy.from'), 'attr') ?>" title="<?= esc(lang('Kpcrudy.from'), 'attr') ?>">
                            <input type="<?= esc($filter['input'], 'attr') ?>" class="<?= $input ?>" step="any"
                                   name="f[<?= esc($filter['name'], 'attr') ?>][to]" value="<?= esc($filter['value']['to'], 'attr') ?>" placeholder="<?= esc(lang('Kpcrudy.to'), 'attr') ?>" title="<?= esc(lang('Kpcrudy.to'), 'attr') ?>">
                        </div>

                    <?php else: ?>
                        <input type="text" class="<?= $input ?>" id="<?= esc($id, 'attr') ?>"
                               name="f[<?= esc($filter['name'], 'attr') ?>]" value="<?= esc($filter['value'], 'attr') ?>">
                    <?php endif ?>
                </div>
            <?php endforeach ?>
        </div>
        <div class="mt-4 flex gap-2">
            <button type="submit" class="inline-flex items-center gap-1 rounded-md border border-blue-600 bg-blue-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-blue-700"><i class="bi bi-funnel"></i> <?= esc(lang('Kpcrudy.applyFilters')) ?></button>
            <a href="<?= esc($filtersReset, 'attr') ?>" class="inline-flex items-center rounded-md border border-slate-300 bg-white px-3 py-1.5 text-sm text-slate-700 hover:bg-slate-50"><?= esc(lang('Kpcrudy.resetFilters')) ?></a>
        </div>
    </form>
</details>
