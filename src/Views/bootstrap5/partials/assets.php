<?php
/**
 * Estils i JavaScript propis del CRUD (src/Assets/). S'inclouen al fragment perquè
 * funcioni també dins d'un layout del projecte: en línia amb el nonce de la CSP
 * (Config\Kpcrudy::$assetsMode = 'inline', per defecte) o com a fitxers externs
 * publicats amb "php spark kpcrudy:publish assets" ('external').
 *
 * Compatible amb una Content-Security-Policy estricta: els blocs porten el
 * nonce de CodeIgniter i les plantilles no fan servir atributs on*="" ni
 * style="" (el comportament va amb atributs data-kp-*).
 *
 * @var \Kpcrudy\Renderer $this
 */
?>
<?= $this->asset('kpcrudy.css') ?>
<?= $this->asset('kpcrudy.js') ?>
