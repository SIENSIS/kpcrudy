<?php
/**
 * Tema Tailwind: importació CSV ('upload', 'preview' i 'report').
 * Rep les mateixes variables que bootstrap5/import.php.
 *
 * @var \Kpcrudy\Renderer $this
 */
$tags = [
    'insert' => ['bg-green-600', lang('Kpcrudy.importRowNew')],
    'update' => ['bg-blue-600', lang('Kpcrudy.importRowUpdate')],
    'error'  => ['bg-red-600', lang('Kpcrudy.importRowError')],
];
$card   = 'overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm';
$button = 'inline-flex items-center gap-1 rounded-md border px-3 py-1.5 text-sm font-medium';
?>
<div class="kpcrudy">
    <?= $this->render('partials/assets') ?>

    <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
        <h1 class="text-2xl font-semibold"><?= esc($title) ?></h1>
        <a href="<?= esc($backUrl, 'attr') ?>" class="<?= $button ?> border-slate-300 bg-white text-slate-700 hover:bg-slate-50"><i class="bi bi-arrow-left"></i> <?= esc(lang('Kpcrudy.backToList')) ?></a>
    </div>

    <?= $this->render('partials/flash', ['flash' => $flash]) ?>

    <?php if ($error): ?>
        <div class="mb-4 rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-800"><?= esc($error) ?></div>
    <?php endif ?>

    <?php if ($step === 'upload'): ?>
        <div class="grid gap-4 lg:grid-cols-12">
            <form method="post" action="<?= esc($action, 'attr') ?>" enctype="multipart/form-data" class="<?= $card ?> lg:col-span-7">
                <?= csrf_field() ?>
                <input type="hidden" name="_kp_step" value="upload">
                <div class="space-y-5 p-4 text-sm">
                    <div>
                        <label class="mb-1 block font-semibold" for="kp-import-file"><?= esc(lang('Kpcrudy.importFile')) ?></label>
                        <input type="file" id="kp-import-file" name="file" accept=".csv,text/csv,text/plain" required class="block w-full text-sm file:mr-3 file:rounded-md file:border-0 file:bg-slate-100 file:px-3 file:py-2">
                        <p class="mt-1 text-xs text-slate-500"><?= esc(lang('Kpcrudy.importFileHelp', [$maxRows, $maxSize])) ?></p>
                    </div>
                    <fieldset>
                        <legend class="mb-1 font-semibold"><?= esc(lang('Kpcrudy.importMode')) ?></legend>
                        <?php if ($canInsert): ?>
                            <label class="block"><input type="radio" class="accent-blue-600" name="mode" value="insert"<?= $mode === 'insert' || ! $canUpdate ? ' checked' : '' ?>> <?= esc(lang('Kpcrudy.importModeInsert')) ?></label>
                        <?php endif ?>
                        <?php if ($canUpdate): ?>
                            <label class="block"><input type="radio" class="accent-blue-600" name="mode" value="upsert"<?= $mode === 'upsert' || ! $canInsert ? ' checked' : '' ?>> <?= esc(lang('Kpcrudy.importModeUpsert', [$keyLabel])) ?></label>
                        <?php endif ?>
                    </fieldset>
                    <fieldset>
                        <legend class="mb-1 font-semibold"><?= esc(lang('Kpcrudy.importOnError')) ?></legend>
                        <label class="block"><input type="radio" class="accent-blue-600" name="on_error" value="stop"<?= $onError === 'stop' ? ' checked' : '' ?>> <?= esc(lang('Kpcrudy.importOnErrorStop')) ?></label>
                        <label class="block"><input type="radio" class="accent-blue-600" name="on_error" value="skip"<?= $onError === 'skip' ? ' checked' : '' ?>> <?= esc(lang('Kpcrudy.importOnErrorSkip')) ?></label>
                    </fieldset>
                </div>
                <div class="flex items-center border-t border-slate-200 bg-slate-50 px-4 py-3">
                    <button type="submit" class="<?= $button ?> border-blue-600 bg-blue-600 text-white hover:bg-blue-700"><i class="bi bi-eye"></i> <?= esc(lang('Kpcrudy.importPreview')) ?></button>
                    <a href="<?= esc($backUrl, 'attr') ?>" class="ml-auto text-sm text-slate-600 underline"><?= esc(lang('Kpcrudy.cancel')) ?></a>
                </div>
            </form>
            <div class="<?= $card ?> p-4 text-sm lg:col-span-5">
                <h2 class="mb-2 font-semibold"><?= esc(lang('Kpcrudy.importColumnsHelp')) ?></h2>
                <ul class="mb-3 space-y-0.5">
                    <?php foreach ($fieldsHelp as $field): ?>
                        <li><b><?= esc($field['label']) ?></b><?= $field['required'] ? ' <span class="text-red-600">*</span>' : '' ?> <span class="text-slate-500">(<?= esc($field['name']) ?>)</span></li>
                    <?php endforeach ?>
                </ul>
                <p class="mb-3 text-xs text-slate-500"><?= esc(lang('Kpcrudy.importValuesHelp')) ?></p>
                <a href="<?= esc($templateUrl, 'attr') ?>" class="<?= $button ?> border-green-600 bg-white text-green-700 hover:bg-green-50"><i class="bi bi-download"></i> <?= esc(lang('Kpcrudy.importTemplate')) ?></a>
            </div>
        </div>

    <?php elseif ($step === 'preview'): ?>
        <div class="mb-3 flex flex-wrap items-center gap-2 text-sm">
            <span class="text-slate-500"><i class="bi bi-file-earmark-spreadsheet"></i> <?= esc($fileName) ?></span>
            <span class="rounded-full bg-green-600 px-2 text-xs text-white"><?= esc(lang('Kpcrudy.importCountNew', [$counts['insert']])) ?></span>
            <span class="rounded-full bg-blue-600 px-2 text-xs text-white"><?= esc(lang('Kpcrudy.importCountUpdate', [$counts['update']])) ?></span>
            <span class="rounded-full bg-red-600 px-2 text-xs text-white"><?= esc(lang('Kpcrudy.importCountError', [$counts['error']])) ?></span>
        </div>

        <div class="<?= $card ?> mb-4 p-4 text-sm">
            <h2 class="mb-2 font-semibold"><?= esc(lang('Kpcrudy.importMapping')) ?></h2>
            <div class="flex flex-wrap gap-2">
                <?php foreach ($columns as $column): ?>
                    <span class="rounded border border-slate-200 px-2 py-1 text-xs <?= $column['field'] === null ? 'text-slate-400' : '' ?>">
                        <?= esc($column['header']) ?> <i class="bi bi-arrow-right"></i>
                        <?= $column['field'] === null ? '<i>' . esc(lang('Kpcrudy.importIgnored')) . '</i>' : '<b>' . esc($column['field']) . '</b>' ?>
                        <?php if ($column['key']): ?><span class="rounded bg-slate-500 px-1 text-white"><?= esc(lang('Kpcrudy.importKey')) ?></span><?php endif ?>
                    </span>
                <?php endforeach ?>
            </div>
        </div>

        <?php
        $fields = [];
        foreach ($columns as $column) {
            if ($column['field'] !== null && array_key_exists($column['field'], $rows[0]['values'] ?? [])) {
                $fields[$column['field']] = $column['label'];
            }
        }
        ?>
        <div class="<?= $card ?> mb-4">
            <div class="border-b border-slate-200 px-4 py-2 text-sm font-semibold"><?= esc(lang('Kpcrudy.importRowsPreview', [count($rows), $total])) ?></div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 text-sm">
                    <thead class="bg-slate-50 text-left text-slate-600">
                        <tr>
                            <th class="px-3 py-2"><?= esc(lang('Kpcrudy.importLine')) ?></th>
                            <th class="px-3 py-2"></th>
                            <?php foreach ($fields as $label): ?><th class="px-3 py-2"><?= esc($label) ?></th><?php endforeach ?>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($rows as $row): ?>
                            <tr class="<?= $row['errors'] !== [] ? 'bg-red-50' : '' ?>">
                                <td class="px-3 py-1.5 text-slate-500"><?= (int) $row['line'] ?></td>
                                <td class="px-3 py-1.5"><span class="rounded-full px-2 text-xs text-white <?= $tags[$row['action']][0] ?>"><?= esc($tags[$row['action']][1]) ?></span></td>
                                <?php foreach (array_keys($fields) as $field): ?>
                                    <td class="px-3 py-1.5"><?= esc(mb_strimwidth((string) ($row['values'][$field] ?? ''), 0, 40, '…')) ?></td>
                                <?php endforeach ?>
                            </tr>
                        <?php endforeach ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php if ($errors !== []): ?>
            <div class="mb-4 rounded-lg border border-red-300 bg-red-50 p-4 text-sm text-red-800">
                <h2 class="mb-1 font-semibold"><?= esc(lang('Kpcrudy.importErrors')) ?></h2>
                <ul class="space-y-0.5">
                    <?php foreach ($errors as $row): ?>
                        <li><b><?= esc(lang('Kpcrudy.importLine')) ?> <?= (int) $row['line'] ?>:</b> <?= esc(implode(' ', $row['errors'])) ?></li>
                    <?php endforeach ?>
                </ul>
            </div>
        <?php endif ?>

        <form method="post" action="<?= esc($action, 'attr') ?>" class="flex flex-wrap items-center gap-2">
            <?= csrf_field() ?>
            <input type="hidden" name="_kp_step" value="confirm">
            <input type="hidden" name="token" value="<?= esc($token, 'attr') ?>">
            <button type="submit" class="<?= $button ?> border-green-600 bg-green-600 text-white hover:bg-green-700 disabled:opacity-50"<?= $canImport ? '' : ' disabled' ?>><i class="bi bi-upload"></i> <?= esc(lang('Kpcrudy.importConfirm', [$importable])) ?></button>
            <a href="<?= esc($action, 'attr') ?>" class="<?= $button ?> border-slate-300 bg-white text-slate-700 hover:bg-slate-50"><?= esc(lang('Kpcrudy.importBack')) ?></a>
            <?php if (! $canImport): ?>
                <span class="text-sm text-red-600"><?= esc(lang($importable === 0 ? 'Kpcrudy.importNothing' : 'Kpcrudy.importBlocked')) ?></span>
            <?php elseif ($counts['error'] > 0): ?>
                <span class="text-sm text-slate-500"><?= esc(lang('Kpcrudy.importSkipNotice', [$counts['error']])) ?></span>
            <?php endif ?>
        </form>

    <?php else: ?>
        <div class="mb-4 rounded-lg border px-4 py-3 text-sm <?= $result['aborted'] ? 'border-red-300 bg-red-50 text-red-800' : 'border-amber-300 bg-amber-50 text-amber-800' ?>">
            <?= esc($result['aborted'] ? lang('Kpcrudy.importAborted') : lang('Kpcrudy.importDone', [$result['created'], $result['updated'], $result['skipped']])) ?>
        </div>
        <div class="mb-4 rounded-lg border border-red-300 bg-red-50 p-4 text-sm text-red-800">
            <h2 class="mb-1 font-semibold"><?= esc(lang('Kpcrudy.importErrors')) ?> · <?= esc($fileName) ?></h2>
            <ul class="space-y-0.5">
                <?php foreach ($result['errors'] as $row): ?>
                    <li><?php if ($row['line'] > 0): ?><b><?= esc(lang('Kpcrudy.importLine')) ?> <?= (int) $row['line'] ?>:</b><?php endif ?> <?= esc(implode(' ', $row['errors'])) ?></li>
                <?php endforeach ?>
            </ul>
        </div>
        <div class="flex gap-2">
            <a href="<?= esc($action, 'attr') ?>" class="<?= $button ?> border-blue-600 bg-blue-600 text-white hover:bg-blue-700"><i class="bi bi-upload"></i> <?= esc(lang('Kpcrudy.importBack')) ?></a>
            <a href="<?= esc($backUrl, 'attr') ?>" class="<?= $button ?> border-slate-300 bg-white text-slate-700 hover:bg-slate-50"><?= esc(lang('Kpcrudy.backToList')) ?></a>
        </div>
    <?php endif ?>
</div>
