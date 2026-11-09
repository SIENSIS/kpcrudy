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
<?= $this->asset('kpcrudy-tailwind.css') ?>
<?= $this->renderParent('partials/assets') ?>
