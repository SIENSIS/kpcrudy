<?php
/**
 * Tema W3.CSS: un camp del formulari.
 *
 * @var \Kpcrudy\Renderer    $this
 * @var array<string, mixed> $field
 */
$name    = $field['name'];
$id      = 'kp-' . $name;
$type    = $field['type'];
$value   = $field['value'];
$border  = $field['error'] !== null ? ' w3-border-red' : '';
$attrs   = $field['attrs'];
$input   = 'w3-input w3-border w3-round' . $border;

$inputTypes = [
    'text' => 'text', 'email' => 'email', 'url' => 'url', 'number' => 'number', 'decimal' => 'number',
    'password' => 'password', 'date' => 'date', 'datetime' => 'datetime-local', 'time' => 'time', 'color' => 'color',
];

if ($type === 'hidden') {
    echo '<input type="hidden" name="' . esc($name, 'attr') . '" value="' . esc((string) $value, 'attr') . '">';

    return;
}
?>
<div class="w3-row w3-margin-bottom">
    <label class="w3-col m3 w3-padding-small" for="<?= esc($id, 'attr') ?>">
        <?= $field['required'] ? '<b>' . esc($field['label']) . '</b> <span class="w3-text-red">*</span>' : esc($field['label']) ?>
    </label>
    <div class="w3-col m9">
        <?php if ($field['html'] !== null): ?>
            <?= $field['html'] ?>

        <?php elseif ($type === 'readonly'): ?>
            <div class="w3-padding-small"><?= $field['display'] ?? '' ?></div>

        <?php elseif ($type === 'textarea'): ?>
            <textarea<?= $this->attrs(['id' => $id, 'name' => $name, 'class' => $input, 'rows' => 4, 'required' => $field['required']] + $attrs) ?>><?= esc((string) $value) ?></textarea>

        <?php elseif ($type === 'boolean'): ?>
            <input type="hidden" name="<?= esc($name, 'attr') ?>" value="0">
            <input<?= $this->attrs(['type' => 'checkbox', 'id' => $id, 'name' => $name, 'value' => '1', 'class' => 'w3-check', 'checked' => (bool) $value] + $attrs) ?>>

        <?php elseif (in_array($type, ['relation', 'enum', 'select'], true)): ?>
            <select<?= $this->attrs(['id' => $id, 'name' => $name, 'class' => 'w3-select w3-border w3-round' . $border, 'required' => $field['required']] + $attrs) ?>>
                <option value=""><?= esc(lang('Kpcrudy.selectOption')) ?></option>
                <?php foreach ($field['options'] as $optionValue => $optionLabel): ?>
                    <option value="<?= esc((string) $optionValue, 'attr') ?>"<?= $value !== null && (string) $optionValue === (string) $value ? ' selected' : '' ?>><?= esc($optionLabel) ?></option>
                <?php endforeach ?>
            </select>

        <?php elseif ($type === 'relation_nn'): ?>
            <?php $selected = array_map('strval', (array) $value); ?>
            <select<?= $this->attrs(['id' => $id, 'name' => $name . '[]', 'class' => 'w3-select w3-border w3-round' . $border, 'multiple' => true, 'size' => min(8, max(3, count($field['options'])))] + $attrs) ?>>
                <?php foreach ($field['options'] as $optionValue => $optionLabel): ?>
                    <option value="<?= esc((string) $optionValue, 'attr') ?>"<?= in_array((string) $optionValue, $selected, true) ? ' selected' : '' ?>><?= esc($optionLabel) ?></option>
                <?php endforeach ?>
            </select>
            <div class="w3-small w3-text-grey"><?= esc(lang('Kpcrudy.multipleHint')) ?></div>

        <?php elseif (in_array($type, ['upload', 'image'], true)): ?>
            <?php if ($field['file'] !== null): ?>
                <div class="w3-margin-bottom">
                    <?php if ($field['file']['isImage']): ?>
                        <a href="<?= esc($field['file']['url'], 'attr') ?>" target="_blank"><img src="<?= esc($field['file']['url'], 'attr') ?>" alt="" class="w3-border w3-round kp-preview"></a>
                    <?php else: ?>
                        <a href="<?= esc($field['file']['url'], 'attr') ?>" target="_blank"><i class="bi bi-paperclip"></i> <?= esc($field['file']['name']) ?></a>
                    <?php endif ?>
                    <?php if (! $field['required']): ?>
                        <label class="w3-margin-left"><input type="checkbox" class="w3-check" name="<?= esc($name, 'attr') ?>__remove" value="1"> <?= esc(lang('Kpcrudy.removeFile')) ?></label>
                    <?php endif ?>
                </div>
            <?php endif ?>
            <input<?= $this->attrs(['type' => 'file', 'id' => $id, 'name' => $name, 'class' => $input, 'accept' => $type === 'image' ? 'image/*' : null, 'required' => $field['required'] && $field['file'] === null] + $attrs) ?>>

        <?php else: ?>
            <input<?= $this->attrs([
                'type'         => $inputTypes[$type] ?? 'text',
                'id'           => $id,
                'name'         => $name,
                'value'        => (string) $value,
                'class'        => $type === 'color' ? 'w3-border w3-round' : $input,
                'maxlength'    => $field['maxlength'],
                'step'         => $type === 'decimal' ? 'any' : null,
                'required'     => $field['required'],
                'autocomplete' => $type === 'password' ? 'new-password' : null,
            ] + $attrs) ?>>
        <?php endif ?>

        <?php if ($field['error'] !== null): ?>
            <div class="w3-small w3-text-red"><?= esc($field['error']) ?></div>
        <?php endif ?>
        <?php if ($field['help'] !== null): ?>
            <div class="w3-small w3-text-grey"><?= esc($field['help']) ?></div>
        <?php endif ?>
    </div>
</div>
