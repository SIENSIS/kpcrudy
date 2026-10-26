<?php
/**
 * Un camp del formulari.
 *
 * @var \Kpcrudy\Renderer    $this
 * @var array<string, mixed> $field
 */
$name    = $field['name'];
$id      = 'kp-' . $name;
$type    = $field['type'];
$value   = $field['value'];
$invalid = $field['error'] !== null ? ' is-invalid' : '';
$attrs   = $field['attrs'];

$inputTypes = [
    'text' => 'text', 'email' => 'email', 'url' => 'url', 'number' => 'number', 'decimal' => 'number',
    'password' => 'password', 'date' => 'date', 'datetime' => 'datetime-local', 'time' => 'time', 'color' => 'color',
];

if ($type === 'hidden') {
    echo '<input type="hidden" name="' . esc($name, 'attr') . '" value="' . esc((string) $value, 'attr') . '">';

    return;
}
?>
<div class="row mb-3">
    <label class="col-md-3 col-form-label<?= $field['required'] ? ' fw-semibold' : '' ?>" for="<?= esc($id, 'attr') ?>">
        <?= esc($field['label']) ?><?= $field['required'] ? ' <span class="text-danger">*</span>' : '' ?>
    </label>
    <div class="col-md-9">
        <?php if ($field['html'] !== null): ?>
            <?= $field['html'] ?>

        <?php elseif ($type === 'readonly'): ?>
            <div class="form-control-plaintext"><?= $field['display'] ?? '' ?></div>

        <?php elseif ($type === 'textarea'): ?>
            <textarea<?= $this->attrs(['id' => $id, 'name' => $name, 'class' => 'form-control' . $invalid, 'rows' => 4, 'required' => $field['required']] + $attrs) ?>><?= esc((string) $value) ?></textarea>

        <?php elseif ($type === 'boolean'): ?>
            <input type="hidden" name="<?= esc($name, 'attr') ?>" value="0">
            <div class="form-check form-switch mt-2">
                <input<?= $this->attrs(['type' => 'checkbox', 'id' => $id, 'name' => $name, 'value' => '1', 'class' => 'form-check-input' . $invalid, 'checked' => (bool) $value] + $attrs) ?>>
            </div>

        <?php elseif (in_array($type, ['relation', 'enum', 'select'], true)): ?>
            <select<?= $this->attrs(['id' => $id, 'name' => $name, 'class' => 'form-select' . $invalid, 'required' => $field['required']] + $attrs) ?>>
                <option value=""><?= esc(lang('Kpcrudy.selectOption')) ?></option>
                <?php foreach ($field['options'] as $optionValue => $optionLabel): ?>
                    <option value="<?= esc((string) $optionValue, 'attr') ?>"<?= $value !== null && (string) $optionValue === (string) $value ? ' selected' : '' ?>><?= esc($optionLabel) ?></option>
                <?php endforeach ?>
            </select>

        <?php elseif ($type === 'relation_nn'): ?>
            <?php $selected = array_map('strval', (array) $value); ?>
            <select<?= $this->attrs(['id' => $id, 'name' => $name . '[]', 'class' => 'form-select' . $invalid, 'multiple' => true, 'size' => min(8, max(3, count($field['options'])))] + $attrs) ?>>
                <?php foreach ($field['options'] as $optionValue => $optionLabel): ?>
                    <option value="<?= esc((string) $optionValue, 'attr') ?>"<?= in_array((string) $optionValue, $selected, true) ? ' selected' : '' ?>><?= esc($optionLabel) ?></option>
                <?php endforeach ?>
            </select>
            <div class="form-text"><?= esc(lang('Kpcrudy.multipleHint')) ?></div>

        <?php elseif (in_array($type, ['upload', 'image'], true)): ?>
            <?php if ($field['file'] !== null): ?>
                <div class="mb-2 d-flex align-items-center gap-3">
                    <?php if ($field['file']['isImage']): ?>
                        <a href="<?= esc($field['file']['url'], 'attr') ?>" target="_blank"><img src="<?= esc($field['file']['url'], 'attr') ?>" alt="" class="img-thumbnail kp-preview"></a>
                    <?php else: ?>
                        <a href="<?= esc($field['file']['url'], 'attr') ?>" target="_blank"><i class="bi bi-paperclip"></i> <?= esc($field['file']['name']) ?></a>
                    <?php endif ?>
                    <?php if (! $field['required']): ?>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="<?= esc($id, 'attr') ?>-remove" name="<?= esc($name, 'attr') ?>__remove" value="1">
                            <label class="form-check-label" for="<?= esc($id, 'attr') ?>-remove"><?= esc(lang('Kpcrudy.removeFile')) ?></label>
                        </div>
                    <?php endif ?>
                </div>
            <?php endif ?>
            <input<?= $this->attrs(['type' => 'file', 'id' => $id, 'name' => $name, 'class' => 'form-control' . $invalid, 'accept' => $type === 'image' ? 'image/*' : null, 'required' => $field['required'] && $field['file'] === null] + $attrs) ?>>

        <?php else: ?>
            <input<?= $this->attrs([
                'type'         => $inputTypes[$type] ?? 'text',
                'id'           => $id,
                'name'         => $name,
                'value'        => (string) $value,
                'class'        => ($type === 'color' ? 'form-control form-control-color' : 'form-control') . $invalid,
                'maxlength'    => $field['maxlength'],
                'step'         => $type === 'decimal' ? 'any' : null,
                'required'     => $field['required'],
                'autocomplete' => $type === 'password' ? 'new-password' : null,
            ] + $attrs) ?>>
        <?php endif ?>

        <?php if ($field['error'] !== null): ?>
            <div class="invalid-feedback d-block"><?= esc($field['error']) ?></div>
        <?php endif ?>
        <?php if ($field['help'] !== null): ?>
            <div class="form-text"><?= esc($field['help']) ?></div>
        <?php endif ?>
    </div>
</div>
