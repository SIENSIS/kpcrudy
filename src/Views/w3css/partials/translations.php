<?php
/**
 * Tema W3.CSS: camps traduïts (setTranslations()), un bloc per idioma.
 *
 * @var \Kpcrudy\Renderer    $this
 * @var array<string, mixed> $field Amb 'tabs': [locale, label, required, hasError, inputs]
 */
?>
<div class="kp-translations">
    <?php foreach ($field['tabs'] as $tab): ?>
        <fieldset class="w3-border w3-round w3-margin-bottom<?= $tab['hasError'] ? ' w3-border-red' : '' ?>">
            <legend class="w3-padding-small">
                <b><?= esc($tab['label']) ?></b><?= $tab['required'] ? ' <span class="w3-text-red">*</span>' : '' ?>
            </legend>
            <?php foreach ($tab['inputs'] as $input): ?>
                <?php $class = 'w3-input w3-border w3-round' . ($input['error'] !== null ? ' w3-border-red' : '') ?>
                <div class="w3-row w3-margin-bottom">
                    <label class="w3-col m3 w3-padding-small" for="<?= esc($input['id'], 'attr') ?>">
                        <?= $input['required'] ? '<b>' . esc($input['label']) . '</b> <span class="w3-text-red">*</span>' : esc($input['label']) ?>
                    </label>
                    <div class="w3-col m9">
                        <?php if ($input['type'] === 'textarea'): ?>
                            <textarea<?= $this->attrs(['id' => $input['id'], 'name' => $input['name'], 'class' => $class, 'rows' => 4, 'required' => $input['required'], 'lang' => $tab['locale']] + $input['attrs']) ?>><?= esc($input['value']) ?></textarea>
                        <?php else: ?>
                            <input<?= $this->attrs(['type' => 'text', 'id' => $input['id'], 'name' => $input['name'], 'value' => $input['value'], 'class' => $class, 'maxlength' => $input['maxlength'], 'required' => $input['required'], 'lang' => $tab['locale']] + $input['attrs']) ?>>
                        <?php endif ?>
                        <?php if ($input['error'] !== null): ?>
                            <div class="w3-small w3-text-red"><?= esc($input['error']) ?></div>
                        <?php endif ?>
                        <?php if ($input['help'] !== null): ?>
                            <div class="w3-small w3-text-grey"><?= esc($input['help']) ?></div>
                        <?php endif ?>
                    </div>
                </div>
            <?php endforeach ?>
        </fieldset>
    <?php endforeach ?>
</div>
