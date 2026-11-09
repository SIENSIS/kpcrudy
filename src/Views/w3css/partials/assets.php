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
<?= $this->asset('kpcrudy-w3css.css') ?>
<?= $this->renderParent('partials/assets') ?>
