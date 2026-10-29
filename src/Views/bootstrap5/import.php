<?php
/**
 * Importació CSV, en tres passos segons $step: 'upload', 'preview' i 'report'.
 *
 * @var \Kpcrudy\Renderer          $this
 * @var string                     $title
 * @var string                     $step
 * @var string                     $action       URL del formulari
 * @var string                     $templateUrl  Descàrrega de la plantilla CSV
 * @var string                     $backUrl
 * @var string                     $mode         'insert' o 'upsert'
 * @var string                     $onError      'stop' o 'skip'
 * @var string|null                $error
 * @var string                     $keyLabel     Camp que identifica els registres existents
 * @var bool                       $canInsert
 * @var bool                       $canUpdate
 * @var int                        $maxRows
 * @var int                        $maxSize      KB
 * @var list<array<string, mixed>> $fieldsHelp   name, label, required
 * @var array<string, string>|null $flash
 *
 * Previsualització ($step = 'preview'):
 * @var string                     $token
 * @var string                     $fileName
 * @var list<array<string, mixed>> $columns      header, field, label, key
 * @var array<string, int>         $counts       insert, update, error
 * @var list<array<string, mixed>> $rows         line, action, values, errors (primeres 20)
 * @var int                        $total
 * @var list<array<string, mixed>> $errors       files amb errors
 * @var int                        $importable
 * @var bool                       $canImport
 *
 * Resultat ($step = 'report'):
 * @var array<string, mixed>       $result       created, updated, skipped, aborted, errors
 */
