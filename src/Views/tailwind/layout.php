<?php
/**
 * Tema Tailwind CSS: pàgina completa quan el layout és 'default'.
 *
 * Fa servir el "Play CDN" de Tailwind, que genera el CSS al navegador: és
 * còmode per provar el tema, però en producció cal compilar el CSS (vegeu
 * docs/new_theme_CA.md).
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
    <script<?= $this->nonce() ?> src="https://cdn.tailwindcss.com/3.4.16"></script>
    <link rel="stylesheet"<?= $this->nonce('style') ?> href="<?= esc($config->iconsCss, 'attr') ?>">
</head>
<body class="bg-slate-100 text-slate-800">
    <main class="mx-auto max-w-7xl px-4 py-8">
        <?= $content ?>
    </main>
</body>
</html>
