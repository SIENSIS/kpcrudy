<?php
/**
 * Tema W3.CSS: panell de filtres. Es fa servir <details>, que s'obre i es
 * tanca sense JavaScript.
 *
 * @var \Kpcrudy\Renderer          $this
 * @var list<array<string, mixed>> $filters
 * @var array<string, mixed>       $filterHidden
 * @var string                     $listUrl
 * @var string                     $filtersReset
 * @var int                        $filtersActive
 */
?>
<details class="kp-filters w3-margin-bottom"<?= $filtersActive > 0 ? ' open' : '' ?>>
    <summary class="w3-button w3-white w3-border w3-round">
        <i class="bi bi-funnel"></i> <?= esc(lang('Kpcrudy.filters')) ?>
        <?php if ($filtersActive > 0): ?><span class="w3-tag w3-round w3-small w3-blue"><?= $filtersActive ?></span><?php endif ?>
    </summary>

    <form method="get" action="<?= esc($listUrl, 'attr') ?>" class="w3-card w3-white w3-padding w3-margin-top">
        <?= $this->hidden($filterHidden) ?>
        <div class="w3-row-padding kp-row-flush">
            <?php foreach ($filters as $filter): ?>
                <?php $id = 'kp-f-' . $filter['name']; ?>
                <div class="w3-col m6 l4 w3-margin-bottom">
                    <label class="w3-small w3-text-dark-grey" for="<?= esc($id, 'attr') ?>"><b><?= esc($filter['label']) ?></b></label>

                    <?php if ($filter['kind'] === 'select'): ?>
                        <select class="w3-select w3-border w3-round" id="<?= esc($id, 'attr') ?>" name="f[<?= esc($filter['name'], 'attr') ?>]">
                            <option value=""><?= esc(lang('Kpcrudy.all')) ?></option>
                            <?php foreach ($filter['options'] as $value => $label): ?>
                                <option value="<?= esc((string) $value, 'attr') ?>"<?= (string) $value === $filter['value'] ? ' selected' : '' ?>><?= esc($label) ?></option>
                            <?php endforeach ?>
                        </select>

                    <?php elseif ($filter['kind'] === 'range'): ?>
                        <div class="w3-cell-row">
                            <div class="w3-cell kp-pr4">
                                <input type="<?= esc($filter['input'], 'attr') ?>" class="w3-input w3-border w3-round" id="<?= esc($id, 'attr') ?>" step="any"
                                       name="f[<?= esc($filter['name'], 'attr') ?>][from]" value="<?= esc($filter['value']['from'], 'attr') ?>" placeholder="<?= esc(lang('Kpcrudy.from'), 'attr') ?>" title="<?= esc(lang('Kpcrudy.from'), 'attr') ?>">
                            </div>
                            <div class="w3-cell kp-pl4">
                                <input type="<?= esc($filter['input'], 'attr') ?>" class="w3-input w3-border w3-round" step="any"
                                       name="f[<?= esc($filter['name'], 'attr') ?>][to]" value="<?= esc($filter['value']['to'], 'attr') ?>" placeholder="<?= esc(lang('Kpcrudy.to'), 'attr') ?>" title="<?= esc(lang('Kpcrudy.to'), 'attr') ?>">
                            </div>
                        </div>

                    <?php else: ?>
                        <input type="text" class="w3-input w3-border w3-round" id="<?= esc($id, 'attr') ?>"
                               name="f[<?= esc($filter['name'], 'attr') ?>]" value="<?= esc($filter['value'], 'attr') ?>">
                    <?php endif ?>
                </div>
            <?php endforeach ?>
        </div>
        <button type="submit" class="w3-button w3-blue w3-round"><i class="bi bi-funnel"></i> <?= esc(lang('Kpcrudy.applyFilters')) ?></button>
        <a href="<?= esc($filtersReset, 'attr') ?>" class="w3-button w3-white w3-border w3-round"><?= esc(lang('Kpcrudy.resetFilters')) ?></a>
    </form>
</details>
