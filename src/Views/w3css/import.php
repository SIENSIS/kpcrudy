<?php
/**
 * Tema W3.CSS: importació CSV ('upload', 'preview' i 'report').
 * Rep les mateixes variables que bootstrap5/import.php.
 *
 * @var \Kpcrudy\Renderer $this
 */
$tags = [
    'insert' => ['w3-green', lang('Kpcrudy.importRowNew')],
    'update' => ['w3-blue', lang('Kpcrudy.importRowUpdate')],
    'error'  => ['w3-red', lang('Kpcrudy.importRowError')],
];
?>
<div class="kpcrudy">
    <?= $this->render('partials/assets') ?>

    <div class="w3-cell-row w3-margin-bottom">
        <?php if ($showTitle ?? true): ?><h2 class="w3-cell w3-cell-middle kp-m0"><?= esc($title) ?></h2><?php endif ?>
        <div class="w3-cell w3-cell-middle w3-right-align">
            <a href="<?= esc($backUrl, 'attr') ?>" class="w3-button w3-white w3-border w3-round"><i class="bi bi-arrow-left"></i> <?= esc(lang('Kpcrudy.backToList')) ?></a>
        </div>
    </div>

    <?= $this->render('partials/flash', ['flash' => $flash]) ?>

    <?php if ($error): ?>
        <div class="w3-panel w3-leftbar w3-pale-red w3-border-red"><p><?= esc($error) ?></p></div>
    <?php endif ?>

    <?php if ($step === 'upload'): ?>
        <div class="w3-row-padding kp-row-flush">
            <div class="w3-col l7">
                <form method="post" action="<?= esc($action, 'attr') ?>" enctype="multipart/form-data" class="w3-card w3-white">
                    <?= csrf_field() ?>
                    <input type="hidden" name="_kp_step" value="upload">
                    <div class="w3-container w3-padding-16">
                        <label for="kp-import-file"><b><?= esc(lang('Kpcrudy.importFile')) ?></b></label>
                        <input type="file" class="w3-input w3-border w3-round" id="kp-import-file" name="file" accept=".csv,text/csv,text/plain" required>
                        <p class="w3-small w3-text-grey"><?= esc(lang('Kpcrudy.importFileHelp', [$maxRows, $maxSize])) ?></p>

                        <p><b><?= esc(lang('Kpcrudy.importMode')) ?></b></p>
                        <?php if ($canInsert): ?>
                            <p><label><input class="w3-radio" type="radio" name="mode" value="insert"<?= $mode === 'insert' || ! $canUpdate ? ' checked' : '' ?>> <?= esc(lang('Kpcrudy.importModeInsert')) ?></label></p>
                        <?php endif ?>
                        <?php if ($canUpdate): ?>
                            <p><label><input class="w3-radio" type="radio" name="mode" value="upsert"<?= $mode === 'upsert' || ! $canInsert ? ' checked' : '' ?>> <?= esc(lang('Kpcrudy.importModeUpsert', [$keyLabel])) ?></label></p>
                        <?php endif ?>

                        <p><b><?= esc(lang('Kpcrudy.importOnError')) ?></b></p>
                        <p><label><input class="w3-radio" type="radio" name="on_error" value="stop"<?= $onError === 'stop' ? ' checked' : '' ?>> <?= esc(lang('Kpcrudy.importOnErrorStop')) ?></label></p>
                        <p><label><input class="w3-radio" type="radio" name="on_error" value="skip"<?= $onError === 'skip' ? ' checked' : '' ?>> <?= esc(lang('Kpcrudy.importOnErrorSkip')) ?></label></p>
                    </div>
                    <div class="w3-container w3-padding w3-light-grey">
                        <button type="submit" class="w3-button w3-blue w3-round"><i class="bi bi-eye"></i> <?= esc(lang('Kpcrudy.importPreview')) ?></button>
                        <a href="<?= esc($backUrl, 'attr') ?>" class="w3-button w3-right"><?= esc(lang('Kpcrudy.cancel')) ?></a>
                    </div>
                </form>
            </div>
            <div class="w3-col l5">
                <div class="w3-card w3-white w3-container w3-padding-16">
                    <b><?= esc(lang('Kpcrudy.importColumnsHelp')) ?></b>
                    <ul class="w3-ul w3-small">
                        <?php foreach ($fieldsHelp as $field): ?>
                            <li><b><?= esc($field['label']) ?></b><?= $field['required'] ? ' <span class="w3-text-red">*</span>' : '' ?> <span class="w3-text-grey">(<?= esc($field['name']) ?>)</span></li>
                        <?php endforeach ?>
                    </ul>
                    <p class="w3-small w3-text-grey"><?= esc(lang('Kpcrudy.importValuesHelp')) ?></p>
                    <a href="<?= esc($templateUrl, 'attr') ?>" class="w3-button w3-white w3-border w3-border-green w3-text-green w3-round w3-small"><i class="bi bi-download"></i> <?= esc(lang('Kpcrudy.importTemplate')) ?></a>
                </div>
            </div>
        </div>

    <?php elseif ($step === 'preview'): ?>
        <p>
            <span class="w3-text-grey"><i class="bi bi-file-earmark-spreadsheet"></i> <?= esc($fileName) ?></span>
            <span class="w3-tag w3-round w3-green"><?= esc(lang('Kpcrudy.importCountNew', [$counts['insert']])) ?></span>
            <span class="w3-tag w3-round w3-blue"><?= esc(lang('Kpcrudy.importCountUpdate', [$counts['update']])) ?></span>
            <span class="w3-tag w3-round w3-red"><?= esc(lang('Kpcrudy.importCountError', [$counts['error']])) ?></span>
        </p>

        <div class="w3-card w3-white w3-container w3-padding w3-margin-bottom">
            <b><?= esc(lang('Kpcrudy.importMapping')) ?></b><br>
            <?php foreach ($columns as $column): ?>
                <span class="w3-tag w3-round w3-light-grey w3-small kp-tag-gap">
                    <?= esc($column['header']) ?> <i class="bi bi-arrow-right"></i>
                    <?= $column['field'] === null ? '<i>' . esc(lang('Kpcrudy.importIgnored')) . '</i>' : '<b>' . esc($column['field']) . '</b>' ?>
                    <?php if ($column['key']): ?>(<?= esc(lang('Kpcrudy.importKey')) ?>)<?php endif ?>
                </span>
            <?php endforeach ?>
        </div>

        <?php
        $fields = [];
        foreach ($columns as $column) {
            if ($column['field'] !== null && array_key_exists($column['field'], $rows[0]['values'] ?? [])) {
                $fields[$column['field']] = $column['label'];
            }
        }
        ?>
        <div class="w3-card w3-white w3-margin-bottom">
            <div class="w3-container w3-padding"><b><?= esc(lang('Kpcrudy.importRowsPreview', [count($rows), $total])) ?></b></div>
            <div class="w3-responsive">
                <table class="w3-table w3-bordered w3-small">
                    <tr class="w3-light-grey">
                        <th><?= esc(lang('Kpcrudy.importLine')) ?></th>
                        <th></th>
                        <?php foreach ($fields as $label): ?><th><?= esc($label) ?></th><?php endforeach ?>
                    </tr>
                    <?php foreach ($rows as $row): ?>
                        <tr<?= $row['errors'] !== [] ? ' class="w3-pale-red"' : '' ?>>
                            <td class="w3-text-grey"><?= (int) $row['line'] ?></td>
                            <td><span class="w3-tag w3-round <?= $tags[$row['action']][0] ?>"><?= esc($tags[$row['action']][1]) ?></span></td>
                            <?php foreach (array_keys($fields) as $field): ?>
                                <td><?= esc(mb_strimwidth((string) ($row['values'][$field] ?? ''), 0, 40, '…')) ?></td>
                            <?php endforeach ?>
                        </tr>
                    <?php endforeach ?>
                </table>
            </div>
        </div>

        <?php if ($errors !== []): ?>
            <div class="w3-panel w3-leftbar w3-pale-red w3-border-red">
                <p><b><?= esc(lang('Kpcrudy.importErrors')) ?></b></p>
                <?php foreach ($errors as $row): ?>
                    <p class="w3-small"><b><?= esc(lang('Kpcrudy.importLine')) ?> <?= (int) $row['line'] ?>:</b> <?= esc(implode(' ', $row['errors'])) ?></p>
                <?php endforeach ?>
            </div>
        <?php endif ?>

        <form method="post" action="<?= esc($action, 'attr') ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="_kp_step" value="confirm">
            <input type="hidden" name="token" value="<?= esc($token, 'attr') ?>">
            <button type="submit" class="w3-button w3-green w3-round"<?= $canImport ? '' : ' disabled' ?>><i class="bi bi-upload"></i> <?= esc(lang('Kpcrudy.importConfirm', [$importable])) ?></button>
            <a href="<?= esc($action, 'attr') ?>" class="w3-button w3-white w3-border w3-round"><?= esc(lang('Kpcrudy.importBack')) ?></a>
            <?php if (! $canImport): ?>
                <span class="w3-small w3-text-red"><?= esc(lang($importable === 0 ? 'Kpcrudy.importNothing' : 'Kpcrudy.importBlocked')) ?></span>
            <?php elseif ($counts['error'] > 0): ?>
                <span class="w3-small w3-text-grey"><?= esc(lang('Kpcrudy.importSkipNotice', [$counts['error']])) ?></span>
            <?php endif ?>
        </form>

    <?php else: ?>
        <div class="w3-panel w3-leftbar <?= $result['aborted'] ? 'w3-pale-red w3-border-red' : 'w3-pale-yellow w3-border-amber' ?>">
            <p><?= esc($result['aborted'] ? lang('Kpcrudy.importAborted') : lang('Kpcrudy.importDone', [$result['created'], $result['updated'], $result['skipped']])) ?></p>
        </div>
        <div class="w3-panel w3-leftbar w3-pale-red w3-border-red">
            <p><b><?= esc(lang('Kpcrudy.importErrors')) ?> · <?= esc($fileName) ?></b></p>
            <?php foreach ($result['errors'] as $row): ?>
                <p class="w3-small">
                    <?php if ($row['line'] > 0): ?><b><?= esc(lang('Kpcrudy.importLine')) ?> <?= (int) $row['line'] ?>:</b><?php endif ?>
                    <?= esc(implode(' ', $row['errors'])) ?>
                </p>
            <?php endforeach ?>
        </div>
        <a href="<?= esc($action, 'attr') ?>" class="w3-button w3-blue w3-round"><i class="bi bi-upload"></i> <?= esc(lang('Kpcrudy.importBack')) ?></a>
        <a href="<?= esc($backUrl, 'attr') ?>" class="w3-button w3-white w3-border w3-round"><?= esc(lang('Kpcrudy.backToList')) ?></a>
    <?php endif ?>
</div>
