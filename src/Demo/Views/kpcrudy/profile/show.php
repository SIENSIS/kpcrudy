<?php
/**
 * "profile" theme: replaces only the detail page of the bundled bootstrap5
 * theme. Every other template (form, partials...) falls back to bootstrap5.
 *
 * @var \Kpcrudy\Renderer          $this
 * @var string                     $title
 * @var list<array<string, mixed>> $items    name, label, html
 * @var list<array<string, mixed>> $actions
 * @var array<string, string>|null $flash
 */
$fields = array_column($items, null, 'name');
$header = ['avatar', 'first_name', 'last_name', 'username'];
?>
<div class="kpcrudy">
    <?= $this->render('partials/flash', ['flash' => $flash]) ?>

    <div class="card shadow-sm mx-auto kp-profile-card">
        <div class="card-body p-4">
            <div class="d-flex flex-wrap align-items-center gap-4 mb-4">
                <div class="kp-profile-avatar">
                    <?php if (($fields['avatar']['html'] ?? '') !== ''): ?>
                        <?= $fields['avatar']['html'] ?>
                    <?php else: ?>
                        <i class="bi bi-person-circle text-body-tertiary kp-profile-empty"></i>
                    <?php endif ?>
                </div>
                <div class="flex-grow-1">
                    <h1 class="h3 mb-0"><?= strip_tags($fields['first_name']['html'] ?? '') ?> <?= strip_tags($fields['last_name']['html'] ?? '') ?></h1>
                    <div class="text-body-secondary">@<?= strip_tags($fields['username']['html'] ?? '') ?></div>
                </div>
                <div class="d-flex gap-2">
                    <?php foreach ($actions as $action): ?>
                        <?= $this->render('partials/button', ['action' => $action]) ?>
                    <?php endforeach ?>
                </div>
            </div>

            <dl class="row mb-0">
                <?php foreach ($items as $item): ?>
                    <?php if (in_array($item['name'], $header, true)) { continue; } ?>
                    <dt class="col-sm-4 text-body-secondary fw-normal"><?= esc($item['label']) ?></dt>
                    <dd class="col-sm-8"><?= $item['html'] !== '' ? $item['html'] : '<span class="text-body-tertiary">—</span>' ?></dd>
                <?php endforeach ?>
            </dl>
        </div>
    </div>
</div>
<?php // $this->nonce(): the block also works when the project enables a Content-Security-Policy ?>
<style<?= $this->nonce('style') ?>>
    .kp-profile-card { max-width: 820px; }
    .kp-profile-empty { font-size: 96px; }
    .kp-profile-avatar img { width: 120px; height: 120px; object-fit: cover; border-radius: 50%; max-height: none; max-width: none; padding: 0; }
</style>
