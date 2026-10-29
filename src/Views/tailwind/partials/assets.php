<?php
/**
 * Tema Tailwind: estils propis i el JavaScript del tema pare (confirmacions,
 * "selecciona-ho tot", accions massives), reaprofitat amb renderParent().
 *
 * El "preflight" de Tailwind treu els estils per defecte (enllaços, etc.), i
 * la llibreria i els callbacks poden generar classes de Bootstrap (badge,
 * text-bg-*, img-thumbnail): aquí n'hi ha una equivalència mínima.
 *
 * @var \Kpcrudy\Renderer $this
 */
?>
<style<?= $this->nonce('style') ?>>
    .kpcrudy td a:not([class]), .kpcrudy dd a:not([class]) { color: #2563eb; text-decoration: underline; }
    .kpcrudy .badge, .kpcrudy .kp-badge { display: inline-block; padding: 1px 8px; border-radius: 9999px; font-size: 12px; font-weight: 600; }
    .kpcrudy .text-bg-primary { background: #2563eb; color: #fff; }
    .kpcrudy .text-bg-secondary, .kpcrudy .kp-no { background: #64748b; color: #fff; }
    .kpcrudy .text-bg-success, .kpcrudy .kp-yes { background: #16a34a; color: #fff; }
    .kpcrudy .text-bg-danger { background: #dc2626; color: #fff; }
    .kpcrudy .text-bg-warning { background: #f59e0b; color: #000; }
    .kpcrudy .text-bg-info { background: #06b6d4; color: #000; }
    .kpcrudy .text-bg-light { background: #f1f5f9; color: #000; }
    .kpcrudy .img-thumbnail { border: 1px solid #e2e8f0; border-radius: 6px; }
    .kpcrudy details > summary { list-style: none; }
    .kpcrudy details > summary::-webkit-details-marker { display: none; }
</style>
<?= $this->renderParent('partials/assets') ?>
