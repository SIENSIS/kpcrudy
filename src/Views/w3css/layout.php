<?php
/**
 * Tema W3.CSS: pàgina completa quan el layout és 'default'.
 *
 * @var \Kpcrudy\Renderer       $this
 * @var string                  $title
 * @var string                  $content
 * @var \Kpcrudy\Config\Kpcrudy $config
 */
?>
<!doctype html>
<html lang="<?= esc(service('request')->getLocale(), 'attr') ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc($title) ?></title>
    <link rel="stylesheet"<?= $this->nonce('style') ?> href="https://www.w3schools.com/w3css/4/w3.css">
    <link rel="stylesheet"<?= $this->nonce('style') ?> href="<?= esc($config->iconsCss, 'attr') ?>">
</head>
<body class="w3-light-grey">
    <main class="w3-container w3-padding-24">
        <?= $content ?>
    </main>
</body>
</html>
