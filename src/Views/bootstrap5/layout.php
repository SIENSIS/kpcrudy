<?php
/**
 * Pàgina completa que embolcalla el CRUD quan el layout és 'default'.
 *
 * @var \Kpcrudy\Renderer          $this
 * @var string                     $title
 * @var string                     $content
 * @var \Kpcrudy\Config\Kpcrudy    $config
 */
?>
<!doctype html>
<html lang="<?= esc(service('request')->getLocale(), 'attr') ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc($title) ?></title>
    <link rel="stylesheet" href="<?= esc($config->bootstrapCss, 'attr') ?>">
    <link rel="stylesheet" href="<?= esc($config->iconsCss, 'attr') ?>">
</head>
<body class="bg-body-tertiary">
    <main class="container-fluid py-4 px-lg-5">
        <?= $content ?>
    </main>
    <script src="<?= esc($config->bootstrapJs, 'attr') ?>"></script>
</body>
</html>