$badges = [
    'insert' => ['text-bg-success', lang('Kpcrudy.importRowNew')],
    'update' => ['text-bg-primary', lang('Kpcrudy.importRowUpdate')],
    'error'  => ['text-bg-danger', lang('Kpcrudy.importRowError')],
];
?>
<div class="kpcrudy">
    <?= $this->render('partials/assets') ?>

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <?php if ($showTitle ?? true): ?><h1 class="h3 mb-0"><?= esc($title) ?></h1><?php endif ?>
        <a href="<?= esc($backUrl, 'attr') ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> <?= esc(lang('Kpcrudy.backToList')) ?></a>
    </div>

    <?= $this->render('partials/flash', ['flash' => $flash]) ?>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= esc($error) ?></div>
    <?php endif ?>

    <?php if ($step === 'upload'): ?>
        <div class="row g-4">
            <div class="col-lg-7">
                <form method="post" action="<?= esc($action, 'attr') ?>" enctype="multipart/form-data" class="card">
                    <?= csrf_field() ?>
                    <input type="hidden" name="_kp_step" value="upload">
                    <div class="card-body">
                        <div class="mb-4">
                            <label class="form-label fw-semibold" for="kp-import-file"><?= esc(lang('Kpcrudy.importFile')) ?></label>
                            <input type="file" class="form-control" id="kp-import-file" name="file" accept=".csv,text/csv,text/plain" required>
                            <div class="form-text"><?= esc(lang('Kpcrudy.importFileHelp', [$maxRows, $maxSize])) ?></div>
                        </div>

                        <fieldset class="mb-4">
                            <legend class="form-label fw-semibold fs-6"><?= esc(lang('Kpcrudy.importMode')) ?></legend>
                            <?php if ($canInsert): ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="mode" id="kp-mode-insert" value="insert"<?= $mode === 'insert' || ! $canUpdate ? ' checked' : '' ?>>
                                    <label class="form-check-label" for="kp-mode-insert"><?= esc(lang('Kpcrudy.importModeInsert')) ?></label>
                                </div>
                            <?php endif ?>
                            <?php if ($canUpdate): ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="mode" id="kp-mode-upsert" value="upsert"<?= $mode === 'upsert' || ! $canInsert ? ' checked' : '' ?>>
                                    <label class="form-check-label" for="kp-mode-upsert"><?= esc(lang('Kpcrudy.importModeUpsert', [$keyLabel])) ?></label>
                                </div>
                            <?php endif ?>
                        </fieldset>

                        <fieldset>
                            <legend class="form-label fw-semibold fs-6"><?= esc(lang('Kpcrudy.importOnError')) ?></legend>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="on_error" id="kp-error-stop" value="stop"<?= $onError === 'stop' ? ' checked' : '' ?>>
                                <label class="form-check-label" for="kp-error-stop"><?= esc(lang('Kpcrudy.importOnErrorStop')) ?></label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="on_error" id="kp-error-skip" value="skip"<?= $onError === 'skip' ? ' checked' : '' ?>>
                                <label class="form-check-label" for="kp-error-skip"><?= esc(lang('Kpcrudy.importOnErrorSkip')) ?></label>
                            </div>
                        </fieldset>
                    </div>
                    <div class="card-footer d-flex gap-2">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-eye"></i> <?= esc(lang('Kpcrudy.importPreview')) ?></button>
                        <a href="<?= esc($backUrl, 'attr') ?>" class="btn btn-link ms-auto"><?= esc(lang('Kpcrudy.cancel')) ?></a>
                    </div>
                </form>
            </div>

            <div class="col-lg-5">
                <div class="card card-body">
                    <h2 class="h6"><?= esc(lang('Kpcrudy.importColumnsHelp')) ?></h2>
                    <ul class="list-unstyled small mb-3">
                        <?php foreach ($fieldsHelp as $field): ?>
                            <li>
                                <strong><?= esc($field['label']) ?></strong><?= $field['required'] ? ' <span class="text-danger">*</span>' : '' ?>
                                <span class="text-body-secondary">(<?= esc($field['name']) ?>)</span>
                            </li>
                        <?php endforeach ?>
                    </ul>
                    <p class="small text-body-secondary"><?= esc(lang('Kpcrudy.importValuesHelp')) ?></p>
                    <div>
                        <a href="<?= esc($templateUrl, 'attr') ?>" class="btn btn-sm btn-outline-success"><i class="bi bi-download"></i> <?= esc(lang('Kpcrudy.importTemplate')) ?></a>
                    </div>
                </div>
            </div>
        </div>

    <?php elseif ($step === 'preview'): ?>
        <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
            <span class="text-body-secondary"><i class="bi bi-file-earmark-spreadsheet"></i> <?= esc($fileName) ?></span>
            <span class="badge text-bg-success"><?= esc(lang('Kpcrudy.importCountNew', [$counts['insert']])) ?></span>
            <span class="badge text-bg-primary"><?= esc(lang('Kpcrudy.importCountUpdate', [$counts['update']])) ?></span>
            <span class="badge text-bg-danger"><?= esc(lang('Kpcrudy.importCountError', [$counts['error']])) ?></span>
        </div>

        <div class="card mb-3">
            <div class="card-header fw-semibold"><?= esc(lang('Kpcrudy.importMapping')) ?></div>
            <div class="card-body d-flex flex-wrap gap-2">
                <?php foreach ($columns as $column): ?>
                    <span class="border rounded px-2 py-1 small<?= $column['field'] === null ? ' text-body-tertiary' : '' ?>">
                        <?= esc($column['header']) ?>
                        <i class="bi bi-arrow-right"></i>
                        <?php if ($column['field'] === null): ?>
                            <em><?= esc(lang('Kpcrudy.importIgnored')) ?></em>
                        <?php else: ?>
                            <strong><?= esc($column['field']) ?></strong>
                            <?php if ($column['key']): ?><span class="badge text-bg-secondary"><?= esc(lang('Kpcrudy.importKey')) ?></span><?php endif ?>
                        <?php endif ?>
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
        <div class="card mb-3">
            <div class="card-header fw-semibold"><?= esc(lang('Kpcrudy.importRowsPreview', [count($rows), $total])) ?></div>
            <div class="table-responsive">
                <table class="table table-sm table-striped align-middle mb-0">
                    <thead>
                        <tr>
                            <th><?= esc(lang('Kpcrudy.importLine')) ?></th>
                            <th></th>
                            <?php foreach ($fields as $label): ?><th><?= esc($label) ?></th><?php endforeach ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $row): ?>
                            <tr<?= $row['errors'] !== [] ? ' class="table-danger"' : '' ?>>
                                <td class="text-body-secondary"><?= (int) $row['line'] ?></td>
                                <td><span class="badge <?= $badges[$row['action']][0] ?>"><?= esc($badges[$row['action']][1]) ?></span></td>
                                <?php foreach (array_keys($fields) as $field): ?>
                                    <td><?= esc(mb_strimwidth((string) ($row['values'][$field] ?? ''), 0, 40, '…')) ?></td>
                                <?php endforeach ?>
                            </tr>
                        <?php endforeach ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php if ($errors !== []): ?>
            <div class="card border-danger mb-3">
                <div class="card-header fw-semibold text-danger"><?= esc(lang('Kpcrudy.importErrors')) ?></div>
                <ul class="list-group list-group-flush small">
                    <?php foreach ($errors as $row): ?>
                        <li class="list-group-item">
                            <strong><?= esc(lang('Kpcrudy.importLine')) ?> <?= (int) $row['line'] ?>:</strong>
                            <?= esc(implode(' ', $row['errors'])) ?>
                        </li>
                    <?php endforeach ?>
                </ul>
            </div>
        <?php endif ?>

        <form method="post" action="<?= esc($action, 'attr') ?>" class="d-flex flex-wrap align-items-center gap-2">
            <?= csrf_field() ?>
            <input type="hidden" name="_kp_step" value="confirm">
            <input type="hidden" name="token" value="<?= esc($token, 'attr') ?>">
            <button type="submit" class="btn btn-success"<?= $canImport ? '' : ' disabled' ?>>
                <i class="bi bi-upload"></i> <?= esc(lang('Kpcrudy.importConfirm', [$importable])) ?>
            </button>
            <a href="<?= esc($action, 'attr') ?>" class="btn btn-outline-secondary"><?= esc(lang('Kpcrudy.importBack')) ?></a>
            <?php if (! $canImport): ?>
                <span class="text-danger small"><?= esc(lang($importable === 0 ? 'Kpcrudy.importNothing' : 'Kpcrudy.importBlocked')) ?></span>
            <?php elseif ($counts['error'] > 0): ?>
                <span class="text-body-secondary small"><?= esc(lang('Kpcrudy.importSkipNotice', [$counts['error']])) ?></span>
            <?php endif ?>
        </form>

    <?php else: ?>
        <div class="alert <?= $result['aborted'] ? 'alert-danger' : 'alert-warning' ?>">
            <?= esc($result['aborted']
                ? lang('Kpcrudy.importAborted')
                : lang('Kpcrudy.importDone', [$result['created'], $result['updated'], $result['skipped']])) ?>
        </div>

        <div class="card border-danger mb-3">
            <div class="card-header fw-semibold text-danger"><?= esc(lang('Kpcrudy.importErrors')) ?> · <?= esc($fileName) ?></div>
            <ul class="list-group list-group-flush small">
                <?php foreach ($result['errors'] as $row): ?>
                    <li class="list-group-item">
                        <?php if ($row['line'] > 0): ?><strong><?= esc(lang('Kpcrudy.importLine')) ?> <?= (int) $row['line'] ?>:</strong><?php endif ?>
                        <?= esc(implode(' ', $row['errors'])) ?>
                    </li>
                <?php endforeach ?>
            </ul>
        </div>

        <div class="d-flex gap-2">
            <a href="<?= esc($action, 'attr') ?>" class="btn btn-primary"><i class="bi bi-upload"></i> <?= esc(lang('Kpcrudy.importBack')) ?></a>
            <a href="<?= esc($backUrl, 'attr') ?>" class="btn btn-outline-secondary"><?= esc(lang('Kpcrudy.backToList')) ?></a>
        </div>
    <?php endif ?>
</div>
