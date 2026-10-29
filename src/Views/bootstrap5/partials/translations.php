<?php
/**
 * Camps traduïts (setTranslations()): una pestanya per idioma.
 *
 * @var \Kpcrudy\Renderer    $this
 * @var array<string, mixed> $field Amb 'tabs': [locale, label, required, hasError, inputs]
 */
$active = 0;

// S'obre la primera pestanya amb errors
foreach ($field['tabs'] as $i => $tab) {
    if ($tab['hasError']) {
        $active = $i;
        break;
    }
}
?>
<div class="kp-translations mb-3">
    <ul class="nav nav-tabs" role="tablist">
        <?php foreach ($field['tabs'] as $i => $tab): ?>
            <li class="nav-item" role="presentation">
                <button type="button" role="tab" data-bs-toggle="tab"
                        class="nav-link<?= $i === $active ? ' active' : '' ?><?= $tab['hasError'] ? ' text-danger' : '' ?>"
                        data-bs-target="#kp-tr-<?= esc($tab['locale'], 'attr') ?>"
                        aria-controls="kp-tr-<?= esc($tab['locale'], 'attr') ?>" aria-selected="<?= $i === $active ? 'true' : 'false' ?>">
                    <?= esc($tab['label']) ?><?= $tab['required'] ? ' <span class="text-danger">*</span>' : '' ?>
                    <?php if ($tab['hasError']): ?><i class="bi bi-exclamation-circle"></i><?php endif ?>
                </button>
            </li>
        <?php endforeach ?>
    </ul>
    <div class="tab-content border border-top-0 rounded-bottom p-3">
        <?php foreach ($field['tabs'] as $i => $tab): ?>
            <div class="tab-pane fade<?= $i === $active ? ' show active' : '' ?>" id="kp-tr-<?= esc($tab['locale'], 'attr') ?>" role="tabpanel">
                <?php foreach ($tab['inputs'] as $input): ?>
                    <div class="row mb-3">
                        <label class="col-md-3 col-form-label<?= $input['required'] ? ' fw-semibold' : '' ?>" for="<?= esc($input['id'], 'attr') ?>">
                            <?= esc($input['label']) ?><?= $input['required'] ? ' <span class="text-danger">*</span>' : '' ?>
                        </label>
                        <div class="col-md-9">
                            <?php $invalid = $input['error'] !== null ? ' is-invalid' : '' ?>
                            <?php if ($input['type'] === 'textarea'): ?>
                                <textarea<?= $this->attrs(['id' => $input['id'], 'name' => $input['name'], 'class' => 'form-control' . $invalid, 'rows' => 4, 'required' => $input['required'], 'lang' => $tab['locale']] + $input['attrs']) ?>><?= esc($input['value']) ?></textarea>
                            <?php else: ?>
                                <input<?= $this->attrs(['type' => 'text', 'id' => $input['id'], 'name' => $input['name'], 'value' => $input['value'], 'class' => 'form-control' . $invalid, 'maxlength' => $input['maxlength'], 'required' => $input['required'], 'lang' => $tab['locale']] + $input['attrs']) ?>>
                            <?php endif ?>
                            <?php if ($input['error'] !== null): ?>
                                <div class="invalid-feedback d-block"><?= esc($input['error']) ?></div>
                            <?php endif ?>
                            <?php if ($input['help'] !== null): ?>
                                <div class="form-text"><?= esc($input['help']) ?></div>
                            <?php endif ?>
                        </div>
                    </div>
                <?php endforeach ?>
            </div>
        <?php endforeach ?>
    </div>
</div>
