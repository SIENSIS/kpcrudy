# Creating a new theme

**English** · [Català](new_theme_CA.md)

A **theme** is the set of PHP templates that render the CRUD: the list, the forms, the detail page, the import and the small pieces (buttons, filters, pagination...). The logic (queries, validation, permissions) is always the same; the theme only decides the HTML and the CSS.

kpcrudy includes three themes:

| Theme | CSS framework | Folder |
|---|---|---|
| `bootstrap5` (default) | [Bootstrap 5](https://getbootstrap.com/) | [src/Views/bootstrap5](../src/Views/bootstrap5) |
| `w3css` | [W3.CSS](https://www.w3schools.com/w3css/) | [src/Views/w3css](../src/Views/w3css) |
| `tailwind` | [Tailwind CSS](https://tailwindcss.com/) | [src/Views/tailwind](../src/Views/tailwind) |

```php
$crud->setTheme('w3css');        // for one CRUD
```

```php
// app/Config/Kpcrudy.php: for all of them
public string $theme = 'tailwind';
```

| W3.CSS | Tailwind CSS |
|---|---|
| ![List with W3.CSS](../screenshots/18-tema-w3css-llistat.png) | ![List with Tailwind](../screenshots/20-tema-tailwind-llistat.png) |
| ![Form with W3.CSS](../screenshots/19-tema-w3css-formulari.png) | ![Form with Tailwind](../screenshots/21-tema-tailwind-formulari.png) |

This document explains how a theme is built and how you would make a new one, using the W3.CSS and Tailwind examples. The two included themes are the complete result of these steps: use them as a reference.

## Contents

- [How a theme works](#how-a-theme-works)
- [The templates](#the-templates)
- [What the theme cannot change](#what-the-theme-cannot-change)
- [HTML that does not come from the templates](#html-that-does-not-come-from-the-templates)
- [Example: a theme with W3.CSS](#example-a-theme-with-w3css)
- [Example: a theme with Tailwind CSS](#example-a-theme-with-tailwind-css)
- [Extending an existing theme](#extending-an-existing-theme)
- [Final checks](#final-checks)

---

## How a theme works

A theme is a **folder** of PHP templates. It is registered in the configuration with a name:

```php
// app/Config/Kpcrudy.php
public array $themes = [
    'w3mini' => APPPATH . 'Views/kpcrudy/w3mini',
];
```

When the CRUD needs a template (for example `list` or `partials/button`), it looks for it **in order**:

1. in the theme's folder;
2. in the parent theme's folder, if it has one (see [Extending an existing theme](#extending-an-existing-theme));
3. in the `bootstrap5` folder.

Therefore, a theme **does not need to have all the templates**: the missing ones are taken from `bootstrap5`. This lets you start with a single template and add more over time. However, if the CSS framework is not Bootstrap, the templates that come from `bootstrap5` will be unstyled: a complete theme for another framework must have all the templates.

Inside a template, `$this` is a `Kpcrudy\Renderer`, which provides:

| Method | What it is for |
|---|---|
| `$this->render('partials/button', [...])` | Include another template (from the same theme, the parent or `bootstrap5`) |
| `$this->renderParent('partials/assets')` | Include **the same template from the parent theme**, to extend it without copying it |
| `$this->hidden($array)` | Hidden fields from an array (to keep the filters in a GET form) |
| `$this->attrs(['name' => 'q', 'required' => true])` | Escaped HTML attributes (`true` = attribute without a value, `false`/`null` = omitted) |

CodeIgniter functions (`esc()`, `lang()`, `csrf_field()`, `site_url()`...) can also be used.

## The templates

| Template | What it renders | Main variables |
|---|---|---|
| `layout.php` | The complete HTML page (only with the `default` layout): `<head>`, CSS, JS | `$title`, `$content`, `$config` |
| `list.php` | List and trash: header, search, filters, table, bulk actions, pagination | `$columns`, `$rows`, `$headerActions`, `$bulkActions`, `$filters`, `$pagination`, `$state`... |
| `form.php` | Add and edit form | `$fields`, `$action`, `$mode`, `$multipart`, `$formError` |
| `show.php` | Detail page | `$items`, `$actions` |
| `import.php` | CSV import in three steps (`$step`: `upload`, `preview`, `report`) | `$columns`, `$rows`, `$counts`, `$result`... |
| `partials/assets.php` | The CRUD's own CSS and JavaScript | — |
| `partials/button.php` | An action button (link or POST) | `$action`, `$compact` |
| `partials/field.php` | A form field, according to its type | `$field` |
| `partials/filters.php` | Filter panel | `$filters`, `$filterHidden`, `$filtersReset`... |
| `partials/flash.php` | Message (saved, deleted, error...) | `$flash` |
| `partials/pagination.php` | Pagination and "Records X to Y of Z" | `$pagination` |

**All the variables** each template receives are documented in the header of the [bootstrap5](../src/Views/bootstrap5) version. The templates of the other themes receive exactly the same ones.

## What the theme cannot change

The CRUD and its JavaScript expect specific names in the forms. The design can be anything, but these elements must be kept:

**Field names** (read by the server):

| Where | Name |
|---|---|
| Search | `q` |
| Records per page | `per_page` |
| Filters | `f[field]`, and for range filters `f[field][from]` and `f[field][to]` |
| Selection checkboxes | `ids[]` |
| Bulk action | `<select name="action">` |
| Save buttons | `name="_kp_after"` with value `list`, `continue` or `new` |
| Import | `file`, `mode`, `on_error`, `_kp_step` and `token` |
| POST forms | always with `<?= csrf_field() ?>` |

**Contract with the JavaScript** in `bootstrap5/partials/assets.php` (which the other themes reuse with `renderParent()`):

| Element | What it does |
|---|---|
| `data-kp-confirm="message"` | Asks for confirmation before a click |
| `data-kp-check-all` | The checkbox that checks or unchecks all the `ids[]` in the form |
| `<form data-kp-bulk="message">` | The bulk actions form; warns if nothing is selected |
| `<option data-confirm="..." data-url="...">` | Confirmation of the bulk action and, with `data-url`, makes it run via GET (export) |
| `<form id="kp-post-form" method="post">` | Hidden form submitted by the POST action buttons |

**POST actions** (delete, restore, actions with `'method' => 'post'`) are buttons that submit this hidden form:

```php
<button type="submit" form="kp-post-form" formaction="<?= esc($action['url'], 'attr') ?>">...</button>
```

## HTML that does not come from the templates

There are three things the theme does not generate that you need to take into account:

**1. Button classes.** Actions come with Bootstrap classes (`btn-primary`, `btn-outline-danger`...), both the library's own (*Add*, *Edit*, *Delete*) and those from `addAction(..., ['class' => ...])`. Another theme's `partials/button.php` must **translate** them. The included themes do this with a lookup table, and classes not in it are left as they are (so the project can use its own classes):

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

**2. Icons.** The library's actions use [Bootstrap Icons](https://icons.getbootstrap.com/) (`bi bi-pencil`). Bootstrap Icons is a font independent of Bootstrap: the included themes load it in `layout.php` (`$config->iconsCss`) and use it as well.

**3. Cell values.** The library generates a little HTML for some field types:

| Type | HTML |
|---|---|
| boolean | `<span class="kp-badge kp-yes badge text-bg-success">Yes</span>` (and `kp-no`) |
| image | `<img class="img-thumbnail kp-thumb">` |
| file | link with `<i class="bi bi-paperclip">` |
| color | `<span class="kp-swatch">` |

The `kp-*` classes belong to kpcrudy and are neutral: style them in your `partials/assets.php`. In addition, the project's `callbackColumn()` functions often use Bootstrap classes (`badge text-bg-success`). The included themes add a minimal equivalent:

```css
.kpcrudy .badge, .kpcrudy .kp-badge { display: inline-block; padding: 1px 8px; border-radius: 4px; font-size: 12px; }
.kpcrudy .text-bg-success, .kpcrudy .kp-yes { background: #4CAF50; color: #fff; }
.kpcrudy .text-bg-secondary, .kpcrudy .kp-no { background: #9e9e9e; color: #fff; }
```

---

## Example: a theme with W3.CSS

W3.CSS is a single CSS file, with no JavaScript or build step: it is the simplest case. The example creates the `w3mini` theme inside the project. The complete included theme is in [src/Views/w3css](../src/Views/w3css).

### 1. Register the theme

```php
// app/Config/Kpcrudy.php
public array $themes = [
    'w3mini' => APPPATH . 'Views/kpcrudy/w3mini',
];
```

```php
// In the controller
$crud->setTheme('w3mini');
```

### 2. The layout: load W3.CSS

`app/Views/kpcrudy/w3mini/layout.php`:

```php
<!doctype html>
<html lang="<?= esc(service('request')->getLocale(), 'attr') ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc($title) ?></title>
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <link rel="stylesheet" href="<?= esc($config->iconsCss, 'attr') ?>">
</head>
<body class="w3-light-grey">
    <main class="w3-container w3-padding-24"><?= $content ?></main>
</body>
</html>
```

If the CRUD is shown inside the project's layout (`setLayout('layouts/admin')`), this file is not used: the CSS must be loaded by the project's layout.

### 3. The assets: custom CSS + the parent's JavaScript

`partials/assets.php`. There is no need to copy the JavaScript from `bootstrap5`: `renderParent()` includes it.

```php
<style>
    .kpcrudy .kp-badge { display: inline-block; padding: 1px 8px; border-radius: 4px; font-size: 12px; }
    .kpcrudy .kp-yes { background: #4CAF50; color: #fff; }
    .kpcrudy .kp-no  { background: #9e9e9e; color: #fff; }
</style>
<?= $this->renderParent('partials/assets') ?>
```

### 4. The buttons

`partials/button.php`: translates the classes and keeps the link (GET) or the hidden form button (POST).

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

### 5. The list

`list.php`. This is a reduced list (without filters or bulk actions) that shows the structure; the complete version is [w3css/list.php](../src/Views/w3css/list.php).

```php
<div class="kpcrudy">
    <?= $this->render('partials/assets') ?>

    <div class="w3-bar w3-margin-bottom">
        <h2 class="w3-bar-item" style="padding-left: 0"><?= esc($title) ?></h2>
        <div class="w3-bar-item w3-right">
            <?php foreach ($headerActions as $action): ?>
                <?= $this->render('partials/button', ['action' => $action]) ?>
            <?php endforeach ?>
        </div>
    </div>

    <?= $this->render('partials/flash', ['flash' => $flash]) ?>

    <?php if ($canSearch): ?>
        <form method="get" action="<?= esc($listUrl, 'attr') ?>" class="w3-margin-bottom" style="max-width: 28rem">
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

Things to keep in mind:

- The cells (`$row['cells']`) are already escaped HTML: they are printed with `<?= ?>`, without `esc()`.
- The `kp-post-form` form is essential if there are POST actions (delete, restore...).
- `partials/pagination` still comes from `bootstrap5`: the next step is to make the W3 version.

### 6. The remaining templates

`form.php`, `partials/field.php`, `show.php`, `partials/filters.php`, `partials/flash.php`, `partials/pagination.php` and `import.php` are made the same way. The most common equivalents:

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
| `collapse` (filters) | `<details>` / `<summary>` (no JavaScript) |
| `btn-close` | `<span onclick="this.parentElement.remove()">&times;</span>` |

W3.CSS has no JavaScript: the included themes use `<details>` for the filter panel and one line of JavaScript to close the messages.

---

## Example: a theme with Tailwind CSS

Tailwind is different: it has no components (`.btn`, `.card`), but **utility classes** (`rounded-md border px-3 py-1.5`) instead, and in production the CSS **is compiled** from the classes that appear in the files. The complete included theme is in [src/Views/tailwind](../src/Views/tailwind).

### 1. For testing: the Play CDN

The "Play CDN" generates the CSS in the browser from the classes on the page. It is the quickest way to get started, and it is what the included theme's `layout.php` does:

```php
<head>
    <!-- ... -->
    <script src="https://cdn.tailwindcss.com/3.4.16"></script>
    <link rel="stylesheet" href="<?= esc($config->iconsCss, 'attr') ?>">
</head>
<body class="bg-slate-100 text-slate-800">
    <main class="mx-auto max-w-7xl px-4 py-8"><?= $content ?></main>
</body>
```

The Play CDN **is not for production**: it is slow and loads the whole compiler in the browser.

### 2. For production: compiling the CSS

Tailwind only puts into the CSS the classes it finds in the `content` files. You must include **the theme's templates**, whether they are in the library or in the project:

```js
// tailwind.config.js
module.exports = {
    content: [
        './app/Views/**/*.php',
        './vendor/siensis/kpcrudy/src/Views/tailwind/**/*.php',   // included theme
        // './app/Views/kpcrudy/mytheme/**/*.php',                // or your theme
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

And in `layout.php` (or the project's layout), the compiled CSS instead of the Play CDN:

```php
<link rel="stylesheet" href="<?= base_url('css/app.css') ?>">
```

Since the button classes are in the lookup table in `partials/button.php` (inside a file Tailwind reads), they are also included in the CSS. On the other hand, **do not build class names from pieces** (`'bg-' . $color . '-600'`): Tailwind would not find them. Always write the full class.

### 3. Buttons with utilities

`partials/button.php` does the same as in W3.CSS, but each variant is a list of utilities:

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

(The rest of the file, the link or the POST button, is the same as in the W3.CSS one.)

### 4. Repeating fewer classes

A Tailwind template has many repeated classes (all the inputs are the same). There are two ways to avoid this:

**A variable in the template**, as the included themes do:

```php
<?php $input = 'block w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500'; ?>
<input type="text" name="q" class="<?= $input ?>">
<select name="per_page" class="<?= $input ?>">...</select>
```

**Components with `@apply`** in the compiled CSS (does not work with the Play CDN without `type="text/tailwindcss"`):

```css
@layer components {
    .kp-input { @apply block w-full rounded-md border border-slate-300 px-3 py-2 text-sm; }
    .kp-btn   { @apply inline-flex items-center gap-1 rounded-md border px-3 py-1.5 text-sm font-medium; }
}
```

```php
<input type="text" name="q" class="kp-input">
```

### 5. The "preflight"

Tailwind removes the browser's default styles: links are not blue or underlined, and lists have no bullets. The HTML generated by the library (email and url links, files) would be left unstyled, so the theme's `partials/assets.php` adds:

```css
.kpcrudy td a:not([class]), .kpcrudy dd a:not([class]) { color: #2563eb; text-decoration: underline; }
```

### 6. Common equivalents

| Bootstrap 5 | Tailwind CSS |
|---|---|
| `card` | `rounded-lg border border-slate-200 bg-white shadow-sm` |
| `table table-hover` | `min-w-full divide-y divide-slate-200 text-sm` + `hover:bg-slate-50` on the rows |
| `form-control` | `block w-full rounded-md border border-slate-300 px-3 py-2 text-sm` |
| `btn btn-primary` | `rounded-md bg-blue-600 px-3 py-1.5 text-white hover:bg-blue-700` |
| `alert alert-danger` | `rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-red-800` |
| `badge text-bg-success` | `rounded-full bg-green-600 px-2 text-xs text-white` |
| `is-invalid` | `border-red-500` |
| `d-flex justify-content-between` | `flex justify-between` |
| `row` + `col-md-3` / `col-md-9` | `grid sm:grid-cols-4` + `sm:col-span-3` |

---

## Extending an existing theme

To change a few things in an included theme, there is no need to copy it: create a theme that has the one you want as its **parent** and put in it only the templates that change.

```php
// app/Config/Kpcrudy.php
public array $themes = [
    'w3teal' => ['path' => APPPATH . 'Views/kpcrudy/w3teal', 'parent' => 'w3css'],
];
public string $theme = 'w3teal';
```

Templates are looked up in `w3teal`, then in `w3css` and, finally, in `bootstrap5`.

**Changing the main color**: `app/Views/kpcrudy/w3teal/partials/assets.php`

```php
<style>
    .kpcrudy .w3-blue { background-color: #009688 !important; }
    .kpcrudy .w3-text-blue { color: #009688 !important; }
    .kpcrudy .w3-border-blue { border-color: #009688 !important; }
</style>
<?= $this->renderParent('partials/assets') ?>
```

`renderParent()` includes the `partials/assets.php` of `w3css`, which in turn includes the one from `bootstrap5` (the JavaScript). Each level adds its own.

**Changing a single template**: for example, a two-column detail page, only for this theme: `app/Views/kpcrudy/w3teal/show.php`. The rest (list, form...) is still the `w3css` one.

The same applies to `tailwind` or any theme of your own. A theme can have another theme of the project as its parent.

## Final checks

Before considering a theme finished, test:

- [ ] List: search, sorting (click on the header), records per page and pagination.
- [ ] Filters: open and close the panel, filter and clear the filters.
- [ ] Bulk actions: "Select all", apply an action, the warning when nothing is selected.
- [ ] Per-row actions: the GET ones (view, edit) and the POST ones (delete) with the confirmation.
- [ ] Trash: restore and delete permanently.
- [ ] Form: all field types (text, drop-down, N-N, date, boolean, image), validation errors, and the three save buttons.
- [ ] Detail page and custom actions (`addAction`).
- [ ] Messages (saved, deleted, error) and the close button.
- [ ] CSV import: upload, preview and the result (if the CRUD has `enableImport()`).
- [ ] With the `csrf` filter enabled.
- [ ] With the project's layout (`setLayout('layouts/admin')`), not only with the `default` one.
- [ ] On a narrow screen (mobile).
