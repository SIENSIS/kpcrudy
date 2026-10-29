# Command reference

**English** · [Català](commands_CA.md)

The library adds seven `spark` commands to the **Kpcrudy** group. Run them from the root of the CodeIgniter project, like any other command (`php spark ...`). Their messages are in English, like the rest of CodeIgniter's commands.

The outputs in this document are real, taken from the [demo](../demo/README_EN.md) project (`http://localhost:8080`), except those of `kpcrudy:publish`, which were taken from a test project (in the demo it would create files). The first line CodeIgniter always prints (`CodeIgniter v4.x.x Command Line Tool - Server Time: ...`) has been left out. Masked codes, signatures and dates change with every installation and every run.

The equivalent functions (`maskIds()`, `Crud::maskId()`, `requireSignature()`, `Crud::signUrl()`...) are described in [functions_EN.md](functions_EN.md), and the programming recipes are in [howto_EN.md](howto_EN.md).

## Contents

- [Quick reference](#quick-reference)
- [Before you start](#before-you-start): the key, `--config`, quotes and exit codes
- [kpcrudy:make](#kpcrudymake): generate the CRUD of a table
- [kpcrudy:demo](#kpcrudydemo): install, check, reset and remove the demo
- [kpcrudy:maskid](#kpcrudymaskid): masked code of an id
- [kpcrudy:unmaskid](#kpcrudyunmaskid): real id of a masked code
- [kpcrudy:signurl](#kpcrudysignurl): create a signed URL
- [kpcrudy:checkurl](#kpcrudycheckurl): check a signed URL
- [kpcrudy:publish](#kpcrudypublish): copy the configuration, a theme or the texts of a language into the project
- [Recipes with commands](#recipes-with-commands)
  - [Generate a CRUD and check it](#generate-a-crud-and-check-it)
  - [Install and remove the demo](#install-and-remove-the-demo)
  - [Find which record a URL refers to](#find-which-record-a-url-refers-to)
  - [Email a signed link valid for 7 days](#email-a-signed-link-valid-for-7-days)
  - [Diagnose a "my link doesn't work"](#diagnose-a-my-link-doesnt-work)
  - [Adapt the configuration and the theme without touching the library](#adapt-the-configuration-and-the-theme-without-touching-the-library)

---

## Quick reference

| Command | What it is for | Example |
|---|---|---|
| `kpcrudy:make [table]` | Writes the controller method, the route and, optionally, the model of a CRUD from a table | `php spark kpcrudy:make clients --controller Admin/Clients --route admin/clients --filter auth` |
| `kpcrudy:demo <action>` | Installs (`install`), checks (`status`), resets (`reset`) or removes (`uninstall`) the demo | `php spark kpcrudy:demo status` |
| `kpcrudy:maskid <context> <id>...` | Shows the masked code of one or more ids (`maskIds()`) | `php spark kpcrudy:maskid invoices 5 6` |
| `kpcrudy:unmaskid <context> <code>...` | Shows the real id of one or more masked codes | `php spark kpcrudy:unmaskid invoices aNiPDZ8eyg3JeFxXeA` |
| `kpcrudy:signurl <url>` | Signs a URL, with or without an expiry | `php spark kpcrudy:signurl shared/agenda --ttl 604800 --free-query` |
| `kpcrudy:checkurl <url>` | Tells whether a signed URL is valid, has expired or has been modified | `php spark kpcrudy:checkurl "http://.../shared/agenda?kp_exp=...&kp_sig=..."` |
| `kpcrudy:publish <config\|theme\|lang> [name]` | Copies the configuration (`app/Config/Kpcrudy.php`), a theme (`app/Views/kpcrudy/...`) or the texts of a language (`app/Language/...`) into the project, to adapt them without touching the library | `php spark kpcrudy:publish theme w3css --as intranet` |

| Option | Commands | Description |
|---|---|---|
| `--config <class>` | `maskid`, `unmaskid`, `signurl`, `checkurl` | Config class of the CRUD, if it is not `app/Config/Kpcrudy.php` |
| `--ttl <seconds>` | `signurl` | How long the link lasts (default `86400` = 1 day; `0` = never expires) |
| `--free-query` | `signurl` | Signs only the path: the parameters (`?q=`, `?page=`...) can change |
| `--namespace`, `--path`, `--routes`, `--extends`, `--controller`, `--method`, `--route`, `--filter`, `--as`, `--model`, `--dry-run`, `--force` | `make` | See [kpcrudy:make](#kpcrudymake) |
| `--force`, `--yes`, `--allow-production` | `demo` | See [kpcrudy:demo](#kpcrudydemo) |
| `--as`, `--only`, `--path`, `--force`, `--dry-run` | `publish` | See [kpcrudy:publish](#kpcrudypublish) |

---

## Before you start

### The key: the same one as the web application

Masked codes and signatures are computed with a secret key. `maskid`, `unmaskid`, `signurl` and `checkurl` only give the same result as the website if they use **the same key and the same configuration** as the CRUD:

- **By default**, the project's `app/Config/Kpcrudy.php` (or the library's, if the project has none). The key comes, in this order, from `$maskKey`, from CodeIgniter's encryption key (`encryption.key`, `php spark key:generate`) or from the file `writable/kpcrudy/mask.key`, which is created automatically the first time.
- **With `--config`**, another class's. It is needed when the CRUD receives its own configuration in the constructor (`new Crud(new OtherConfig())`). The class must extend `Kpcrudy\Config\Kpcrudy`.

The demo is the example: all its CRUDs use `new Crud(new CrudConfig())`, with their own key stored in `writable/kpcrudy-demo.json`. That is why, in the demo project, these four commands must **always** be run with:

```bash
--config "Kpcrudy\Demo\Config\CrudConfig"
```

Without `--config`, the command uses the project's key: the codes do not match (`invalid code for this context`), the signatures are not valid (`Invalid signature`) and, if the project has no key at all, `writable/kpcrudy/mask.key` is created.

**Careful**: if you change the key (or delete `writable/kpcrudy/mask.key`), every masked code changes and every signed link already sent stops working.

### Quoting the URL

A signed URL contains `&` (`?kp_exp=...&kp_sig=...`), which the shell interprets: bash and zsh send the command to the background, `cmd` starts another command, and PowerShell reports an error. **Always quote the URL**:

```bash
php spark kpcrudy:checkurl "http://localhost:8080/kpcrudy-demo/shared/agenda?kp_exp=1793621771&kp_sig=5oHTP3ugjCNNcfdWSIQujwXVeXQWsMe6" --config "Kpcrudy\Demo\Config\CrudConfig"
```

Double quotes work in bash, zsh, PowerShell and `cmd`. Quote the class name of `--config` too, because it contains `\`.

### Exit codes

Every command ends with `0` when it succeeds and `1` when there is an error (or, for `checkurl`, when the signature is not valid). They can be used in scripts:

```bash
php spark kpcrudy:checkurl "$URL" --config "Kpcrudy\Demo\Config\CrudConfig" && echo "The link works"
```

### Getting help

```bash
php spark list --simple          # every command; the kpcrudy ones start with "kpcrudy:"
php spark help kpcrudy:signurl   # syntax, arguments and options of a command
```

---

## kpcrudy:make

Reads the structure of a table (fields, primary and foreign keys, unique indexes, junction tables) and writes the code of a CRUD that works on it: the controller method (creating the controller if it does not exist), the line in `app/Config/Routes.php` (or in a module's routes file, with `--namespace`) and, on request, a model. The code includes the 1-N and N-N relations, the labels, the list columns, the field types that can be guessed from the name (image, file, color, password) and the `is_unique` rules. It is a starting point to adapt, not a finished CRUD. Every check runs before anything is written, so an error never leaves half-written files.

### Syntax

```bash
php spark kpcrudy:make [table] [--namespace Ns] [--path dir] [--routes file] [--extends Class]
                       [--controller Class] [--method method] [--route route] [--filter filter] [--as name]
                       [--model] [--dry-run] [--force]
```

Without the table, it asks for every value one by one.

### Arguments and options

| Argument / option | Description | Default |
|---|---|---|
| `table` | Database table (`default` connection). Only letters, numbers and `_` | Asked |
| `--namespace` | Base namespace of the application or the module (`Blog`, `Acme\Shop`). Controllers go to `<ns>\Controllers`, models to `<ns>\Models` | `App` |
| `--path` | Directory of the base namespace, absolute or relative to the project root (`modules/Blog`). Only needed if the namespace is not registered in the autoloader | Taken from the autoloader (`app/` for `App`; for `Acme\Shop`, the directory of `Acme` + `/Shop` if only `Acme` is registered) |
| `--routes` | Routes file, absolute or relative to the project root. Created if it does not exist | `<path>/Config/Routes.php` |
| `--extends` | Parent class of a **new** controller (an existing one keeps its own) | `<ns>\Controllers\BaseController` if it exists; otherwise `App\Controllers\BaseController`; otherwise `CodeIgniter\Controller` |
| `--controller` | Class, relative to `<ns>\Controllers` (`Admin/Clients`). Created if it does not exist; if it does, the method is added to it | The table name in *StudlyCase* (`kpdemo_categories` → `KpdemoCategories`) |
| `--method` | Controller method. It must start with a lowercase letter and cannot be a method of the base controller | `index` |
| `--route` | CRUD route. Lowercase letters, numbers, `-`, `_` and `/`; it cannot end with an operation (`add`, `edit`, `trash`...) | From the controller and method (`Admin/Clients` → `admin/clients`; with `--method list_all` → `admin/clients/list-all`) |
| `--filter` | Route filter (`auth`, `session`, `auth:admin`...) | None |
| `--as` | Route name, for `route_to()` and `url_to()` (`admin-clients`). Letters, numbers, `-`, `_` and `.` | None |
| `--model` | Also generates `<path>/Models/{Table}Model.php` (with `allowedFields`, timestamps, soft deletes and rules) and uses `setModel()` | No |
| `--dry-run` | Shows the code and runs the checks without writing any file | No |
| `--force` | Replaces the method (and the model) if they already exist | No |

### Examples

```bash
# See what it would generate, without touching anything
php spark kpcrudy:make kpdemo_categories --dry-run

# Admin CRUD with login and a model
php spark kpcrudy:make clients --controller Admin/Clients --route admin/clients --filter auth --model

# A second CRUD on the same table, in another method of the same controller
php spark kpcrudy:make clients --controller Admin/Clients --method archived

# Generate an existing method again (manual changes are lost)
php spark kpcrudy:make clients --controller Admin/Clients --force

# Interactive mode
php spark kpcrudy:make

# With a route name (route_to('admin-clients'))
php spark kpcrudy:make clients --controller Admin/Clients --route admin/clients --as admin-clients

# In the Blog module (namespace registered in Config\Autoload)
php spark kpcrudy:make posts --namespace Blog --route admin/posts --as blog-posts

# In a namespace the autoloader does not know (yet)
php spark kpcrudy:make posts --namespace Blog --path modules/Blog --route admin/posts

# Controllers that extend the project's admin controller
php spark kpcrudy:make clients --controller Admin/Clients --extends "App\Controllers\AdminController"
```

### In a module

By default everything goes to the application (`App`): `app/Controllers`, `app/Models` and `app/Config/Routes.php`. With `--namespace`, it goes to a module or package instead:

| | `App` (default) | `--namespace Blog` (in `modules/Blog`) |
|---|---|---|
| Controller | `app/Controllers/Posts.php` (`App\Controllers\Posts`) | `modules/Blog/Controllers/Posts.php` (`Blog\Controllers\Posts`) |
| Model (`--model`) | `app/Models/PostModel.php` | `modules/Blog/Models/PostModel.php` |
| Routes | `app/Config/Routes.php` | `modules/Blog/Config/Routes.php` (or `--routes`) |
| Handler of the route | `Posts::index` | `\Blog\Controllers\Posts::index` |
| Parent of a new controller | `App\Controllers\BaseController` | `Blog\Controllers\BaseController` if it exists, otherwise the application's |

Outside `App`, the handler is written with the full class name, because the default namespace of the routes (`$routes->setDefaultNamespace()`) is `App\Controllers`. The directory comes from the autoloader (`Config\Autoload::$psr4` or Composer); if the namespace is not registered yet, give it with `--path` (and remember to register it, or the controller will not be found).

If the module's routes file does not exist, it is created, and the command reminds you how CodeIgniter loads it:

```
Controller: modules/Blog/Controllers/Posts.php (created index())
Route:      \Kpcrudy\Crud::routes($routes, 'admin/posts', '\Blog\Controllers\Posts::index', ['as' => 'blog-posts']);
Created modules/Blog/Config/Routes.php: CodeIgniter loads it if route discovery is enabled (Config\Modules) or if app/Config/Routes.php includes it.
```

Route discovery is on by default (`Config\Modules`: `$enabled = true` and `'routes'` in `$aliases`). If the project has it turned off, add `require ROOTPATH . 'modules/Blog/Config/Routes.php';` to `app/Config/Routes.php`. Before writing, the command checks every loaded route (also those of other modules), so it does not add a path that already exists.

### Output

`php spark kpcrudy:make kpdemo_categories --dry-run`:

```
Table kpdemo_categories: 5 fields, primary key id
  N-N  users -> kpdemo_users through kpdemo_user_interests
  unique  name

--- Controller App\Controllers\KpdemoCategories ---
    /**
     * CRUD for the "kpdemo_categories" table (generated by "php spark kpcrudy:make").
     */
    public function index()
    {
        $crud = new Crud();
        $crud->setTable('kpdemo_categories')
            ->setSubject('Category', 'Categories')
            ->columns(['id', 'name', 'color', 'created_at', 'updated_at', 'users'])
            ->fields(['name', 'color', 'users'])
            ->displayAs([
                'id'         => 'Id',
                'name'       => 'Name',
                'color'      => 'Color',
                'created_at' => 'Created at',
                'updated_at' => 'Updated at',
                'users'      => 'Users',
            ])
            ->setRelationNN('users', 'kpdemo_user_interests', 'kpdemo_users', 'category_id', 'user_id', '{first_name} {last_name}')
            ->fieldType('color', 'color')
            ->setRules('name', 'required|max_length[80]|is_unique[kpdemo_categories.name,id,{id}]');

        return $crud->render();
    }

--- app/Config/Routes.php ---
\Kpcrudy\Crud::routes($routes, 'kpdemo-categories', 'KpdemoCategories::index');

Dry run: no files have been written.
```

`php spark kpcrudy:make kpdemo_events --controller Admin/Events --route admin/events --filter auth --model --dry-run` (summary, 1-N relations, model and route with a filter; the controller code has been trimmed):

```
Table kpdemo_events: 17 fields, primary key id
  1-N  category_id -> kpdemo_categories (name)
  1-N  created_by -> kpdemo_users ({first_name} {last_name})

--- Controller App\Controllers\Admin\Events ---
    ...
        $crud->setModel(KpdemoEventModel::class)
            ->setSubject('Event', 'Events')
    ...
            ->setRelation('category_id', 'kpdemo_categories', 'name')
            ->setRelation('created_by', 'kpdemo_users', '{first_name} {last_name}')
            ->fieldType('poster', 'image', ['path' => FCPATH . 'uploads/kpdemo_events', 'url' => base_url('uploads/kpdemo_events')]);
    ...

--- Model App\Models\KpdemoEventModel ---
<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Model for the "kpdemo_events" table (generated by "php spark kpcrudy:make").
 */
class KpdemoEventModel extends Model
{
    protected $table          = 'kpdemo_events';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $useSoftDeletes = true;
    protected $allowedFields  = ['category_id', 'title', 'summary', 'description', 'starts_at', 'ends_at', 'location', 'capacity', 'price', 'audience', 'poster', 'status', 'created_by'];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'category_id' => ['label' => 'Category', 'rules' => 'required'],
        'title'       => ['label' => 'Title', 'rules' => 'required|max_length[150]'],
        'summary'     => ['label' => 'Summary', 'rules' => 'required|max_length[255]'],
        'starts_at'   => ['label' => 'Starts at', 'rules' => 'required'],
        'location'    => ['label' => 'Location', 'rules' => 'permit_empty|max_length[150]'],
        'audience'    => ['label' => 'Audience', 'rules' => 'permit_empty|max_length[100]'],
    ];
}

--- app/Config/Routes.php ---
\Kpcrudy\Crud::routes($routes, 'admin/events', 'Admin\Events::index', ['filter' => 'auth']);

Dry run: no files have been written.
```

In interactive mode (`php spark kpcrudy:make --dry-run`), the value in brackets is used when you press Enter:

```
Tables:
  [1] kpdemo_attendances
  [2] kpdemo_categories
  [3] kpdemo_category_translations
  [4] kpdemo_events
  [5] kpdemo_user_interests
  [6] kpdemo_users
Table (number or name) : 2
Controller (relative to App\Controllers)  [KpdemoCategories]: Admin/Categories
Method  [index]:
Route  [admin/categories]:
Route filter (empty for none) : auth
Route name (empty for none) :
Generate a model too? [n, y]: n
Table kpdemo_categories: 5 fields, primary key id
...
--- app/Config/Routes.php ---
\Kpcrudy\Crud::routes($routes, 'admin/categories', 'Admin\Categories::index', ['filter' => 'auth']);

Dry run: no files have been written.
```

Without `--dry-run`, it shows the files it has written instead of the code. The word in parentheses is `created` (new controller or new method), `added` (method added to an existing controller) or `replaced` (with `--force`):

```
Table clients: 12 fields, primary key id
  1-N  city_id -> cities (name)
  N-N  tags -> tags through client_tag
  unique  email

Model:      app/Models/ClientModel.php
Controller: app/Controllers/Admin/Clients.php (created index())
Route:      \Kpcrudy\Crud::routes($routes, 'admin/clients', 'Admin\Clients::index', ['filter' => 'auth']);

Open http://localhost:8080/admin/clients and adapt the generated code to your needs.
```

### Exit codes

| Code | When |
|---|---|
| `0` | Code generated (or shown, with `--dry-run`) |
| `1` | Any of the errors below; no file has been written |

### Common errors

| Message | Cause and fix |
|---|---|
| `The table "clients" does not exist.` | The table is not in the `default` connection. Check the name (`php spark db:table --show`) and the database in `.env` |
| `Cannot read the database: ...` | It cannot connect. Check `database.default.*` in `.env` and that the server is running |
| `The route cannot end with "edit": the CRUD uses that word as an operation.` | The route ends with `add`, `edit`, `show`, `trash`... Choose another one with `--route` |
| `The route "kpcrudy-demo/admin/categories" already exists and points to \Kpcrudy\Demo\Controllers\Admin\Categories::index. Use --route to choose another path.` | Another route already uses that path. Choose another one with `--route` |
| `Invalid method name "_remap": it must start with a lowercase letter and contain only letters, numbers and _.` | Change `--method`. Names of base controller methods (`initController`...) cannot be used either |
| `Invalid controller name "..."` | Each segment of `--controller` must start with an uppercase letter (the command already capitalises the first letter) and contain only letters, numbers and `_` |
| `The controller cannot be BaseController.` | Choose another `--controller` |
| `Invalid namespace "..."` | Each segment of `--namespace` must start with an uppercase letter and contain only letters, numbers and `_` (`Blog`, `Acme\Shop`) |
| `Cannot find the directory of the namespace Blog. Register it in Config\Autoload::$psr4 or use --path.` | The namespace is not in the autoloader. Register it, or give its directory with `--path` |
| `Invalid parent class "..."` | `--extends` must be a class name (`App\Controllers\AdminController`) |
| `Invalid route name "...": use letters, numbers, -, _ and .` | Change `--as` |
| `The method index() already exists in Clients. Use --force to replace it.` | The method already exists. Use another `--method`, or `--force` to replace it |
| `The model ClientModel already exists and is not for the table "clients" (it uses "...")...` | There is a model with that name for another table. Use `--force` or generate the CRUD without `--model` |
| `The table "..." has columns whose names cannot be used in generated code: ...` | Some column has characters that cannot go into code. Write the CRUD by hand |

---

## kpcrudy:demo

Manages the [demo application](../demo/README_EN.md), which is a module of the library: `install` creates the `kpdemo_*` tables with sample data, the images in `public/uploads/kpcrudy-demo` and the marker file `writable/kpcrudy-demo.json`, which enables the `/kpcrudy-demo` routes; `status` tells you what is installed; `reset` goes back to the initial data, and `uninstall` removes everything and leaves the project as it was. It does not copy or modify any project file. Because the demo has no real authentication, with `CI_ENVIRONMENT = production` `install` and `reset` refuse to run unless `--allow-production` is added.

### Syntax

```bash
php spark kpcrudy:demo <install|status|reset|uninstall> [--force] [--yes] [--allow-production]
```

### Arguments and options

| Argument / option | Description |
|---|---|
| `install` | Creates the tables, data, images and marker file. If it fails halfway, it undoes everything |
| `status` | Shows whether it is installed, the date, the URL, whether the routes are active, the rows of each table and the files |
| `reset` | Goes back to the initial data (asks for confirmation) |
| `uninstall` | Removes tables, images and marker file (asks for confirmation). Also works after an incomplete installation |
| `--force` | `install`: drops the tables left by a previous installation and starts again |
| `--yes` | `reset`, `uninstall`: does not ask for confirmation |
| `--allow-production` | `install`, `reset`: run even if the environment is `production`; with `install`, the demo routes are also registered there (anyone can log in as an administrator!) |

Without an action (or with an unknown one), it shows the help.

> **After upgrading kpcrudy**, a demo installed with an earlier version must be reset: `php spark kpcrudy:demo reset`. The tables have changed (the `editor` role in `kpdemo_users`, the `audience` column of `kpdemo_events` and the new `kpdemo_category_translations` table), and `reset` drops them and creates them again with the initial data.

### Examples

```bash
php spark kpcrudy:demo install            # install
php spark kpcrudy:demo install --force    # install, dropping the tables of a previous attempt
php spark kpcrudy:demo status             # what is installed
php spark kpcrudy:demo reset --yes        # back to the initial data, without asking (scripts)
php spark kpcrudy:demo uninstall          # remove it (asks for confirmation)
```

### Output

`php spark kpcrudy:demo status`:

```
kpcrudy demo
  Installed: yes
  Date:      2026-10-23T17:40:40+02:00
  URL:       http://localhost:8080/kpcrudy-demo
  Routes:    active
  Tables:
    kpdemo_categories       6 rows
    kpdemo_users            26 rows
    kpdemo_user_interests   46 rows
    kpdemo_events           36 rows
    kpdemo_attendances      353 rows
    kpdemo_category_translations 16 rows
  Files:     52 in public/uploads/kpcrudy-demo
```

`php spark kpcrudy:demo install`:

```
Installing the kpcrudy demo...
Done!

  Tables:   kpdemo_categories, kpdemo_users, kpdemo_user_interests, kpdemo_events, kpdemo_attendances, kpdemo_category_translations
  Images:   public/uploads/kpcrudy-demo
  URL:      http://localhost:8080/kpcrudy-demo

To remove it: php spark kpcrudy:demo uninstall
```

`php spark kpcrudy:demo uninstall`:

```
The demo will be removed:
  - Tables: kpdemo_categories, kpdemo_users, kpdemo_user_interests, kpdemo_events, kpdemo_attendances, kpdemo_category_translations
  - Files:  public/uploads/kpcrudy-demo
  - Routes: /kpcrudy-demo
Continue? [n, y]: y
The demo has been removed. The project is back to how it was.
```

`php spark kpcrudy:demo reset --yes`:

```
The demo data has been reset: http://localhost:8080/kpcrudy-demo
```

### Exit codes

| Code | When |
|---|---|
| `0` | Action done; also when you answer `n` to the confirmation (`Cancelled.`) or when `uninstall` finds nothing to remove |
| `1` | Error, unknown action or no action (the help is shown) |

### Common errors

| Message | Cause and fix |
|---|---|
| `The demo is already installed.` | `install` cannot run twice. Use `reset` to go back to the initial data |
| `Some demo tables already exist: ...` | Tables were left by an incomplete installation. `uninstall` drops them; `install --force` drops them and installs again |
| `The demo is not installed. Use "php spark kpcrudy:demo install".` | `reset` without the demo installed |
| `Refusing to install the demo: ENVIRONMENT is "production".` | The environment is `production`. Install it in development or, if you really need to, add `--allow-production` |
| `Cannot write to public/.` / `Cannot write to writable/.` | Write permissions on the project folders |
| `The demo could not be installed: ...` | Database error (connection, permissions...). Everything created has been removed; fix it and try again |
| `Warning: the GD extension is not available; ...` | Warning: it is installed without images. Enable `extension=gd` in `php.ini` and run `reset` |
| `Warning: route discovery for modules is disabled in this project.` | Warning: add `\Kpcrudy\Demo\Demo::routes($routes);` to `app/Config/Routes.php` (and remove it when you uninstall the demo) |

---

## kpcrudy:maskid

Shows the masked code of one or more real ids, as it appears in the URLs of a CRUD with `maskIds()` (`edit/aNiPDZ8eyg3JeFxXeA` instead of `edit/1`). Use it to build a URL to a specific record by hand, to check that a link points to the right record or to prepare test data. A record's code is always the same, but it depends on the context and on the key: to get the website's code, the command must use the same configuration as the CRUD (see [The key](#the-key-the-same-one-as-the-web-application)).

### Syntax

```bash
php spark kpcrudy:maskid <context> <id> [<id>...] [--config Class]
```

### Arguments and options

| Argument / option | Description |
|---|---|
| `context` | The table name of the CRUD, or the value passed to `maskIds(context: ...)` if there is one |
| `id` | One or more real ids |
| `--config` | Config class of the CRUD, if it is not `app/Config/Kpcrudy.php` (in the demo, `"Kpcrudy\Demo\Config\CrudConfig"`) |

### Examples

```bash
php spark kpcrudy:maskid invoices 5                       # project with the default configuration
php spark kpcrudy:maskid invoices 5 6 7                   # several ids at once
php spark kpcrudy:maskid customer-invoices 5              # CRUD with maskIds(context: 'customer-invoices')
php spark kpcrudy:maskid kpdemo_events 1 2 --config "Kpcrudy\Demo\Config\CrudConfig"   # demo
```

### Output

`php spark kpcrudy:maskid kpdemo_events 1 2 --config "Kpcrudy\Demo\Config\CrudConfig"`:

```
Context: kpdemo_events
+----+--------------------+
| Id | Masked code        |
+----+--------------------+
| 1  | aNiPDZ8eyg3JeFxXeA |
| 2  | J2ghJzT9uHikdfUq1A |
+----+--------------------+
```

So the details page of event 1 is `kpcrudy-demo/admin/events/show/aNiPDZ8eyg3JeFxXeA`.

### Exit codes

| Code | When |
|---|---|
| `0` | Every id has been converted |
| `1` | Missing arguments, the `--config` class is not valid or the key is not correct |

### Common errors

| Message | Cause and fix |
|---|---|
| `Usage: php spark kpcrudy:maskid <context> <id>...` | The context or the id is missing |
| `The class "App\Config\Nope" does not exist or does not extend Kpcrudy\Config\Kpcrudy.` | Misspelt class name (use the full namespace, in quotes) or the class does not extend `Kpcrudy\Config\Kpcrudy` |
| `Kpcrudy: $maskKey ha de tenir com a mínim 32 caràcters ...` | The configured key is too short (the message is in Catalan). Generate one with `bin2hex(random_bytes(32))` |
| `The kpcrudy demo is not installed (php spark kpcrudy:demo install).` | The demo configuration was used without the demo installed |
| The code does not match the website's | Different context or key: check the context (`maskIds(context: ...)`) and add `--config` if the CRUD receives its own configuration |

---

## kpcrudy:unmaskid

The opposite of `maskid`: tells you which real id one or more masked codes stand for. It is useful when a user sends a URL (`.../edit/aNiPDZ8eyg3JeFxXeA`), when a code shows up in an error log or a support ticket, or to find out which record a link opens. A forged, modified or other-context code gives no id: it shows `invalid code for this context`. Like `maskid`, it must use the same configuration as the CRUD.

### Syntax

```bash
php spark kpcrudy:unmaskid <context> <code> [<code>...] [--config Class]
```

### Arguments and options

| Argument / option | Description |
|---|---|
| `context` | The table name of the CRUD, or the value passed to `maskIds(context: ...)` |
| `code` | One or more masked codes: the URL segment after `edit/`, `show/`... |
| `--config` | Config class of the CRUD, if it is not `app/Config/Kpcrudy.php` (in the demo, `"Kpcrudy\Demo\Config\CrudConfig"`) |

### Examples

```bash
php spark kpcrudy:unmaskid invoices Xk3f9QaLm2PwE7
php spark kpcrudy:unmaskid kpdemo_events aNiPDZ8eyg3JeFxXeA J2ghJzT9uHikdfUq1A --config "Kpcrudy\Demo\Config\CrudConfig"
```

### Output

`php spark kpcrudy:unmaskid kpdemo_events aNiPDZ8eyg3JeFxXeA J2ghJzT9uHikdfUq1A --config "Kpcrudy\Demo\Config\CrudConfig"`:

```
Context: kpdemo_events
+--------------------+----+
| Masked code        | Id |
+--------------------+----+
| aNiPDZ8eyg3JeFxXeA | 1  |
| J2ghJzT9uHikdfUq1A | 2  |
+--------------------+----+
```

The same code in another context (`php spark kpcrudy:unmaskid kpdemo_users aNiPDZ8eyg3JeFxXeA --config "Kpcrudy\Demo\Config\CrudConfig"`):

```
Context: kpdemo_users
+--------------------+-------------------------------+
| Masked code        | Id                            |
+--------------------+-------------------------------+
| aNiPDZ8eyg3JeFxXeA | invalid code for this context |
+--------------------+-------------------------------+
```

### Exit codes

| Code | When |
|---|---|
| `0` | Every code is valid |
| `1` | Some code is not valid for this context (the table shows the ones that are), missing arguments or the `--config` class is not valid |

### Common errors

| Message | Cause and fix |
|---|---|
| `invalid code for this context` | The code belongs to another table or context, was copied wrongly (with an extra `?` or `/`) or was computed with another key. Check the context and, if the CRUD receives its own configuration, add `--config` |
| `Usage: php spark kpcrudy:unmaskid <context> <code>...` | The context or the code is missing |
| `The class "..." does not exist or does not extend Kpcrudy\Config\Kpcrudy.` | See [kpcrudy:maskid](#kpcrudymaskid) |

---

## kpcrudy:signurl

Creates a signed URL: it adds `kp_sig` (the signature) and, if it expires, `kp_exp` (the expiry time). Use it to share a page protected with `requireSignature()` (like the demo's shared agenda) or a custom route that checks the signature with `Crud::checkSignature()`, without the recipient having to log in: by email, in a message, in a QR code... The signature covers the path and the expiry (and, without `--free-query`, every parameter): changing a single character invalidates it, and the expiry cannot be extended. It does not cover the domain or the port. It is the equivalent of `Crud::signUrl()` and must use the same key as the website (see [The key](#the-key-the-same-one-as-the-web-application)).

### Syntax

```bash
php spark kpcrudy:signurl <url> [--ttl seconds] [--free-query] [--config Class]
```

### Arguments and options

| Argument / option | Description | Default |
|---|---|---|
| `url` | Absolute URL or path relative to the project (completed with `site_url()`, that is, with `app.baseURL`). Quote it if it contains `?` or `&` | — |
| `--ttl` | Seconds until it expires: `3600` (1 hour), `86400` (1 day), `604800` (7 days)... `0` = never expires | `86400` |
| `--free-query` | Signs only the path: the parameters (`?q=`, `?page=`, `?sort=`...) can change. Use it for CRUD pages with `requireSignature()` | The whole URL is signed |
| `--config` | Config class of the CRUD, if it is not `app/Config/Kpcrudy.php` (in the demo, `"Kpcrudy\Demo\Config\CrudConfig"`) | — |

When to use `--free-query`:

- **CRUD page with `requireSignature()`**: with `--free-query`. The visitor can search, filter and change pages; what they can see is limited by `where()`, not by the signature.
- **Custom route with `Crud::checkSignature()`** (a download, a confirmation...): without `--free-query`, so that the parameters (`?file=...`) cannot be changed. `checkSignature()` only accepts whole-URL signatures unless it is given `allowFreeQuery: true`.

### Examples

```bash
# The demo's shared agenda, valid 7 days; the visitor can search and page
php spark kpcrudy:signurl kpcrudy-demo/shared/agenda --ttl 604800 --free-query --config "Kpcrudy\Demo\Config\CrudConfig"

# Details of a single demo event, valid 1 hour (code from kpcrudy:maskid)
php spark kpcrudy:signurl kpcrudy-demo/shared/agenda/show/aNiPDZ8eyg3JeFxXeA --ttl 3600 --config "Kpcrudy\Demo\Config\CrudConfig"

# Download from a custom route: the file parameter is locked
php spark kpcrudy:signurl "invoices/download?file=2026-001.pdf" --ttl 3600

# A link that never expires (it can only be revoked by changing the key)
php spark kpcrudy:signurl shared/catalog --ttl 0 --free-query

# Absolute URL (for another domain of the same project)
php spark kpcrudy:signurl "https://intranet.example.com/shared/catalog" --ttl 86400 --free-query
```

### Output

`php spark kpcrudy:signurl kpcrudy-demo/shared/agenda --ttl 604800 --free-query --config "Kpcrudy\Demo\Config\CrudConfig"`:

```
http://localhost:8080/kpcrudy-demo/shared/agenda?kp_exp=1793621771&kp_sig=5oHTP3ugjCNNcfdWSIQujwXVeXQWsMe6
Valid until 2026-11-02 13:16:11.
```

Without `--free-query` it warns that the whole URL is locked (`php spark kpcrudy:signurl kpcrudy-demo/shared/agenda/show/aNiPDZ8eyg3JeFxXeA --ttl 3600 --config "Kpcrudy\Demo\Config\CrudConfig"`):

```
http://localhost:8080/kpcrudy-demo/shared/agenda/show/aNiPDZ8eyg3JeFxXeA?kp_exp=1793020571&kp_sig=keNdCuyeT3NR3KPqgDIaAQgMj3MSEAfe
Valid until 2026-10-26 14:16:11.
The whole URL is signed: changing any parameter makes it invalid.
```

With `--ttl 0` there is no `kp_exp`:

```
http://localhost:8080/kpcrudy-demo/shared/agenda?kp_sig=mSa4Ovjxmc5GoEnjnZXm7lptEPnoWvXY
It never expires.
```

The time is shown in the application's time zone (`app.appTimezone`).

### Exit codes

| Code | When |
|---|---|
| `0` | URL signed |
| `1` | Missing URL, `--ttl` is not a number or the configuration is not valid |

### Common errors

| Message or symptom | Cause and fix |
|---|---|
| `--ttl must be a number of seconds (0 = the link never expires).` | `--ttl` has units (`7d`) or is negative. Write the seconds: `604800` for 7 days |
| `Usage: php spark kpcrudy:signurl <url> ...` | The URL is missing |
| The URL starts with `http://localhost:8080/` instead of the real domain | A relative path is completed with `app.baseURL` from `.env`. Set the public address there or pass an absolute URL (the signature does not depend on the domain) |
| The website says *This link is not valid.* | The command used another key (missing `--config`), the path is not the one CodeIgniter sees (e.g. without the `kpcrudy-demo/` prefix) or the custom route does not accept `--free-query`. Check it with [kpcrudy:checkurl](#kpcrudycheckurl) |
| The command stops or the shell complains about `&` | The URL contains `&` and is not quoted |

---

## kpcrudy:checkurl

Checks the signature of a URL and shows its expiry: it tells you whether it is valid, has expired (but is correct), is not signed or is not valid (modified, signed with another key or from another project). It is the tool to diagnose a "my link doesn't work" without opening it: all you need is the URL from the user. It tells you what the signature covers (the whole URL or only the path) and who made it: the developer (`kpcrudy:signurl`, `Crud::signUrl()`, `signedUrl()`) or a CRUD while someone was browsing (page 2, a details page...). It must use the same key as the website (see [The key](#the-key-the-same-one-as-the-web-application)).

### Syntax

```bash
php spark kpcrudy:checkurl "<url>" [--config Class]
```

### Arguments and options

| Argument / option | Description |
|---|---|
| `url` | Signed URL, absolute or relative to the project. **Quoted**: it contains `&` |
| `--config` | Config class of the CRUD, if it is not `app/Config/Kpcrudy.php` (in the demo, `"Kpcrudy\Demo\Config\CrudConfig"`) |

### Examples

```bash
# Link to the demo's shared agenda
php spark kpcrudy:checkurl "http://localhost:8080/kpcrudy-demo/shared/agenda?kp_exp=1793621771&kp_sig=5oHTP3ugjCNNcfdWSIQujwXVeXQWsMe6" --config "Kpcrudy\Demo\Config\CrudConfig"

# It can also be given without the domain
php spark kpcrudy:checkurl "/kpcrudy-demo/shared/agenda?kp_exp=1793621771&kp_sig=5oHTP3ugjCNNcfdWSIQujwXVeXQWsMe6" --config "Kpcrudy\Demo\Config\CrudConfig"

# In a script: exits with 0 only if it is valid
php spark kpcrudy:checkurl "$URL" || echo "A new link must be sent"
```

### Output

Valid link signed with `--free-query` (also if parameters have been added, such as `&q=jazz&page=2`):

```
Valid signature (path only: the parameters can change; signed with signUrl() or signedUrl())
Expires: 2026-11-02 13:16:11
```

Valid link signed as a whole:

```
Valid signature (path and parameters; signed with signUrl() or signedUrl())
Expires: 2026-10-26 14:16:11
```

Valid link with no expiry (`--ttl 0`):

```
Valid signature (path and parameters; signed with signUrl() or signedUrl())
It never expires.
```

The same URL with `kp_exp` changed to extend it, or with another code in the details page:

```
Invalid signature (modified URL, another key or another project)
```

Expired link:

```
Expired (the signature is correct)
Expired: 2026-10-26 13:16:33
```

URL without a signature (`http://localhost:8080/kpcrudy-demo/shared/agenda`):

```
Not signed (no kp_sig parameter)
```

### Exit codes

| Code | When |
|---|---|
| `0` | `Valid signature` |
| `1` | `Expired`, `Not signed`, `Invalid signature`, missing URL or the configuration is not valid |

### Common errors

| Message or symptom | Cause and fix |
|---|---|
| `Invalid signature` for a link you have just created | `--config` is missing (or is not the same as in `signurl`): the check uses another key. In the demo project, without `--config` it always says `Invalid signature` |
| `signed by a CRUD while browsing` | It is a link copied from inside a page (page 2, a details page): it is correct, but only that CRUD accepts it, never a custom route with `Crud::checkSignature()` | Normal. If the problem is that it expired, it expires together with the original link used to enter |
| `Invalid signature` for a link that used to work | The key has changed (`$maskKey`, `encryption.key` or `writable/kpcrudy/mask.key`) or the link was cut when copied (the mail client wrapped the line). Send a new one |
| `Expired` | The `kp_exp` date has passed. It cannot be extended: create another one with `kpcrudy:signurl` |
| `Not signed` | The `?kp_exp=...&kp_sig=...` part has been lost (often because the URL was not quoted) |
| `Usage: php spark kpcrudy:checkurl "<url>" [--config Class]` | The URL is missing |

---

## kpcrudy:publish

Copies part of the library into the project so that you can adapt it without touching `vendor/` (changes made in `vendor/` are lost when you update):

- **`config`**: creates `app/Config/Kpcrudy.php`, a `Config\Kpcrudy` class that extends `\Kpcrudy\Config\Kpcrudy` and contains **every** option, with the library's default value and comment, as a reference. From then on, `config('Kpcrudy')` returns this class. The options you delete from the file take the library's value, and so do the ones added in future versions: you only need to keep the ones you change.
- **`theme <theme>`**: copies the templates of a theme (`bootstrap5`, `w3css`, `tailwind` or one registered in `$themes`) to `app/Views/kpcrudy/<new>` and registers the new theme in `$themes` of `app/Config/Kpcrudy.php` (if that file does not exist, it creates it like `config`). The new theme's name is the one given with `--as` (default `<theme>-custom`) and cannot be the name of a library theme.
  - **Full copy**: the new theme's parent is the original theme's parent (`bootstrap5`), so the templates that extend the parent with `renderParent()` keep working the same way.
  - **Partial copy** (`--only`): the parent is the original theme, which provides the templates that were not copied. A template that extends its parent with `renderParent()` (such as `partials/assets` of `w3css` and `tailwind`) is not duplicated: an extension (`renderParent()` and an empty `<style>`) is written instead, where you add only what you want.
  - Then use it in one CRUD with `->setTheme('<new>')` or in all of them with `$theme = '<new>'` in `app/Config/Kpcrudy.php`. How to build a theme: [new_theme_EN.md](new_theme_EN.md).
- **`lang <locale>`**: copies the library's `src/Language/<locale>/Kpcrudy.php` to `app/Language/<locale>/Kpcrudy.php`, with a comment at the top. CodeIgniter merges both files: the project's texts take precedence and the missing ones come from the library. So you can delete the lines you do not change (and new texts from future versions will keep appearing).

It never overwrites a file without `--force`. The only exception is `app/Config/Kpcrudy.php` when a theme is registered in it: it is updated by adding one line to `$themes`. With `--dry-run` it only shows what it would do. Files are written atomically (saved aside and then renamed): an error never leaves a half-written file.

### Syntax

```bash
php spark kpcrudy:publish config [--force] [--dry-run]
php spark kpcrudy:publish theme <theme> [--as name] [--only template,template...] [--path folder] [--force] [--dry-run]
php spark kpcrudy:publish lang <locale> [--force] [--dry-run]
```

Without arguments, it asks what to publish (`config`, `theme` or `lang`) and, if needed, the theme or the locale.

### Arguments and options

| Argument / option | Description | Default |
|---|---|---|
| `config` / `theme` / `lang` | What is copied: the configuration, a theme or the texts of a language | Asked |
| `theme` (with `theme`) | Theme to copy: `bootstrap5`, `w3css`, `tailwind` or one registered in `$themes` | Asked |
| `locale` (with `lang`) | Language to copy: `ca`, `es`, `en`, `fr`, `pt` or `de` | Asked |
| `--as` | `theme`: name of the new theme. Lowercase letters, numbers, `-` and `_`; it cannot be `bootstrap5`, `w3css` or `tailwind` | `<theme>-custom` |
| `--only` | `theme`: copy only these templates, separated by commas and without `.php` (`list,partials/pagination`). The rest comes from the original theme | All |
| `--path` | `theme`: destination folder | `app/Views/kpcrudy/<name>` |
| `--force` | Replaces the files that already exist. With `config`, it generates `app/Config/Kpcrudy.php` again from scratch: your changes and the registered themes are lost | No |
| `--dry-run` | Shows the files that would be created (`create`), updated (`update`) or skipped (`skip`), without writing anything | No |

The templates of a theme (the names `--only` accepts) are `form`, `import`, `layout`, `list`, `show`, `partials/assets`, `partials/button`, `partials/field`, `partials/filters`, `partials/flash` and `partials/pagination`.

### Examples

```bash
# The project's configuration, with every option as a reference
php spark kpcrudy:publish config

# A custom theme based on w3css (full copy); first, see what it would do
php spark kpcrudy:publish theme w3css --as intranet --dry-run
php spark kpcrudy:publish theme w3css --as intranet

# Change only the list and add CSS to the tailwind theme (the rest comes from tailwind)
php spark kpcrudy:publish theme tailwind --as tw --only list,partials/assets

# A theme in another folder
php spark kpcrudy:publish theme bootstrap5 --as admin --path app/Views/admin/kpcrudy

# The project's own Catalan texts
php spark kpcrudy:publish lang ca

# Interactive mode
php spark kpcrudy:publish
```

**Add a pagination option.** After `php spark kpcrudy:publish config`, change this line of `app/Config/Kpcrudy.php`:

```php
public array $perPageOptions = [10, 25, 50, 100, 250];
```

Every CRUD that uses the project's configuration now offers 250 records per page.

### Output

`php spark kpcrudy:publish config`:

```
  create    app/Config/Kpcrudy.php

Edit app/Config/Kpcrudy.php: config('Kpcrudy') now returns this class.
It contains every option with its default value and comment; change only what you need.
```

`php spark kpcrudy:publish theme w3css --as intranet --dry-run` (full copy; `update` means that the theme would be added to `$themes`):

```
  create    app/Views/kpcrudy/intranet/form.php
  create    app/Views/kpcrudy/intranet/import.php
  create    app/Views/kpcrudy/intranet/layout.php
  create    app/Views/kpcrudy/intranet/list.php
  create    app/Views/kpcrudy/intranet/partials/assets.php
  create    app/Views/kpcrudy/intranet/partials/button.php
  create    app/Views/kpcrudy/intranet/partials/field.php
  create    app/Views/kpcrudy/intranet/partials/filters.php
  create    app/Views/kpcrudy/intranet/partials/flash.php
  create    app/Views/kpcrudy/intranet/partials/pagination.php
  create    app/Views/kpcrudy/intranet/show.php
  update    app/Config/Kpcrudy.php

Theme "intranet": 11 template(s) in app/Views/kpcrudy/intranet.
Use it in a CRUD with ->setTheme('intranet'), or for every CRUD with $theme = 'intranet' in app/Config/Kpcrudy.php.
Each template documents at the top the variables it receives. See docs/new_theme_CA.md / new_theme_EN.md.
Dry run: nothing has been written.
```

`php spark kpcrudy:publish theme tailwind --as tw --only list,partials/assets` (partial copy):

```
  create    app/Views/kpcrudy/tw/list.php
  create    app/Views/kpcrudy/tw/partials/assets.php
  update    app/Config/Kpcrudy.php

Theme "tw": 2 template(s) in app/Views/kpcrudy/tw (the rest comes from "tailwind").
...
```

After both commands (without `--dry-run`), `$themes` in `app/Config/Kpcrudy.php` looks like this:

```php
    public array $themes = [
        'intranet' => ['path' => APPPATH . 'Views/kpcrudy/intranet', 'parent' => 'bootstrap5'],
        'tw' => ['path' => APPPATH . 'Views/kpcrudy/tw', 'parent' => 'tailwind'],
    ];
```

`intranet` is a full copy of `w3css`: its parent is the parent of `w3css` (`bootstrap5`). `tw` is a partial copy: its parent is `tailwind`, which provides the remaining templates. Since `partials/assets` of `tailwind` already extends its parent, `tw/partials/assets.php` contains an extension instead of a copy (its comments are in Catalan):

```php
<?php
/**
 * Amplia la plantilla "partials/assets" del tema "tailwind" (creada amb php spark kpcrudy:publish).
 * L'original es manté a la llibreria; aquí només s'hi afegeix el que vulguis.
 *
 * @var \Kpcrudy\Renderer $this
 */
?>
<?= $this->renderParent('partials/assets', get_defined_vars()) ?>
<style>
    /* Els teus estils */
</style>
```

`php spark kpcrudy:publish lang ca`:

```
  create    app/Language/ca/Kpcrudy.php

Change the texts in app/Language/ca/Kpcrudy.php: they take precedence over the library ones.
```

If a file already exists, it is left alone (with `--force`, it is replaced):

```
  skip      app/Views/kpcrudy/intranet/list.php (it exists; use --force to replace it)
```

### Exit codes

| Code | When |
|---|---|
| `0` | Files written (or shown, with `--dry-run`); also when some were skipped because they already existed |
| `1` | Any of the errors below that is not a warning |

### Common errors

| Message | Cause and fix |
|---|---|
| `"bootstrap5" is a theme of the library: choose another name with --as.` | The new theme cannot have the name of a library theme. Choose another one with `--as` |
| `Invalid theme name "..."` | `--as` only accepts lowercase letters, numbers, `-` and `_`, and must start with a letter |
| `The theme "nope" does not exist. Themes: bootstrap5, w3css, tailwind, intranet, tw.` | Misspelt theme. The list includes the themes registered in `$themes` |
| `Unknown templates: nolist. Templates of w3css: form, import, layout, list, partials/assets, partials/button, partials/field, partials/filters, partials/flash, partials/pagination, show.` | `--only` contains a name that is not a template of the theme. Use the names in the list, without `.php` |
| `  skip      <file> (it exists; use --force to replace it)` | Warning: the file already exists and was not touched. Add `--force` to replace it (your changes are lost) |
| `app/Config/Kpcrudy.php already exists (use --force to replace it).` | Warning: the project already has the configuration. `--force` generates it again from scratch (your changes and the registered themes are lost) |
| `The theme "intranet" is already registered in app/Config/Kpcrudy.php: check its path and parent.` | Warning: the theme was already in `$themes` and nothing was changed there. Check that the path and the parent are the ones you want |
| `Add this line to $themes in app/Config/Kpcrudy.php:` | Warning: `public array $themes = [...]` was not found in the file (it was edited by hand). Add the line shown |
| `The language "xx" does not exist. Languages: ca, de, en, es, fr, pt.` | The library does not have that language. To add one, see [howto_EN.md](howto_EN.md) |
| `Cannot write ...` / `Cannot create the folder ...` | Write permissions of `app/` (or of the `--path` folder) |

---

## Recipes with commands

### Generate a CRUD and check it

1. See what it will generate, without writing anything:

   ```bash
   php spark kpcrudy:make clients --controller Admin/Clients --route admin/clients --filter auth --dry-run
   ```

2. Review the summary (`1-N`, `N-N`, `unique`): if a relation is missing, the table has no foreign key for it. When it looks right, run it again without `--dry-run`.
3. Check the route and open the URL the command printed:

   ```bash
   php spark routes | grep admin/clients        # PowerShell: php spark routes | Select-String admin/clients
   php spark serve
   ```

4. Adapt the generated code (labels, filters, permissions...). To generate it again from scratch, add `--force` (changes made to the method are lost).

### Install and remove the demo

```bash
php spark kpcrudy:demo install          # creates tables, data, images and the marker file
php spark kpcrudy:demo status           # check it: "Installed: yes" and "Routes: active"
php spark serve                         # open http://localhost:8080/kpcrudy-demo
php spark kpcrudy:demo reset --yes      # after testing, back to the initial data
php spark kpcrudy:demo uninstall --yes  # remove it completely
php spark kpcrudy:demo status           # "Installed: no" and every table "missing"
```

If `install` says `Some demo tables already exist`, run `php spark kpcrudy:demo install --force`.

### Find which record a URL refers to

A user sends `http://localhost:8080/kpcrudy-demo/admin/events/edit/J2ghJzT9uHikdfUq1A`. The context is the table of the `admin/events` CRUD (or the `maskIds(context: ...)` value, if it has one), and the code is the last segment:

```bash
php spark kpcrudy:unmaskid kpdemo_events J2ghJzT9uHikdfUq1A --config "Kpcrudy\Demo\Config\CrudConfig"
```

```
Context: kpdemo_events
+--------------------+----+
| Masked code        | Id |
+--------------------+----+
| J2ghJzT9uHikdfUq1A | 2  |
+--------------------+----+
```

In your own project, leave out `--config` if the CRUD uses `app/Config/Kpcrudy.php`. The other way round, to build the URL of a record, use `kpcrudy:maskid`.

### Email a signed link valid for 7 days

7 days are `7 × 86400 = 604800` seconds. For a CRUD page with `requireSignature()`, use `--free-query` so that the recipient can search and page:

```bash
php spark kpcrudy:signurl kpcrudy-demo/shared/agenda --ttl 604800 --free-query --config "Kpcrudy\Demo\Config\CrudConfig"
```

```
http://localhost:8080/kpcrudy-demo/shared/agenda?kp_exp=1793621771&kp_sig=5oHTP3ugjCNNcfdWSIQujwXVeXQWsMe6
Valid until 2026-11-02 13:16:11.
```

- First check that `app.baseURL` is the public address: it is the one in the link.
- Paste the whole link into the email; if the mail client splits it into two lines, the signature will not be valid.
- Anyone with the link can open it until it expires. Do not use it for data only one person should see.
- To send it from code (for example, in an automatic email), use `Crud::signUrl()` or `$crud->signedUrl()`: see [functions_EN.md](functions_EN.md).

### Diagnose a "my link doesn't work"

1. Ask for the **exact** URL the user opens and check it, quoted:

   ```bash
   php spark kpcrudy:checkurl "<the user's url>" --config "Kpcrudy\Demo\Config\CrudConfig"
   ```

2. Depending on the result:

   | Result | What happened | What to do |
   |---|---|---|
   | `Valid signature` | The link is fine | The problem is elsewhere: the route does not exist in this environment, the record no longer matches the `where()` (e.g. the event has already taken place) or the user opens another address |
   | `Expired` | It has expired (on the date shown) | Send a new one with `kpcrudy:signurl` |
   | `Invalid signature` | It was modified (or cut), it comes from another environment or the key has changed | Compare it with the one that was sent; if it is the same, the key has changed: send a new one |
   | `Not signed` | `kp_sig` is missing | The user copied the address without the parameters |

3. If the URL is the details page of a record, find out which one with `kpcrudy:unmaskid` (the code is the segment after `show/`):

   ```bash
   php spark kpcrudy:unmaskid kpdemo_events aNiPDZ8eyg3JeFxXeA --config "Kpcrudy\Demo\Config\CrudConfig"
   ```

`checkurl` also recognises links copied from inside a CRUD page (page 2, a details page): they show as `signed by a CRUD while browsing` and expire together with the original link used to enter.

### Adapt the configuration and the theme without touching the library

1. Create the project's configuration and change only what you need in it (for example, `$perPageOptions` or `$dateFormat`):

   ```bash
   php spark kpcrudy:publish config
   ```

2. Make your own theme from the one closest to what you want. If you only need to change one or two templates, copy only those with `--only`: the rest keeps coming from the original theme and gets the library's improvements.

   ```bash
   php spark kpcrudy:publish theme w3css --as intranet --only list,partials/assets --dry-run
   php spark kpcrudy:publish theme w3css --as intranet --only list,partials/assets
   ```

3. Use it in every CRUD with `$theme = 'intranet';` in `app/Config/Kpcrudy.php`, or in a single one with `->setTheme('intranet')`, and edit the templates in `app/Views/kpcrudy/intranet`.
4. To change some texts, copy those of the language and keep only the lines you change:

   ```bash
   php spark kpcrudy:publish lang ca
   ```

When you update the library, these files are not touched, and the options and texts you deleted take the library's new values.
