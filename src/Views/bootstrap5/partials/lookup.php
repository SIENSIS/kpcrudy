<?php
/**
 * Camp de cerca d'una relació amb 'ajax' => true (formulari o filtre). El valor
 * real va al camp ocult; el JavaScript de kpcrudy consulta base/lookup i omple la llista.
 * Sense JavaScript es pot continuar enviant el valor que ja tenia.
 *
 * @var \Kpcrudy\Renderer    $this
 * @var string               $id         id del camp visible
 * @var string               $name       nom del camp ocult (p. ex. city_id o f[city_id])
 * @var string               $value      valor actual (id)
 * @var array<string, mixed> $lookup     url, minChars, text
 * @var string               $inputClass classes del camp visible
 * @var bool                 $required
 */
$required ??= false;
?>
<div class="kp-lookup" data-kp-lookup="<?= esc($lookup['url'], 'attr') ?>" data-kp-min="<?= (int) $lookup['minChars'] ?>" data-kp-empty="<?= esc(lang('Kpcrudy.lookupEmpty'), 'attr') ?>">
    <input type="hidden" name="<?= esc($name, 'attr') ?>" value="<?= esc($value, 'attr') ?>" data-kp-lookup-value>
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
