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
- [Actions that modify data](#actions-that-modify-data)
- [Show an action only on some records](#show-an-action-only-on-some-records)
- [Open the list already filtered or sorted](#open-the-list-already-filtered-or-sorted)
- [Custom bulk actions](#custom-bulk-actions)

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
- [Cross-field validation](#cross-field-validation)
- [Prevent deletion when there are related records](#prevent-deletion-when-there-are-related-records)
- [Several CRUDs on the same table](#several-cruds-on-the-same-table)
- [A user profile: single-record CRUD](#a-user-profile-single-record-crud)

**Relations**
- [1-N relation with filtered options](#1-n-relation-with-filtered-options)
- [N-N relation](#n-n-relation)
- [Junction table with its own data](#junction-table-with-its-own-data)
- [CRUD of a record's children](#crud-of-a-records-children)

**Presentation**
- [Dropdowns with labels](#dropdowns-with-labels)
- [Formats: currency, badges and text](#formats-currency-badges-and-text)
- [Computed columns without one query per row](#computed-columns-without-one-query-per-row)
- [Upload files and images](#upload-files-and-images)

**Integration**
- [Integrate the CRUD into the project layout](#integrate-the-crud-into-the-project-layout)
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

The generated code is a starting point. Review in particular the labels (they are generated from the field names), the list columns and the field displayed for each relation.

### Use an existing model

If you already have a model with validation, it is reused as is:

```php
$crud->setModel(\App\Models\ClientModel::class)
     ->setSubject('client', 'Clients');
```

If the model's rules use `{id}`, the primary key must have its own rule (CodeIgniter 4.5+):

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
$crud->addAction('Reset password', 'admin/users-reset-password/{id}', [
    'icon'    => 'bi bi-key',
    'method'  => 'post',
    'confirm' => 'A new password will be generated and sent by email. Continue?',
]);
```

```php
// Routes.php
$routes->post('admin/users-reset-password/(:num)', 'Admin\Users::resetPassword/$1');

// Controller
public function resetPassword(int $id)
{
    $user     = db_connect()->table('users')->where('id', $id)->get()->getRowArray();
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

`{id}` is the primary key, and `{field}` any field of the record. Relative URLs go through `site_url()`:

```php
$crud->addAction('Invoices', 'invoices/client/{id}', ['icon' => 'bi bi-receipt']);
$crud->addAction('Orders', 'orders?client={id}&year={year}', ['icon' => 'bi bi-cart']);
```

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

When the link leaves the CRUD (a public page, an email, an external system) you may not want the id to be visible or guessable. The URL is built with a closure, and the target page reverses the transformation. There are three options:

| Option | URL | Purpose |
|---|---|---|
| A. Encrypted | long, changes every time | The id cannot be known in any way |
| B. Obfuscated (Sqids) | short and fixed: `/invoice/Uk9pQ2` | No sequential number is visible |
| C. Signed | visible id + signature: `/invoice/123/9f2c...` | The id cannot be changed, and the link expires |

None of the three replaces the permission check on the target page.

**A. Encrypted with CodeIgniter's Encrypter.** A key is required: `php spark key:generate`.

```php
// app/Helpers/mask_helper.php
function mask_id(int|string $id): string
{
    $cipher = service('encrypter')->encrypt((string) $id);

    return rtrim(strtr(base64_encode($cipher), '+/', '-_'), '=');
}

function unmask_id(string $token): ?string
{
    try {
        $cipher = base64_decode(strtr($token, '-_', '+/'), true);

        return $cipher === false ? null : service('encrypter')->decrypt($cipher);
    } catch (\CodeIgniter\Encryption\Exceptions\EncryptionException) {
        return null;
    }
}
```

```php
// CRUD controller
helper('mask');

$crud->addAction('Link for the client', fn (array $row) => site_url('invoice/' . mask_id($row['id'])), [
    'icon'   => 'bi bi-link-45deg',
    'target' => '_blank',
]);
```

```php
// Routes.php
$routes->get('invoice/(:segment)', 'PublicInvoice::show/$1');

// Target controller
public function show(string $token)
{
    helper('mask');
    $id = unmask_id($token) ?? throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();

    $invoice = model('InvoiceModel')->find($id);
    // ...
}
```

**B. Obfuscated with Sqids.** It produces short URLs that are always the same for the same id. It is not encryption: with the alphabet it can be reversed, but neither the order nor the number of records is revealed.

```bash
composer require sqids/sqids
```

```php
$sqids = new \Sqids\Sqids(alphabet: 'k3G7QAe51FCsPW92uEOyq4Bg6Sp8YzVTmnU0liwDdHXLajZrfxNhobJIRcMvKt', minLength: 6);

$crud->addAction('Short link', fn (array $row) => site_url('i/' . $sqids->encode([(int) $row['id']])));

// Target
$id = $sqids->decode($code)[0] ?? null;
```

Store the alphabet in a shared place (a config) and shuffle it into your own order so the codes do not match those of other projects.

**C. Signed link with expiry.** The id is visible, but if someone changes it, the signature is no longer valid. It is useful for sending links by email or to an external system that shares the secret.

```php
// app/Helpers/signed_helper.php
function signed_url(string $path, int|string $id, int $ttl = 86400): string
{
    $expires = time() + $ttl;
    $sign    = hash_hmac('sha256', "{$path}|{$id}|{$expires}", config('Encryption')->key);

    return site_url("{$path}/{$id}/{$expires}/{$sign}");
}

function signed_ok(string $path, string $id, string $expires, string $sign): bool
{
    $expected = hash_hmac('sha256', "{$path}|{$id}|{$expires}", config('Encryption')->key);

    return (int) $expires >= time() && hash_equals($expected, $sign);
}
```

```php
// CRUD controller
helper('signed');
$crud->addAction('24 h link', fn (array $row) => signed_url('download', $row['id']));

// Routes.php
$routes->get('download/(:num)/(:num)/(:alphanum)', 'Downloads::get/$1/$2/$3');

// Target
public function get(string $id, string $expires, string $sign)
{
    helper('signed');

    if (! signed_ok('download', $id, $expires, $sign)) {
        throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
    }
    // ...
}
```

> These techniques are for links that **leave** the CRUD. The CRUD's internal URLs (`edit/5`, `show/5`) use the real id; those URLs are protected by permissions and `where()`.

### Actions that modify data

An action that changes data must not be a GET link: a search engine or a link preview could execute it. With `'method' => 'post'` the button submits a form with the CSRF token:

```php
$crud->addAction('Duplicate', 'admin/events-duplicate/{id}', [
    'icon'    => 'bi bi-copy',
    'method'  => 'post',
    'confirm' => 'Do you want to create a copy of this event?',
]);
```

```php
// Routes.php: a POST route that does not start like the CRUD ones
$routes->post('admin/events-duplicate/(:num)', 'Admin\Events::duplicate/$1');

// Controller
public function duplicate(int $id)
{
    // ... create the copy ...
    return redirect()->to(site_url('admin/events/edit/' . $newId))->with('message', 'Copy created.');
}
```

The route cannot be `admin/events/duplicate/5`, because `admin/events/(:any)` already belongs to the CRUD. Use another prefix, such as `admin/events-duplicate/5`.

### Show an action only on some records

```php
$crud->addAction('Pay', 'invoices/pay/{id}', [
    'icon'    => 'bi bi-credit-card',
    'class'   => 'btn-success',
    'method'  => 'post',
    'visible' => fn (array $row) => $row['status'] === 'pending',
]);
```

If the condition depends on other tables, compute it before the CRUD (see [Computed columns without one query per row](#computed-columns-without-one-query-per-row)):

```php
$registered = array_column(db_connect()->table('attendances')->select('event_id')->where('user_id', $userId)->get()->getResultArray(), 'event_id');

$crud->addAction('Sign me up', 'events-join/{id}', [
    'method'  => 'post',
    'visible' => fn (array $row) => ! in_array($row['id'], $registered),
]);
```

`visible` only hides the button. The target route must check again that the action is allowed.

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
// In the clients CRUD, a button to see their orders already filtered
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

    // There is no list: the page is the detail view
    if (uri_string() === 'profile') {
        return redirect()->to(site_url('profile/show/' . $userId));
    }

    $crud = new Crud();
    $crud->setTable('users')
         ->setSubject('profile', 'My profile')
         ->where('id', $userId)
         ->setBackUrl(site_url('profile/show/' . $userId))
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

A client's notes, at `clients/5/notes`. `Crud::routes()` accepts routes with parameters:

```php
// Routes.php
\Kpcrudy\Crud::routes($routes, 'clients/(:num)/notes', 'Clients::notes/$1');
```

```php
// Controller: the first parameter is the client id
public function notes(int $clientId)
{
    $client = model('ClientModel')->find($clientId) ?? throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();

    $crud = new Crud();
    $crud->setTable('client_notes')
         ->setSubject('note', 'Notes for ' . $client['name'])
         ->where('client_id', $clientId)
         ->fieldType('client_id', 'hidden', ['value' => $clientId])
         ->columns(['created_at', 'text'])
         ->addHeaderAction('Back to client', 'admin/clients/show/' . $clientId, ['icon' => 'bi bi-arrow-left']);

    return $crud->render();
}
```

And from the clients CRUD:

```php
$crud->addAction('Notes', 'clients/{id}/notes', ['icon' => 'bi bi-journal-text']);
```

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

### Change a text or add a language

To change any of the library's texts, create a file in the project with only the keys you want to override:

```php
// app/Language/en/Kpcrudy.php
<?php

return [
    'add'       => 'New record',
    'noRecords' => 'Nothing here yet. Start by creating one!',
];
```

To add a language, copy [src/Language/en/Kpcrudy.php](../src/Language/en/Kpcrudy.php) to `app/Language/{code}/Kpcrudy.php` and translate its values, keeping the `{0}`, `{1}`... parameters. The language is the request's (`app.defaultLocale` or the one negotiated by CodeIgniter).

Validation messages come from CodeIgniter. To have them translated, install `composer require codeigniter4/translations` (it includes Spanish, French, Portuguese, German and many others, but **not Catalan**). For Catalan, create `app/Language/ca/Validation.php` with the translations; you can start from the package's Spanish file.
