<?php
/**
 * Vista intermèdia per posar el CRUD dins d'una secció d'un layout del projecte
 * (Config\Kpcrudy::$layoutSection o Crud::setLayout($layout, $section)).
 *
 * @var \CodeIgniter\View\View $this
 * @var string                 $kpcrudy        HTML del CRUD
 * @var string                 $kpcrudyLayout  Vista que s'estén
 * @var string                 $kpcrudySection Secció on va el CRUD
 */
$this->extend($kpcrudyLayout);
$this->section($kpcrudySection);
echo $kpcrudy;
$this->endSection();
