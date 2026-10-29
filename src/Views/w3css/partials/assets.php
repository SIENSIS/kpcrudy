<?php
/**
 * Tema W3.CSS: estils propis i el JavaScript del tema pare (confirmacions,
 * "selecciona-ho tot", accions massives), reaprofitat amb renderParent().
 *
 * També hi ha una equivalència mínima de les classes de Bootstrap que la
 * llibreria (o els callbacks del projecte) poden posar als valors: badge,
 * text-bg-*, img-thumbnail.
 *
 * @var \Kpcrudy\Renderer $this
 */
?>
<style<?= $this->nonce('style') ?>>
    .kpcrudy .badge, .kpcrudy .kp-badge { display: inline-block; padding: 1px 8px; border-radius: 4px; font-size: 12px; font-weight: 600; }
    .kpcrudy .text-bg-primary { background: #2196F3; color: #fff; }
    .kpcrudy .text-bg-secondary, .kpcrudy .kp-no { background: #9e9e9e; color: #fff; }
    .kpcrudy .text-bg-success, .kpcrudy .kp-yes { background: #4CAF50; color: #fff; }
    .kpcrudy .text-bg-danger { background: #f44336; color: #fff; }
    .kpcrudy .text-bg-warning { background: #ffc107; color: #000; }
    .kpcrudy .text-bg-info { background: #00bcd4; color: #000; }
    .kpcrudy .text-bg-light { background: #f1f1f1; color: #000; }
    .kpcrudy .img-thumbnail { border: 1px solid #ddd; border-radius: 4px; }
    .kpcrudy .kp-filters > summary { cursor: pointer; list-style: none; }
    .kpcrudy .kp-filters > summary::-webkit-details-marker { display: none; }
    .kpcrudy .kp-pages .w3-button { min-width: 36px; }
    .kpcrudy .kp-m0 { margin: 0; }
    .kpcrudy .kp-row-flush { margin: 0 -16px; }
    .kpcrudy .kp-tag-gap { margin: 4px 4px 0 0; }
    .kpcrudy .kp-search { display: flex; gap: 4px; }
    .kpcrudy .kp-grow { flex: 1; }
    .kpcrudy .kp-wauto { width: auto; }
    .kpcrudy .kp-nodeco { text-decoration: none; }
    .kpcrudy .kp-w25 { width: 25%; }
    .kpcrudy .kp-pr4 { padding-right: 4px; }
    .kpcrudy .kp-pl4 { padding-left: 4px; }
</style>
<?= $this->renderParent('partials/assets') ?>
