<?php
/**
 * Tema Tailwind: un camp del formulari.
 *
 * @var \Kpcrudy\Renderer    $this
 * @var array<string, mixed> $field
 */
$name   = $field['name'];
$id     = 'kp-' . $name;
$type   = $field['type'];
$value  = $field['value'];
$attrs  = $field['attrs'];
$input  = 'block w-full rounded-md border bg-white px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 '
    . ($field['error'] !== null ? 'border-red-500' : 'border-slate-300');

$inputTypes = [
    'text' => 'text', 'email' => 'email', 'url' => 'url', 'number' => 'number', 'decimal' => 'number',
    'password' => 'password', 'date' => 'date', 'datetime' => 'datetime-local', 'time' => 'time', 'color' => 'color',
];

// Camps traduïts (setTranslations()): un bloc per idioma
if ($type === 'translations') {
    echo $this->render('partials/translations', ['field' => $field]);

    return;
}

if ($type === 'hidden') {
    echo '<input type="hidden" name="' . esc($name, 'attr') . '" value="' . esc((string) $value, 'attr') . '">';

    return;
}
?>
<div class="grid gap-1 py-3 sm:grid-cols-4 sm:gap-4">
    <label class="pt-2 text-sm <?= $field['required'] ? 'font-semibold' : '' ?>" for="<?= esc($id, 'attr') ?>">
        <?= esc($field['label']) ?><?= $field['required'] ? ' <span class="text-red-600">*</span>' : '' ?>
    </label>
    <div class="sm:col-span-3">
        <?php if ($field['html'] !== null): ?>
            <?= $field['html'] ?>

        <?php elseif ($type === 'readonly'): ?>
            <div class="pt-2 text-sm"><?= $field['display'] ?? '' ?></div>

        <?php elseif ($type === 'textarea'): ?>
            <textarea<?= $this->attrs(['id' => $id, 'name' => $name, 'class' => $input, 'rows' => 4, 'required' => $field['required']] + $attrs) ?>><?= esc((string) $value) ?></textarea>

        <?php elseif ($type === 'boolean'): ?>
            <input type="hidden" name="<?= esc($name, 'attr') ?>" value="0">
            <input<?= $this->attrs(['type' => 'checkbox', 'id' => $id, 'name' => $name, 'value' => '1', 'class' => 'mt-2 h-4 w-4 rounded border-slate-300 accent-blue-600', 'checked' => (bool) $value] + $attrs) ?>>

        <?php elseif (in_array($type, ['relation', 'enum', 'select'], true)): ?>
            <select<?= $this->attrs(['id' => $id, 'name' => $name, 'class' => $input, 'required' => $field['required']] + $attrs) ?>>
                <option value=""><?= esc(lang('Kpcrudy.selectOption')) ?></option>
                <?php foreach ($field['options'] as $optionValue => $optionLabel): ?>
                    <option value="<?= esc((string) $optionValue, 'attr') ?>"<?= $value !== null && (string) $optionValue === (string) $value ? ' selected' : '' ?>><?= esc($optionLabel) ?></option>
                <?php endforeach ?>
            </select>

        <?php elseif (in_array($type, ['relation_nn', 'multiselect'], true)): ?>
            <?php $selected = array_map('strval', (array) $value); ?>
            <select<?= $this->attrs(['id' => $id, 'name' => $name . '[]', 'class' => $input, 'multiple' => true, 'size' => min(8, max(3, count($field['options'])))] + $attrs) ?>>
                <?php foreach ($field['options'] as $optionValue => $optionLabel): ?>
                    <option value="<?= esc((string) $optionValue, 'attr') ?>"<?= in_array((string) $optionValue, $selected, true) ? ' selected' : '' ?>><?= esc($optionLabel) ?></option>
                <?php endforeach ?>
            </select>
            <p class="mt-1 text-xs text-slate-500"><?= esc(lang('Kpcrudy.multipleHint')) ?></p>

        <?php elseif (in_array($type, ['upload', 'image'], true)): ?>
            <?php if ($field['file'] !== null): ?>
                <div class="mb-2 flex items-center gap-4">
                    <?php if ($field['file']['isImage']): ?>
                        <a href="<?= esc($field['file']['url'], 'attr') ?>" target="_blank"><img src="<?= esc($field['file']['url'], 'attr') ?>" alt="" class="max-h-40 rounded-md border border-slate-200"></a>
                    <?php else: ?>
                        <a href="<?= esc($field['file']['url'], 'attr') ?>" target="_blank" class="text-blue-600 underline"><i class="bi bi-paperclip"></i> <?= esc($field['file']['name']) ?></a>
                    <?php endif ?>
                    <?php if (! $field['required']): ?>
                        <label class="text-sm"><input type="checkbox" class="accent-blue-600" name="<?= esc($name, 'attr') ?>__remove" value="1"> <?= esc(lang('Kpcrudy.removeFile')) ?></label>
                    <?php endif ?>
                </div>
            <?php endif ?>
            <input<?= $this->attrs(['type' => 'file', 'id' => $id, 'name' => $name, 'class' => 'block w-full text-sm file:mr-3 file:rounded-md file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm', 'accept' => $type === 'image' ? 'image/*' : null, 'required' => $field['required'] && $field['file'] === null] + $attrs) ?>>

        <?php else: ?>
            <input<?= $this->attrs([
                'type'         => $inputTypes[$type] ?? 'text',
                'id'           => $id,
                'name'         => $name,
                'value'        => (string) $value,
                'class'        => $type === 'color' ? 'h-10 w-16 rounded-md border border-slate-300' : $input,
                'maxlength'    => $field['maxlength'],
                'step'         => $type === 'decimal' ? 'any' : null,
                'required'     => $field['required'],
                'autocomplete' => $type === 'password' ? 'new-password' : null,
            ] + $attrs) ?>>
        <?php endif ?>

        <?php if ($field['error'] !== null): ?>
            <p class="mt-1 text-xs text-red-600"><?= esc($field['error']) ?></p>
        <?php endif ?>
        <?php if ($field['help'] !== null): ?>
            <p class="mt-1 text-xs text-slate-500"><?= esc($field['help']) ?></p>
        <?php endif ?>
    </div>
</div>
