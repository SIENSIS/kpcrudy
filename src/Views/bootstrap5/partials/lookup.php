<?php
/**
 * Camp de cerca d'una relació amb 'ajax' => true (formulari o filtre). El valor
 * real va al camp ocult; el JavaScript de kpcrudy consulta base/lookup i omple la llista.
 * Sense JavaScript es pot continuar enviant el valor que ja tenia.
 *
 * Camps N-N (setRelationNN(..., ['ajax' => true]), $lookup['multiple']): els valors
 * triats es mostren com a etiquetes amb un botó per treure'ls, cadascun amb el seu
 * camp ocult "nom[]"; triar un resultat afegeix una etiqueta.
 *
 * @var \Kpcrudy\Renderer    $this
 * @var string               $id         id del camp visible
 * @var string               $name       nom del camp ocult (p. ex. city_id o f[city_id])
 * @var list<string>|string  $value      valor actual (id; llista d'ids per als camps N-N)
 * @var array<string, mixed> $lookup     url, minChars, text, multiple, items, max
 * @var string               $inputClass classes del camp visible
 * @var bool                 $required
 */
$required ??= false;
$multiple = (bool) ($lookup['multiple'] ?? false);
?>
<?php if ($multiple): ?>
<div<?= $this->attrs([
    'class'            => 'kp-lookup kp-lookup-multi',
    'data-kp-lookup'   => $lookup['url'],
    'data-kp-min'      => (int) $lookup['minChars'],
    'data-kp-empty'    => lang('Kpcrudy.lookupEmpty'),
    'data-kp-multiple' => $name . '[]',
    'data-kp-max'      => $lookup['max'] ?? null,
    'data-kp-remove'   => lang('Kpcrudy.lookupRemove'),
]) ?>>
    <ul class="kp-lookup-chips" aria-live="polite">
        <?php foreach ($lookup['items'] as $item): ?>
            <li class="kp-lookup-chip" data-id="<?= esc($item['id'], 'attr') ?>">
                <span><?= esc($item['text']) ?></span>
                <button type="button" class="kp-lookup-chip-remove" data-kp-chip-remove aria-label="<?= esc(lang('Kpcrudy.lookupRemove', [$item['text']]), 'attr') ?>">&times;</button>
                <input type="hidden" name="<?= esc($name . '[]', 'attr') ?>" value="<?= esc($item['id'], 'attr') ?>">
            </li>
        <?php endforeach ?>
    </ul>
    <input<?= $this->attrs([
        'type'              => 'search',
        'id'                => $id,
        'class'             => $inputClass,
        'autocomplete'      => 'off',
        'role'              => 'combobox',
        'aria-autocomplete' => 'list',
        'aria-expanded'     => 'false',
        'placeholder'       => lang('Kpcrudy.lookupPlaceholder', [(int) $lookup['minChars']]),
        'data-kp-lookup-text' => true,
    ]) ?>>
    <div class="kp-lookup-list" role="listbox" hidden></div>
</div>
<?php else: ?>
<div class="kp-lookup" data-kp-lookup="<?= esc($lookup['url'], 'attr') ?>" data-kp-min="<?= (int) $lookup['minChars'] ?>" data-kp-empty="<?= esc(lang('Kpcrudy.lookupEmpty'), 'attr') ?>">
    <input type="hidden" name="<?= esc($name, 'attr') ?>" value="<?= esc(is_array($value) ? '' : $value, 'attr') ?>" data-kp-lookup-value>
    <input<?= $this->attrs([
        'type'              => 'search',
        'id'                => $id,
        'class'             => $inputClass,
        'value'             => $lookup['text'],
        'autocomplete'      => 'off',
        'role'              => 'combobox',
        'aria-autocomplete' => 'list',
        'aria-expanded'     => 'false',
        'placeholder'       => lang('Kpcrudy.lookupPlaceholder', [(int) $lookup['minChars']]),
        'required'          => $required,
        'data-kp-lookup-text' => true,
    ]) ?>>
    <div class="kp-lookup-list" role="listbox" hidden></div>
</div>
<?php endif ?>
