# kpcrudy

**English** · [Català](README_CA.md)

Automatic CRUD generator for **CodeIgniter 4** (4.4 or later, PHP 8.1+), in the style of GroceryCRUD.

You create an instance, tell it the table (or the model) and what you want to show, and the library takes care of all the interaction with the user. All you need is a route and a controller method.

Features:

- List with **pagination**, **search**, **column sorting** and **filters**
- **CSV export** of the filtered list or of the selected records
- **CSV import** with preview: add or update records, with the same validation as the form
- **Trash** using CodeIgniter's *soft deletes*: restore, delete permanently, empty
- **Detail view** of each record
- **Custom links** on each row with the `id` (or any field), as many as you need
- Add and edit forms with **validation** (CodeIgniter rules and rules inferred from the table)
- **1-N relations** (dropdown) and **N-N relations** (multiple selection with a junction table)
- Field types: text, textarea, number, decimal, email, url, date, date and time, time, color, boolean, enum/select, password, hidden, read-only, **file and image upload**
- Column, field and export **callbacks**, and **hooks** before and after saving or deleting
- **Bulk actions** (delete, restore, export and your own)
- **Permissions** per operation, global or record by record
- **Masked ids** in the URLs (`edit/Xk3f9...` instead of `edit/5`), optional, per CRUD or for the whole project
- **Signed URLs with an expiry** to share read-only pages without logging in (`requireSignature()`): the visitor can search and browse, but cannot change the path or extend the expiry
- **Swappable templates** (Bootstrap 5 theme by default) and configurable layout
- Texts in **Catalan, Spanish, English, French, Portuguese and German**

---

## Documentation

- [docs/functions_EN.md](docs/functions_EN.md): reference of all public functions and properties, with examples.
- [docs/howto_EN.md](docs/howto_EN.md): recipes for common cases (passwords, links with the id, masked id, permissions, relations, files, integration...).
- [docs/commands_EN.md](docs/commands_EN.md): reference of the `spark` commands (`kpcrudy:make`, `kpcrudy:demo`, `kpcrudy:maskid`, `kpcrudy:unmaskid`, `kpcrudy:signurl`, `kpcrudy:checkurl`, `kpcrudy:publish`), with real outputs and recipes.
- [docs/new_theme_EN.md](docs/new_theme_EN.md): how to create a theme, with the W3.CSS and Tailwind CSS examples.

## Demo

