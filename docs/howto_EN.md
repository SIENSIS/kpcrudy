# Recipes

**English** · [Català](howto_CA.md)

Short solutions for common cases with **kpcrudy**. Each recipe is self-contained. The description of every function and all its options is in [functions_EN.md](functions_EN.md).

In the examples, `$crud` is an instance of `Kpcrudy\Crud` inside a controller method that ends with `return $crud->render();`.

## Contents

**Getting started**
- [Minimal CRUD](#minimal-crud)
- [Generate a table's CRUD with a command](#generate-a-tables-crud-with-a-command)
- [Use an existing model](#use-an-existing-model)
- [Route ending in a reserved word](#route-ending-in-a-reserved-word)
- [Named routes](#named-routes)
- [CRUD inside a module](#crud-inside-a-module)

**Passwords**
- [Handle a password field](#handle-a-password-field)
- [Ask for the password twice](#ask-for-the-password-twice)
- [Use a different hashing algorithm](#use-a-different-hashing-algorithm)
- [Password handled by the model](#password-handled-by-the-model)
- [Password reset button](#password-reset-button)

**Links and actions**
- [Link to a project page with the id](#link-to-a-project-page-with-the-id)
- [Link to an external page with the id](#link-to-an-external-page-with-the-id)
- [Link with a masked id](#link-with-a-masked-id)
- [Find which record a masked code refers to](#find-which-record-a-masked-code-refers-to)
- [Actions that modify data](#actions-that-modify-data)
- [Custom operations on the CRUD route](#custom-operations-on-the-crud-route)
- [Show an action only on some records](#show-an-action-only-on-some-records)
- [Open the list already filtered or sorted](#open-the-list-already-filtered-or-sorted)
- [Custom bulk actions](#custom-bulk-actions)

**Signed links**
- [Share a read-only list without login](#share-a-read-only-list-without-login)
- [Share a single record for a few days](#share-a-single-record-for-a-few-days)
- [Internal list that hands out signed links](#internal-list-that-hands-out-signed-links)
- [Signed download or custom page](#signed-download-or-custom-page)
- [Signed links with masked ids](#signed-links-with-masked-ids)
- [Find out why a signed link does not work](#find-out-why-a-signed-link-does-not-work)

**CSV import**
- [Import data from a CSV](#import-data-from-a-csv)
- [Export, edit and re-import](#export-edit-and-re-import)
- [Update records by a unique field](#update-records-by-a-unique-field)
- [Import users with passwords](#import-users-with-passwords)
- [Import for some users only](#import-for-some-users-only)
- [Values in special formats](#values-in-special-formats)

**Data and security**
- [Limit the CRUD to the logged-in user](#limit-the-crud-to-the-logged-in-user)
- [Store who created the record](#store-who-created-the-record)
- [Permissions by role and by record](#permissions-by-role-and-by-record)
- [Central permissions for all CRUDs](#central-permissions-for-all-cruds)
- [Cross-field validation](#cross-field-validation)
- [Prevent deletion when there are related records](#prevent-deletion-when-there-are-related-records)
- [Run code after every change](#run-code-after-every-change)
- [Several CRUDs on the same table](#several-cruds-on-the-same-table)
- [A user profile: single-record CRUD](#a-user-profile-single-record-crud)

**Relations**
- [1-N relation with filtered options](#1-n-relation-with-filtered-options)
- [N-N relation](#n-n-relation)
- [Junction table with its own data](#junction-table-with-its-own-data)
- [CRUD of a record's children](#crud-of-a-records-children)
- [Translated fields](#translated-fields)

**Presentation**
- [Dropdowns with labels](#dropdowns-with-labels)
- [Several values in one column](#several-values-in-one-column)
- [Formats: currency, badges and text](#formats-currency-badges-and-text)
- [Computed columns without one query per row](#computed-columns-without-one-query-per-row)
- [Upload files and images](#upload-files-and-images)

**Integration**
- [Integrate the CRUD into the project layout](#integrate-the-crud-into-the-project-layout)
- [Content-Security-Policy](#content-security-policy)
- [Custom confirmation dialogs](#custom-confirmation-dialogs)
- [CRUD inside your own view](#crud-inside-your-own-view)
- [Change a template](#change-a-template)
- [Different configuration for one CRUD](#different-configuration-for-one-crud)
- [Change a text or add a language](#change-a-text-or-add-a-language)

---

## Getting started

### Minimal CRUD

```php
// app/Config/Routes.php
\Kpcrudy\Crud::routes($routes, 'admin/clients', 'Admin::clients');
```

```php
// app/Controllers/Admin.php
namespace App\Controllers;

use Kpcrudy\Crud;

class Admin extends BaseController
{
    public function clients()
    {
        $crud = new Crud();
        $crud->setTable('clients')
             ->setSubject('client', 'Clients');

        return $crud->render();
    }
}
```

With this you already get a list, search, sorting, pagination, export, create, edit, detail view and delete, plus a trash if the table has `deleted_at`.

### Generate a table's CRUD with a command

Instead of writing the method by hand, `kpcrudy:make` generates it from the table structure and adds the route to `app/Config/Routes.php`.

**Interactive mode.** Without arguments, the command lists the database tables and asks for each value, with a default suggestion (just press Enter):

```
$ php spark kpcrudy:make
Tables:
  [1] cities
  [2] client_tag
  [3] clients
  [4] tags
Table (number or name) : 3
Controller (relative to App\Controllers)  [Clients]: Admin/Clients
Method  [index]:
Route  [admin/clients]:
Route filter (empty for none) : auth
Route name (empty for none) :
Generate a model too? [n, y]: n
```

**With arguments.** To do it in one go (or from a script), pass the table and the options; anything not given takes its default value:

```bash
php spark kpcrudy:make clients --controller Admin/Clients --route admin/clients --filter auth
```

**See the code before writing it**, with `--dry-run`:

```bash
php spark kpcrudy:make clients --dry-run
```

**Several CRUDs in the same controller.** If the controller already exists, the new method is added to it (along with `use Kpcrudy\Crud;` if it was missing):

```bash
php spark kpcrudy:make cities --controller Admin/Catalog --method cities --route admin/cities
php spark kpcrudy:make tags   --controller Admin/Catalog --method tags   --route admin/tags
```

If the method already exists, the command stops; with `--force` it replaces it (useful after changing the table, but any changes you made to it are lost). If any check fails (for example, a column whose name is not only letters, numbers and `_`), no file is written.

**With a model.** `--model` creates `app/Models/{Table}Model.php` with the `allowedFields`, timestamps, soft deletes and validation rules (`required`, `max_length`, `valid_email` and `is_unique` with `{id}`), and the CRUD uses it through `setModel()`. If the model already exists, it is reused only if it belongs to the same table (`--force` replaces it).

**In a module or with a route name**, see [CRUD inside a module](#crud-inside-a-module) and [Named routes](#named-routes).

The generated code is a starting point. Review in particular the labels (they are generated from the field names), the list columns and the field displayed for each relation.

### Use an existing model

If you already have a model with validation, it is reused as is:

```php
$crud->setModel(\App\Models\ClientModel::class)
     ->setSubject('client', 'Clients');
```

If the model's rules use `{id}`, the primary key must have its own rule (CodeIgniter 4.5+). The CRUD always puts the real id there, even if the URL carries a [masked code](#link-with-a-masked-id):

```php
protected $validationRules = [
    'id'    => 'permit_empty|is_natural_no_zero',
    'email' => 'required|valid_email|is_unique[clients.email,id,{id}]',
];
```

### Route ending in a reserved word

The CRUD uses the last segments of the URL to determine the operation (`add`, `edit/5`, `trash`...). If your route ends with one of these words, specify its base:

```php
\Kpcrudy\Crud::routes($routes, 'warehouse/trash', 'Warehouse::trash');

// Controller
$crud->setTable('waste_items')->setBaseUrl(site_url('warehouse/trash'));
```

### Named routes

`Crud::routes()` accepts the same options as a CodeIgniter route, including `as`:

```php
// app/Config/Routes.php
use Kpcrudy\Crud;

Crud::routes($routes, 'admin/clients', 'Admin\Clients::index', ['filter' => 'auth', 'as' => 'admin-clients']);
```

```php
// In a view or a controller
<a href="<?= url_to('admin-clients') ?>">Clients</a>
<a href="<?= url_to('admin-clients') . '/add' ?>">New client</a>
<a href="<?= url_to('admin-clients') . '/edit/' . Crud::idForUrl($id, 'clients') ?>">Edit</a>
```

Only the base route carries the name: CodeIgniter does not register a second route with a name that already exists, so the operation URLs (`add`, `edit/5`...) are built by appending them to the base. With [masked ids](#link-with-a-masked-id), `Crud::idForUrl()` gives the code to put in the URL. `kpcrudy:make --as admin-clients` writes the route with the name.

### CRUD inside a module

kpcrudy works the same in a module (a namespace of its own, such as `Blog` in `modules/Blog`). Register the namespace and let `kpcrudy:make` generate there:

```php
// app/Config/Autoload.php
public $psr4 = [
    APP_NAMESPACE => APPPATH,
    'Blog'        => ROOTPATH . 'modules/Blog',
];
```

```bash
php spark kpcrudy:make posts --namespace Blog --route admin/posts --as blog-posts --model
```

This creates `Blog\Controllers\Posts` (in `modules/Blog/Controllers/Posts.php`), `Blog\Models\PostModel` and the route in `modules/Blog/Config/Routes.php`, with the full class name because the default namespace of the routes is `App\Controllers`:

```php
// modules/Blog/Config/Routes.php
\Kpcrudy\Crud::routes($routes, 'admin/posts', '\Blog\Controllers\Posts::index', ['as' => 'blog-posts']);
```

If the file did not exist, the command creates it. CodeIgniter reads it with route discovery, which is on by default (`Config\Modules`: `$enabled = true` and `'routes'` in `$aliases`) and looks in every namespace of `Config\Autoload`. If your project has it turned off, include the file from `app/Config/Routes.php`:

```php
// app/Config/Routes.php
require ROOTPATH . 'modules/Blog/Config/Routes.php';
```

The other options (`--path` for a namespace that is not registered, `--routes`, `--extends`) are in [kpcrudy:make](commands_EN.md#kpcrudymake).

---

## Passwords

### Handle a password field

A field named `password` (or `contrasenya`) is already of type password. If it has a different name, say so with `fieldType()`.

```php
$crud->fieldType('pass_hash', 'password')
     ->setRules('pass_hash', 'required|min_length[8]')
     ->displayAs('pass_hash', 'Password');
```

Behaviour:

- It is stored with `password_hash()`.
- On **create**, the `required` rule applies (also without `setRules()`, if the column is `NOT NULL` without a default value). On **edit** it is ignored: if left blank, the password does not change, and below the field the hint "Leave blank to keep the current password" is shown.
- It does not appear in the list, the detail view, search, filters or the export. If you explicitly put it in `columns()`, `••••••` is shown.
- The table's other sensitive fields (`reset_token`, `api_key`, `salt`... according to `$sensitiveFields`) are not shown by default either, not even in forms.
- The form never shows the stored value.

To verify it at login:

```php
if (password_verify($request->getPost('password'), $user['pass_hash'])) {
    // correct
}
```

### Ask for the password twice

With `callbackField()` you render both inputs, and with a hook you check that they match:

```php
$crud->callbackField('password', fn ($value, ?array $row) =>
    '<input type="password" name="password" class="form-control mb-2" autocomplete="new-password" placeholder="Password"' . ($row === null ? ' required' : '') . '>'
    . '<input type="password" name="password_confirm" class="form-control" autocomplete="new-password" placeholder="Repeat the password">'
);

$sameTwice = function () {
    $request = service('request');

    return $request->getPost('password') === $request->getPost('password_confirm')
        ? null
        : 'The two passwords do not match.';
};

$crud->beforeInsert($sameTwice)
     ->beforeUpdate($sameTwice);
```

The hook compares the values coming from the form, because in `$data` the password is already hashed.

### Use a different hashing algorithm

With `'hash' => false` the library applies no hash, and you can do it yourself in a hook. When editing, if the field is left blank, it does not reach `$data`:

```php
$hash = function (array $data) {
    if (isset($data['password'])) {
        $data['password'] = password_hash($data['password'], PASSWORD_ARGON2ID);
    }

    return $data;
};

$crud->fieldType('password', 'password', ['hash' => false])
     ->beforeInsert($hash)
     ->beforeUpdate($hash);
```

### Password handled by the model

If the model already hashes the password in its callbacks (`beforeInsert`, `beforeUpdate`), disable the library's hashing so it is not done twice:

```php
$crud->setModel(\App\Models\UserModel::class)
     ->fieldType('password', 'password', ['hash' => false]);
```

### Password reset button

The administrator should neither see nor type passwords: a button generates a temporary password and sends it by email.

```php
// CRUD controller
$crud->addAction('Reset password', 'admin/users-reset-password/{kp_id}', [
    'icon'    => 'bi bi-key',
    'method'  => 'post',
    'confirm' => 'A new password will be generated and sent by email. Continue?',
]);
```

```php
// Routes.php: (:segment), because with masked ids the id is a code
$routes->post('admin/users-reset-password/(:segment)', 'Admin\Users::resetPassword/$1');

// Controller
public function resetPassword(string $code)
{
    // The real id, whether ids are masked or not
    $id       = \Kpcrudy\Crud::idFromUrl($code, 'users') ?? throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
    $user     = db_connect()->table('users')->where('id', $id)->get()->getRowArray() ?? throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
    $password = bin2hex(random_bytes(6));

    db_connect()->table('users')->where('id', $id)->update(['password' => password_hash($password, PASSWORD_DEFAULT)]);

    service('email')->setTo($user['email'])
        ->setSubject('Your new password')
        ->setMessage("Temporary password: {$password}")
        ->send();

    return redirect()->back()->with('message', 'A new password has been sent to ' . $user['email']);
}
```

Actions with `'method' => 'post'` send the CSRF token; the route must be `post`.

---

## Links and actions

### Link to a project page with the id

`{kp_id}` is the id as it appears in the CRUD's URLs (a code if ids are [masked](#link-with-a-masked-id), otherwise the real id), `{id}` is always the real primary key, and `{field}` any field of the record. Relative URLs go through `site_url()`:

```php
$crud->addAction('Invoices', 'invoices/client/{kp_id}', ['icon' => 'bi bi-receipt']);
$crud->addAction('Orders', 'orders?client={kp_id}&year={year}', ['icon' => 'bi bi-cart']);
```

For your own pages, use `{kp_id}`: the route, with `(:segment)` (not `(:num)`), gets the real id with [`Crud::idFromUrl()`](functions_EN.md#crudidfromurlstring-value-string-context-bool-masked--null-kpcrudyconfig-config--null-string-static), and works whether ids are masked or not (see [Actions that modify data](#actions-that-modify-data)). To open another CRUD filtered, on the other hand, you need `{id}`, because filters use the real values (see [Open the list already filtered or sorted](#open-the-list-already-filtered-or-sorted)).

> `{id}` and `{kp_id}` always point to the table's **primary key**, even if the field is not called `id`. If the key is `code`, `{id}` gives the value of `code` and `{kp_id}` gives that value as it appears in the CRUD's URLs. `{kp_code}` does not exist: the `kp_` prefix is only for the key (see [`addAction()`](functions_EN.md#addactionstring-label-closurestring-url-array-options---static)).

If the URL has to be built with some logic, use a closure:

```php
$crud->addAction('Public page', fn (array $row) => site_url('catalog/' . $row['category_slug'] . '/' . $row['slug']));
```

### Link to an external page with the id

URLs starting with `http://`, `https://`, `//` or `/` are left as they are. The `{...}` values are URL-encoded:

```php
// External system that receives the id
$crud->addAction('View in CRM', 'https://crm.example.com/contacts/{external_id}', [
    'icon'   => 'bi bi-box-arrow-up-right',
    'target' => '_blank',
]);

// A field as a search parameter
$crud->addAction('Map', 'https://www.openstreetmap.org/search?query={address}', [
    'icon'   => 'bi bi-geo-alt',
    'target' => '_blank',
]);
```

To combine several fields into a single parameter, use a closure:

```php
$crud->addAction('Map', fn (array $row) =>
    'https://www.openstreetmap.org/search?query=' . rawurlencode($row['address'] . ', ' . $row['city']),
    ['target' => '_blank']
);
```

### Link with a masked id

You may not want the id to be visible or guessable: neither in the CRUD's URLs (`edit/5`) nor in the links that leave it (a public page, an email, an external system). There are three options, and C is combined with A:

| Option | URL | Purpose |
|---|---|---|
| A. `maskIds()` (recommended) | short and fixed: `/invoice/Xk3f9QaLm2PwE7` | The id can be neither seen nor changed, inside the CRUD and outside |
| B. Custom masker (Sqids) | shorter: `/invoice/Uk9pQ2xa` | No sequential number is visible |
| C. A + signature (`Crud::signUrl()`) | `/invoice/Xk3f9QaLm2PwE7?kp_exp=…&kp_sig=…` | Also, the link expires and only opens for whoever received it |

None of the three replaces the permission check on the target page.

**A. The CRUD's masked ids.** With [`maskIds()`](functions_EN.md#masked-ids), every CRUD URL carries a code instead of the id (`admin/invoices/edit/Xk3f9QaLm2PwE7`), and the links that leave it use the same code. No extra route is needed: the CRUD uses the ones from `Crud::routes()`. Hooks, permissions and callbacks still receive the real id.

```php
// CRUD controller
$crud->setTable('invoices')
     ->maskIds()
     ->addAction('Link for the client', 'invoice/{kp_id}', [   // {kp_id}: the masked id; {id}: the real one
         'icon'   => 'bi bi-link-45deg',
         'target' => '_blank',
     ]);
```

The public page decodes the code with `Crud::unmaskId()`, with the same context as the CRUD (by default, the table name). A forged or altered code returns `null`:

```php
// Routes.php
Crud::routes($routes, 'admin/invoices', 'Admin\Invoices::index', ['filter' => 'auth:admin']);
$routes->get('invoice/(:segment)', 'PublicInvoice::show/$1');   // a different prefix!

// Target controller
use Kpcrudy\Crud;

public function show(string $code)
{
    $id = Crud::unmaskId($code, 'invoices') ?? throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();

    $invoice = model('InvoiceModel')->find($id);
    // ...
}
```

To build the link outside the CRUD (e.g. in an email), `Crud::maskId()` gives the same code:

```php
$link = site_url('invoice/' . Crud::maskId($invoice['id'], 'invoices'));
```

**If your own route must work with masked ids or without them** (e.g. it receives the `{kp_id}` of an action and masking can be turned on or off in the configuration), use [`Crud::idFromUrl()`](functions_EN.md#crudidfromurlstring-value-string-context-bool-masked--null-kpcrudyconfig-config--null-string-static) instead of `unmaskId()`, and `Crud::idForUrl()` instead of `maskId()`: they decode and encode the code only if ids are masked. If the CRUD enables it only with `maskIds()` (not in the configuration), pass `true` as the third parameter:

```php
$id   = Crud::idFromUrl($code, 'invoices', true) ?? throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
$link = site_url('invoice/' . Crud::idForUrl($invoice['id'], 'invoices', true));
```

**The public route must use a different prefix** (`invoice/...`, not `admin/invoices/...`). `Crud::routes()` registers `admin/invoices/(:any)`, which captures everything starting with `admin/invoices/`: an `admin/invoices/(:segment)` route defined after it would never run, and defined before it would take over `admin/invoices/add`, `admin/invoices/trash`... In development, the CRUD detects this and throws an exception explaining the problem (see [`Crud::checkRoutes()`](functions_EN.md#crudcheckroutes-void-static)). More specific routes that do not capture any CRUD URL (e.g. `admin/invoices/pdf/(:segment)` defined before `Crud::routes()`) are fine.

To enable it for every CRUD, set `public bool $maskIds = true;` in `app/Config/Kpcrudy.php`. The key is `$maskKey` (at least 32 characters) or the project's encryption key (`php spark key:generate`); if it changes, old links stop working (see [Context and key](functions_EN.md#context-and-key)).

**B. Shorter codes with Sqids.** If you prefer a different code format, implement `Kpcrudy\IdMasker` and set it in `Config\Kpcrudy::$idMasker`: everything else (routes, `{kp_id}`, `Crud::maskId()`...) works the same. [Interface `Kpcrudy\IdMasker`](functions_EN.md#interface-kpcrudyidmasker) has a complete example with [Sqids](https://sqids.org/php). It is not encryption: with the alphabet it can be reversed, but neither the order nor the number of records is revealed.

**C. Signed link with expiry.** If the link must also expire, and the page must not open just by knowing the code, sign it with `Crud::signUrl()` and check it on the public page with `Crud::hasValidSignature()` (or `Crud::checkSignature()`, to tell an expired link from a forged one). If someone changes the code or the expiry, the signature is no longer valid:

```php
// CRUD controller: 24 h link with the masked id
$crud->addAction('24 h link', fn (array $row) => Crud::signUrl('invoice/' . Crud::maskId($row['id'], 'invoices'), 86400));

// PublicInvoice::show(string $code), before decoding the code
if (! Crud::hasValidSignature()) {
    throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound(lang('Kpcrudy.linkInvalid'));
}
```

To share a whole CRUD (a list or a detail view) instead of a custom page, see the [Signed links](#share-a-read-only-list-without-login) group.

### Find which record a masked code refers to

With masked ids, a URL from an error report or a complaint (`admin/invoices/edit/Xk3f9QaLm2PwE7`) does not tell you which record it is. The `kpcrudy:unmaskid` command decodes it; the first parameter is the context (the table name, or the value passed to `maskIds(context: ...)`):

```bash
php spark kpcrudy:unmaskid invoices Xk3f9QaLm2PwE7 Pq81LmZt0aXcW9
```

And the other way round, to find a record's URL:

```bash
php spark kpcrudy:maskid invoices 5 6 7
```

Codes that are not valid for this context are flagged (they may belong to another table or to an old key). If the CRUDs use their own configuration (`new Crud(new \Config\KpcrudyPublic())`), pass it with `--config "Config\KpcrudyPublic"`, because it may have a different key. See [the commands](functions_EN.md#spark-commands). If the code comes from a signed link that does not work, see [Find out why a signed link does not work](#find-out-why-a-signed-link-does-not-work).

### Actions that modify data

An action that changes data must not be a GET link: a search engine or a link preview could execute it. With `'method' => 'post'` the button submits a form with the CSRF token:

```php
$crud->addAction('Duplicate', 'admin/events-duplicate/{kp_id}', [   // {kp_id}: the id as in the CRUD's URLs
    'icon'    => 'bi bi-copy',
    'method'  => 'post',
    'confirm' => 'Do you want to create a copy of this event?',
]);
```

```php
// Routes.php: a POST route that does not start like the CRUD ones
// (:segment), not (:num): with masked ids a code arrives (DxbBosxVUi2fWpPq3w)
$routes->post('admin/events-duplicate/(:segment)', 'Admin\Events::duplicate/$1');

// Controller
use Kpcrudy\Crud;

public function duplicate(string $code)
{
    $id = Crud::idFromUrl($code, 'events') ?? throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
    // ... create the copy ...
    return redirect()->to(site_url('admin/events/edit/' . Crud::idForUrl($newId, 'events')))->with('message', 'Copy created.');
}
```

`Crud::idFromUrl()` and `Crud::idForUrl()` decode and encode the code only if ids are masked, so the example works in both cases (if the CRUD enables it only with `maskIds()`, pass them `true` as the third parameter).

The route cannot be `admin/events/duplicate/<id>`, because `admin/events/(:any)` already belongs to the CRUD. Use another prefix, such as `admin/events-duplicate/<id>`. Or, simpler, let the CRUD serve it with `addOperation()` (next recipe): no route, no id decoding and no permission check to write.

### Custom operations on the CRUD route

`addOperation()` adds an operation of your own that is served by the CRUD's route (`admin/events/duplicate/<id>`). Before calling it, the CRUD does the same as for its own operations: CSRF token, masked id, `where()` scope, permissions and signature; afterwards it returns to the list with the message. There is no route to add.

```php
$crud->addOperation('duplicate', function (string $id, array $event, Crud $crud) {
    unset($event['id']);
    $event['title']  .= ' (copy)';
    $event['status']  = 'draft';

    $db = db_connect();
    $db->table('events')->insert($event);

    // $crud->url() masks (and signs) the id if needed
    return redirect()->to($crud->url('edit', (string) $db->insertID()))->with('message', 'Copy created.');
}, [
    'icon'    => 'bi bi-copy',
    'confirm' => 'Create a copy of this event?',
]);
```

The callback receives the real id and the record, and returns:

| Return | Result |
|---|---|
| a string | back to the list with that success message |
| `null` | back to the list with a generic message |
| `false` | back to the list with "cancelled" |
| `$crud->backToList('danger', 'Message')` | back to the list with an error (also `warning`, `info`) |
| a response | that response (redirect, download, page...) |

```php
$crud->addOperation('join', function (string $id, array $event, Crud $crud) use ($userId) {
    if ($event['places'] <= 0) {
        return $crud->backToList('danger', 'There are no places left.');
    }

    db_connect()->table('attendances')->insert(['event_id' => $id, 'user_id' => $userId]);

    return 'You are registered.';
}, ['icon' => 'bi bi-person-plus', 'visible' => fn (array $row) => $row['places'] > 0]);
```

**A general operation** (not on a record) with `'row' => false` gets a button in the list header. With `'method' => 'get'` it can show a page; `$crud->page()` wraps your HTML in the same layout as the CRUD:

```php
$crud->addOperation('report', function (?string $id, ?array $row, Crud $crud) {
    $totals = db_connect()->table('events')->select('status, COUNT(*) AS total')->groupBy('status')->get()->getResultArray();

    return service('response')->setBody($crud->page(view('events/report', ['totals' => $totals]), 'Events report'));
}, ['row' => false, 'method' => 'get', 'icon' => 'bi bi-bar-chart', 'label' => 'Report']);
```

Other options: `'permission'` (by default the key: decide it with `setPermission('duplicate', ...)` or with the [permission resolver](#central-permissions-for-all-cruds)), `'trash' => true` (on records in the trash), `'button' => false` (no button, if you link to it yourself). The key cannot be one of the CRUD's operations (`add`, `edit`, `trash`...).

### Show an action only on some records

```php
$crud->addAction('Pay', 'invoices/pay/{kp_id}', [
    'icon'    => 'bi bi-credit-card',
    'class'   => 'btn-success',
    'method'  => 'post',
    'visible' => fn (array $row) => $row['status'] === 'pending',
]);
```

If the condition depends on other tables, compute it before the CRUD (see [Computed columns without one query per row](#computed-columns-without-one-query-per-row)):

```php
$registered = array_column(db_connect()->table('attendances')->select('event_id')->where('user_id', $userId)->get()->getResultArray(), 'event_id');

$crud->addAction('Sign me up', 'events-join/{kp_id}', [
    'method'  => 'post',
    'visible' => fn (array $row) => ! in_array($row['id'], $registered),
]);
```

`visible` only hides the button. The target route (with `(:segment)` and `Crud::idFromUrl()`, as in [Actions that modify data](#actions-that-modify-data)) must check again that the action is allowed.

### Open the list already filtered or sorted

The list state is in the URL, so you can link to it directly:

| Parameter | Example | Effect |
|---|---|---|
| `q` | `?q=garcia` | Search |
| `f[field]` | `?f[status]=paid` | List or text filter |
| `f[field][from]`, `f[field][to]` | `?f[created_at][from]=2026-01-01` | Range filter |
| `sort`, `dir` | `?sort=name&dir=desc` | Sort order |
| `page`, `per_page` | `?page=2&per_page=50` | Pagination |

The field must be in `filters()` (or in the default filters) and among the sortable columns.

```php
// In the clients CRUD, a button to see their orders already filtered.
// {id}, not {kp_id}: filters use the real id, even with masked ids
$crud->addAction('Orders', 'admin/orders?f[client_id]={id}&sort=date&dir=desc', ['icon' => 'bi bi-cart']);
```

```php
// In any view
<a href="<?= site_url('admin/orders?f[status]=pending') ?>">Pending orders</a>
```

### Custom bulk actions

```php
$crud->addBulkAction('publish', 'Publish', function (array $ids) {
    db_connect()->table('posts')->whereIn('id', $ids)->update(['status' => 'published']);

    return count($ids) . ' posts published.';
});

// With confirmation, and available in the trash
$crud->addBulkAction('anonymize', 'Anonymize', function (array $ids) {
    db_connect()->table('users')->whereIn('id', $ids)->update(['email' => null, 'phone' => null]);

    return 'Personal data removed.';
}, ['confirm' => 'This cannot be undone. Continue?', 'trash' => true]);
```

`$ids` is already filtered by the CRUD's scope (`where()`): there is no need to check that the records are "yours".

---

## Signed links

A signed link opens a page without logging in, only for whoever received it and until it expires. How it works, what the signature covers and every option: [Signed URLs](functions_EN.md#signed-urls).

### Share a read-only list without login

An events agenda that anyone with the link can browse for 7 days, without an account. This is what the demo's [Shared](../src/Demo/Controllers/Shared.php) controller does.

```php
// app/Config/Routes.php
use Kpcrudy\Crud;

Crud::routes($routes, 'admin/events', 'Admin\Events::index', ['filter' => 'auth:admin']);
// No login filter (the signature is the permission) and a different prefix from the admin area
Crud::routes($routes, 'shared/agenda', 'Shared::agenda');
```

```php
// app/Controllers/Shared.php
namespace App\Controllers;

use Kpcrudy\Crud;

class Shared extends BaseController
{
    public const TTL = 7 * 86400;   // 7 days

    public function agenda()
    {
        $crud = new Crud();
        $crud->setTable('events')
             ->setSubject('event', 'Agenda')
             // What the visitor can see: the signature does not cover ?q=, ?page=, ?f[...]=...
             ->where('status', 'published')
             ->where('starts_at >=', date('Y-m-d H:i:s'))
             ->readOnly()
             ->unsetTrash()
             ->unsetBulk()
             ->unsetExport()
             ->maskIds()                 // recommended for shared links
             ->requireSignature()        // '*': every page needs a signature
             ->setLayout('default')      // no application menu: the visitor is not logged in
             ->columns(['title', 'starts_at', 'location']);

        return $crud->render();
    }

    /**
     * Entry link to the agenda. Only the path is signed ($lockQuery = false):
     * the visitor can search and change pages, but cannot extend the link.
     */
    public static function link(): string
    {
        return Crud::signUrl('shared/agenda', self::TTL, false);
    }
}
```

```php
// app/Controllers/Admin/Events.php: where the link is created (a button in the admin area)
$crud->addHeaderAction('Share agenda', \App\Controllers\Shared::link(), [
    'icon'   => 'bi bi-share',
    'target' => '_blank',
]);
```

Every time the admin page is shown a new link is created, valid for 7 days from that moment. While the visitor browses (pages, sorting, search, detail views), the CRUD signs the links by itself, with the same expiry.

**The visitor can** view, search, sort and open the detail view of published upcoming events for 7 days; **they cannot** edit anything, see other events (not even by changing the search in the URL), extend the link or open any other page of the application.

### Share a single record for a few days

A link to an invoice's detail view, valid for 3 days, to send to the client. Whoever receives it sees that invoice and nothing else.

```php
// app/Config/Routes.php
Crud::routes($routes, 'admin/invoices', 'Admin\Invoices::index', ['filter' => 'auth:admin']);
Crud::routes($routes, 'shared/invoices', 'SharedInvoices::index');   // no login
```

```php
// app/Controllers/SharedInvoices.php
public function index()
{
    $crud = new Crud();
    $crud->setTable('invoices')
         ->where('status', 'sent')        // only sent invoices can be shared
         ->maskIds()
         ->readOnly()
         ->unsetExport()
         ->requireSignature()             // the list too: only a signed list link would open it
         ->setLayout('default')
         ->showFields(['number', 'date', 'client_id', 'total']);

    return $crud->render();
}
```

```php
// app/Controllers/Admin/Invoices.php: an action on each row of the admin area
$crud->addAction('Link for the client (3 days)', fn (array $row) =>
    Crud::signUrl('shared/invoices/show/' . Crud::maskId($row['id'], 'invoices'), 3 * 86400, false),
    ['icon' => 'bi bi-share', 'target' => '_blank']
);
```

The link must carry the masked code with the same context as the public CRUD (the table name). Do not use the admin CRUD's `$crud->signedUrl()`: it would sign an admin URL. Since the link is a detail view's, the CRUD signs no other link: there is no back-to-list button, and if the invoice is deleted or no longer matches the `where()`, the visitor sees a 404 with "Record not found.".

**The visitor can** view that invoice for 3 days; **they cannot** reach the list (`shared/invoices` without a signature is a 404), open any other invoice or extend the link.

### Internal list that hands out signed links

Staff see the orders list with login, and each detail view in the list has a 7-day signed link they can copy and send to someone without an account.

```php
// app/Config/Routes.php: no filter on the route. A login filter would also block
// the shared detail views, because they have the same prefix: the controller decides.
Crud::routes($routes, 'orders', 'Orders::index');
```

```php
// app/Controllers/Orders.php
public function index()
{
    $loggedIn = session('user_id') !== null;

    // No login and no signature: go and log in
    if (! $loggedIn && $this->request->getGet('kp_sig') === null) {
        return redirect()->to('login');
    }

    $crud = new Crud();
    $crud->setTable('orders')
         ->maskIds()
         ->readOnly();

    if ($loggedIn) {
        // The list opens without a signature and gives 7-day links to the detail views
        $crud->requireSignature(['show'], 7 * 86400, ['list']);
    } else {
        // Without login, everything needs a signature: only the detail views handed out by the list open
        $crud->requireSignature();
    }

    return $crud->render();
}
```

**Why the rest must be protected.** With `requireSignature(['show'], ...)`, only the detail view needs a signature: the list and any other operation you have not removed would be open to everyone who reaches the route. That is why, without login, the controller redirects to the login page or requires a signature for everything (`requireSignature()`). If the recipients also have accounts, a login filter on the route and `requireSignature(['show'], 7 * 86400, ['list'])` are enough.

**Whoever receives the link can** view that order for 7 days without login; **they cannot** see the list (it takes them to the login page), any other order, or extend the link.

### Signed download or custom page

A download link for a report, valid for 1 hour, to a custom route (not a CRUD). Here the signature covers the path **and** the parameters (`$lockQuery = true`, the default).

```php
// app/Config/Routes.php: no login, and a prefix different from any CRUD
$routes->get('reports/(:segment)/pdf', 'Reports::pdf/$1');
```

```php
// app/Controllers/Admin/Reports.php: where the link is created (an action of the admin CRUD)
$crud->maskIds()
     ->addAction('Download link (1 h)', fn (array $row) =>
         Crud::signUrl('reports/' . Crud::maskId($row['id'], 'reports') . '/pdf'),   // 3600 s by default
         ['icon' => 'bi bi-file-earmark-pdf']
     );
```

```php
// app/Controllers/Reports.php
use CodeIgniter\Exceptions\PageNotFoundException;
use Kpcrudy\Crud;
use Kpcrudy\UrlSigner;

public function pdf(string $code)
{
    $status = Crud::checkSignature();

    if ($status === UrlSigner::EXPIRED) {
        // The signature is good but the hour has passed: you can say so clearly
        return $this->response->setStatusCode(410)->setBody(lang('Kpcrudy.linkExpired') . ' Ask for a new link.');
    }

    if ($status !== UrlSigner::VALID) {   // 'missing' or 'invalid': give no hints
        throw PageNotFoundException::forPageNotFound(lang('Kpcrudy.linkInvalid'));
    }

    $id     = Crud::unmaskId($code, 'reports') ?? throw PageNotFoundException::forPageNotFound();
    $report = model('ReportModel')->find($id) ?? throw PageNotFoundException::forPageNotFound();

    return $this->response->download(WRITEPATH . 'reports/' . $report['file'], null);
}
```

If the link was made with `$lockQuery = false`, check it with `Crud::checkSignature(allowFreeQuery: true)`. `checkSignature()` only accepts links made with `Crud::signUrl()` or `signedUrl()`: the ones a CRUD signs while browsing do not open custom pages.

**Whoever receives the link can** download that report for 1 hour; **they cannot** change the code, add or remove parameters, or use it for another report.

### Signed links with masked ids

They can be used separately, but for shared links combining them is recommended:

| | Signature only | Signature + `maskIds()` |
|---|---|---|
| URL | `shared/invoices/show/123?kp_exp=…&kp_sig=…` | `shared/invoices/show/Xk3f9QaLm2PwE7?kp_exp=…&kp_sig=…` |
| Can another record be opened by changing the URL? | No | No |
| Is the real id visible (how many invoices there are, the order)? | Yes | No |

```php
// Public CRUD
$crud->setTable('invoices')->maskIds()->readOnly()->requireSignature();

// With signedUrl(), the real id is masked automatically
$link = $crud->signedUrl('show', 123, 3 * 86400);

// With Crud::signUrl(), mask it yourself, with the same context as the CRUD (the table name)
$link = Crud::signUrl('shared/invoices/show/' . Crud::maskId(123, 'invoices'), 3 * 86400, false);
```

Both use the same [key](functions_EN.md#context-and-key): if it changes, the codes and the signatures stop working at the same time.

### Find out why a signed link does not work

When someone says "the link does not work", ask them for the whole link and check it with `kpcrudy:checkurl` (in quotes, because it contains `&`):

```bash
php spark kpcrudy:checkurl "https://example.com/shared/invoices/show/Xk3f9QaLm2PwE7?kp_exp=1767225600&kp_sig=Qm9..."
```

```text
Valid signature (path only: the parameters can change)
Expires: 2026-01-01 00:00:00
```

| Result | What it means | What to do |
|---|---|---|
| `Valid signature` | The link is fine | The problem is in the data: the record was deleted or no longer matches the `where()`, or the operation was removed |
| `Expired` | The signature is good, but `kp_exp` has passed | Create a new one |
| `Invalid signature` | It was modified or cut, or signed with another key (another environment or another configuration) | Copy it again in full; pass `--config` if the CRUD uses its own configuration |
| `Not signed` | It has no `kp_sig` (the link was cut) | Copy it again in full |

The command also says who signed the link: `signed with signUrl() or signedUrl()` (an entry link) or `signed by a CRUD while browsing` (copied from a page of the signed list, such as page 2 or a detail view). The latter are only accepted by that CRUD and expire together with the entry link.

To find out which record it is, decode the code in the URL; and to send a new link without going through the application, create it with `kpcrudy:signurl` (`--ttl` in seconds; `--free-query` for CRUD pages):

```bash
php spark kpcrudy:unmaskid invoices Xk3f9QaLm2PwE7
php spark kpcrudy:signurl "shared/invoices/show/Xk3f9QaLm2PwE7" --ttl 259200 --free-query
```

All the command options are in [commands_EN.md](commands_EN.md).

---

## CSV import

### Import data from a CSV

Import is not enabled by default, because it changes a lot of data at once. It is enabled on each CRUD:

```php
$crud->setTable('products')
     ->enableImport();
```

The **Import CSV** button appears in the list. The user:

1. uploads the file and chooses what to do with each row (*always add* or *update if it already exists*) and with rows that have errors (*import nothing* or *skip them*);
2. sees the preview: mapped columns, new rows, rows to update and errors per line;
3. confirms the import.

A file like this one is valid, even if the columns are in a different order or there are extra ones (they are ignored):

```csv
Name;Category;Price;Active;Notes
Blue mug;Kitchen;12,50;Yes;from the old shop
Red mug;Kitchen;12.50;1;
```

Columns are recognised by the field name (`category_id`) or by the label (`Category`), and dropdowns and relations by the id or by the text (`Kitchen`). The **Download template** button on the import page generates a CSV with the correct header.

| 1. Upload the file | 2. Preview |
|---|---|
| ![Upload the CSV](../screenshots/15-importacio-pujada.png) | ![Preview](../screenshots/16-importacio-previsualitzacio.png) |

### Export, edit and re-import

With the default key (the primary key), an exported file has the `Id` column, and when you import it again with *update if it already exists* the corresponding records are modified:

1. **Export CSV** from the list (you can filter it first).
2. Edit the file in a spreadsheet: change prices, statuses, categories...
3. **Import CSV** → *Update the record if its "Id" already exists, add it otherwise* → preview → import.

The export's computed columns (virtual columns, read-only N-N relations) are ignored automatically. Rows without an `Id`, or with an `Id` that does not exist, are added as new records. The apostrophe the export puts before values that look like formulas (`'=...`) is removed on import. In an update only the edit form fields (`editFields()`) are changed.

So that the exported price can be read back, export it as a number:

```php
$crud->callbackColumn('price', fn ($value) => esc(number_format((float) $value, 2, ',', '.')) . ' €')   // list
     ->callbackExport('price', fn ($value) => number_format((float) $value, 2, '.', ''));             // CSV
```

### Update records by a unique field

If the file comes from another system and does not carry the database ids, use a unique field as the key:

```php
$crud->setTable('students')
     ->enableImport(['key' => 'email']);
```

With *update if it already exists*, each row is looked up by email: if it exists, the columns in the file are updated (the others are left untouched); if not, it is added. The lookup respects the CRUD's `where()`, so a record outside the scope is never updated.

If the key is a field that is read-only when editing (`readonlyFields`), it is used to find the record but is not modified. If the key value matches more than one record, the row has an error: the key must be unique.

### Import users with passwords

Fields of type `password` are stored with `password_hash()`, just like in the form:

```csv
Username;Password;Email;Role
nora.bell;Welcome2026;nora@example.com;User
alice;;alice@example.com;Admin
```

```php
$crud->setTable('users')
     ->setRules('password', 'required|min_length[8]')
     ->enableImport(['key' => 'username']);
```

- `nora.bell` is new: the password is mandatory (the `required` rule applies).
- `alice` already exists: with an empty password it is not changed, and the email and role are updated.

Never send files with real passwords by email. The safest approach is to import without passwords and let each user create one (see [Password reset button](#password-reset-button)).

### Import for some users only

Import has its own permission, `import`, which decides who sees the button and the page:

```php
// Only administrators can import
$crud->enableImport()
     ->setPermission('import', auth()->user()->inGroup('admin'));
```

In addition, each row checks the permission of the operation it will perform: `add` for new rows and `edit` (with the record) for updates. For example, so that editors can import but only update their own records:

```php
$isAdmin = auth()->user()->inGroup('admin');

$crud->enableImport()
     ->setPermission('add', $isAdmin)
     ->setPermission('edit', fn (?array $row) => $isAdmin || $row === null || $row['owner_id'] == session('user_id'));
```

An editor can upload a file, but new rows and rows for other people's records show up as errors in the preview ("You are not allowed to add or change this record.").

### Values in special formats

The automatic conversion understands the usual formats: `Yes/No` and `1/0`, `YYYY-MM-DD` dates or dates in the display format, decimals with a comma or a dot... For any other format, transform the value with `callbackImport()`:

```php
// Dates in US format
$crud->callbackImport('birthdate', function (string $value) {
    $date = DateTime::createFromFormat('!m/d/Y', $value);

    return $date ? $date->format('Y-m-d') : $value;
});

// A country code from the name
$countries = ['Spain' => 'ES', 'France' => 'FR', 'Portugal' => 'PT'];
$crud->callbackImport('country', fn (string $value) => $countries[$value] ?? strtoupper($value));
```

The value returned by the callback is validated with the field's rules, so an incorrect value still shows up as an error in the preview. For relation, `enum`, `select` and N-N fields, the returned value must be a valid option. A text that matches more than one option is an error: use the id. Numbers only accept digits, separators, the minus sign, spaces and `€`, `$` or `£`; with letters (`12 EUR`), transform them with a callback.

Fields with a fixed value (`fieldType($field, 'hidden', ['value' => ...])`) and those of the simple equality conditions of `where()` are always stored with that value, even if the file has the column. So, in a CRUD limited to one company, all imported rows belong to that company:

```php
$crud->where('company_id', $companyId)
     ->enableImport();
```

---

## Data and security

### Limit the CRUD to the logged-in user

`where()` limits what can be seen and what can be touched. With a simple equality condition (`field => value`), the value is also assigned automatically to new and edited records:

```php
$userId = session('user_id');

$crud->setTable('notes')
     ->where('user_id', $userId);
```

The user cannot see, edit, delete or export other users' notes, even by changing the id in the URL or tampering with the bulk actions form.

The `user_id` field does not appear in the form and is always saved with the `where()` value, on create, on edit and in the CSV import, even if it is not in `fields()` and even if someone adds `user_id` to the form. If after saving the record were outside the scope, the change is rolled back.

The hidden field with a fixed value that used to be needed still works, but is no longer necessary:

```php
$crud->where('user_id', $userId)
     ->fieldType('user_id', 'hidden', ['value' => $userId]);   // optional
```

It is still needed (or a hook or `setDefault()`) if the condition has an operator (`where('user_id >', 0)`) or is an SQL string, because then there is no value to assign.

The same works for applications with several companies or schools:

```php
$crud->where('school_id', $schoolId);
```

### Store who created the record

With a hidden field, the value is written on create **and** on edit. If it should only be written on creation, use a hook:

```php
$crud->beforeInsert(function (array $data) {
    $data['created_by'] = session('user_id');

    return $data;
});

// And who modified it last
$crud->beforeUpdate(function (array $data) {
    $data['updated_by'] = session('user_id');

    return $data;
});
```

To show it with the name, add a relation and put it in the detail view:

```php
$crud->setRelation('created_by', 'users', '{first_name} {last_name}')
     ->showFields(['title', 'body', 'created_by', 'created_at']);
```

### Permissions by role and by record

A basic recipe; [functions_EN.md](functions_EN.md#permissions) has more examples and the table of what each operation controls.

```php
$user    = auth()->user();
$isAdmin = $user->inGroup('admin');

// By role: only administrators create, delete and see the trash
$crud->setPermission('add', $isAdmin)
     ->setPermission('delete', $isAdmin)
     ->setPermission('trash', $isAdmin);

// By record: editors only edit their own records
$crud->setPermission('edit', fn (?array $row) =>
    $isAdmin || $row === null || $row['author_id'] == $user->id
);
```

Remember: the closure receives `null` when the decision is not about a specific record (the "Add" button, bulk actions). If it returns `false` with `null`, the option disappears.

The `edit` closure is evaluated again when saving, with the record as it would end up: an editor cannot change the `author_id` of one of their articles to someone else's (the save is refused).

### Central permissions for all CRUDs

Instead of repeating `setPermission()` in every controller, a class that implements `Kpcrudy\PermissionResolver` decides for every CRUD. kpcrudy asks it before each operation, button, action and bulk action, with the CRUD's name (`setResource()`; by default the table), the operation and the record (`null` when the check is general, such as the "Add" button):

```php
// app/Libraries/CrudPermissions.php
namespace App\Libraries;

use Kpcrudy\Crud;
use Kpcrudy\PermissionResolver;

class CrudPermissions implements PermissionResolver
{
    public function allows(string $resource, string $operation, ?array $row, Crud $crud): ?bool
    {
        $user = session('user');   // however your project keeps the logged-in user

        if ($user === null) {
            return false;
        }

        if ($user['role'] === 'admin') {
            return null;            // no opinion: setPermission() in each CRUD still applies
        }

        // Editors: read everything, change only their own records
        if (in_array($operation, ['list', 'show', 'search', 'filters', 'export'], true)) {
            return true;
        }

        return $user['role'] === 'editor'
            && in_array($operation, ['add', 'edit', 'duplicate'], true)
            && ($row === null || (int) $row['created_by'] === (int) $user['id']);
    }
}
```

```php
// app/Config/Kpcrudy.php
public ?string $permissionResolver = \App\Libraries\CrudPermissions::class;

// In each controller: the name the resolver receives
$crud->setTable('blog_posts')->setResource('posts');
```

The answer is `true` (allow), `false` (deny) or `null` (no opinion). The resolver has the last word: if it denies, `setPermission()` cannot allow again; if it allows or returns `null`, the CRUD's own `setPermission()` rules can still restrict. With `public bool $denyByDefault = true;` everything that nobody allows is denied. One CRUD can use another resolver with `setPermissionResolver()` (an instance or a closure with the same signature).

**With CodeIgniter Shield** (optional: kpcrudy does not need it), the resolver can map each operation to a Shield permission such as `posts.edit`:

```php
public function allows(string $resource, string $operation, ?array $row, Crud $crud): ?bool
{
    $user = auth()->user();

    return $user !== null && $user->can("{$resource}.{$operation}");
}
```

**Actions with a permission.** Actions, bulk actions and custom operations are checked with their `'permission'` option (for operations, the key by default). The button only appears if the user has it, on that record:

```php
$crud->addAction('Attendees', 'admin/attendances?f[event_id]={id}', ['permission' => 'attendees'])
     ->addBulkAction('publish', 'Publish', $publish, ['permission' => 'publish']);
```

An action that links to a route of your own only hides the button: that route must check the permission too (`$crud->isAllowed('attendees', $row)` gives the same answer). With [`addOperation()`](#custom-operations-on-the-crud-route) the CRUD checks it.

### Cross-field validation

CodeIgniter rules validate one field at a time. To compare fields with each other, use a hook that returns the error message:

```php
$checkDates = function (array $data) {
    if (! empty($data['ends_at']) && $data['ends_at'] < $data['starts_at']) {
        return 'The end date must be later than the start date.';
    }

    return null;
};

$crud->beforeInsert($checkDates)
     ->beforeUpdate($checkDates);
```

The form is shown again with the entered data and the message at the top.

### Prevent deletion when there are related records

If there is a foreign key, the database already prevents it, but with a technical message. With `beforeDelete` the user gets an explanation:

```php
$crud->beforeDelete(function (string $id) {
    $total = db_connect()->table('products')->where('category_id', $id)->countAllResults();

    return $total > 0 ? "It cannot be deleted: there are {$total} products in this category." : null;
});
```

If the table has a trash and you only want to prevent **permanent** deletion, use `$purge`:

```php
$crud->beforeDelete(fn (string $id, array $row, bool $purge) =>
    $purge && $row['invoiced'] ? 'An invoiced client cannot be permanently deleted.' : null
);
```

### Run code after every change

`afterWrite()` runs after any write the CRUD makes: create, edit, delete (to the trash or permanent) and restore, also from bulk actions and the CSV import. It is the place to clear a cache or write an audit log, without repeating it in each `after*` hook:

```php
$crud->afterWrite(function (string $operation, string $id, array $data, ?array $old) {
    cache()->delete('menu_categories');

    db_connect()->table('audit_log')->insert([
        'table'     => 'categories',
        'record_id' => $id,
        'operation' => $operation,                  // insert, update, delete or restore
        'before'    => $old === null ? null : json_encode($old),
        'after'     => $data === [] ? null : json_encode($data),
        'user_id'   => session('user_id'),
        'at'        => date('Y-m-d H:i:s'),
    ]);
});
```

`$data` holds the saved values (empty on `delete` and `restore`) and `$old` the record before the change (`null` on `insert`). It runs after the specific hooks (`afterInsert`, `afterUpdate`...).

### Several CRUDs on the same table

Each controller method can configure the same table differently. For example, the administration area and a public events calendar:

```php
// Admin\Events::index — full management
$crud->setTable('events')
     ->fields(['title', 'category_id', 'starts_at', 'status', 'poster']);

// Agenda::index — read-only, published and upcoming only
$crud->setTable('events')
     ->where('status', 'published')
     ->where('starts_at >=', date('Y-m-d H:i:s'))
     ->readOnly()
     ->unsetTrash()
     ->unsetBulk();
```

Each CRUD has its own route and remembers its list state (page, filters...) separately.

### A user profile: single-record CRUD

The logged-in user edits their own data and nothing else:

```php
public function profile()
{
    $userId = session('user_id');

    // The profile detail view: profile/show/<id>, or the code if ids are masked
    $showUrl = site_url('profile/show/' . Crud::idForUrl($userId, 'users'));

    // There is no list: the page is the detail view
    if (uri_string() === 'profile') {
        return redirect()->to($showUrl);
    }

    $crud = new Crud();
    $crud->setTable('users')
         ->setSubject('profile', 'My profile')
         ->where('id', $userId)
         ->setBackUrl($showUrl)
         ->unsetAdd()->unsetDelete()->unsetTrash()->unsetExport()->unsetBulk()
         ->editFields(['first_name', 'last_name', 'email', 'avatar', 'password'])
         ->showFields(['avatar', 'username', 'first_name', 'last_name', 'email']);

    return $crud->render();
}
```

`where('id', ...)` makes another user's `profile/edit/7` fail, and `setBackUrl()` makes it return to the detail view after saving instead of to a list.

![Profile in the demo](../screenshots/14-perfil.png)

---

## Relations

### 1-N relation with filtered options

```php
// Only active sales reps, sorted by last name
$crud->setRelation('seller_id', 'users', '{last_name}, {first_name}', [
    'where' => ['role' => 'seller', 'active' => 1, 'deleted_at' => null],
    'order' => 'last_name',
]);
```

The `where` also limits the values accepted when saving: the id of a user who is not a sales rep cannot be submitted.

### N-N relation

```sql
CREATE TABLE post_tag (post_id INT, tag_id INT, PRIMARY KEY (post_id, tag_id));
```

```php
$crud->setTable('posts')
     ->setRelationNN('tags', 'post_tag', 'tags', 'post_id', 'tag_id', 'name')
     ->columns(['title', 'tags'])
     ->fields(['title', 'body', 'tags'])
     ->filters(['tags'])
     ->setDefault('tags', [1]);   // tag preselected on create
```

### Junction table with its own data

If the junction table stores more than the two keys (status, date, note...), do not use it as an editable N-N field: saving the form would rewrite the rows and that data would be lost. Give it its own primary key and build a CRUD for it:

```php
// attendances: id, user_id, event_id, status, registered_at, notes
$crud->setTable('attendances')
     ->setRelation('user_id', 'users', '{first_name} {last_name}')
     ->setRelation('event_id', 'events', 'title')
     ->editFields(['user_id', 'event_id', 'status', 'notes'])
     ->readonlyFields(['user_id', 'event_id']);
```

And in the events CRUD, show it as a **read-only** N-N (in the columns or the detail view, not in `fields()`):

```php
$crud->setRelationNN('attendees', 'attendances', 'users', 'event_id', 'user_id', '{first_name} {last_name}')
     ->showFields(['title', 'starts_at', 'attendees']);
```

![CRUD of the attendance table in the demo](../screenshots/09-assistencia.png)

### CRUD of a record's children

A client's notes, at `clients/5/notes` or, with [masked ids](#link-with-a-masked-id), at `clients/DxbBosxVUi2fWpPq3w/notes`. `Crud::routes()` accepts routes with parameters. With `(:segment)` (not `(:num)`, which does not accept the code's letters, `-` and `_`) the same route works in both cases:

```php
// Routes.php
\Kpcrudy\Crud::routes($routes, 'clients/(:segment)/notes', 'Clients::notes/$1');
```

```php
use CodeIgniter\Exceptions\PageNotFoundException;
use Kpcrudy\Crud;

// Controller: the first parameter is the client id as it appears in the URL
public function notes(string $clientCode)
{
    // The real id: decodes the code if ids are masked (a forged code gives null).
    // The context is the clients CRUD's one: by default, the table name
    $clientId = Crud::idFromUrl($clientCode, 'clients') ?? throw PageNotFoundException::forPageNotFound();
    $client   = model('ClientModel')->find($clientId) ?? throw PageNotFoundException::forPageNotFound();

    $crud = new Crud();
    $crud->setTable('client_notes')
         ->setSubject('note', 'Notes for ' . $client['name'])
         ->where('client_id', $clientId)   // with the real id; also makes new notes belong to this client
         ->columns(['created_at', 'text'])
         ->addHeaderAction('Back to client', 'admin/clients/show/' . Crud::idForUrl($clientId, 'clients'), ['icon' => 'bi bi-arrow-left']);

    return $crud->render();
}
```

And from the clients CRUD, with `{kp_id}` (the id as it appears in the CRUD's URLs; `{id}` is always the real one):

```php
$crud->addAction('Notes', 'clients/{kp_id}/notes', ['icon' => 'bi bi-journal-text']);
```

`where('client_id', $clientId)` limits the notes to the client and also hides the `client_id` field from the form and always saves it with this value: no hidden field is needed. Changing the id in the URL only gives access to another client's notes if the user is allowed to see them; if that is not the case, check it in the controller (e.g. that the client belongs to the logged-in user).

If ids are masked only with `maskIds()` in the clients CRUD (not in the configuration), pass `true` as the third parameter to `idFromUrl()` and `idForUrl()`. Without masking, `idFromUrl()` returns the URL value as is: if you only want to accept numbers, check it in the controller.

> **Route order.** If the clients CRUD also lives under `clients` (`Crud::routes($routes, 'clients', ...)`), define the children route first: `clients/(:any)` would capture `clients/<code>/notes`. In development, `Crud::checkRoutes()` detects it and explains it with an exception.

### Translated fields

Texts in several languages go in a child table, one row per language:

```sql
CREATE TABLE news_translations (
    id      INT AUTO_INCREMENT PRIMARY KEY,
    news_id INT NOT NULL,
    locale  VARCHAR(5) NOT NULL,
    title   VARCHAR(150) NOT NULL,
    body    TEXT NULL,
    UNIQUE KEY (news_id, locale),
    FOREIGN KEY (news_id) REFERENCES news (id) ON DELETE CASCADE
);
```

```php
$crud->setTable('news')
     ->setTranslations('news_translations', 'news_id', 'locale', ['title', 'body'], [
         'en' => 'English',
         'ca' => 'Català',
     ], [
         'required' => ['en'],                              // by default, the first language
         'types'    => ['body' => 'textarea'],
         'rules'    => ['title' => 'required|max_length[150]'],
     ])
     ->columns(['title', 'published_at'])
     ->fields(['title', 'body', 'published_at'])
     ->searchFields(['title', 'body']);
```

The translated fields are used like any other in `columns()`, `fields()`, `showFields()`, `searchFields()` or `displayAs()`. The form shows them in one tab per language; the `required` rule only applies to the required languages, and an optional language left empty stores no row. The list, the search and the export use the request's language if it is one of them (otherwise the first one; `'listLocale'` forces one). When a record is permanently deleted, its translations are deleted too. In a `beforeInsert` or `beforeUpdate` hook, `$crud->postedTranslations()` gives what was sent (`[locale => [field => value]]`).

---

## Presentation

### Dropdowns with labels

```php
// Values stored in the DB => text the user sees
$crud->fieldType('status', 'select', ['options' => [
    'draft'     => 'Draft',
    'published' => 'Published',
    'archived'  => 'Archived',
]]);
```

For MySQL `ENUM` columns, if you do not specify `options`, the dropdown is built automatically from the column's values. The label is used in the form, the list, the filters and the export, and incoming values are validated against the list.

### Several values in one column

When a record can have several values from a short, fixed list and you do not need a junction table (for that, see [N-N relation](#n-n-relation)), a `multiselect` field stores them in a single column:

```php
$crud->fieldType('audience', 'multiselect', [
    'options' => ['kids' => 'Kids', 'adults' => 'Adults', 'seniors' => 'Seniors'],
    'storage' => 'json',     // ["kids","adults"] (default); 'csv' stores kids,adults
]);
```

The column must be a text type (`VARCHAR`, `TEXT` or `JSON`). The form shows a multiple selection, the list and the detail view show the labels, and incoming values are validated against `options`. As a filter (`filters(['audience'])`), it finds the records that contain the chosen value.

### Formats: currency, badges and text

```php
// Currency in the list and the detail view; "plain" number in the CSV
$crud->callbackColumn('price', fn ($value) => esc(number_format((float) $value, 2, ',', '.')) . ' €')
     ->callbackExport('price', fn ($value) => number_format((float) $value, 2, ',', ''));

// Coloured badge depending on the status
$colors = ['draft' => 'secondary', 'published' => 'success', 'archived' => 'dark'];
$crud->callbackColumn('status', fn ($value) =>
    '<span class="badge text-bg-' . $colors[$value] . '">' . esc(ucfirst($value)) . '</span>'
);

// Virtual column: full name
$crud->columns(['full_name', 'email'])
     ->callbackColumn('full_name', fn ($value, array $row) => esc($row['first_name'] . ' ' . $row['last_name']))
     ->displayAs('full_name', 'Name');
```

`callbackColumn` returns HTML: **always escape** the data with `esc()`. Columns with `callbackColumn` are not searched unless you add them to `searchFields()`: if you want to search by `status`, add it there.

Date formats are in the configuration (`$dateFormat` and `$datetimeFormat`), and the length of texts in the list in `$listTruncate`.

### Computed columns without one query per row

A query inside `callbackColumn` runs for every row (and for every row of the export). With few rows it does not matter, but it is better to compute everything once before configuring the CRUD:

```php
$totals = array_column(
    db_connect()->table('orders')->select('client_id, COUNT(*) AS total')->groupBy('client_id')->get()->getResultArray(),
    'total',
    'client_id',
);

$crud->columns(['name', 'orders'])
     ->callbackColumn('orders', fn ($value, array $row) => (string) ($totals[$row['id']] ?? 0));
```

### Upload files and images

```php
// Image: thumbnail in the list, preview in the form
$crud->fieldType('photo', 'image', [
    'path'    => FCPATH . 'uploads/products',
    'url'     => base_url('uploads/products'),
    'maxSize' => 1024,   // KB
]);

// PDF document in a folder per client
$crud->fieldType('contract', 'upload', [
    'allowed' => ['pdf'],
    'subdir'  => 'client-{client_id}',
]);

// Images in folders by year and month
$crud->fieldType('poster', 'image', [
    'subdir' => fn (array $data) => date('Y/m'),
]);
```

How it works:

- The file is saved with a random name, and the database stores the relative path (`2026/11/1730000000_ab12cd.png`).
- When it is replaced or "Remove file" is ticked, the old one is deleted.
- When the record is permanently deleted, the file is deleted too (with `'deleteFiles' => false` it is kept).
- The file's actual type is checked, not just the extension in its name.

If the field is required (`requiredFields(['photo'])`), when editing there is no need to upload the file again if one already exists.

---

## Integration

### Integrate the CRUD into the project layout

```php
// app/Config/Kpcrudy.php: for all CRUDs
public string|false $layout = 'layouts/admin';

// Or for just one
$crud->setLayout('layouts/admin');
```

```php
<!-- app/Views/layouts/admin.php -->
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

The default theme requires Bootstrap 5 (CSS and JS) and Bootstrap Icons.

**Layout with sections.** If the project's layout uses `$this->renderSection()` (the pages do `$this->extend('layouts/admin')`), say which section the CRUD goes in. There is no need to change the layout:

```php
// app/Config/Kpcrudy.php
public string|false $layout     = 'layouts/admin';
public ?string $layoutSection   = 'content';

// Or for just one
$crud->setLayout('layouts/admin', 'content');
```

**The layout's own variables** (menu, user, breadcrumbs...) are passed with `setLayoutData()`; the layout also receives `$title`:

```php
$crud->setLayoutData([
    'menu'        => 'clients',
    'breadcrumbs' => ['Admin' => site_url('admin'), 'Clients' => null],
]);
```

**Title shown by the layout.** If the layout already shows the page title, hide the CRUD's `<h1>` with `showTitle(false)` (or `public bool $showTitle = false;` in the configuration). The layout gets the title in `$title` ("New: client", "Clients"...); after `render()`, `$crud->getTitle()` also gives it.

**The project's alerts.** If the layout already shows the session messages (`session('message')`, `session('error')`...), `flashKeys` sends the CRUD's messages there instead of its own alert box. Types not in the list keep showing inside the CRUD:

```php
// app/Config/Kpcrudy.php
public array $flashKeys = ['success' => 'message', 'danger' => 'error', 'warning' => 'warning'];
```

### Content-Security-Policy

kpcrudy's templates work with a strict Content-Security-Policy: the `<style>` and `<script>` blocks carry CodeIgniter's nonce (`csp_style_nonce()` and `csp_script_nonce()`), and there are no `onclick=""` handlers or `style=""` attributes (the behaviour goes through `data-kp-*` attributes). You only have to enable the CSP and allow the CDN the theme loads Bootstrap from:

```php
// app/Config/App.php
public bool $CSPEnabled = true;
```

```php
// app/Config/ContentSecurityPolicy.php (only the directives that change)
public $scriptSrc                 = ['self', 'https://cdn.jsdelivr.net'];
public array|string $scriptSrcElem = ['self', 'https://cdn.jsdelivr.net'];
public $styleSrc                  = ['self', 'https://cdn.jsdelivr.net'];
public array|string $styleSrcElem  = ['self', 'https://cdn.jsdelivr.net'];
public $fontSrc                   = ['self', 'https://cdn.jsdelivr.net'];   // Bootstrap Icons font
public $imageSrc                  = ['self', 'data:'];                     // SVG icons inside Bootstrap's CSS
```

With your own layout, the `<script>` and `<style>` blocks of the layout also need the nonce: `<script <?= csp_script_nonce() ?>>`. The HTML you add yourself (`callbackColumn()`, `callbackField()`, `page()`...) must follow the same rules. The demo has a complete example in `demo/app/Config/ContentSecurityPolicy.php`.

### Custom confirmation dialogs

Before asking with `window.confirm()`, kpcrudy sends the cancellable `kpcrudy:confirm` event to the document. If a listener cancels it, it can show its own dialog and call `e.detail.proceed()` if the user accepts. With SweetAlert2:

```html
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.14.5/dist/sweetalert2.all.min.js"></script>
<script <?= csp_script_nonce() ?>>
document.addEventListener('kpcrudy:confirm', function (e) {
    e.preventDefault();
    Swal.fire({ text: e.detail.message, icon: 'warning', showCancelButton: true })
        .then(function (result) { if (result.isConfirmed) { e.detail.proceed(); } });
});
</script>
```

`e.detail` has `message` (the text of the `confirm` option), `element` (the button or form) and `proceed()`. It applies to every confirmation: delete, actions and operations with `confirm`, and bulk actions.

### CRUD inside your own view

With `setLayout(false)`, `render()` returns only the CRUD's HTML. But it can also return a **response** (a redirect after saving, a CSV download), which must be returned as is:

```php
use CodeIgniter\HTTP\ResponseInterface;

public function dashboard()
{
    $crud = new Crud();
    $crud->setTable('tasks')
         ->where('done', 0)
         ->setLayout(false)
         ->setPerPage(5);

    $result = $crud->render();

    if ($result instanceof ResponseInterface) {
        return $result;
    }

    return view('dashboard', [
        'tasks' => $result,
        'stats' => model('StatsModel')->summary(),
    ]);
}
```

```php
<!-- app/Views/dashboard.php -->
<div class="row">
    <div class="col-md-4"><?= view('partials/stats', ['stats' => $stats]) ?></div>
    <div class="col-md-8"><?= $tasks ?></div>
</div>
```

### Change a template

A custom theme only needs to contain the templates that change; the rest are loaded from the `bootstrap5` theme.

The quickest way is the [kpcrudy:publish](commands_EN.md#kpcrudypublish) command, which copies the template and registers the theme in one step:

```bash
php spark kpcrudy:publish theme bootstrap5 --as compact --only show
```

Then modify `app/Views/kpcrudy/compact/show.php` and do step 3. By hand, the steps are these:

1. Copy the template, for example [src/Views/bootstrap5/show.php](../src/Views/bootstrap5/show.php), to `app/Views/kpcrudy/compact/show.php`, and modify it. The variables it receives are documented in the file header.
2. Register the theme:

```php
// app/Config/Kpcrudy.php
public array $themes = ['compact' => APPPATH . 'Views/kpcrudy/compact'];
```

3. Use it for all CRUDs (`public string $theme = 'compact';`) or for just one:

```php
$crud->setTheme('compact');
```

Inside the template, `$this->render('partials/button', [...])` reuses the partials of the base theme.

### Different configuration for one CRUD

The configuration is shared. To change it for one CRUD only, pass a modified copy to the constructor:

```php
$config = clone config('Kpcrudy');
$config->csvDelimiter = ',';
$config->csvBom       = false;
$config->listTruncate = 0;

$crud = new Crud($config);
```

To change it for every CRUD, `php spark kpcrudy:publish config` creates `app/Config/Kpcrudy.php` with every option and its default value: change only what you need (see [kpcrudy:publish](commands_EN.md#kpcrudypublish)). `config('Kpcrudy')`, and therefore the copy above, starts from those values.

### Change a text or add a language

The quickest way is `php spark kpcrudy:publish lang en` (see [kpcrudy:publish](commands_EN.md#kpcrudypublish)): it copies all the texts of the language to `app/Language/en/Kpcrudy.php`. Change the ones you want and delete the rest: the missing keys come from the library.

To change any of the library's texts, create a file in the project with only the keys you want to override:

```php
// app/Language/en/Kpcrudy.php
<?php

return [
    'add'       => 'New record',
    'noRecords' => 'Nothing here yet. Start by creating one!',
];
```

To add a language, copy [src/Language/en/Kpcrudy.php](../src/Language/en/Kpcrudy.php) to `app/Language/{code}/Kpcrudy.php` and translate its values, keeping the `{0}`, `{1}`... parameters. The language is the request's (`app.defaultLocale` or the one negotiated by CodeIgniter). kpcrudy includes English, Catalan, Spanish, French, Portuguese and German; for any other language (or a missing key), CodeIgniter first tries the language without the region (`pt-BR` → `pt`) and then English, so the CRUD never shows the bare keys.

Validation messages come from CodeIgniter. To have them translated, install `composer require codeigniter4/translations` (it includes Spanish, French, Portuguese, German and many others, but **not Catalan**). For Catalan, create `app/Language/ca/Validation.php` with the translations; you can start from the package's Spanish file.
