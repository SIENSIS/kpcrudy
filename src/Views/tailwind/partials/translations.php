<?php
/**
 * Tema Tailwind: camps traduïts (setTranslations()), un bloc per idioma.
 *
 * @var \Kpcrudy\Renderer    $this
 * @var array<string, mixed> $field Amb 'tabs': [locale, label, required, hasError, inputs]
 */
?>
<div class="kp-translations mb-4 space-y-4">
    <?php foreach ($field['tabs'] as $tab): ?>
        <fieldset class="rounded-lg border p-4 <?= $tab['hasError'] ? 'border-red-400' : 'border-slate-200' ?>">
            <legend class="px-1 text-sm font-semibold"><?= esc($tab['label']) ?><?= $tab['required'] ? ' <span class="text-red-600">*</span>' : '' ?></legend>
            <?php foreach ($tab['inputs'] as $input): ?>
                <?php $class = 'block w-full rounded-md border bg-white px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 '
                    . ($input['error'] !== null ? 'border-red-500' : 'border-slate-300') ?>
                <div class="mb-3 grid gap-1 sm:grid-cols-4 sm:gap-4">
                    <label class="pt-2 text-sm <?= $input['required'] ? 'font-semibold' : '' ?>" for="<?= esc($input['id'], 'attr') ?>">
                        <?= esc($input['label']) ?><?= $input['required'] ? ' <span class="text-red-600">*</span>' : '' ?>
                    </label>
                    <div class="sm:col-span-3">
                        <?php if ($input['type'] === 'textarea'): ?>
                            <textarea<?= $this->attrs(['id' => $input['id'], 'name' => $input['name'], 'class' => $class, 'rows' => 4, 'required' => $input['required'], 'lang' => $tab['locale']] + $input['attrs']) ?>><?= esc($input['value']) ?></textarea>
                        <?php else: ?>
                            <input<?= $this->attrs(['type' => 'text', 'id' => $input['id'], 'name' => $input['name'], 'value' => $input['value'], 'class' => $class, 'maxlength' => $input['maxlength'], 'required' => $input['required'], 'lang' => $tab['locale']] + $input['attrs']) ?>>
                        <?php endif ?>
                        <?php if ($input['error'] !== null): ?>
                            <p class="mt-1 text-xs text-red-600"><?= esc($input['error']) ?></p>
                        <?php endif ?>
                        <?php if ($input['help'] !== null): ?>
                            <p class="mt-1 text-xs text-slate-500"><?= esc($input['help']) ?></p>
                        <?php endif ?>
                    </div>
                </div>
            <?php endforeach ?>
        </fieldset>
    <?php endforeach ?>
</div>