The library includes a complete event management application that uses all the features. It is installed in your project with `php spark kpcrudy:demo install` and uninstalled without leaving a trace. The steps are in [Installing the demo in your project](#installing-the-demo-in-your-project), and [screenshots](screenshots/README_EN.md) has a walkthrough with screenshots.

![Demo event list](screenshots/04-esdeveniments-filtres.png)

---

## Installation

The library is not on Packagist. Add the repository to your project's `composer.json`:

```json
"repositories": [
    { "type": "vcs", "url": "https://github.com/SIENSIS/kpcrudy" }
]
```

and install it:

```bash
composer require siensis/kpcrudy:dev-main
```

To develop it locally you can use a `path` repository:

```json
"repositories": [
    { "type": "path", "url": "../kpcrudy", "options": { "symlink": true } }
]
```

CodeIgniter discovers the `Kpcrudy` namespace (views, languages and configuration) on its own thanks to Composer's autoload.

> Validation messages (e.g. "The Name field is required") come from CodeIgniter, not from kpcrudy. The `composer require codeigniter4/translations` package translates them into many languages (Spanish, French, Portuguese, German...), but **not into Catalan**: to have them in Catalan, create `app/Language/ca/Validation.php` with the translations (you can start from `vendor/codeigniter4/translations/Language/es/Validation.php`).

---

## Installing the demo in your project

The demo is an event management application (users, categories, events and attendances) where every screen is a kpcrudy CRUD. Each page has a **Code for this page** button that shows the code of the controller that generates it and the features it displays. The demo is in English (data and texts), so that it can be integrated into any project.

It ships inside the library (in `src/Demo`) and is installed in the project where you already have kpcrudy with a `spark` command. **It does not copy or modify any project file**, and when you uninstall it the project is left exactly as it was.

### Requirements

- kpcrudy installed in the project (see [Installation](#installation)).
- The database configured in `.env` (`default` group) and accessible.
- Write permission on `public/` and `writable/`.
- Optional: PHP's `gd` extension, to generate the sample images. Without GD, the demo is still installed, but without avatars or posters.

### Step 1. Check that the command is available

```bash
php spark list
```

The **Kpcrudy** group must appear in the list with seven commands: `kpcrudy:demo` (the one you need now), `kpcrudy:make` (generate a CRUD from a table), `kpcrudy:maskid` and `kpcrudy:unmaskid` (encode and decode masked ids), `kpcrudy:signurl` and `kpcrudy:checkurl` (create and check signed URLs), and `kpcrudy:publish` (copy the configuration, a theme or the texts of a language into the project to adapt them). They are all described, with examples and real outputs, in the [command reference](docs/commands_EN.md). To see the demo's help:

```bash
php spark help kpcrudy:demo
```

```
Usage:
  kpcrudy:demo <install|uninstall|reset|status> [options]

Arguments:
  action  install: creates tables, data and images | uninstall: removes everything | reset: back to the initial data | status: current state

Options:
  --force             install: drops the tables left by a previous installation and starts again
  --yes               Do not ask for confirmation (uninstall and reset)
  --allow-production  install and reset: run even if ENVIRONMENT is "production"; install also serves the demo routes there (anyone can log in as an administrator!)
```

### Step 2. Install the demo

```bash
php spark kpcrudy:demo install
```

```
Installing the kpcrudy demo...
Done!

  Tables:   kpdemo_categories, kpdemo_users, kpdemo_user_interests, kpdemo_events, kpdemo_attendances
  Images:   public/uploads/kpcrudy-demo
  URL:      http://localhost:8080/kpcrudy-demo

To remove it: php spark kpcrudy:demo uninstall
```

If something fails halfway, the command undoes whatever it has created and shows the error.

### Step 3. Open it

Start the project as usual (with Apache or with `php spark serve`) and open the address shown by the command:

```bash
php spark serve
# http://localhost:8080/kpcrudy-demo
```

The entry page shows the list of test users: **click one to log in**, no password needed.

- `admin` or `john.smith`: administrators, with access to all pages.
- `alice` or any other: users, with the agenda, their attendances and their profile.

### What the installation creates

| What | Where |
|---|---|
| 5 tables with test data (25 users, 6 categories, 36 events, 353 attendances) | `kpdemo_categories`, `kpdemo_users`, `kpdemo_user_interests`, `kpdemo_events`, `kpdemo_attendances` |
| Sample avatars and posters, and the files you upload from the demo | `public/uploads/kpcrudy-demo/` |
| Marker file that enables the demo | `writable/kpcrudy-demo.json` |
| Demo pages | `/kpcrudy-demo/...` |

The `kpdemo_` and `kpcrudy-demo` prefixes avoid any collision with the project: none of its own tables (for example, a `users` table or Shield's), routes, files or the project's login are touched, because the demo uses its own session key.

**How the routes are enabled.** CodeIgniter discovers the library's `src/Config/Routes.php` file on its own (this is the modules mechanism, enabled by default). That file only registers the demo routes if `writable/kpcrudy-demo.json` exists; without the demo installed, it adds nothing. With `CI_ENVIRONMENT = production` it does not register them either (see **Production** below).

### Checking the status

```bash
php spark kpcrudy:demo status
```

```
kpcrudy demo
  Installed: yes
  Date:      2026-10-23T17:18:28+02:00
  URL:       http://localhost:8080/kpcrudy-demo
  Routes:    active
  Tables:
    kpdemo_categories       6 rows
    kpdemo_users            25 rows
    kpdemo_user_interests   48 rows
    kpdemo_events           36 rows
    kpdemo_attendances      353 rows
  Files:     52 in public/uploads/kpcrudy-demo
```

### Going back to the initial data

After testing (deleting, editing, uploading images...), you can go back to the initial state:

```bash
php spark kpcrudy:demo reset          # asks for confirmation
php spark kpcrudy:demo reset --yes    # without confirmation
```

It can also be done from the demo itself: on the home page, an administrator has the **Reset data** button (except in production, where this button and its route do not exist). If the reset fails, the demo stays installed and it can be tried again.

### Uninstalling the demo

```bash
php spark kpcrudy:demo uninstall
```

```
The demo will be removed:
  - Tables: kpdemo_categories, kpdemo_users, kpdemo_user_interests, kpdemo_events, kpdemo_attendances
  - Files:  public/uploads/kpcrudy-demo
  - Routes: /kpcrudy-demo
Continue? [n, y]: y
The demo has been removed. The project is back to how it was.
```

The `kpdemo_*` tables, the `public/uploads/kpcrudy-demo` folder (and `public/uploads`, if the demo created it and it is left empty) and the marker file are deleted, so the `/kpcrudy-demo` routes no longer exist. With `--yes` no confirmation is asked, for example in a script:

```bash
php spark kpcrudy:demo uninstall --yes
```

### Command summary

| Command | What it does |
|---|---|
| `php spark kpcrudy:demo install` | Creates the tables, the data and the images, and enables `/kpcrudy-demo` |
| `php spark kpcrudy:demo install --force` | The same, but first drops any tables left over from a previous installation |
| `php spark kpcrudy:demo status` | Shows whether it is installed, the records in each table and the files |
| `php spark kpcrudy:demo reset [--yes]` | Goes back to the initial data |
| `php spark kpcrudy:demo uninstall [--yes]` | Deletes everything and leaves the project as it was |
| `php spark help kpcrudy:demo` | Command help |

Every option, output and error of `kpcrudy:demo` (and of the other commands) is in the [command reference](docs/commands_EN.md#kpcrudydemo).

### Special situations

**"The demo is already installed".** `install` cannot be run twice. To start over, use `reset`.

**"Demo tables already exist".** Tables have been left over from a previous installation that did not finish or was deleted by hand (`status` warns about it). You have two options:

```bash
php spark kpcrudy:demo uninstall          # drops them
php spark kpcrudy:demo install --force    # drops them and installs again
```

**The project has route discovery disabled.** If the `$aliases` array in `app/Config/Modules.php` does not include `'routes'`, the command detects it and tells you the line you have to add to `app/Config/Routes.php`:

```php
\Kpcrudy\Demo\Demo::routes($routes);
```

The line is harmless: it only registers routes while the demo is installed (and never in production, unless `--allow-production`). Even so, remove it when you uninstall the demo; `uninstall` reminds you.

**Migrations.** The demo tables are created with CodeIgniter migrations ([src/Demo/Database/Migrations](src/Demo/Database/Migrations)), but they are run by the installer and **are not recorded in the project's migration history**. So the project's `php spark migrate`, `migrate:rollback` or `migrate:refresh` never touch them, and uninstalling the demo does not affect the project's own migrations.

**Language.** The demo (data, texts and command messages) is in **English**, so that it can be integrated into any project. It is shown in English even if the project uses another language, including the library's texts.

**CSRF.** The demo routes verify the CSRF token whether or not the project has the `csrf` filter enabled (if it is enabled, they do not verify it twice).

**URL, port and `index.php`.** All the demo addresses (links, forms, actions and images) are built on each request with `site_url()` and `base_url()`, and only relative paths are stored in the database. Therefore, the demo keeps working if you change `app.baseURL`, the port, the folder where the project lives or `app.indexPage`, without reinstalling it. The URL shown by `install` and `status` is computed with the current configuration.

**Production.** The demo is for learning and testing: you log in without a password, and anyone can log in as an administrator. That is why, with `CI_ENVIRONMENT = production`:

- `install` and `reset` refuse to run and say so.
- The `/kpcrudy-demo` routes are not registered, even if `writable/kpcrudy-demo.json` exists (for example, if it was uploaded by mistake).
- The demo footer does not show the CodeIgniter and PHP versions.

If you really want it in a production environment (a test machine, for example), install it with `php spark kpcrudy:demo install --allow-production`: this is recorded in the marker file and only then are the routes registered there (without the reset from the web). `reset` also needs `--allow-production`. Never install it on a public server, and **do not upload `writable/kpcrudy-demo.json`** to the repository. If it is already there, `uninstall` cleans it up.

To find out what each page demonstrates and how it is built internally, see the [demo README](demo/README_EN.md).

---

## Quick start

**Route** (`app/Config/Routes.php`):

```php
\Kpcrudy\Crud::routes($routes, 'admin/clients', 'Admin::clients');
```

This registers `admin/clients` and `admin/clients/(:any)` for GET and POST. It also accepts route options, such as an authentication filter:

```php
\Kpcrudy\Crud::routes($routes, 'admin/clients', 'Admin::clients', ['filter' => 'session']);
```

**Controller**:

```php
<?php

namespace App\Controllers;

use Kpcrudy\Crud;

class Admin extends BaseController
{
    public function clients()
    {
        $crud = new Crud();
        $crud->setTable('clients')
             ->setSubject('Client', 'Clients');

        return $crud->render();
    }
}
```

With this you already have list, add, edit, detail, delete, search, export and, if the table has a `deleted_at` field, trash.

### Generating it with a command

The `kpcrudy:make` command reads the structure of a table and writes the controller method and the route:

```bash
php spark kpcrudy:make                     # asks for the table, the controller, the method, the route...
php spark kpcrudy:make clients             # with the default values
php spark kpcrudy:make clients --controller Admin/Clients --route admin/clients --filter auth --model
```

```
Table clients: 12 fields, primary key id
  1-N  city_id -> cities (name)
  N-N  tags -> tags through client_tag
  unique  email

Model:      app/Models/ClientModel.php
Controller: app/Controllers/Admin/Clients.php (created index())
Route:      \Kpcrudy\Crud::routes($routes, 'admin/clients', 'Admin\Clients::index', ['filter' => 'auth']);
```

The generated code already includes:

- the **1-N relations**, from the foreign keys, with the field that best describes the other table (`name`, `title`, `{first_name} {last_name}`...);
- the **N-N relations**, from the junction tables;
- the **labels** (`displayAs`) and the list **columns**, without the long texts;
- the **field type** of those that can be guessed from the name: `photo`/`avatar`/`image` (image), `document`/`file` (file), `color`, passwords;
- the **`is_unique` rules** of the fields with a unique index.

| Option | Description |
|---|---|
| `--controller` | Class, relative to `App\Controllers` (`Admin/Clients`). If it already exists, the method is added to it |
| `--method` | Method name (by default, `index`) |
| `--route` | Route (by default, derived from the controller: `admin/clients`) |
| `--filter` | Route filter (`auth`, `session`, `auth:admin`...) |
| `--model` | Also generates a model in `app/Models` (with `allowedFields`, timestamps, soft deletes and rules) and uses `setModel()` |
| `--dry-run` | Shows the code without writing any file |
| `--force` | Replaces the method (and the model) if they already exist |

It is a starting point: open the controller and adapt whatever you need (labels in your language, filters, actions, permissions...). More examples, the interactive mode and the common errors are in [kpcrudy:make](docs/commands_EN.md#kpcrudymake).

### URLs it handles

| URL                         | Method | Operation                        |
|-----------------------------|--------|----------------------------------|
| `base`                      | GET    | List                             |
| `base/trash`                | GET    | Trash                            |
| `base/add`                  | GET/POST | Add form                       |
| `base/edit/{id}`            | GET/POST | Edit form                      |
| `base/show/{id}`            | GET    | Detail view                      |
| `base/export`               | GET    | CSV export                       |
| `base/import`               | GET/POST | CSV import (if enabled with `enableImport()`) |
| `base/delete/{id}`          | POST   | Delete (to the trash if there is one) |
| `base/restore/{id}`         | POST   | Restore from the trash           |
| `base/purge/{id}`           | POST   | Delete permanently               |
| `base/emptytrash`           | POST   | Empty the trash                  |
| `base/bulk`                 | POST   | Bulk action                      |

The base URL is deduced automatically. If the route ends with one of these words (e.g. `admin/trash`), set it with `setBaseUrl(site_url('admin/trash'))`.

All operations that modify data go through POST and include the CSRF token. If CodeIgniter's `csrf` filter is active for the route, the filter checks it; otherwise, the CRUD verifies the token itself (this can be disabled with `$csrfProtection = false` in the [configuration](#configuration)).

**Masked ids.** With `$crud->maskIds()` (or `$maskIds = true` in the [configuration](#configuration), for every CRUD), the `{id}` in these URLs is a code (`edit/Xk3f9QaLm2PwE7` instead of `edit/5`), also in the list checkboxes and in the redirects. It uses the same two routes; a forged code or a real id in the URL is treated as a record that does not exist, and hooks, permissions and callbacks receive the real id. Outside the CRUD, `Crud::maskId()` and `Crud::unmaskId()` encode and decode the code, and in actions `{kp_id}` inserts the masked id. For your own routes that must work both with masked ids and without them, `Crud::idForUrl()` and `Crud::idFromUrl()` encode and decode the code only if masking is on (and the route uses `(:segment)`, not `(:num)`). The commands `php spark kpcrudy:maskid <table> <id>` and `php spark kpcrudy:unmaskid <table> <code>` tell you which record a code refers to (with `--config` if the CRUD receives its own configuration; see the [command reference](docs/commands_EN.md#kpcrudyunmaskid)). See [Masked ids](docs/functions_EN.md#masked-ids) and the recipe [Link with a masked id](docs/howto_EN.md#link-with-a-masked-id).

**Route conflicts.** `Crud::routes()` registers `base/(:any)`, which captures every URL starting with `base/`. A route of your own with the same prefix defined after it (e.g. `admin/invoices/(:segment)` for a public page) would never run, and one defined before that captures CRUD URLs (`admin/invoices/add`...) would stop the CRUD from working. In development, `render()` detects this and throws an exception explaining the problem. Use a different prefix for your own pages (`invoice/(:segment)`).

---

## Full example

```php
public function clients()
{
    $crud = new Crud();

    $crud->setTable('clients')
        ->setSubject('Client', 'Clients')

        // What is shown where
        ->columns(['id', 'photo', 'name', 'email', 'city_id', 'status', 'active', 'tags'])
        ->fields(['name', 'email', 'city_id', 'status', 'active', 'birthdate', 'notes', 'photo', 'password', 'tags'])
        ->showFields(['name', 'email', 'city_id', 'status', 'tags', 'notes', 'created_at'])
        ->displayAs(['name' => 'Name', 'city_id' => 'City', 'tags' => 'Tags'])

        // Relations
        ->setRelation('city_id', 'cities', '{name} ({province})')
        ->setRelationNN('tags', 'client_tag', 'tags', 'client_id', 'tag_id', 'name')

        // Types and validation
        ->fieldType('photo', 'image', ['maxSize' => 1024])
        ->fieldType('status', 'select', ['options' => ['lead' => 'Lead', 'active' => 'Active']])
        ->setRules('email', 'permit_empty|valid_email|is_unique[clients.email,id,{id}]')
        ->requiredFields(['city_id'])
        ->readonlyFields(['email'])
        ->setHelp('notes', 'Internal information, not visible to the client.')

        // List
        ->filters(['city_id', 'status', 'active', 'tags', 'birthdate'])
        ->defaultOrder('name')
        ->setPerPage(25, [10, 25, 50])

        // Links on each row
        ->addAction('Invoices', 'invoices/customer/{kp_id}', ['icon' => 'bi bi-receipt', 'class' => 'btn-outline-info'])
        ->addAction('Email', fn ($row) => 'mailto:' . $row['email'], [
            'icon'    => 'bi bi-envelope',
            'visible' => fn ($row) => ! empty($row['email']),
        ])

        // Custom bulk action
        ->addBulkAction('activate', 'Activate', function (array $ids) {
            db_connect()->table('clients')->whereIn('id', $ids)->update(['active' => 1]);

            return count($ids) . ' clients activated';
        })

        // Permissions
        ->unsetPurge()
        ->setPermission('delete', fn (?array $row) => $row === null || $row['status'] !== 'active');

    return $crud->render();
}
```

---

## Reference

### Data source

| Method | Description |
|---|---|
| `setTable(string $table)` | Works directly with the table. A dynamic model is created that detects the primary key, the timestamps (`created_at`, `updated_at`) and the soft deletes (`deleted_at`). |
| `setModel(Model\|string $model)` | Works with a CodeIgniter model. Its table, primary key, `allowedFields`, validation, timestamps, soft deletes and callbacks are used. |
| `setDatabase(string\|BaseConnection $db)` | Database group or connection (table mode). |
| `setPrimaryKey(string $key)` | Forces the primary key. |
| `softDeletes(bool $enabled = true, string $field = 'deleted_at')` | Enables or disables the trash (table mode). In model mode, the model decides. |
| `where(string\|array $field, $value = null)` | Limits the records managed by the CRUD. It applies to the list, detail, editing, deletion and bulk actions. |
| `setSubject(string $singular, ?string $plural)` | Names shown in titles and messages. |

> **Model with `{id}` in the rules**: in CodeIgniter 4.5+ the primary key field must have its own rule to be able to use `{id}`, for example `'id' => 'permit_empty|is_natural_no_zero'`. Kpcrudy adds the identifier automatically when editing (the real id, even if the URL carries a masked code).

### Fields and columns

By default:

- **List**: all fields except `deleted_at` and sensitive fields (passwords, tokens...).
- **Forms**: all except the primary key, the timestamp fields and the sensitive fields that are not passwords, plus the N-N relations.
- **Detail**: all except `deleted_at` and sensitive fields, plus the N-N relations.

Sensitive fields are those whose name is in `$sensitiveFields` (`password`, `token`, `secret`, `hash`, `apikey`...), whole or as a part separated by `_` (`api_token`). If you need one of them, list it explicitly in `columns()`, `showFields()`, `fields()`...

| Method | Description |
|---|---|
| `columns(array)` | List columns. You can add virtual columns with `callbackColumn()`. |
| `fields(array)` | Add and edit fields. |
| `addFields(array)` / `editFields(array)` | Different fields for adding and for editing. |
| `showFields(array)` | Detail view fields. |
| `searchFields(array)` | Fields searched by the search box. By default, the text fields and the relations in the list that have no `callbackColumn`. |
| `filters(array)` | Fields with a filter. By default, the relation, enum, select and boolean columns. |
| `exportColumns(array)` | CSV columns. By default, those of the list. |
| `unsortable(array)` | Columns that cannot be sorted. |
| `displayAs(string\|array $field, ?string $label)` | Labels. |
| `setRules(string\|array $field, string\|array $rules)` | CodeIgniter validation rules. `{id}` is the (real) identifier when editing. |
| `requiredFields(array)` | Required fields. |
| `readonlyFields(array)` | Fields that are shown but cannot be modified when editing. |
| `setDefault(string $field, $value)` | Initial value when adding. |
| `setHelp(string $field, string $text)` | Help text under the field. |
| `fieldType(string $field, string $type, array $options = [])` | Forces the type of a field (see the next table). |

If you do not define rules, Kpcrudy infers them from the table structure: `NOT NULL` without a default value → `required`, `VARCHAR(n)` → `max_length[n]`, integer → `integer`, decimal → `decimal`, date → `valid_date`, time → `HH:MM[:SS]`, color → `#rrggbb`, and email or url (only `http`/`https`) depending on the name. It can be disabled with `$inferRules = false` in the configuration.

#### Field types

The type is deduced from the database and from the field name (`email`, `password`, `url`...), but it can be forced:

| Type | Options | Notes |
|---|---|---|
| `text`, `textarea`, `email`, `url`, `color` | | |
| `number`, `decimal` | | `decimal` accepts decimals |
| `date`, `datetime`, `time` | | Displayed with `$dateFormat` and `$datetimeFormat` |
| `boolean` | | Switch. `TINYINT(1)` is detected automatically |
| `enum` | `options` | MySQL `ENUM`s are detected automatically |
| `select` | `options` => `[value => label]` | |
| `password` | `hash` (default `true`) | Saved with `password_hash()`. When editing, if left blank it does not change |
| `hidden` | `value` | With `value`, that value is always saved (e.g. the current user) |
| `readonly` | | Display only |
| `upload` | `path`, `url`, `allowed`, `maxSize` (KB), `deleteFiles`, `subdir` | Saves the file path relative to `path`. By default in `public/uploads/{table}` |
| `image` | same as `upload` | Images only, with a thumbnail in the list |

**Files in subfolders.** With `subdir`, each new file is saved in a subfolder of `path`, and the field stores the relative path (`2026/10/abc123.png`). It can be a string with `{field}` or a closure that receives the data to be saved and the current record:

```php
$crud->fieldType('poster', 'image', [
    'path'   => FCPATH . 'uploads/posters',
    'url'    => base_url('uploads/posters'),
    // public/uploads/posters/2026/10/abc123.png
    'subdir' => fn (array $data) => date('Y/m', strtotime($data['starts_at'])),
]);

$crud->fieldType('document', 'upload', ['subdir' => 'clients/{client_id}']);
```

The folders are created automatically. For security, only letters, numbers, hyphens and slashes are allowed (never `..`). If the value changes later, the files already saved stay where they were: the path is stored in the record.

Any type accepts `attrs` => `[attribute => value]` to add attributes to the input (`placeholder`, `min`, `pattern`...).

### Relations

```php
// 1-N: clients.city_id -> cities.id
$crud->setRelation('city_id', 'cities', 'name');
$crud->setRelation('city_id', 'cities', '{name} ({province})', [
    'where' => ['active' => 1],   // only these options
    'order' => 'name',
    'pk'    => 'id',
]);

// N-N: clients <-> client_tag <-> tags
$crud->setRelationNN('tags', 'client_tag', 'tags', 'client_id', 'tag_id', 'name');
```

`tags` is a virtual field that you can put in `columns()`, `fields()`, `showFields()` and `filters()`. Relations can be searched, sorted (1-N ones) and filtered. Saved values are validated against the allowed options.

### Callbacks

```php
// How a column is displayed (HTML; you must escape it yourself)
$crud->callbackColumn('credit', fn ($value, array $row) => esc(number_format((float) $value, 2, ',', '.')) . ' €');

// Virtual column
$crud->columns(['name', 'total'])
     ->callbackColumn('total', fn ($value, array $row) => esc(model('InvoiceModel')->totalFor($row['id'])));

// Custom input in the form (it must use name="field")
$crud->callbackField('color', fn ($value, ?array $row, array $field) =>
    '<input type="color" name="color" class="form-control form-control-color" value="' . esc($value ?? '#000000', 'attr') . '">');

// Value in the CSV (if there is none, the callbackColumn one is used without HTML)
$crud->callbackExport('credit', fn ($value, array $row) => (string) $value);
```

### Hooks

```php
$crud->beforeInsert(function (array $data) {
    $data['created_by'] = session('user_id');
    return $data;            // modified data
});

$crud->beforeUpdate(function (array $data, string $id, array $old) {
    if ($old['locked']) {
        return 'This record is locked.';   // error message: it is not saved
    }
    return null;             // null: continue unchanged
});

$crud->beforeDelete(fn (string $id, array $row, bool $purge) => $row['invoices'] > 0 ? 'It has invoices.' : null);

$crud->afterInsert(fn (string $id, array $data) => log_message('info', "Client {$id} created"));
$crud->afterUpdate(fn (string $id, array $data, array $old) => null);
$crud->afterDelete(fn (string $id, array $row, bool $purge) => null);
$crud->afterRestore(fn (string $id, array $row) => null);
```

The `before*` hooks can return an array (new data), `null` (no changes), a string (error message) or `false` (cancel).

### Actions

```php
// On each list row and on the detail view. {id} (real id), {kp_id} (the id as in the CRUD's URLs:
// the code if masked) and {any_field} are replaced. {id} and {kp_id} are always the primary
// key, even if the field is called "code" (there is no {kp_code}).
// For your own pages, {kp_id}: the route, with (:segment), gets the real id with Crud::idFromUrl().
// To open another CRUD filtered, {id}: 'admin/orders?f[client_id]={id}' (filters use the real id).
$crud->addAction('Invoices', 'invoices/customer/{kp_id}', [
    'icon'    => 'bi bi-receipt',          // Bootstrap Icons
    'class'   => 'btn-outline-info',
    'target'  => '_blank',
    'confirm' => 'Open the invoices?',
    'visible' => fn (array $row) => $row['active'] == 1,
]);

// Actions that modify data: via POST (with the CSRF token)
$crud->addAction('Duplicate', 'admin/events-duplicate/{kp_id}', ['method' => 'post', 'confirm' => 'Are you sure?']);
// Routes.php: $routes->post('admin/events-duplicate/(:segment)', 'Admin\Events::duplicate/$1');
// duplicate(string $code): $id = Crud::idFromUrl($code, 'events') ?? throw PageNotFoundException::forPageNotFound();

// The URL can also be a closure
$crud->addAction('Map', fn (array $row) => 'https://maps.google.com/?q=' . urlencode($row['address']));

// Header buttons
$crud->addHeaderAction('Import', 'admin/clients/import', ['icon' => 'bi bi-upload']);

// Bulk actions on the selection. They receive the ids (already filtered by the CRUD's scope).
$crud->addBulkAction('newsletter', 'Send newsletter', function (array $ids, Crud $crud) {
    // ...
    return 'Newsletter sent to ' . count($ids) . ' clients';   // or a ResponseInterface
}, ['confirm' => 'Are you sure?', 'trash' => false]);
```

Relative URLs go through `site_url()`.

### CSV import

Import is enabled on each CRUD with `enableImport()`. The **Import CSV** button appears in the list, leading to a three-step process:

1. **Upload the file** and choose what to do with each row (*always add* or *update if it already exists*) and with the rows with errors (*import nothing* or *skip them*).
2. **Preview**: how the columns have been mapped, how many rows are new, how many will be updated and which ones have errors (per line).
3. **Confirm**. A summary is shown, or a report with the rows that could not be imported.

```php
$crud->enableImport();                                   // key: the primary key
$crud->enableImport(['key' => 'email']);                 // updates the records with the same email
$crud->enableImport([
    'fields'  => ['name', 'email', 'city_id', 'tags'],   // only these columns
    'maxRows' => 500,
    'maxSize' => 1024,                                   // KB
]);

// Transform a value before validating it
$crud->callbackImport('phone', fn (string $value, array $row) => preg_replace('/\D/', '', $value));
```

Each row goes through the same conversion, validation, hooks and permissions as the form, and passwords are saved hashed. Columns are recognized by **field name or by label**, and the values of relations and dropdowns **by id or by the text** displayed. This way, an exported CSV can be edited in a spreadsheet and imported again as is. The encoding (UTF-8 or Windows-1252) and the separator (`;`, `,` or tab) are detected automatically, and there is a button to download a template with the columns.

![Preview of a CSV import](screenshots/16-importacio-previsualitzacio.png)

### Permissions

Operations: `list`, `add`, `edit`, `show`, `delete`, `export`, `import`, `trash`, `restore`, `purge`, `search`, `filters`, `bulk`.

```php
$crud->unsetAdd()->unsetExport();       // also unsetEdit, unsetDelete, unsetShow,
                                        // unsetTrash, unsetPurge, unsetSearch,
                                        // unsetFilters, unsetBulk
$crud->readOnly();                      // no add, edit, delete, restore or purge

// Record by record: $row is null when there is no specific record
// (e.g. to decide whether the button or the bulk action is shown)
$crud->setPermission('edit', fn (?array $row) => $row === null || $row['owner_id'] == session('user_id'));
```

Permissions are also checked on the server, not just in the interface. The `edit` closure is evaluated again when saving (form and import) with the record as it would be after the change: if it returns `false`, nothing is saved. Export requires the `list` permission in addition to `export`.

### Presentation

| Method | Description |
|---|---|
| `setPerPage(int $n, ?array $options)` | Records per page and selector options. |
| `defaultOrder(string $field, string $dir = 'asc')` | Initial order. |
| `setTheme(string $theme)` | Template theme. |
| `setLayout(string\|false $layout)` | `'default'`, `false` or a project view. |
| `setBaseUrl(string $url)` | Base URL of the CRUD. |
| `setBackUrl(string $url)` | Where to go back after saving, deleting or cancelling (by default, the list as you left it). |

---

## Configuration

Create `app/Config/Kpcrudy.php`:

```php
<?php

namespace Config;

class Kpcrudy extends \Kpcrudy\Config\Kpcrudy
{
    public int $perPage            = 25;
    public array $perPageOptions   = [10, 25, 50, 100];
    public string $dateFormat      = 'd/m/Y';
    public string $datetimeFormat  = 'd/m/Y H:i';
    public string $csvDelimiter    = ';';
    public int $importMaxRows      = 1000;
    public int $exportMaxRows      = 100000;   // 0 = no limit
    public bool $csrfProtection    = true;     // verify the token if the csrf filter is not active
    public bool $maskIds           = false;    // true: masked ids in every URL
    public string|false $layout    = 'layouts/admin';
    public array $themes           = ['admin' => APPPATH . 'Views/kpcrudy/admin'];
    public string $theme           = 'admin';
}
```

The quickest way to create it is `php spark kpcrudy:publish config`: it writes this class with **every** option, the library's default value and comment, as a reference. Change only what you need; the options you delete (and those added in new versions) take the library's value. See [kpcrudy:publish](docs/commands_EN.md#kpcrudypublish).

All options are documented in [src/Config/Kpcrudy.php](src/Config/Kpcrudy.php). The security-related ones include `$csrfProtection`, `$exportMaxRows`, `$maskIds` (with `$maskKey` and `$idMasker`, see [Masked ids](docs/functions_EN.md#masked-ids)) and `$sensitiveFields` (names of secret fields that are hidden by default; if you extend it, start from the original list).

## Layouts and themes

**Layout.** By default the CRUD is shown in a full page with Bootstrap 5 and Bootstrap Icons from a CDN. To integrate it into the project's template:

```php
$crud->setLayout('layouts/admin');
```

The `app/Views/layouts/admin.php` view receives `$kpcrudy` (the CRUD HTML) and `$title`:

```php
<!doctype html>
<html>
<head>
    <title><?= esc($title) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>
    <?= view('partials/menu') ?>
    <main class="container py-4"><?= $kpcrudy ?></main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
```

With `setLayout(false)`, `render()` returns only the HTML fragment.

**Themes.** The library includes three themes: `bootstrap5` (default), `w3css` ([W3.CSS](https://www.w3schools.com/w3css/)) and `tailwind` ([Tailwind CSS](https://tailwindcss.com/)).

```php
$crud->setTheme('w3css');
```

Each theme's templates are in `src/Views/{theme}`:

```
layout.php          full page ('default' layout)
list.php            list and trash
form.php            add and edit
show.php            detail
import.php          CSV import
partials/
    assets.php      own CSS and JS
    button.php      action button
    field.php       a form field
    filters.php     filter panel
    flash.php       messages
    pagination.php  pagination
```

To make your own theme, register its directory in `$themes` and copy into it **only the templates you want to change**: the missing ones are loaded from the parent theme (if any) and, finally, from `bootstrap5`. Inside the templates, `$this` is a `Kpcrudy\Renderer`, with `render()` to include partials, `renderParent()` to extend the parent theme's template, `hidden()` and `attrs()`. The variables each template receives are documented in the header of the `bootstrap5` version.

```php
// app/Config/Kpcrudy.php
public array $themes = [
    'admin'  => APPPATH . 'Views/kpcrudy/admin',                                  // parent: bootstrap5
    'w3teal' => ['path' => APPPATH . 'Views/kpcrudy/w3teal', 'parent' => 'w3css'], // extends w3css
];
```

The command `php spark kpcrudy:publish theme w3css --as intranet` copies the templates of a theme to `app/Views/kpcrudy/intranet` and registers it in `$themes`; with `--only list,partials/pagination` it copies only some of them and the rest comes from the original theme. Then use `->setTheme('intranet')` or `$theme = 'intranet'`. To adapt the texts, `php spark kpcrudy:publish lang ca` (see [kpcrudy:publish](docs/commands_EN.md#kpcrudypublish)).

The [docs/new_theme_EN.md](docs/new_theme_EN.md) guide explains step by step how to make a theme, with the W3.CSS and Tailwind examples.

| W3.CSS (`w3css`) | Tailwind CSS (`tailwind`) |
|---|---|
| ![W3.CSS theme](screenshots/18-tema-w3css-llistat.png) | ![Tailwind theme](screenshots/20-tema-tailwind-llistat.png) |

## Languages

Included languages:

| Code | Language |
|------|--------|
| `ca` | Catalan |
| `es` | Spanish |
| `en` | English |
| `fr` | French |
| `pt` | Portuguese |
| `de` | German |

The texts are in `src/Language/{code}/Kpcrudy.php` and follow the request locale (`app.defaultLocale`, or the one negotiated by CodeIgniter if you have `negotiateLocale` enabled and the code in `supportedLocales`). To change any of them, create `app/Language/{code}/Kpcrudy.php` with the keys you want to override (`php spark kpcrudy:publish lang en` copies all the texts of the language into it: keep only the ones you change).

To add a new language, copy `src/Language/en/Kpcrudy.php` to the folder of the corresponding code and translate its values. `{0}`, `{1}`... are parameters and must be kept. Portuguese is European Portuguese: for Brazilian Portuguese, create `app/Language/pt-BR/Kpcrudy.php`.

## Security notes

- All text is escaped in the templates. The `callbackColumn` and `callbackField` callbacks return HTML as is: escape the data in them.
- **CSRF**: every POST request of the CRUD checks the token. If CodeIgniter's `csrf` filter (or a subclass of `CodeIgniter\Filters\CSRF`) is active for the request, the filter does it; otherwise, the CRUD does it itself. It can be disabled with `$csrfProtection = false` if the project protects requests in another way.
- **`where()` scope**: simple equality conditions (`field => value`) are also enforced when saving: the field does not appear in the form, it is always saved with that value (also on import) and, if after saving the record is outside the scope, the change is rolled back.
- **Sensitive fields**: columns with names like `password`, `token`, `secret`, `salt`, `hash`, `apikey`... (`$sensitiveFields`) are hidden by default from the list, the detail view, search, sorting, filters, export and forms (passwords do appear in the form, and are saved hashed). They are only shown if you list them explicitly.
- Columns with `callbackColumn` are not searched unless you add them to `searchFields()`, because the callback may hide the real value.
- Values of relations, enum and select are validated against the allowed options. Invalid filter values are ignored, the `%` and `_` search wildcards are matched literally, and an id that cannot be a valid primary key is treated as not found.
- **Masked ids** (`maskIds()`): codes are always the same for each record, encrypted and signed with a server key; a forged or altered code, or one from another table, is treated as not found. They do not replace permissions or `where()`. In development, the CRUD warns about routes that conflict with its own (`Crud::checkRoutes()`).
- **Signed URLs** (`requireSignature()`, `Crud::signUrl()`): the signature is an HMAC with a server key and covers the path (operation and id) and the expiry, so a link cannot be extended or pointed at another record; the domain and port are not covered. In a CRUD the parameters (`?q=`, `?page=`) stay free, so what the visitor can see must be limited with `where()`. Custom routes are strict: `Crud::checkSignature()` only accepts (unless `allowFreeQuery` is set) URLs signed as a whole with `signUrl()` or `signedUrl()`, never the ones a CRUD signs while browsing. Anyone with the link can open it until it expires; the only way to revoke it earlier is to change the key. See "Signed URLs" in [docs/functions_EN.md](docs/functions_EN.md) and [kpcrudy:signurl / kpcrudy:checkurl](docs/commands_EN.md#kpcrudysignurl).
- URLs (`url` fields and action URLs returned by closures) are only used if they are relative or `http`, `https`, `mailto` or `tel`; links with `target` get `rel="noopener noreferrer"`.
- Uploaded files are validated by extension (the real and the declared one), MIME type (images) and size, and are saved with a random name. When they are replaced or deleted, only files inside the field's upload directory are deleted.
- The CSV export neutralizes values starting with `=`, `+`, `-` or `@` (also with leading spaces or line breaks, and their full-width variants) to prevent formula injection. It has a row limit (`$exportMaxRows`) and reads the records in chunks.
- The temporary import file is private and is deleted when the user goes back to the upload page or after one hour.
- Outside `production`, database errors are shown in the message; in production they are only logged.
