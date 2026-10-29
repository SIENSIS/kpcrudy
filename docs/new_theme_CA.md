# Crear un tema nou

[English](new_theme_EN.md) · **Català**

Un **tema** és el conjunt de plantilles PHP que pinten el CRUD: el llistat, els formularis, la fitxa, la importació i les peces petites (botons, filtres, paginació...). La lògica (consultes, validació, permisos) és sempre la mateixa; el tema només decideix l'HTML i el CSS.

kpcrudy inclou tres temes:

| Tema | Framework CSS | Carpeta |
|---|---|---|
| `bootstrap5` (per defecte) | [Bootstrap 5](https://getbootstrap.com/) | [src/Views/bootstrap5](../src/Views/bootstrap5) |
| `w3css` | [W3.CSS](https://www.w3schools.com/w3css/) | [src/Views/w3css](../src/Views/w3css) |
| `tailwind` | [Tailwind CSS](https://tailwindcss.com/) | [src/Views/tailwind](../src/Views/tailwind) |

```php
$crud->setTheme('w3css');        // per a un CRUD
```

```php
// app/Config/Kpcrudy.php: per a tots
public string $theme = 'tailwind';
```

| W3.CSS | Tailwind CSS |
|---|---|
| ![Llistat amb W3.CSS](../screenshots/18-tema-w3css-llistat.png) | ![Llistat amb Tailwind](../screenshots/20-tema-tailwind-llistat.png) |
| ![Formulari amb W3.CSS](../screenshots/19-tema-w3css-formulari.png) | ![Formulari amb Tailwind](../screenshots/21-tema-tailwind-formulari.png) |

Aquest document explica com està fet un tema i com en faries un de nou, amb els exemples de W3.CSS i de Tailwind. Els dos temes inclosos són el resultat complet d'aquests passos: fes-los servir com a referència.

## Índex

- [Començar amb una còpia](#començar-amb-una-còpia)
- [Com funciona un tema](#com-funciona-un-tema)
- [Les plantilles](#les-plantilles)
- [El que el tema no pot canviar](#el-que-el-tema-no-pot-canviar)
- [HTML que no surt de les plantilles](#html-que-no-surt-de-les-plantilles)
- [Content-Security-Policy](#content-security-policy)
- [Exemple: un tema amb W3.CSS](#exemple-un-tema-amb-w3css)
- [Exemple: un tema amb Tailwind CSS](#exemple-un-tema-amb-tailwind-css)
- [Ampliar un tema existent](#ampliar-un-tema-existent)
- [Comprovacions finals](#comprovacions-finals)

---

## Començar amb una còpia

La manera més ràpida de començar un tema és copiar-ne un d'existent amb [kpcrudy:publish](commands_CA.md#kpcrudypublish):

```bash
php spark kpcrudy:publish theme w3css --as intranet                    # còpia sencera
php spark kpcrudy:publish theme w3css --as intranet --only list,show   # només aquestes plantilles
```

La comanda copia les plantilles a `app/Views/kpcrudy/intranet` i registra el tema a `$themes` d'`app/Config/Kpcrudy.php` (afegeix `--dry-run` per veure-ho abans). En una **còpia sencera**, el pare del tema nou és el pare de l'original (`bootstrap5`), de manera que les plantilles que fan servir `renderParent()` continuen funcionant. En una **còpia parcial** (`--only`), el pare és el tema original, que dona les plantilles que no s'han copiat; i si una de les copiades ja amplia el seu pare amb `renderParent()` (com `partials/assets` de `w3css` i `tailwind`), en lloc de duplicar-la s'hi escriu una ampliació: `renderParent()` i un `<style>` buit on afegir el teu CSS. Després, fes-lo servir amb `->setTheme('intranet')` o amb `$theme = 'intranet'`. La resta d'aquest document explica què fa cada plantilla.

---

## Com funciona un tema

Un tema és una **carpeta** amb plantilles PHP. Es registra a la configuració amb un nom:

```php
// app/Config/Kpcrudy.php
public array $themes = [
    'w3mini' => APPPATH . 'Views/kpcrudy/w3mini',
];
```

Quan el CRUD necessita una plantilla (per exemple `list` o `partials/button`), la busca **en ordre**:

1. a la carpeta del tema;
2. a la del tema pare, si en té (vegeu [Ampliar un tema existent](#ampliar-un-tema-existent));
3. a la de `bootstrap5`.

Per tant, un tema **no ha de tenir totes les plantilles**: les que falten es fan servir de `bootstrap5`. Això permet començar per una sola plantilla i anar-ne afegint. Però, si el framework CSS no és Bootstrap, les plantilles que vinguin de `bootstrap5` sortiran sense estil: un tema complet per a un altre framework ha de tenir totes les plantilles.

Dins d'una plantilla, `$this` és un `Kpcrudy\Renderer`, que té:

| Mètode | Per a què serveix |
|---|---|
| `$this->render('partials/button', [...])` | Incloure una altra plantilla (del mateix tema, del pare o de `bootstrap5`) |
| `$this->renderParent('partials/assets')` | Incloure **la mateixa plantilla del tema pare**, per ampliar-la sense copiar-la |
| `$this->hidden($array)` | Camps ocults a partir d'un array (per conservar els filtres en un formulari GET) |
| `$this->attrs(['name' => 'q', 'required' => true])` | Atributs HTML escapats (`true` = atribut sense valor, `false`/`null` = no el posa) |
| `$this->nonce()` · `$this->nonce('style')` | L'atribut nonce de la CSP per a un `<script>` o un `<style>` (buit si la CSP de CodeIgniter no està activa); vegeu [Content-Security-Policy](#content-security-policy) |

Les funcions de CodeIgniter (`esc()`, `lang()`, `csrf_field()`, `site_url()`...) també es poden fer servir.

## Les plantilles

| Plantilla | Què pinta | Variables principals |
|---|---|---|
| `layout.php` | La pàgina HTML completa (només amb el layout `default`): `<head>`, CSS, JS | `$title`, `$content`, `$config` |
| `list.php` | Llistat i paperera: capçalera, cerca, filtres, taula, accions massives, paginació | `$title`, `$showTitle`, `$columns`, `$rows`, `$headerActions`, `$bulkActions`, `$filters`, `$pagination`, `$state`... |
| `form.php` | Formulari d'alta i d'edició | `$title`, `$showTitle`, `$fields`, `$action`, `$mode`, `$multipart`, `$formError` |
| `show.php` | Fitxa de detall | `$title`, `$showTitle`, `$items`, `$actions` |
| `import.php` | Importació CSV en tres passos (`$step`: `upload`, `preview`, `report`) | `$title`, `$showTitle`, `$columns`, `$rows`, `$counts`, `$result`... |
| `partials/assets.php` | CSS i JavaScript propis del CRUD | — |
| `partials/button.php` | Un botó d'acció (enllaç o POST) | `$action`, `$compact` |
| `partials/field.php` | Un camp del formulari, segons el tipus | `$field` |
| `partials/translations.php` | Els camps traduïts de `setTranslations()`, un bloc o pestanya per idioma | `$field` (amb `tabs`) |
| `partials/filters.php` | Panell de filtres | `$filters`, `$filterHidden`, `$filtersReset`... |
| `partials/flash.php` | Missatge (desat, esborrat, error...) | `$flash` |
| `partials/pagination.php` | Paginació i "Registres X a Y de Z" | `$pagination` |

**Totes les variables** que rep cada plantilla estan documentades a la capçalera de la versió de [bootstrap5](../src/Views/bootstrap5). Les plantilles dels altres temes reben exactament les mateixes.

**El títol.** `list.php`, `form.php`, `show.php` i `import.php` només mostren el títol si `$showTitle` és cert (és `false` amb `showTitle(false)`, quan el layout del projecte ja el mostra). Fes servir `$showTitle ?? true`, perquè la plantilla funcioni també amb versions anteriors:

```php
<?php if ($showTitle ?? true): ?><h2><?= esc($title) ?></h2><?php endif ?>
```

**Tipus de camp a `partials/field.php`.** A més dels tipus simples (`text`, `textarea`, `number`, `date`, `select`, `boolean`, `upload`...), la plantilla ha de tractar:

- `multiselect` (`fieldType(..., 'multiselect')`): una selecció múltiple igual que `relation_nn`: `<select name="<?= $name ?>[]" multiple>` amb `$field['options']`; `$field['value']` és la llista de valors seleccionats.
- `translations` (`setTranslations()`): un sol "camp" amb tots els inputs traduïts. Passa'l a la seva plantilla:

```php
if ($field['type'] === 'translations') {
    echo $this->render('partials/translations', ['field' => $field]);

    return;
}
```

`partials/translations.php` rep `$field['tabs']`, una per idioma, cadascuna amb:

| Clau | Contingut |
|---|---|
| `locale` | Codi de l'idioma (`ca`) |
| `label` | Nom de l'idioma (`Català`) |
| `required` | Si l'idioma és obligatori |
| `hasError` | Si algun dels seus inputs té un error (per obrir o destacar aquella pestanya) |
| `inputs` | Els inputs: `name`, `id`, `label`, `type` (`text` o `textarea`), `value`, `required`, `error`, `help`, `maxlength`, `attrs` |

Mantén el `name` de cada input tal com arriba (`kp_tr[ca][title]`: el servidor llegeix aquests noms). `bootstrap5` mostra els idiomes en pestanyes (amb el JavaScript de Bootstrap); `w3css` i `tailwind` mostren un `<fieldset>` per idioma, sense JavaScript.

## El que el tema no pot canviar

El CRUD i el seu JavaScript esperen uns noms concrets als formularis. El disseny pot ser qualsevol, però aquests elements s'han de mantenir:

**Noms dels camps** (els llegeix el servidor):

| On | Nom |
|---|---|
| Cerca | `q` |
| Registres per pàgina | `per_page` |
| Filtres | `f[camp]`, i als de rang `f[camp][from]` i `f[camp][to]` |
| Caselles de selecció | `ids[]` |
| Acció massiva | `<select name="action">` |
| Botons de desar | `name="_kp_after"` amb valor `list`, `continue` o `new` |
| Importació | `file`, `mode`, `on_error`, `_kp_step` i `token` |
| Formularis POST | sempre amb `<?= csrf_field() ?>` |

**Contracte amb el JavaScript** de `bootstrap5/partials/assets.php` (que els altres temes reaprofiten amb `renderParent()`):

| Element | Què fa |
|---|---|
| `data-kp-confirm="missatge"` | Demana confirmació abans d'un clic (amb `window.confirm()` o amb el diàleg del projecte, a través de l'esdeveniment `kpcrudy:confirm`) |
| `data-kp-check-all` | La casella que marca o desmarca totes les `ids[]` del formulari |
| `data-kp-autosubmit` | Un `<select>` que envia el seu formulari quan canvia ("registres per pàgina") |
| `data-kp-alert` + `data-kp-dismiss` | La caixa del missatge i l'element de dins que la tanca |
| `data-kp-color="#hex"` | Pinta el fons de l'element amb aquest color (el `kp-swatch` dels camps de color) |
| `<form data-kp-bulk="missatge">` | El formulari d'accions massives; avisa si no hi ha res seleccionat |
| `<option data-confirm="..." data-url="...">` | Confirmació de l'acció massiva i, amb `data-url`, que es faci per GET (exportar) |
| `<form id="kp-post-form" method="post">` | Formulari ocult que envien els botons d'acció POST |

Les **accions POST** (esborrar, restaurar, accions amb `'method' => 'post'`) són botons que envien aquest formulari ocult:

```php
<button type="submit" form="kp-post-form" formaction="<?= esc($action['url'], 'attr') ?>">...</button>
```

## HTML que no surt de les plantilles

Hi ha tres coses que no genera el tema i que cal tenir en compte:

**1. Les classes dels botons.** Les accions arriben amb classes de Bootstrap (`btn-primary`, `btn-outline-danger`...), tant les de la llibreria (*Afegeix*, *Edita*, *Esborra*) com les d'`addAction(..., ['class' => ...])`. El `partials/button.php` d'un altre tema les ha de **traduir**. Els temes inclosos ho fan amb una taula, i les classes que no hi són es deixen tal qual (així el projecte pot fer servir classes pròpies):

```php
$map = [
    'btn-primary'         => 'w3-blue',
    'btn-outline-danger'  => 'w3-white w3-border w3-border-red w3-text-red',
    // ...
];
$variant = implode(' ', array_map(
    static fn (string $class): string => $map[$class] ?? $class,
    preg_split('/\s+/', trim((string) ($action['class'] ?? 'btn-outline-secondary'))),
));
```

**2. Les icones.** Les accions de la llibreria porten icones de [Bootstrap Icons](https://icons.getbootstrap.com/) (`bi bi-pencil`). Bootstrap Icons és una font independent de Bootstrap: els temes inclosos la carreguen al `layout.php` (`$config->iconsCss`) i la fan servir igualment.

**3. Els valors de les cel·les.** La llibreria genera una mica d'HTML per a alguns tipus de camp:

| Tipus | HTML |
|---|---|
| booleà | `<span class="kp-badge kp-yes badge text-bg-success">Sí</span>` (i `kp-no`) |
| imatge | `<img class="img-thumbnail kp-thumb">` |
| fitxer | enllaç amb `<i class="bi bi-paperclip">` |
| color | `<span class="kp-swatch" data-kp-color="#ff8800">` (el pinta el JavaScript) |
| multiselect | les etiquetes, separades per comes |

Les classes `kp-*` són pròpies de kpcrudy i neutres: estilitza-les al teu `partials/assets.php`. A més, els `callbackColumn()` del projecte sovint fan servir classes de Bootstrap (`badge text-bg-success`). Els temes inclosos hi afegeixen una equivalència mínima:

```css
.kpcrudy .badge, .kpcrudy .kp-badge { display: inline-block; padding: 1px 8px; border-radius: 4px; font-size: 12px; }
.kpcrudy .text-bg-success, .kpcrudy .kp-yes { background: #4CAF50; color: #fff; }
.kpcrudy .text-bg-secondary, .kpcrudy .kp-no { background: #9e9e9e; color: #fff; }
```

## Content-Security-Policy

Els temes inclosos funcionen amb una Content-Security-Policy estricta (`$CSPEnabled` de CodeIgniter, sense `'unsafe-inline'`). Un tema nou ha de seguir les mateixes regles, o el navegador en bloquejarà els estils i els scripts:

**1. Nonce a cada bloc `<style>` i `<script>`**, i als `<link>` i `<script src>` del `layout.php`. `$this->nonce()` retorna l'atribut (amb un espai al davant) o un text buit si la CSP no està activa:

```php
<style<?= $this->nonce('style') ?>>
    .kpcrudy .kp-thumb { max-height: 48px; }
</style>
<script<?= $this->nonce() ?>>
    // ...
</script>
<link rel="stylesheet"<?= $this->nonce('style') ?> href="<?= esc($config->iconsCss, 'attr') ?>">
```

**2. Sense atributs `on*=""`** (`onclick`, `onchange`, `onsubmit`...). El comportament va amb els atributs `data-kp-*`, que el JavaScript compartit de `bootstrap5/partials/assets.php` tracta amb listeners delegats (vegeu la [taula de dalt](#el-que-el-tema-no-pot-canviar)):

| En lloc de | Fes servir |
|---|---|
| `onclick="return confirm('...')"` | `data-kp-confirm="..."` |
| `onchange="this.form.submit()"` | `data-kp-autosubmit` |
| `onclick="this.parentElement.remove()"` | `data-kp-dismiss` dins d'un `data-kp-alert` |
| `onchange` que marca totes les caselles | `data-kp-check-all` |
| `onsubmit` del formulari massiu | `<form data-kp-bulk="...">` |

Si el tema necessita JavaScript propi, posa'l a `partials/assets.php`, en un `<script>` amb el nonce, i fes servir listeners (`addEventListener`).

**3. Sense atributs `style=""`.** Fes servir classes, definides al `<style>` de `partials/assets.php`. El tema `w3css` hi afegeix unes petites classes d'utilitat que pots reaprofitar o copiar:

| Classe | CSS |
|---|---|
| `kp-m0` | `margin: 0` |
| `kp-row-flush` | `margin: 0 -16px` |
| `kp-tag-gap` | `margin: 4px 4px 0 0` |
| `kp-search` | `display: flex; gap: 4px` |
| `kp-grow` | `flex: 1` |
| `kp-wauto` | `width: auto` |
| `kp-nodeco` | `text-decoration: none` |
| `kp-w25` | `width: 25%` |
| `kp-pr4` · `kp-pl4` | `padding-right: 4px` · `padding-left: 4px` |

Els valors que depenen de les dades, com el color d'un camp de color, no poden ser una classe: van en un atribut `data-*` que aplica el JavaScript (`data-kp-color`).

Per provar-ho, activa `$CSPEnabled` a `app/Config/App.php` i mira la consola del navegador: hi surt cada estil o script bloquejat. La configuració de `Config\ContentSecurityPolicy` (CDN, fonts, imatges `data:`) és a [Content-Security-Policy](howto_CA.md#content-security-policy) de les receptes.

---

## Exemple: un tema amb W3.CSS

W3.CSS és un únic fitxer CSS, sense JavaScript ni compilació: és el cas més senzill. L'exemple crea el tema `w3mini` dins del projecte. El tema complet inclòs és a [src/Views/w3css](../src/Views/w3css).

### 1. Registra el tema

```php
// app/Config/Kpcrudy.php
public array $themes = [
    'w3mini' => APPPATH . 'Views/kpcrudy/w3mini',
];
```

```php
// Al controlador
$crud->setTheme('w3mini');
```

### 2. El layout: carrega W3.CSS

`app/Views/kpcrudy/w3mini/layout.php`:

```php
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
    <main class="w3-container w3-padding-24"><?= $content ?></main>
</body>
</html>
```

Si el CRUD es mostra dins del layout del projecte (`setLayout('layouts/admin')`), aquest fitxer no es fa servir: el CSS l'ha de carregar el layout del projecte.

### 3. Els recursos: CSS propi + el JavaScript del pare

`partials/assets.php`. No cal copiar el JavaScript de `bootstrap5`: `renderParent()` l'inclou.

```php
<style<?= $this->nonce('style') ?>>
    .kpcrudy .kp-badge { display: inline-block; padding: 1px 8px; border-radius: 4px; font-size: 12px; }
    .kpcrudy .kp-yes { background: #4CAF50; color: #fff; }
    .kpcrudy .kp-no  { background: #9e9e9e; color: #fff; }
    .kpcrudy .kp-pl0 { padding-left: 0; }
</style>
<?= $this->renderParent('partials/assets') ?>
```

### 4. Els botons

`partials/button.php`: tradueix les classes i manté l'enllaç (GET) o el botó del formulari ocult (POST).

```php
<?php
$compact ??= false;
$map     = ['btn-primary' => 'w3-blue', 'btn-outline-primary' => 'w3-white w3-border w3-text-blue', 'btn-outline-danger' => 'w3-white w3-border w3-text-red'];
$variant = implode(' ', array_map(static fn ($c) => $map[$c] ?? $c, preg_split('/\s+/', trim((string) ($action['class'] ?? '')))));
$classes = 'w3-button w3-round ' . ($compact ? 'w3-small ' : '') . $variant;
$inner   = ! empty($action['icon']) ? '<i class="' . esc($action['icon'], 'attr') . '"></i>' . ($compact ? '' : ' ' . esc($action['label'])) : esc($action['label']);
?>
<?php if (($action['method'] ?? 'get') === 'post'): ?>
    <button type="submit" form="kp-post-form"<?= $this->attrs(['formaction' => $action['url'], 'class' => $classes, 'title' => $action['label'], 'data-kp-confirm' => $action['confirm'] ?? null]) ?>><?= $inner ?></button>
<?php else: ?>
    <a<?= $this->attrs(['href' => $action['url'], 'class' => $classes, 'title' => $action['label'], 'target' => $action['target'] ?? null, 'data-kp-confirm' => $action['confirm'] ?? null]) ?>><?= $inner ?></a>
<?php endif ?>
```

### 5. El llistat

`list.php`. Aquest és un llistat reduït (sense filtres ni accions massives) que mostra l'estructura; la versió completa és [w3css/list.php](../src/Views/w3css/list.php).

```php
<div class="kpcrudy">
    <?= $this->render('partials/assets') ?>

    <div class="w3-bar w3-margin-bottom">
        <?php if ($showTitle ?? true): ?><h2 class="w3-bar-item kp-pl0"><?= esc($title) ?></h2><?php endif ?>
        <div class="w3-bar-item w3-right">
            <?php foreach ($headerActions as $action): ?>
                <?= $this->render('partials/button', ['action' => $action]) ?>
            <?php endforeach ?>
        </div>
    </div>

    <?= $this->render('partials/flash', ['flash' => $flash]) ?>

    <?php if ($canSearch): ?>
        <form method="get" action="<?= esc($listUrl, 'attr') ?>" class="w3-margin-bottom kp-search">
            <?= $this->hidden($searchHidden) ?>
            <input type="search" name="q" class="w3-input w3-border w3-round" value="<?= esc($state['q'], 'attr') ?>" placeholder="<?= esc(lang('Kpcrudy.searchPlaceholder'), 'attr') ?>">
        </form>
    <?php endif ?>

    <div class="w3-card w3-white w3-responsive">
        <table class="w3-table w3-striped w3-bordered w3-hoverable">
            <tr class="w3-light-grey">
                <?php foreach ($columns as $column): ?>
                    <th><?= $column['sortable'] ? '<a href="' . esc($column['url'], 'attr') . '">' . esc($column['label']) . '</a>' : esc($column['label']) ?></th>
                <?php endforeach ?>
                <th></th>
            </tr>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <?php foreach ($columns as $column): ?>
                        <td><?= $row['cells'][$column['name']] ?></td>
                    <?php endforeach ?>
                    <td class="w3-right-align">
                        <?php foreach ($row['actions'] as $action): ?>
                            <?= $this->render('partials/button', ['action' => $action, 'compact' => true]) ?>
                        <?php endforeach ?>
                    </td>
                </tr>
            <?php endforeach ?>
        </table>
    </div>

    <?= $this->render('partials/pagination', ['pagination' => $pagination]) ?>

    <form id="kp-post-form" method="post" hidden><?= csrf_field() ?></form>
</div>
```

Coses a tenir en compte:

- Les cel·les (`$row['cells']`) ja són HTML escapat: s'imprimeixen amb `<?= ?>`, sense `esc()`.
- El formulari `kp-post-form` és imprescindible si hi ha accions POST (esborrar, restaurar...).
- Sense `style=""`: la plantilla fa servir classes (`kp-pl0`, dels recursos de dalt, i `kp-search`, de les del pare), i així funciona amb una [Content-Security-Policy](#content-security-policy).
- `partials/pagination` encara ve de `bootstrap5`: el pas següent és fer-ne la versió W3.

### 6. La resta de plantilles

Amb el mateix procediment es fan `form.php`, `partials/field.php`, `show.php`, `partials/filters.php`, `partials/flash.php`, `partials/pagination.php` i `import.php`. Les equivalències més habituals:

| Bootstrap 5 | W3.CSS |
|---|---|
| `card` | `w3-card w3-white` |
| `table table-striped table-hover` | `w3-table w3-striped w3-hoverable w3-bordered` |
| `form-control` | `w3-input w3-border w3-round` |
| `form-select` | `w3-select w3-border w3-round` |
| `form-check-input` | `w3-check` / `w3-radio` |
| `btn btn-primary` | `w3-button w3-blue w3-round` |
| `alert alert-success` | `w3-panel w3-leftbar w3-pale-green w3-border-green` |
| `badge text-bg-primary` | `w3-tag w3-round w3-blue` |
| `is-invalid` + `invalid-feedback` | `w3-border-red` + `w3-small w3-text-red` |
| `collapse` (filtres) | `<details>` / `<summary>` (sense JavaScript) |
| `btn-close` | `<span data-kp-dismiss>&times;</span>` dins la caixa `data-kp-alert` |

W3.CSS no té JavaScript: els temes inclosos fan servir `<details>` per al panell de filtres i l'atribut `data-kp-dismiss` (que tracta el JavaScript compartit) per tancar els missatges.

---

## Exemple: un tema amb Tailwind CSS

Tailwind és diferent: no té components (`.btn`, `.card`), sinó **classes d'utilitat** (`rounded-md border px-3 py-1.5`), i en producció el CSS **es compila** a partir de les classes que apareixen als fitxers. El tema complet inclòs és a [src/Views/tailwind](../src/Views/tailwind).

### 1. Per provar: el Play CDN

El "Play CDN" genera el CSS al navegador a partir de les classes de la pàgina. És la manera més ràpida de començar, i és el que fa el `layout.php` del tema inclòs:

```php
<head>
    <!-- ... -->
    <script<?= $this->nonce() ?> src="https://cdn.tailwindcss.com/3.4.16"></script>
    <link rel="stylesheet"<?= $this->nonce('style') ?> href="<?= esc($config->iconsCss, 'attr') ?>">
</head>
<body class="bg-slate-100 text-slate-800">
    <main class="mx-auto max-w-7xl px-4 py-8"><?= $content ?></main>
</body>
```

El Play CDN **no és per a producció**: és lent i carrega tot el compilador al navegador. Tampoc no funciona amb una [Content-Security-Policy](#content-security-policy) estricta, perquè injecta blocs `<style>` sense nonce; el CSS compilat sí.

### 2. Per a producció: compilar el CSS

Tailwind només posa al CSS les classes que troba als fitxers de `content`. Cal incloure-hi **les plantilles del tema**, siguin a la llibreria o al projecte:

```js
// tailwind.config.js
module.exports = {
    content: [
        './app/Views/**/*.php',
        './vendor/siensis/kpcrudy/src/Views/tailwind/**/*.php',   // tema inclòs
        // './app/Views/kpcrudy/mytheme/**/*.php',                // o el teu tema
    ],
};
```

```css
/* resources/tailwind.css */
@tailwind base;
@tailwind components;
@tailwind utilities;
```

```bash
npx tailwindcss -i resources/tailwind.css -o public/css/app.css --minify
```

I al `layout.php` (o al layout del projecte), el CSS compilat en lloc del Play CDN:

```php
<link rel="stylesheet"<?= $this->nonce('style') ?> href="<?= base_url('css/app.css') ?>">
```

Com que les classes dels botons són a la taula de `partials/button.php` (dins d'un fitxer que Tailwind llegeix), també s'inclouen al CSS. En canvi, **no construeixis noms de classe a trossos** (`'bg-' . $color . '-600'`): Tailwind no els trobaria. Escriu sempre la classe sencera.

### 3. Botons amb utilitats

`partials/button.php` fa el mateix que a W3.CSS, però cada variant és una llista d'utilitats:

```php
<?php
$compact ??= false;
$map = [
    'btn-primary'           => 'border-blue-600 bg-blue-600 text-white hover:bg-blue-700',
    'btn-outline-primary'   => 'border-blue-600 bg-white text-blue-600 hover:bg-blue-50',
    'btn-outline-secondary' => 'border-slate-300 bg-white text-slate-700 hover:bg-slate-50',
    'btn-outline-danger'    => 'border-red-500 bg-white text-red-600 hover:bg-red-50',
];
$variant = implode(' ', array_map(static fn ($c) => $map[$c] ?? $c, preg_split('/\s+/', trim((string) ($action['class'] ?? 'btn-outline-secondary')))));
$classes = 'inline-flex items-center gap-1 rounded-md border text-sm font-medium ' . ($compact ? 'px-2 py-1 ' : 'px-3 py-1.5 ') . $variant;
?>
```

(La resta del fitxer, l'enllaç o el botó POST, és igual que al de W3.CSS.)

### 4. Repetir menys classes

Una plantilla amb Tailwind té moltes classes repetides (tots els inputs són iguals). Hi ha dues maneres d'evitar-ho:

**Una variable a la plantilla**, com fan els temes inclosos:

```php
<?php $input = 'block w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500'; ?>
<input type="text" name="q" class="<?= $input ?>">
<select name="per_page" class="<?= $input ?>">...</select>
```

**Components amb `@apply`** al CSS compilat (no funciona amb el Play CDN sense `type="text/tailwindcss"`):

```css
@layer components {
    .kp-input { @apply block w-full rounded-md border border-slate-300 px-3 py-2 text-sm; }
    .kp-btn   { @apply inline-flex items-center gap-1 rounded-md border px-3 py-1.5 text-sm font-medium; }
}
```

```php
<input type="text" name="q" class="kp-input">
```

### 5. El "preflight"

Tailwind esborra els estils per defecte del navegador: els enllaços no es veuen blaus ni subratllats, i les llistes no tenen pics. L'HTML que genera la llibreria (enllaços d'email i url, fitxers) quedaria sense estil, així que el `partials/assets.php` del tema hi afegeix:

```css
.kpcrudy td a:not([class]), .kpcrudy dd a:not([class]) { color: #2563eb; text-decoration: underline; }
```

### 6. Equivalències habituals

| Bootstrap 5 | Tailwind CSS |
|---|---|
| `card` | `rounded-lg border border-slate-200 bg-white shadow-sm` |
| `table table-hover` | `min-w-full divide-y divide-slate-200 text-sm` + `hover:bg-slate-50` a les files |
| `form-control` | `block w-full rounded-md border border-slate-300 px-3 py-2 text-sm` |
| `btn btn-primary` | `rounded-md bg-blue-600 px-3 py-1.5 text-white hover:bg-blue-700` |
| `alert alert-danger` | `rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-red-800` |
| `badge text-bg-success` | `rounded-full bg-green-600 px-2 text-xs text-white` |
| `is-invalid` | `border-red-500` |
| `d-flex justify-content-between` | `flex justify-between` |
| `row` + `col-md-3` / `col-md-9` | `grid sm:grid-cols-4` + `sm:col-span-3` |

---

## Ampliar un tema existent

Per canviar poca cosa d'un tema inclòs, no cal copiar-lo: crea un tema que tingui el que vols com a **pare** i posa-hi només les plantilles que canvien.

```php
// app/Config/Kpcrudy.php
public array $themes = [
    'w3teal' => ['path' => APPPATH . 'Views/kpcrudy/w3teal', 'parent' => 'w3css'],
];
public string $theme = 'w3teal';
```

Les plantilles es busquen a `w3teal`, després a `w3css` i, al final, a `bootstrap5`.

**Canviar el color principal**: `app/Views/kpcrudy/w3teal/partials/assets.php`

```php
<style<?= $this->nonce('style') ?>>
    .kpcrudy .w3-blue { background-color: #009688 !important; }
    .kpcrudy .w3-text-blue { color: #009688 !important; }
    .kpcrudy .w3-border-blue { border-color: #009688 !important; }
</style>
<?= $this->renderParent('partials/assets') ?>
```

`renderParent()` inclou el `partials/assets.php` de `w3css`, que alhora inclou el de `bootstrap5` (el JavaScript). Cada nivell hi afegeix el seu.

**Canviar una sola plantilla**: per exemple, una fitxa de detall en dues columnes, només per a aquest tema: `app/Views/kpcrudy/w3teal/show.php`. La resta (llistat, formulari...) continua sent la de `w3css`.

El mateix serveix per a `tailwind` o per a qualsevol tema teu. Un tema pot tenir com a pare un altre tema del projecte.

Tot això ho pot preparar la comanda: `php spark kpcrudy:publish theme w3css --as w3teal --only partials/assets,show` registra `w3teal` amb `w3css` com a pare, hi copia `show.php` i, com que `partials/assets.php` de `w3css` ja fa servir `renderParent()`, hi escriu una ampliació buida on posar els estils de dalt. Vegeu [Començar amb una còpia](#començar-amb-una-còpia).

## Comprovacions finals

Abans de donar un tema per acabat, prova:

- [ ] Llistat: cerca, ordenació (clic a la capçalera), registres per pàgina i paginació.
- [ ] Filtres: obrir i tancar el panell, filtrar i treure els filtres.
- [ ] Accions massives: "selecciona-ho tot", aplicar una acció, l'avís quan no hi ha res seleccionat.
- [ ] Accions per fila: les GET (veure, editar) i les POST (esborrar) amb la confirmació.
- [ ] Paperera: restaurar i esborrar definitivament.
- [ ] Formulari: tots els tipus de camp (text, desplegable, N-N, multiselect, data, booleà, imatge), errors de validació, i els tres botons de desar.
- [ ] Camps traduïts (`setTranslations()`): un bloc per idioma, els errors d'un idioma opcional i d'un d'obligatori.
- [ ] Fitxa de detall i accions pròpies (`addAction`).
- [ ] Missatges (desat, esborrat, error) i el botó de tancar.
- [ ] Importació CSV: pujar, previsualitzar i el resultat (si el CRUD té `enableImport()`).
- [ ] Amb el filtre `csrf` actiu.
- [ ] Amb el layout del projecte (`setLayout('layouts/admin')`), no només amb el `default`, i amb `showTitle(false)`.
- [ ] Amb `$CSPEnabled = true`: res bloquejat a la consola del navegador.
- [ ] En una pantalla estreta (mòbil).
