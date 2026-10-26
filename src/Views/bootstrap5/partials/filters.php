<?php
/**
 * Panell de filtres (formulari GET).
 *
 * @var \Kpcrudy\Renderer          $this
 * @var list<array<string, mixed>> $filters
 * @var array<string, mixed>       $filterHidden
 * @var string                     $listUrl
 * @var string                     $filtersReset
 * @var int                        $filtersActive
 */
?>
<div class="collapse<?= $filtersActive > 0 ? ' show' : '' ?>" id="kp-filters">
    <form method="get" action="<?= esc($listUrl, 'attr') ?>" class="card card-body mb-3">
        <?= $this->hidden($filterHidden) ?>
        <div class="row g-3">
            <?php foreach ($filters as $filter): ?>
                <?php $id = 'kp-f-' . $filter['name']; ?>
                <div class="<?= $filter['kind'] === 'range' ? 'col-md-6 col-xl-4' : 'col-md-4 col-xl-3' ?>">
                    <label class="form-label small fw-semibold" for="<?= esc($id, 'attr') ?>"><?= esc($filter['label']) ?></label>

                    <?php if ($filter['kind'] === 'select'): ?>
                        <select class="form-select form-select-sm" id="<?= esc($id, 'attr') ?>" name="f[<?= esc($filter['name'], 'attr') ?>]">
                            <option value=""><?= esc(lang('Kpcrudy.all')) ?></option>
                            <?php foreach ($filter['options'] as $value => $label): ?>
                                <option value="<?= esc((string) $value, 'attr') ?>"<?= (string) $value === $filter['value'] ? ' selected' : '' ?>><?= esc($label) ?></option>
                            <?php endforeach ?>
                        </select>

                    <?php elseif ($filter['kind'] === 'range'): ?>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text"><?= esc(lang('Kpcrudy.from')) ?></span>
                            <input type="<?= esc($filter['input'], 'attr') ?>" class="form-control" id="<?= esc($id, 'attr') ?>"
                                   name="f[<?= esc($filter['name'], 'attr') ?>][from]" value="<?= esc($filter['value']['from'], 'attr') ?>" step="any">
                            <span class="input-group-text"><?= esc(lang('Kpcrudy.to')) ?></span>
                            <input type="<?= esc($filter['input'], 'attr') ?>" class="form-control"
                                   name="f[<?= esc($filter['name'], 'attr') ?>][to]" value="<?= esc($filter['value']['to'], 'attr') ?>" step="any">
                        </div>

                    <?php else: ?>
                        <input type="text" class="form-control form-control-sm" id="<?= esc($id, 'attr') ?>"
                               name="f[<?= esc($filter['name'], 'attr') ?>]" value="<?= esc($filter['value'], 'attr') ?>">
                    <?php endif ?>
                </div>
            <?php endforeach ?>
        </div>
        <div class="d-flex gap-2 mt-3">
            <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-funnel"></i> <?= esc(lang('Kpcrudy.applyFilters')) ?></button>
            <a href="<?= esc($filtersReset, 'attr') ?>" class="btn btn-sm btn-outline-secondary"><?= esc(lang('Kpcrudy.resetFilters')) ?></a>
        </div>
    </form>
</div>
