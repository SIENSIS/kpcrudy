# Function reference

**English** · [Català](functions_CA.md)

All the public functions and properties of **kpcrudy**, with an example of each one.

Almost all configuration methods return the same instance (`static`), so they can be chained:

```php
$crud = new \Kpcrudy\Crud();
$crud->setTable('clients')
     ->setSubject('client', 'Clients')
     ->columns(['name', 'email']);

return $crud->render();
```

For complete recipes for common cases, see [howto_EN.md](howto_EN.md).

## Index

- [Creating and running](#creating-and-running): `__construct`, `render`, `routes`
- [Data source](#data-source): `setTable`, `setModel`, `setDatabase`, `setPrimaryKey`, `setSubject`, `softDeletes`, `where`
- [Columns and fields](#columns-and-fields): `columns`, `fields`, `addFields`, `editFields`, `showFields`, `searchFields`, `filters`, `exportColumns`, `unsortable`, `displayAs`
- [Field types and validation](#field-types-and-validation): `fieldType`, `setRules`, `requiredFields`, `readonlyFields`, `setDefault`, `setHelp`
- [Relations](#relations): `setRelation`, `setRelationNN`
- [Display callbacks](#display-callbacks): `callbackColumn`, `callbackField`, `callbackExport`
- [Hooks](#hooks): `beforeInsert`, `afterInsert`, `beforeUpdate`, `afterUpdate`, `beforeDelete`, `afterDelete`, `afterRestore`
- [Actions](#actions): `addAction`, `addHeaderAction`, `addBulkAction`
- [CSV import](#csv-import): `enableImport`, `callbackImport`
- [Permissions](#permissions): `setPermission`, `unsetAdd`, `unsetEdit`, `unsetDelete`, `unsetShow`, `unsetExport`, `unsetTrash`, `unsetPurge`, `unsetSearch`, `unsetFilters`, `unsetBulk`, `readOnly`
- [Display and navigation](#display-and-navigation): `setPerPage`, `defaultOrder`, `setBaseUrl`, `setBackUrl`, `setTheme`, `setLayout`
- [Access from callbacks](#access-from-callbacks): `getModel`, `getTable`, `getPrimaryKey`, `getBaseUrl`, `url`
- [Masked ids](#masked-ids): `maskIds`, `encodeId`, `decodeId`, `maskId`, `unmaskId`, `idFromUrl`, `idForUrl`, `IdMasker`, `checkRoutes`
- [Signed URLs](#signed-urls): `requireSignature`, `signedUrl`, `signUrl`, `checkSignature`, `hasValidSignature`
- [Constants](#constants): `ID_OPERATIONS`, `OPERATIONS`
- [Spark commands](#spark-commands): `kpcrudy:make`, `kpcrudy:demo`, `kpcrudy:maskid`, `kpcrudy:unmaskid`, `kpcrudy:signurl`, `kpcrudy:checkurl`
- [Global configuration](#global-configuration): properties of `Config\Kpcrudy`
- [Templates](#templates): `Renderer::render`, `Renderer::renderParent`, `Renderer::locate`, `Renderer::hidden`, `Renderer::attrs`

---

## Creating and running

### `__construct(?KpcrudyConfig $config = null)`

Creates a CRUD. Without parameters, it uses the project configuration (`app/Config/Kpcrudy.php`, if it exists) or the library's. You can also pass a specific configuration, which must extend `Kpcrudy\Config\Kpcrudy`.

```php
use Kpcrudy\Crud;

$crud = new Crud();

// With a custom configuration for one area of the application
$crud = new Crud(new \Config\KpcrudyPublic());
```

### `render(): ResponseInterface|string`

Runs the operation that matches the current URL (list, form, detail view, delete, export...) and returns the result. It can be HTML or a response (a redirect or a download), so the controller must return it as is.

```php
public function clients()
{
    $crud = new Crud();
    $crud->setTable('clients');

    return $crud->render();
}
```

If you want to place the CRUD inside your own view, first check whether it is a response (see the recipe [CRUD inside your own view](howto_EN.md#crud-inside-your-own-view)).

### `Crud::routes(RouteCollection $routes, string $path, string $handler, array $options = []): void` (static)

Registers the two routes a CRUD needs, `path` and `path/(:any)`, for GET and POST. `$options` are CodeIgniter route options (`filter`, `as`...).

```php
// app/Config/Routes.php
use Kpcrudy\Crud;

Crud::routes($routes, 'admin/clients', 'Admin::clients');
Crud::routes($routes, 'admin/invoices', 'Admin\Invoices::index', ['filter' => 'auth:admin']);

// Inside a group, the group options are inherited
$routes->group('', ['filter' => 'auth'], static function ($routes) {
    Crud::routes($routes, 'my/orders', 'Orders::mine');
});

// The route can have parameters: the controller receives them first
Crud::routes($routes, 'clients/(:segment)/notes', 'Clients::notes/$1');   // notes(string $code): the real id with Crud::idFromUrl($code, 'clients')
```

The routes it registers carry the option `'kpcrudy' => true`, which lets [`Crud::checkRoutes()`](#crudcheckroutes-void-static) find them. In development, `render()` checks that no other route conflicts with the CRUD's: a route with the same prefix defined **after** `Crud::routes()` would never run (`prefix/(:any)` captures it first), and one defined **before** that captures CRUD URLs (`prefix/add`, `prefix/edit/1`...) would stop them from working. Your own pages (e.g. a public link with a [masked id](#masked-ids)) must use a different prefix.

These routes are **strict**: after the CRUD base only `operation` (`add`, `trash`, `export`...) or `operation/id` (`edit/5`, `show/Xk3f9QaLm2PwE7`) is accepted; anything else (`admin/clients/foo`, `admin/clients/edit/5/extra`) is a 404. This way each page has a single URL, which [signed URLs](#signed-urls) also rely on. With hand-written routes (without `Crud::routes()`), the operation is deduced from the last segments of the URL.

---

## Data source

### `setTable(string $table): static`

The CRUD works directly with the table. A dynamic model is created that detects:

- the **primary key** (`PRIMARY KEY` field, or `id` if none is found);
- the **timestamps** (`created_at` and `updated_at`, if they exist);
- **soft deletes** (`deleted_at`, if it exists), which enable the trash.

```php
$crud->setTable('clients');
```

### `setModel(Model|string $model): static`

The CRUD works with a CodeIgniter model. It uses its table, primary key, `allowedFields`, validation (rules and messages), timestamps, soft deletes and model callbacks.

```php
$crud->setModel(\App\Models\ClientModel::class);

// Or an already created instance
$crud->setModel(model('ClientModel'));
```

> If the model rules use `{id}` (for example `is_unique[clients.email,id,{id}]`), on CodeIgniter 4.5+ the primary key field must also have a rule: `'id' => 'permit_empty|is_natural_no_zero'`. Kpcrudy adds the identifier when editing: always the real id, even if the URL carries a [masked code](#masked-ids).

### `setDatabase(BaseConnection|string $db): static`

Database group or connection. It only applies in table mode; in model mode, the model decides.

```php
$crud->setTable('legacy_products')->setDatabase('legacy');
```

### `setPrimaryKey(string $primaryKey): static`

Forces the primary key when it cannot be detected (for example, in a view or in a table without a `PRIMARY KEY`).

An id in the URL or the form that cannot be a valid key value (e.g. `5abc` for an integer key) is treated as a record that does not exist.

```php
$crud->setTable('countries')->setPrimaryKey('iso_code');
```

### `setSubject(string $singular, ?string $plural = null): static`

Names of the record type for titles ("New: client", "Edit: client") and messages. The plural is the list title. If not set, the table name is used.

```php
$crud->setSubject('client', 'Clients');
```

### `softDeletes(bool $enabled = true, string $field = 'deleted_at'): static`

Enables or disables the trash in table mode. By default it is enabled automatically if the table has a `deleted_at` field. In model mode, the model's `useSoftDeletes` decides.

```php
// The table uses a different field name
$crud->setTable('posts')->softDeletes(true, 'removed_at');

// It has deleted_at, but you want permanent deletes
$crud->setTable('logs')->softDeletes(false);
```

### `where(array|string $field, mixed $value = null): static`

Limits the records the CRUD manages. The condition applies to **everything**: list, search, export, detail view, edit, delete and bulk actions. A user cannot access a record outside the scope even if they change its id in the URL.

It accepts the format of the Query Builder's `where()`, operators included. It can be called more than once.

```php
$crud->where('company_id', session('company_id'));
$crud->where('price >', 0);
$crud->where(['status' => 'published', 'deleted_by' => null]);
```

**Simple equality** conditions (`field => value` with a scalar or `null` value, no operator, optionally prefixed with the table name) are also enforced when **saving**:

- the field does not appear in the add and edit forms;
- it is always saved with the condition's value, both from the form and from the CSV import (even if the field is not in `fields()` and even if a `before*` hook sets another value);
- after saving, the record must still be inside the CRUD scope; if it is not (e.g. because of a condition with an operator), the change is rolled back and the error "You are not allowed to perform this operation" is shown.

In the examples above, `company_id`, `status` and `deleted_by` are always saved with the given value; `price >` assigns nothing, but a price that does not meet it cannot be saved.

> For conditions with an operator, you have to assign the value of new records yourself, with `fieldType('field', 'hidden', ['value' => ...])`, `setDefault()` or a hook (see the recipe [Limit the CRUD to the logged-in user](howto_EN.md#limit-the-crud-to-the-logged-in-user)).

---

## Columns and fields

If they are not configured, these are the default values:

| List | Default |
|---|---|
| List columns | All fields except `deleted_at` and sensitive fields |
| Form fields | All except the primary key, timestamps and sensitive fields (passwords are included), plus N-N relations |
| Detail view fields | All except `deleted_at` and sensitive fields, plus N-N relations |
| Search | Text, number, enum and 1-N relation columns, except those with `callbackColumn` |
| Filters | Relation, enum, select and boolean columns |
| Export | The list columns |

**Sensitive fields** are those whose name is in [`$sensitiveFields`](#global-configuration) (`password`, `token`, `secret`, `salt`, `hash`, `apikey`, `otp`...): the whole name, the name without `_` (`api_key`) or any part separated by `_` (`reset_token`, `password_hash`). By default they are hidden from the list, the detail view, search, sorting, filters, export and forms; `password`-type fields do appear in forms (they are saved hashed). If you need one of them, list it explicitly in `columns()`, `showFields()`, `fields()`, `searchFields()`, `exportColumns()`... In the import preview their values are masked.

### `columns(array $columns): static`

List columns, in order. You can include table fields, relations and **virtual columns** (names that do not exist in the table and are rendered with `callbackColumn()`).

```php
$crud->columns(['photo', 'name', 'email', 'city_id', 'total_orders']);
```

### `fields(array $fields): static`

Fields of the add **and** edit forms.

```php
$crud->fields(['name', 'email', 'city_id', 'notes']);
```

### `addFields(array $fields): static` · `editFields(array $fields): static`

Different fields for adding and for editing.

```php
$crud->addFields(['username', 'password', 'email'])
     ->editFields(['email', 'password', 'active']);
```

### `showFields(array $fields): static`

Fields of the detail view.

```php
$crud->showFields(['name', 'email', 'city_id', 'tags', 'notes', 'created_at']);
```

### `searchFields(array $fields): static`

Fields searched by the general search box. If you include a 1-N relation, the search uses the displayed text (e.g. the city name, not the id). The `%` and `_` characters in the search text are matched literally, not as wildcards.

By default, columns with `callbackColumn()` are not searched, because the callback may hide the real value (e.g. show `****`) and the search would reveal it. If you want them searched, list them here explicitly.

```php
$crud->searchFields(['name', 'email', 'city_id']);
```

### `filters(array $fields): static`

Fields with a filter in the filters panel. The filter type depends on the field type:

- **dropdown**: 1-N and N-N relations, enum, select and boolean;
- **range** (from / to): date, date and time, number and decimal;
- **text** (contains): the rest.

With an empty array there are no filters. Invalid filter values (a non-numeric range, a dropdown value that is not one of the options) are ignored.

```php
$crud->filters(['city_id', 'status', 'active', 'tags', 'created_at', 'price']);
```

### `exportColumns(array $columns): static`

CSV columns, if they must differ from the list columns.

The export requires the `export` and `list` permissions, reads the records in chunks and has a row limit ([`$exportMaxRows`](#global-configuration)): if the filtered list has more rows, the user goes back to the list with a warning asking to filter it. Values starting with `=`, `+`, `-` or `@` (also with leading spaces or line breaks, and their full-width variants) are neutralized with an apostrophe to prevent formula injection.

```php
$crud->exportColumns(['id', 'name', 'email', 'phone', 'city_id', 'created_at']);
```

### `unsortable(array $columns): static`

Columns that cannot be sorted by clicking the header. Virtual columns and N-N relations are already not sortable. Columns with `callbackColumn()` are sortable: if the order could reveal the value the callback hides, add them here.

```php
$crud->unsortable(['notes', 'photo']);
```

### `displayAs(array|string $field, ?string $label = null): static`

Label of a field or column. Without a label, the field name with its first letter capitalized is used, and for relations the `_id` suffix is removed (`city_id` → "City").

```php
$crud->displayAs('city_id', 'City');

$crud->displayAs([
    'name'    => 'Name',
    'email'   => 'Email',
    'city_id' => 'City',
]);
```

---

## Field types and validation

### `fieldType(string $field, string $type, array $options = []): static`

Forces the type of a field. If not forced, it is inferred from the database (integer, decimal, date, `TINYINT(1)`, `ENUM`, `TEXT`...) and from the name (`email`, `password`, `url`).

| Type | Form | List |
|---|---|---|
| `text` | text input | truncated text |
| `textarea` | text area | truncated text (full in the detail view) |
| `number` | integer number | number |
| `decimal` | decimal number | number |
| `email` | email input | `mailto:` link |
| `url` | url input | link (only if `http`/`https`; otherwise, plain text) |
| `password` | password (hashed) | not shown |
| `date` | date picker | `dateFormat` |
| `datetime` | date and time | `datetimeFormat` |
| `time` | time | time |
| `color` | color picker | color swatch (only `#rrggbb` colors) |
| `boolean` | switch | Yes / No |
| `enum` | dropdown | label |
| `select` | dropdown | label |
| `hidden` | hidden field | value |
| `readonly` | read-only | value |
| `upload` | file | link |
| `image` | image | thumbnail |

Options:

| Option | Type | Description |
|---|---|---|
| `options` | `enum`, `select` | `[value => label]`. For MySQL `ENUM`s, if not given, the column values are used |
| `value` | `hidden` | Value that is always saved, when adding and when editing. The field does not appear in the form, but it must be in the field list (`fields()` or the default one) |
| `hash` | `password` | `true` (default): saved with `password_hash()`. `false`: saved as is |
| `path` | `upload`, `image` | Directory where the files are saved. By default, `uploadPath/{table}` |
| `url` | `upload`, `image` | Public URL of that directory |
| `subdir` | `upload`, `image` | Subfolder for each new file: text with `{field}` or `fn (array $data, ?array $record): string` |
| `allowed` | `upload`, `image` | Allowed extensions (array or comma-separated text) |
| `maxSize` | `upload`, `image` | Maximum size in KB |
| `deleteFiles` | `upload`, `image` | `true` (default): deletes the file when the record is permanently deleted. Only files inside the field's directory (`path`) are deleted |
| `attrs` | all | HTML attributes of the input: `['placeholder' => ..., 'min' => 0]` |

```php
$crud->fieldType('notes', 'textarea', ['attrs' => ['rows' => 8]])
     ->fieldType('status', 'select', ['options' => ['draft' => 'Draft', 'published' => 'Published']])
     ->fieldType('company_id', 'hidden', ['value' => session('company_id')])
     ->fieldType('photo', 'image', ['maxSize' => 1024])
     ->fieldType('contract', 'upload', ['allowed' => ['pdf'], 'subdir' => '{client_id}'])
     ->fieldType('price', 'decimal', ['attrs' => ['min' => 0, 'step' => '0.01']])
     ->fieldType('code', 'readonly');
```

### `setRules(array|string $field, array|string|null $rules = null): static`

CodeIgniter validation rules. `{id}` is replaced with the real identifier of the record being edited (empty when adding), even if the URL carries a masked code. Rules can be passed as text separated by `|` or as an array; use the array if a rule contains `|`, such as `regex_match`.

If a field has its own rules, no automatic rules are inferred for it (see `$inferRules`).

```php
$crud->setRules('email', 'required|valid_email|is_unique[clients.email,id,{id}]');

$crud->setRules([
    'name'  => 'required|min_length[3]',
    'phone' => ['permit_empty', 'regex_match[/^[0-9 +]{9,15}$/]'],
]);
```

> When editing, the `required` rule of a `password` field is ignored: leaving it blank means not changing the password. When adding, if the column is `NOT NULL` without a default value, the password is required (inferred rule).

### `requiredFields(array $fields): static`

Adds the `required` rule to the given fields, in addition to the rules they already have.

```php
$crud->requiredFields(['city_id', 'phone']);
```

### `readonlyFields(array $fields): static`

Fields that are shown in the **edit** form but cannot be modified. When adding, they are editable. For a field that must never be editable, use `fieldType($field, 'readonly')`.

```php
$crud->readonlyFields(['username', 'created_by']);
```

### `setDefault(string $field, mixed $value): static`

Initial value of a field in the **add** form. The user can change it. If none is set, the column's default value in the database is used.

```php
$crud->setDefault('status', 'draft')
     ->setDefault('date', date('Y-m-d'))
     ->setDefault('tags', [1, 3]);   // N-N relation: selected ids
```

### `setHelp(string $field, string $text): static`

Help text below the field in the form.

```php
$crud->setHelp('capacity', 'Leave it blank if there is no limit on places.');
```

---

## Relations

### `setRelation(string $field, string $table, string $display, array $options = []): static`

**1-N** relation: the field `$field` is a foreign key to `$table`. The form shows a dropdown, and the list, detail view, search, sorting and CSV show the text from the related table.

`$display` can be a field (`'name'`) or a template with several fields (`'{last_name}, {first_name}'`).

| Option | Default | Description |
|---|---|---|
| `pk` | `'id'` | Primary key of the related table |
| `where` | `[]` | Conditions for the dropdown options |
| `order` | first field of `$display` | Order of the options |

The values coming from the form are validated against the allowed options (including the `where`).

```php
$crud->setRelation('city_id', 'cities', 'name');

$crud->setRelation('manager_id', 'users', '{last_name}, {first_name}', [
    'where' => ['role' => 'manager', 'deleted_at' => null],
    'order' => 'last_name',
]);
```

### `setRelationNN(string $name, string $junction, string $otherTable, string $thisKey, string $otherKey, string $display, array $options = []): static`

**N-N** relation through a junction table. `$name` is a virtual field you can put in `columns()`, `fields()`, `showFields()`, `filters()` and `exportColumns()`. The form shows a multiple-select dropdown; when saving, the junction table rows for the options the user could choose (respecting the relation's `where`) are rewritten. Links to records outside those options are kept.

| Parameter | Example | Description |
|---|---|---|
| `$name` | `'tags'` | Name of the virtual field |
| `$junction` | `'client_tag'` | Junction table |
| `$otherTable` | `'tags'` | Related table |
| `$thisKey` | `'client_id'` | Junction table field that points to this table |
| `$otherKey` | `'tag_id'` | Junction table field that points to the related table |
| `$display` | `'name'` | Field or template to display |
| `$options` | | `pk`, `where`, `order` (as in `setRelation`) |

```php
$crud->setRelationNN('tags', 'client_tag', 'tags', 'client_id', 'tag_id', 'name');
$crud->columns(['name', 'tags'])->filters(['tags']);
```

> When a record is permanently deleted, its rows in the junction table are deleted. If the junction table holds more data than the two keys (status, date...), do not put it in `fields()`: that data would be lost when the form is saved. Give it its own CRUD instead (see the recipe [Junction table with its own data](howto_EN.md#junction-table-with-its-own-data)).

---

## Display callbacks

### `callbackColumn(string $column, Closure $callback): static`

Customizes how a value is displayed in the **list** and the **detail view**: `fn (mixed $value, array $row): string`. It returns **HTML**, so you must escape the data with `esc()`. It is also used to create virtual columns.

A column with `callbackColumn` is not searched unless you add it to `searchFields()` (the callback may hide the real value). It is still sortable; if the order could reveal the value, add it to `unsortable()`.

```php
// Currency format
$crud->callbackColumn('price', fn ($value) => esc(number_format((float) $value, 2, ',', '.')) . ' €');

// Colored badge depending on the status
$crud->callbackColumn('status', fn ($value) => match ($value) {
    'paid'    => '<span class="badge text-bg-success">Paid</span>',
    'pending' => '<span class="badge text-bg-warning">Pending</span>',
    default   => esc($value),
});

// Virtual column that combines fields
$crud->columns(['full_name', 'email'])
     ->callbackColumn('full_name', fn ($value, array $row) => esc($row['first_name'] . ' ' . $row['last_name']));
```

### `callbackField(string $field, Closure $callback): static`

Replaces the input of a field in the form: `fn (mixed $value, ?array $row, array $field): string`. `$row` is the record being edited (`null` when adding) and `$field` contains the data the template would use (`label`, `required`, `error`, `options`...). The input must use `name="{field}"`.

```php
$crud->callbackField('rating', fn ($value) =>
    '<input type="range" class="form-range" min="1" max="5" name="rating" value="' . esc((string) ($value ?? 3), 'attr') . '">'
);
```

### `callbackExport(string $column, Closure $callback): static`

Value of a column in the CSV: `fn (mixed $value, array $row): string`. If there is none, `callbackColumn` is used without the HTML, or the text shown in the list.

```php
// "1.234,50 €" in the list, a number the spreadsheet understands in the CSV
$crud->callbackExport('price', fn ($value) => number_format((float) $value, 2, ',', ''));
```

---

## Hooks

Hooks run when saving or deleting. The `before*` hooks can stop the operation:

| Return | Effect |
|---|---|
| `null` | Continues without changes |
| `array` | Continues with this data (only `beforeInsert` and `beforeUpdate`) |
| `string` | Stops and shows the text as an error |
| `false` | Stops with the generic message "The operation was cancelled" |

The data they receive is what will be written to the database: the password already hashed, dates in `Y-m-d H:i:s` format, and without read-only fields or N-N relations.

### `beforeInsert(Closure $callback): static`

`fn (array $data): array|string|false|null`

```php
$crud->beforeInsert(function (array $data) {
    $data['created_by'] = session('user_id');
    $data['slug']       = url_title($data['title'], '-', true);

    return $data;
});
```

### `afterInsert(Closure $callback): static`

`fn (string $id, array $data): void`. Runs after saving, with the id of the new record.

```php
$crud->afterInsert(fn (string $id, array $data) => log_message('info', "Client {$id} created: {$data['name']}"));
```

### `beforeUpdate(Closure $callback): static`

`fn (array $data, string $id, array $old): array|string|false|null`. `$old` is the record as it was before saving.

```php
$crud->beforeUpdate(function (array $data, string $id, array $old) {
    if ($old['status'] === 'closed') {
        return 'A closed invoice cannot be modified.';
    }

    return null;
});
```

### `afterUpdate(Closure $callback): static`

`fn (string $id, array $data, array $old): void`

```php
$crud->afterUpdate(function (string $id, array $data, array $old) {
    if (($data['status'] ?? null) === 'shipped' && $old['status'] !== 'shipped') {
        service('email')->setTo($old['email'])->setSubject('Order shipped')->send();
    }
});
```

### `beforeDelete(Closure $callback): static`

`fn (string $id, array $row, bool $purge): string|false|null`. `$purge` is `true` if the deletion is permanent (or the table has no trash) and `false` if the record goes to the trash.

```php
$crud->beforeDelete(function (string $id, array $row, bool $purge) {
    $orders = db_connect()->table('orders')->where('client_id', $id)->countAllResults();

    return $orders > 0 ? "Cannot be deleted: it has {$orders} orders." : null;
});
```

### `afterDelete(Closure $callback): static`

`fn (string $id, array $row, bool $purge): void`

```php
$crud->afterDelete(function (string $id, array $row, bool $purge) {
    if ($purge) {
        cache()->delete("client_{$id}");
    }
});
```

### `afterRestore(Closure $callback): static`

`fn (string $id, array $row): void`. Runs when a record is restored from the trash.

```php
$crud->afterRestore(fn (string $id) => log_message('info', "Client {$id} restored"));
```

---

## Actions

### `addAction(string $label, Closure|string $url, array $options = []): static`

Adds a button to **each row** of the list and to the **detail view**. You can add as many as you need.

The URL can be:

- a **string** with `{id}` (primary key), `{kp_id}` (the id as it appears in the CRUD's URLs: [masked](#masked-ids) if `maskIds()` is enabled, otherwise the real id) and `{field}` (any field of the record), which are URL-encoded. If it is relative, it goes through `site_url()`;
- a **closure** `fn (array $row): string` that returns the full URL. It is only used if it is relative or starts with `http:`, `https:`, `mailto:` or `tel:`; otherwise (e.g. `javascript:`), the button points to `#`.

> **`{id}` and `{kp_id}` are always the primary key**, whatever the field is called (`id`, `code`, `vat_number`...): the one the library detects in the table or the one set with `setPrimaryKey()` (or in the model). If the key is called `code`:
>
> | Placeholder | Value |
> |---|---|
> | `{id}` | the real value of `code` |
> | `{code}` | the same: it is a field of the record |
> | `{kp_id}` | the value of `code` as it appears in the CRUD's URLs (masked if `maskIds()` is on) |
> | `{kp_code}` | **does not exist**: it is replaced by an empty string and the link is broken |
>
> The `kp_` prefix only exists for the key (`{kp_id}`). And if the table has a field called `id` that is not the key, `{id}` still gives the key: for that field, use a closure (`fn ($row) => '...' . $row['id']`).

| Option | Default | Description |
|---|---|---|
| `icon` | none | Icon class, e.g. `'bi bi-receipt'` ([Bootstrap Icons](https://icons.getbootstrap.com/)). With an icon, the list button shows only the icon |
| `class` | `'btn-outline-secondary'` | Button class |
| `target` | none | `'_blank'` to open in a new tab. The link gets `rel="noopener noreferrer"` |
| `confirm` | none | Confirmation message before running the action |
| `visible` | always | `fn (array $row): bool` to show it only on some records |
| `method` | `'get'` | `'post'` for actions that modify data. A form with the CSRF token is submitted |

```php
// {kp_id}: the id as it appears in the CRUD's URLs (a code if masked).
// The target route, with (:segment), gets the real id with Crud::idFromUrl()
$crud->addAction('Invoices', 'invoices/client/{kp_id}', ['icon' => 'bi bi-receipt']);

$crud->addAction('Map', 'https://www.openstreetmap.org/search?query={address}', [
    'icon' => 'bi bi-geo-alt', 'target' => '_blank',
]);

$crud->addAction('Send reminder', 'clients/remind/{kp_id}', [
    'method'  => 'post',
    'confirm' => 'Do you want to send the reminder?',
    'visible' => fn (array $row) => $row['balance'] > 0,
]);
```

### `addHeaderAction(string $label, string $url, array $options = []): static`

Adds a button to the list **header**, next to "Add". It accepts the same options as `addAction()`. `visible` receives `null`.

```php
$crud->addHeaderAction('Import CSV', 'clients/import', ['icon' => 'bi bi-upload']);
$crud->addHeaderAction('Recalculate balances', 'clients/recalculate', ['method' => 'post', 'confirm' => 'This may take a few minutes. Continue?']);
```

### `addBulkAction(string $key, string $label, Closure $callback, array $options = []): static`

Action on the records **selected** with the checkboxes: `fn (array $ids, Crud $crud): string|ResponseInterface|null`.

`$ids` only contains identifiers that exist and are within the CRUD's scope (`where()`), even if someone tampers with the form. If the callback returns a string, it is shown as a message; if it returns a response (a download, a redirect...), it is returned as is.

| Option | Default | Description |
|---|---|---|
| `confirm` | none | Confirmation message |
| `trash` | `false` | `true` to offer the action in the trash instead of the list |

```php
$crud->addBulkAction('activate', 'Activate', function (array $ids) {
    db_connect()->table('clients')->whereIn('id', $ids)->update(['active' => 1]);

    return count($ids) . ' clients activated.';
});

// Return a download
$crud->addBulkAction('labels', 'PDF labels', function (array $ids) {
    $pdf = (new \App\Libraries\Labels())->render($ids);

    return service('response')->download('labels.pdf', $pdf);
});
```

Custom bulk actions do not check permissions record by record: if needed, do it inside the callback.

---

## CSV import

### `enableImport(array $options = []): static`

Enables importing from a CSV file. The list shows the **Import CSV** button, which leads to `base/import`. The process has three steps:

1. **Upload the file** and choose:
   - what to do with each row: *always add a new record* or *update the record if the key already exists, add it otherwise*;
   - what to do if there are rows with errors: *import nothing* (all in one transaction) or *import the valid rows and skip the others*.
2. **Preview**: it shows how the columns have been mapped, how many rows are new, how many will be updated, which ones have errors (by line) and the first rows.
3. **Confirm**. If everything goes well, you return to the list with the summary; if some rows could not be imported, a report is shown.

![Import preview](../screenshots/16-importacio-previsualitzacio.png)

| Option | Default | Description |
|---|---|---|
| `fields` | add form fields | Fields that can be imported. Files, virtual fields, `readonly` fields and `hidden` fields with a fixed value are never included |
| `key` | primary key | Field that identifies an existing record in order to update it (e.g. `email`, `username`, `code`) |
| `maxRows` | `$importMaxRows` (1000) | Maximum number of rows |
| `maxSize` | `$importMaxSize` (2048) | Maximum file size, in KB |

```php
$crud->enableImport();

$crud->enableImport([
    'key'     => 'email',
    'fields'  => ['name', 'email', 'phone', 'city_id', 'tags', 'active'],
    'maxRows' => 300,
]);
```

**How the file is read:**

- The first row is the header. Each column is mapped to a field by its **name** (`city_id`) or by its **label** (`City`), case-insensitively. Columns that do not match any field are ignored.
- The encoding (UTF-8, with or without BOM, or Windows-1252) and the delimiter (`;`, `,` or tab) are detected automatically.
- The upload page has a button to download a **template** with the columns.
- A row with fewer columns than needed is an error.
- Reading stops as soon as `maxRows` is exceeded.
- If a value starts with an apostrophe followed by a formula character (`'=`, `'+`...), the apostrophe added by the export is removed.

**How values are converted:**

| Type | Accepts |
|---|---|
| 1-N relation, `enum`, `select` | the id or the displayed text (`3` or `Barcelona`) |
| N-N relation | several comma-separated ids or texts (`Music, Sports`) |
| `boolean` | `1`/`0`, `yes`/`no`, `true`/`false`, `sí`/`no` and the "Yes/No" text of the current language |
| `date`, `datetime` | `YYYY-MM-DD [HH:MM[:SS]]` or the display format (`$dateFormat`, `$datetimeFormat`) |
| `number`, `decimal` | `1234.5`, `1234,5`, `1.234,50`, `€ 12.00`... Only digits, separators, a leading minus sign, spaces and the `€`, `$` and `£` symbols: with letters, the value is invalid |
| `password` | the text, which is saved hashed. In an update, if it is empty, it is not changed |

Thanks to this, a file generated with **Export CSV** can be edited with a spreadsheet and imported back without changes.

If a text matches more than one option (two cities with the same name), the row has an error ("matches more than one record"): use the id. The same happens if, with *update if it already exists*, the key value matches more than one record.

**What is checked on each row:**

- The same **validation rules** as the form (the configured ones and the ones inferred from the table).
- The **permissions**: `add` to add and `edit` (with the record) to update. The `edit` closure is evaluated again with the record as it would be after the import.
- The **`where()`** scope: only records within the scope are updated, fields with a simple equality condition are always saved with the condition's value and, after saving, the record must still be inside the scope.
- In an update, only the columns present in the file that are in the edit form (`editFields()`) are changed, and never the `readonlyFields`. Inserts use the import fields (`fields`).
- When adding, the fields not present take the value from `setDefault()` or from the database.

While the preview is shown, the file is stored in `writable/uploads/kpcrudy-import/` with a random name, private permissions (`0600`) and tied to the user's session. It is deleted when importing, when uploading another one, when going back to the upload page or, if abandoned, after one hour. In the preview, the values of [sensitive fields](#columns-and-fields) are masked.

When importing, the **hooks** (`beforeInsert`, `afterInsert`, `beforeUpdate`, `afterUpdate`) run as if the form were saved, and the N-N relations present in the file are rewritten. The after-hooks (`afterInsert`, `afterUpdate`) only run once the transaction has been committed; with *import nothing* if there are errors, only if the whole import succeeds. The preview does not run the hooks or detect duplicates within the same file (two rows with the same email): those errors show up when importing.

### `callbackImport(string $field, Closure $callback): static`

Transforms the value of a column before validating it: `fn (string $value, array $row): mixed`. `$row` contains the values of the whole row (field => CSV text). The returned value is validated and saved as is, without the automatic conversion. For relation, `enum`, `select` and N-N fields, the result must still be a valid option.

```php
// Phone numbers with digits only
$crud->callbackImport('phone', fn (string $value) => preg_replace('/\D/', '', $value));

// Accept "Free" as price 0
$crud->callbackImport('price', fn (string $value) => strcasecmp($value, 'free') === 0 ? '0.00' : $value);

// Dates in US format (m/d/Y), which the automatic conversion does not recognize
$crud->callbackImport('birthdate', function (string $value) {
    $date = DateTime::createFromFormat('!m/d/Y', $value);

    return $date ? $date->format('Y-m-d') : $value;   // if it is not valid, validation will report it
});
```

The callback only runs if the column is in the file.

---

## Permissions

### `setPermission(string $operation, bool|Closure $allowed): static`

Allows or denies an operation. All operations are allowed by default, except `trash`, `restore` and `purge`, which only exist if the table has a trash.

Permissions are checked on the **server**, not only in the interface: if an action is not allowed, its button is not shown and, if someone accesses it by URL, they get an error.

| Operation | What it controls | Receives `$row`? |
|---|---|---|
| `list` | Viewing the list (if denied, 404 error) | no |
| `add` | "Add" button and add form | no |
| `edit` | Edit button and form | yes |
| `show` | Button and detail view | yes |
| `delete` | Delete button and bulk action | yes, and `null` for the bulk action |
| `export` | Export button and bulk action (also requires `list`) | no |
| `import` | Import button and page (requires `enableImport()`). Each row also needs `add` or `edit` | no (rows, via `add` and `edit`) |
| `trash` | Trash (also required for `restore` and `purge`) | yes, and `null` for the link |
| `restore` | Restoring from the trash | yes, and `null` for the bulk action |
| `purge` | Permanently deleting and emptying the trash | yes, and `null` for the bulk action and "Empty trash" |
| `search` | Search box | no |
| `filters` | Filters panel | no |
| `bulk` | Checkboxes and bulk actions | no |

`$allowed` can be:

- a **boolean**: allowed or denied for everyone;
- a **closure** `fn (?array $row, string $operation): bool`, which decides record by record.

The closure receives the record (`$row`) when there is a specific one, and `null` when the decision is general: whether to show the "Add" button, whether to offer the bulk action, etc. **If it returns `false` with `null`, the option disappears completely.** That is why most closures start with `$row === null ||`.

The **`edit`** closure is evaluated twice: before editing, with the stored record, and when saving (form and CSV import), with the record as it would be after the change (the stored record merged with the posted data). If it returns `false` the second time, nothing is saved. So, in example 3, a user cannot change the `user_id` of one of their notes to hand it over to another user.

**Example 1. Disable an operation for everyone**

```php
$crud->setPermission('export', false);   // equivalent to unsetExport()
```

**Example 2. Depending on the user's role** (a boolean computed beforehand)

```php
$isAdmin = auth()->user()->inGroup('admin');   // with CodeIgniter Shield

$crud->setPermission('add', $isAdmin)
     ->setPermission('delete', $isAdmin)
     ->setPermission('trash', $isAdmin);
```

**Example 3. Only the owner can edit or delete the record**

```php
$userId = session('user_id');
$owner  = fn (?array $row) => $row === null || (int) $row['user_id'] === (int) $userId;

$crud->setPermission('edit', $owner)
     ->setPermission('delete', $owner);
```

With `$row === null` returning `true`, the "Delete" bulk action is still available, but when applied only the user's own records are deleted: the library checks the permission again for each selected record.

**Example 4. Depending on the record's status**

```php
// Closed invoices cannot be modified or deleted
$crud->setPermission('edit', fn (?array $row) => $row === null || $row['status'] !== 'closed')
     ->setPermission('delete', fn (?array $row) => $row === null || $row['status'] === 'draft');
```

**Example 5. A single closure for several operations, using `$operation`**

```php
$rules = function (?array $row, string $operation): bool {
    if (auth()->user()->inGroup('admin')) {
        return true;
    }

    return match ($operation) {
        'show'   => true,
        'edit'   => $row === null || $row['user_id'] == session('user_id'),
        'delete' => false,
        default  => false,
    };
};

foreach (['show', 'edit', 'delete', 'add'] as $operation) {
    $crud->setPermission($operation, $rules);
}
```

**Example 6. With a time condition**

```php
// Comments can only be edited during the first 24 hours
$crud->setPermission('edit', fn (?array $row) =>
    $row === null || strtotime($row['created_at']) > strtotime('-24 hours')
);
```

**Example 7. Hide the option in the interface but decide record by record**

```php
// No bulk delete (null => false), but each deletable row has its own button
$crud->setPermission('delete', fn (?array $row) => $row !== null && $row['locked'] == 0);
```

### Shortcuts

All of them are equivalent to `setPermission($operation, false)`:

| Method | Operation |
|---|---|
| `unsetAdd()` | `add` |
| `unsetEdit()` | `edit` |
| `unsetDelete()` | `delete` |
| `unsetShow()` | `show` |
| `unsetExport()` | `export` |
| `unsetTrash()` | `trash` (and therefore `restore` and `purge`) |
| `unsetPurge()` | `purge` |
| `unsetSearch()` | `search` |
| `unsetFilters()` | `filters` |
| `unsetBulk()` | `bulk` |

```php
$crud->unsetAdd()->unsetBulk()->unsetExport();
```

### `readOnly(): static`

View only: equivalent to `unsetAdd()->unsetEdit()->unsetDelete()->unsetPurge()` plus `setPermission('restore', false)`, so restoring, permanently deleting and emptying the trash are not possible either. The list, detail view, search, filters and export remain available.

```php
$crud->setTable('logs')->readOnly()->unsetTrash();
```

---

## Display and navigation

### `setPerPage(int $perPage, ?array $options = null): static`

Records per page and, optionally, the options of the "Per page" selector.

```php
$crud->setPerPage(50);
$crud->setPerPage(5, [5, 10, 20]);
```

### `defaultOrder(string $field, string $direction = 'asc'): static`

Initial order of the list. It can be a 1-N relation (sorted by the text). By default, by primary key ascending.

```php
$crud->defaultOrder('created_at', 'desc');
$crud->defaultOrder('city_id');
```

### `setBaseUrl(string $url): static`

Base URL of the CRUD. It is normally inferred automatically from the current URL. You only need to set it if the route ends with a word the CRUD uses as an operation (`add`, `trash`, `export`, `import`, `bulk`, `emptytrash`, `edit`, `show`, `delete`, `restore`, `purge`).

```php
// Route "admin/trash": "trash" is an operation
Crud::routes($routes, 'admin/trash', 'Admin::trash');

$crud->setBaseUrl(site_url('admin/trash'));
```

### `setBackUrl(string $url): static`

Where to go back after saving, deleting or cancelling, and where the "Back to list" button leads. By default, to the list with the page, search and filters the user had left there.

```php
// After editing the profile, go back to the detail view
$crud->setBackUrl(site_url('profile/show/' . Crud::idForUrl($userId, 'users')));   // the code if ids are masked
```

### `setTheme(string $theme): static`

Template theme for this CRUD: one of the bundled ones (`bootstrap5`, `w3css`, `tailwind`) or one registered in `$themes`. See [new_theme_EN.md](new_theme_EN.md).

```php
$crud->setTheme('w3css');
$crud->setTheme('compact');   // custom theme
```

### `setLayout(string|false $layout): static`

How the CRUD's HTML is wrapped:

| Value | Result |
|---|---|
| `'default'` | Full HTML page of the theme (`layout.php`) with Bootstrap from a CDN |
| `false` | Only the CRUD's HTML fragment |
| a view name | CodeIgniter view that receives `$kpcrudy` (the CRUD's HTML) and `$title` |

```php
$crud->setLayout('layouts/admin');
```

---

## Access from callbacks

These methods are useful inside callbacks, hooks and bulk actions.

### `getModel(): Model`

The model the CRUD uses: yours, or the dynamic one in table mode.

```php
$crud->addBulkAction('archive', 'Archive', function (array $ids, Crud $crud) {
    $crud->getModel()->update($ids, ['archived' => 1]);

    return 'Archived.';
});
```

### `getTable(): string` · `getPrimaryKey(): string`

Name of the table and of the primary key.

```php
$crud->addBulkAction('touch', 'Mark as reviewed', function (array $ids, Crud $crud) {
    db_connect()->table($crud->getTable())
        ->whereIn($crud->getPrimaryKey(), $ids)
        ->update(['reviewed_at' => date('Y-m-d H:i:s')]);
});
```

### `getBaseUrl(): string` · `url(string $operation, ?string $id = null): string`

Base URL of the CRUD and URL of an operation. Inside callbacks they are already resolved.

```php
$crud->addBulkAction('duplicate', 'Duplicate', function (array $ids, Crud $crud) {
    $newId = (new \App\Libraries\Duplicator())->copy($ids[0]);

    return redirect()->to($crud->url('edit', (string) $newId));
});
```

`url()` takes the real id: if [ids are masked](#masked-ids), it masks it.

---

## Masked ids

By default, the CRUD's URLs carry the primary key: `admin/invoices/edit/5`. With `maskIds()` they carry a code instead of the id: `admin/invoices/edit/Xk3f9QaLm2PwE7`.

**When to use it.** When URLs leave the application (a public link, an email, a [signed link](#signed-urls)) or when you do not want anyone to see how many records there are or to try consecutive ids (`edit/6`, `edit/7`...). It does not replace permissions or `where()`: it is one more layer. In a well-protected internal CRUD it is not essential.

### Quick start

1. **Enable it in the CRUD** (or in all of them, with `public bool $maskIds = true;` in the [global configuration](#global-configuration)). No extra route is needed:

   ```php
   $crud->setTable('invoices')->maskIds();
   ```

2. **Your own pages that receive the code** (e.g. a public detail page) use **a different prefix** from the CRUD and decode it with `Crud::unmaskId()`, with the same context (by default, the table name):

   ```php
   // Routes.php
   Crud::routes($routes, 'admin/invoices', 'Admin\Invoices::index', ['filter' => 'auth:admin']);
   $routes->get('invoice/(:segment)', 'PublicInvoice::show/$1');   // invoice/..., not admin/invoices/...

   // PublicInvoice::show(string $code)
   $id = Crud::unmaskId($code, 'invoices') ?? throw PageNotFoundException::forPageNotFound();
   ```

   If the route receives the id from a CRUD action (`{kp_id}`) and must work whether ids are masked or not, use [`Crud::idFromUrl()`](#crudidfromurlstring-value-string-context-bool-masked--null-kpcrudyconfig-config--null-string-static) instead of `unmaskId()`, and [`Crud::idForUrl()`](#crudidforurlintstring-id-string-context-bool-masked--null-kpcrudyconfig-config--null-string-static) to build its links. The route always uses `(:segment)`, never `(:num)`: the code has letters, `-` and `_`.

3. **In actions**, `{kp_id}` puts the code in the URL (`{id}` is still the real id). Outside the CRUD (an email, a view), `Crud::maskId($id, 'invoices')` gives the same code:

   ```php
   $crud->addAction('Public link', 'invoice/{kp_id}', ['target' => '_blank']);
   ```

### What changes and what does not

| Item | With `maskIds()` |
|---|---|
| Edit, detail view, delete, restore and purge URLs | They carry the code: `edit/Xk3f9QaLm2PwE7` |
| List checkboxes (bulk actions, export of the selection) and redirects after saving | They use the code |
| A real id or a forged code in the URL | Treated as a record that does not exist ("Record not found.") |
| Primary key column | Not shown by default in the list, the detail view or the export (it would appear next to the code). If you want it, add it to `columns()`, `showFields()` or `exportColumns()` |
| Hooks, permissions, callbacks, bulk actions, `addAction()` closures and `{id}` | **Unchanged**: they receive the real id |
| Filters in the URL (`admin/attendances?f[event_id]=5`) | **Unchanged**: they use the real values |
| Routes | **Unchanged**: the same two from `Crud::routes()` |

### Context and key

- **Context**: by default, the table name, so the same id gives different codes in each table (an invoice's code does not open any client). Outside the CRUD you must use the same context. If you change it (`maskIds(context: 'invoice')`), the codes change.
- **Key**: `Config\Kpcrudy::$maskKey` (at least 32 characters, e.g. `bin2hex(random_bytes(32))`; if it is shorter, an exception is thrown); if it is empty, CodeIgniter's encryption key (`php spark key:generate`); and if there is none, a random key created the first time in `writable/kpcrudy/mask.key`. It is the same key that signs [signed URLs](#signed-urls) (a different one is derived for each use).
- **If the key changes, every code changes** and old links stop working, signed ones too. Use the same key on every server and do not delete `mask.key` when deploying.
- If a CRUD uses its own configuration (`new Crud(new \Config\KpcrudyPublic())`), pass it to `Crud::maskId()` and `Crud::unmaskId()` too (`$config`), because it may have a different key or masker.

### `maskIds(bool $enabled = true, ?string $context = null, IdMasker|string|null $masker = null): static`

Masks the ids in every CRUD URL (see [What changes and what does not](#what-changes-and-what-does-not)). It works with the same two routes registered by [`Crud::routes()`](#crudroutesroutecollection-routes-string-path-string-handler-array-options---void-static).

- `$context`: by default, the table name (see [Context and key](#context-and-key)).
- `$masker`: an instance or the name of a class implementing [`IdMasker`](#interface-kpcrudyidmasker). By default, the one in `Config\Kpcrudy::$idMasker`.

```php
$crud->setTable('invoices')->maskIds();

// With a custom context (e.g. two CRUDs that share codes)
$crud->maskIds(context: 'invoice');

// Disable it for one CRUD when it is enabled in the global configuration
$crud->maskIds(false);
```

The default code ([`DefaultIdMasker`](../src/DefaultIdMasker.php)):

- is **always the same** for the same id and context (URLs do not change and can be bookmarked);
- is encrypted with AES-256-CTR using an HMAC of the id as the initialization vector, which is also the signature: it **cannot be forged or altered** without the key (it is treated as invalid);
- is **short** (about 16 characters plus the length of the id) and only has URL-safe characters (`A-Z a-z 0-9 - _`);
- needs PHP's `openssl` extension.

### `encodeId(int|string $id): string` · `decodeId(string $value): ?string`

The id as it appears in this CRUD's URLs, and the real id from what appears in the URL (`null` if the code is not valid). If `maskIds()` is not enabled, they return the value unchanged.

```php
$crud->callbackColumn('number', fn ($value, array $row) =>
    '<a href="' . site_url('admin/invoices/show/' . $crud->encodeId($row['id'])) . '">' . esc($value) . '</a>');
```

### `Crud::maskId(int|string $id, string $context, ?KpcrudyConfig $config = null): string` (static)

Masks an id outside a CRUD: a public page, an email, a custom route. The context must be the CRUD's (by default, the table name). Without `$config`, it uses the project configuration (the key and the masker).

```php
use Kpcrudy\Crud;

$link = site_url('invoice/' . Crud::maskId($invoice['id'], 'invoices'));
```

### `Crud::unmaskId(string $code, string $context, ?KpcrudyConfig $config = null): ?string` (static)

Real id of a code made by a CRUD or by `Crud::maskId()`, or `null` if it is not valid for this context (forged, altered, from another table or from an old key).

```php
// Routes.php: a different prefix from the CRUD's (admin/invoices)
$routes->get('invoice/(:segment)', 'PublicInvoice::show/$1');

// Controller
public function show(string $code)
{
    $id = Crud::unmaskId($code, 'invoices') ?? throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
    // ...
}
```

### `Crud::idFromUrl(string $value, string $context, ?bool $masked = null, ?KpcrudyConfig $config = null): ?string` (static)

Real id from the value that reaches one of your own routes (e.g. the `{kp_id}` of an action), **whether ids are masked or not**:

- with masking, it decodes the code like `unmaskId()` (`null` if it is not valid);
- without it, it returns the value as is (`null` if empty). It does not check that it is a number: the record query will do that.

`$masked`: `null` (default) follows `Config\Kpcrudy::$maskIds`; pass `true` if the CRUD enables it only with `maskIds()` (or `false` if it disables it with `maskIds(false)`). `$config`: the CRUD's configuration, if it uses its own.

```php
// Clients CRUD controller
$crud->addAction('Send reminder', 'clients/remind/{kp_id}', ['method' => 'post']);

// Routes.php: (:segment), not (:num)
$routes->post('clients/remind/(:segment)', 'Clients::remind/$1');

// Target
public function remind(string $code)
{
    $id = Crud::idFromUrl($code, 'clients') ?? throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
    // If the CRUD enables it only with maskIds(): Crud::idFromUrl($code, 'clients', true)
    // ...
}
```

### `Crud::idForUrl(int|string $id, string $context, ?bool $masked = null, ?KpcrudyConfig $config = null): string` (static)

The value that goes in the URL for a real id: the code (like `maskId()`) if ids are masked, and the id as is otherwise. It is the same value `{kp_id}` inserts in actions. `$masked` and `$config` work as in `idFromUrl()`.

Use it to build links and redirects to a CRUD's pages from outside the CRUD without assuming the id appears as is in the URL. If you have the CRUD instance, `$crud->url('edit', $id)` or `$crud->encodeId($id)` already know whether masking is on.

```php
return redirect()->to(site_url('admin/clients/show/' . Crud::idForUrl($id, 'clients')));
```

In actions, `{kp_id}` inserts the id as it appears in the CRUD's URLs (the code, if masked). The target route must use `(:segment)` (not `(:num)`) and get the real id with `Crud::idFromUrl()` (or `Crud::unmaskId()` if ids are always masked, as in the demo). For example, the demo's *Duplicate* action ([Events::duplicate](../src/Demo/Controllers/Admin/Events.php)), adapted to work in both cases:

```php
// CRUD controller
$crud->addAction('Duplicate', 'admin/events-duplicate/{kp_id}', ['method' => 'post']);

// Routes.php
$routes->post('admin/events-duplicate/(:segment)', 'Admin\Events::duplicate/$1');

// Target
public function duplicate(string $code)
{
    $id = Crud::idFromUrl($code, 'events') ?? throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
    // ...
    return redirect()->to(site_url('admin/events/edit/' . Crud::idForUrl($newId, 'events')));
}
```

`{id}` is still the real id: that is what you need, for example, to open another CRUD filtered (`admin/attendances?f[event_id]={id}`), because filters use the real values.

### Interface `Kpcrudy\IdMasker`

To use a different code format, implement the interface and set the class in `Config\Kpcrudy::$idMasker` (for the whole project) or pass it to `maskIds(masker: ...)`. The constructor receives the configuration (`Kpcrudy\Config\Kpcrudy`).

```php
interface IdMasker
{
    public function encode(string $id, string $context): string;   // code for the URL
    public function decode(string $code, string $context): ?string; // real id, or null if it is not valid
}
```

For example, shorter codes with [Sqids](https://sqids.org/php) (`composer require sqids/sqids`). It is not encryption: it hides the order and the number of records, but anyone who knows the alphabet can decode it.

```php
// app/Libraries/SqidsMasker.php
namespace App\Libraries;

use Kpcrudy\IdMasker;
use Sqids\Sqids;

class SqidsMasker implements IdMasker
{
    public function encode(string $id, string $context): string
    {
        return $this->sqids($context)->encode([(int) $id]);
    }

    public function decode(string $code, string $context): ?string
    {
        $ids = $this->sqids($context)->decode($code);

        // Only valid if it gives back the same code
        return count($ids) === 1 && $this->encode((string) $ids[0], $context) === $code ? (string) $ids[0] : null;
    }

    protected function sqids(string $context): Sqids
    {
        // Custom alphabet, shuffled differently (but deterministically) for each context
        $alphabet = str_split('k3G7QAe51FCsPW92uEOyq4Bg6Sp8YzVTmnU0liwDdHXLajZrfxNhobJIRcMvKt');
        usort($alphabet, fn ($a, $b) => strcmp(md5($context . $a), md5($context . $b)));

        return new Sqids(implode('', $alphabet), 8);
    }
}
```

```php
// app/Config/Kpcrudy.php
public bool $maskIds   = true;
public string $idMasker = \App\Libraries\SqidsMasker::class;
```

### `Crud::checkRoutes(): void` (static)

Checks that no route conflicts with those of the CRUDs registered with [`Crud::routes()`](#crudroutesroutecollection-routes-string-path-string-handler-array-options---void-static) and, if it finds one, throws a `RuntimeException` explaining the problem. `render()` calls it automatically when `ENVIRONMENT` is `development` (once per request), so the error shows up while you are coding and not in production. To disable it, set `public bool $checkRoutes = false;` in the [global configuration](#global-configuration) (only if you have a route that replaces a CRUD operation on purpose).

There are four error cases:

- a route with the same prefix defined **after** `Crud::routes()`: it is never reached, because `prefix/(:any)` captures it first;
- a route defined **before** that captures a CRUD URL (`prefix/add`, `prefix/edit/1`, `prefix/trash`...): the CRUD stops working for that URL;
- a route identical to `prefix/(:any)` defined before: CodeIgniter ignores the CRUD's one;
- a CRUD whose prefix contains another one defined **after** it (`admin` and then `admin/users`): the second one never runs. Define the CRUD with the longer prefix first.

More specific routes defined before that do not capture any CRUD URL (e.g. `admin/invoices/pdf/(:segment)`) are fine. With masked ids it is easy to fall into the first case (`admin/invoices/(:segment)` for the public page): use a different prefix (`invoice/(:segment)`).

```php
// Wrong: never runs, Crud::routes() already captures admin/invoices/...
Crud::routes($routes, 'admin/invoices', 'Admin\Invoices::index');
$routes->get('admin/invoices/(:segment)', 'PublicInvoice::show/$1');

// Right
$routes->get('invoice/(:segment)', 'PublicInvoice::show/$1');
```

### Troubleshooting masked ids

| Symptom | Cause | Fix |
|---|---|---|
| "Record not found." when opening `edit/5` or a hand-made link | With `maskIds()`, a real id in the URL is not valid | Build links with `{kp_id}`, `$crud->url('edit', $id)`, `encodeId()`, `Crud::idForUrl()` or `Crud::maskId()` |
| Your own page returns a 404 with a good code (`Crud::unmaskId()` or `Crud::idFromUrl()` returns `null`) | A different context (the table or the one in `maskIds(context: ...)`) or a different configuration | The same context; pass `$config` if the CRUD uses its own |
| Your own route that receives `{kp_id}` returns a 404 before reaching the controller | The route uses `(:num)` and the code has letters, `-` and `_` | `(:segment)` and `Crud::idFromUrl()` in the controller |
| `Crud::idFromUrl()` returns the code without decoding it | Ids are masked only with `maskIds()` in the CRUD, not in the configuration | Pass `true` as `$masked` |
| After a deploy, no old link works and the codes are different | The key changed: another `$maskKey`, a new `encryption.key`, or `writable/kpcrudy/mask.key` was deleted | Restore the old key; set `$maskKey` in every environment |
| Your custom route is never reached (the CRUD or a 404 opens) | It has the same prefix as the CRUD and `prefix/(:any)` captures it | A different prefix (`invoice/...`); in development, [`checkRoutes()`](#crudcheckroutes-void-static) warns you |
| Exception "... no s'executarà mai" (will never run) or "... captura ..." (captures) in development | `checkRoutes()` found a route conflict | Change your route's prefix, or define the CRUD with the longer prefix first |
| The id column disappeared from the list | `maskIds()` hides the primary key by default | Add it to `columns()` (or `showFields()`, `exportColumns()`) |
| A list filtered with `{kp_id}` finds nothing | Filters use the real values | `?f[event_id]={id}` |
| Finding which record a code refers to | | `php spark kpcrudy:unmaskid <table> <code>` (see [the recipe](howto_EN.md#find-which-record-a-masked-code-refers-to)) |

---

## Signed URLs

A signed URL carries a signature (`kp_sig`, 32 characters) and, if it expires, the expiry time (`kp_exp`, in Unix seconds): `shared/agenda?kp_exp=1767225600&kp_sig=Qm9...`. Whoever has the link can open the page **without logging in**; if they change anything that is signed, it stops working.

**When to use it.** To share a read-only view with someone who has no account (an agenda, a price list, an order's details), to email a link that expires, or to protect a download or a custom page. It is not meant to give anonymous visitors permission to edit.

**How it works:**

1. In the CRUD being shared, [`requireSignature()`](#requiresignaturearraystring-operations---int-ttl--null-arraystring-signfrom---static) states which operations only open with a signed URL.
2. Somewhere else (the admin area, an email, a command) the entry link is created with [`Crud::signUrl()`](#crudsignurlstring-url-int-ttl--3600-bool-lockquery--true-kpcrudyconfig-config--null-string-static) or [`signedUrl()`](#signedurlstring-operation--list-intstringnull-id--null-int-ttl--86400-array-query---string), with a lifetime.
3. The visitor opens it: the CRUD checks the signature and, if it is not valid or has expired, answers with a 404.
4. While browsing a signed list, the links the CRUD generates (pages, sorting, search, detail views, export) are signed automatically, with **the same expiry** as the entry link.
5. When the entry link expires, the whole navigation expires: browsing never extends the validity.

```text
Admin area (logged in)                        Visitor (not logged in), CRUD with requireSignature()
Crud::signUrl('shared/agenda', 7 days) ───►  shared/agenda?kp_exp=…&kp_sig=…           list
                                               ├─ ?page=2 · ?sort=… · ?q=…              signed automatically, same expiry
                                               ├─ show/Xk3f9QaLm2PwE7?kp_exp=…&kp_sig=…  detail view (no back button)
                                               └─ export?kp_exp=…&kp_sig=…              CSV (unless removed)
                                             shared/agenda/edit/Xk3f9…  (no signature)    ─► 404 "This link is not valid."
                                             any link after kp_exp                       ─► 404 "This link has expired."
```

**What the signature covers** in CRUD pages: the path (the CRUD base, the operation and the masked id) and the expiry. It does **not** cover the query string (`?q=`, `?page=`, `?sort=`, `?f[...]=`), which the visitor can change to search, filter and paginate. Therefore, **what the visitor can see is limited by `where()`** (and by the path parameters, e.g. the `5` in `clients/5/notes`), never by a filter placed in the link.

### `requireSignature(array|string $operations = '*', ?int $ttl = null, array|string $signFrom = []): static`

The given operations only open with a valid signed URL. Without a signature, with a signature that does not match or with an expired one, the CRUD answers with a 404 and the message "This link is not valid." or "This link has expired." (language keys `linkInvalid` and `linkExpired`).

- `$operations`: `'*'` (the default, and the safest): every operation. Or a list (`['list', 'show']`): the ones not in it stay **open to anyone** who reaches the route; protect them with an authentication filter or remove them with `unset*()`. A name that does not exist (`'lsit'`) throws an exception, because it would leave the operation open without warning. The operations are `list` and those in [`ID_OPERATIONS` and `OPERATIONS`](#crudid_operations--crudoperations).
- `$signFrom`: operations that open **without** a signature and hand out signed links. E.g. `['list']` for an internal list, protected by login, that gives signed links to the detail views. An operation cannot be both in `$operations` and in `$signFrom`.
- `$ttl`: lifetime, in seconds, of the links handed out by the `$signFrom` operations (`null` = they never expire). Links signed while browsing with a signed URL do not use it: they inherit the entry link's expiry.

```php
// The whole CRUD only with a signed link (the usual case)
$crud->readOnly()->requireSignature();

// Only the list and the detail views: the rest stays open and must be removed
$crud->readOnly()->unsetExport()->unsetTrash()->unsetBulk()->requireSignature(['list', 'show']);

// Internal list (logged in) that gives 7-day links to the detail views
$crud->requireSignature(['show'], 7 * 86400, ['list']);
```

Which links the CRUD signs by itself:

- Only when it was entered with a signed URL of a **list** (or of the trash), or through a `$signFrom` operation. A request without a valid signature never obtains signed links (otherwise anyone could get them).
- On a **detail view** opened with a signed link (`show/X`), the CRUD signs no other link: a detail view's link does not give access to the list. There is no back-to-list button (from a signed list, the visitor goes back with the browser's button), and errors (e.g. a record that no longer exists) are shown as a 404 page with the message, instead of redirecting to a list the visitor could not open.
- Exporting the trash needs a signature issued from the trash page.

### `signedUrl(string $operation = 'list', int|string|null $id = null, ?int $ttl = 86400, array $query = []): string`

Signed URL of an operation of this CRUD, to share it. The id is the real one (it is masked automatically if needed). `$ttl` in seconds (by default, 1 day; `null` = it never expires). `$query`: initial parameters (a search, an order); they are not signed, so the visitor can change them. Only the path is signed, like the links the CRUD makes while browsing.

The link points to this CRUD's URL. If you create it from another controller (e.g. the admin area), build the public CRUD in one place and give it its base URL with [`setBaseUrl()`](#setbaseurlstring-url-static):

```php
class SharedEvents extends BaseController
{
    public static function crud(): Crud
    {
        return (new Crud())->setTable('events')
            ->setBaseUrl(site_url('shared/events'))
            ->maskIds()
            ->readOnly()
            ->requireSignature();
    }

    public function index()
    {
        return static::crud()->render();
    }
}

// In the admin area
$week  = SharedEvents::crud()->signedUrl('list', null, 7 * 86400, ['sort' => 'starts_at']);
$event = SharedEvents::crud()->signedUrl('show', $row['id'], 3 * 86400);   // real id
```

### `Crud::signUrl(string $url, ?int $ttl = 3600, bool $lockQuery = true, ?KpcrudyConfig $config = null): string` (static)

Signs any URL of the project, absolute (`site_url(...)`) or relative (`'shared/agenda'`, which goes through `site_url()`). `$ttl` in seconds (by default, 1 hour; `null` = it never expires). The path must be exactly the page's, with the masked id if the CRUD uses `maskIds()`.

- `$lockQuery = true` (the default): the signature also covers every parameter; none can be changed, removed or added. For custom routes and downloads, which are checked with [`Crud::checkSignature()`](#crudchecksignatureincomingrequest-request--null-bool-allowfreequery--false-kpcrudyconfig-config--null-string-static).
- `$lockQuery = false`: only the path. This is what you need to create, from somewhere else, the **entry link to a CRUD** with `requireSignature()`, so the visitor can search and change pages (as the [demo](../src/Demo/Controllers/Shared.php)'s `Shared::link()` does).
- `$config`: the configuration the key comes from, if the CRUD uses its own.

```php
use Kpcrudy\Crud;

// Entry to a signed CRUD (path only), 7 days
$agenda = Crud::signUrl('shared/agenda', 7 * 86400, false);
$event  = Crud::signUrl('shared/agenda/show/' . Crud::maskId($id, 'events'), 7 * 86400, false);

// Custom route (path and parameters), 10 minutes
$pdf = Crud::signUrl(site_url('reports/' . Crud::maskId($id, 'reports') . '/pdf') . '?copy=1', 600);
```

### `Crud::checkSignature(?IncomingRequest $request = null, bool $allowFreeQuery = false, ?KpcrudyConfig $config = null): string` (static)

Signature status of the request (by default, the current one), for custom controllers: `'valid'`, `'missing'` (no `kp_sig`), `'invalid'` or `'expired'`, which are the constants `UrlSigner::VALID`, `MISSING`, `INVALID` and `EXPIRED`.

- `$allowFreeQuery`: also accepts links made with `$lockQuery = false` or with `signedUrl()`, where the parameters can change. Not by default.
- It only accepts links made with `Crud::signUrl()` or `signedUrl()` for this same path: the ones a CRUD signs while browsing are not valid for a custom page.
- `'expired'` is only returned if the signature is correct, so you can say "ask for a new link" without giving hints to a forger.

```php
use CodeIgniter\Exceptions\PageNotFoundException;
use Kpcrudy\Crud;
use Kpcrudy\UrlSigner;

$status = Crud::checkSignature();

if ($status !== UrlSigner::VALID) {
    throw PageNotFoundException::forPageNotFound(lang($status === UrlSigner::EXPIRED ? 'Kpcrudy.linkExpired' : 'Kpcrudy.linkInvalid'));
}
```

### `Crud::hasValidSignature(?IncomingRequest $request = null, bool $allowFreeQuery = false, ?KpcrudyConfig $config = null): bool` (static)

The same as `Crud::checkSignature() === UrlSigner::VALID`, when you do not need to know why the link is not good.

```php
if (! Crud::hasValidSignature()) {
    throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
}
```

### Security

What it guarantees:

- Nothing in the path can be changed, nor the expiry: not the operation (`show` → `edit`), the id, the CRUD base or `kp_exp`. Any change, including encoding tricks (`%2F`, `..`), gives "This link is not valid.".
- Browsing does not extend the validity, and a detail view's link gives no access to the list or to any other record.
- A request without a valid signature never obtains signed links (except in the `$signFrom` operations, which must be protected by login).
- Links a CRUD signs while browsing are not valid for custom pages, and a masked code does not work as a signature or the other way round (each use has its own derived key).
- The signature is an HMAC-SHA256 with a key derived from the [library key](#context-and-key), and it is compared in constant time. If the key changes, every link stops working.
- The path is compared as CodeIgniter sees it, without the domain, the port, the base directory or `index.php`: a link is still valid if the domain or the port changes.
- `Crud::routes()` routes are strict: after the base only `operation` or `operation/id` is accepted, and any extra segment is a 404.

Limits:

- Whoever has the link can enter until it expires: a single link cannot be revoked. If you need to withdraw access, limit the CRUD with a column (`where('shared', 1)`) and clear it; to revoke all of them, change the key (the masked codes change too).
- In CRUD pages the query string is not signed: the visitor can search, filter and sort everything `where()` allows.
- It is meant for read-only views: combine it with `readOnly()` and remove (`unset*()`) whatever the visitor should not do.
- The link may end up in the browser history, in server logs or be forwarded: use HTTPS and short lifetimes for sensitive data. With `$ttl = null`, it never expires.

### Troubleshooting signed URLs

| Symptom | Cause | Fix |
|---|---|---|
| "This link is not valid." with a link just created | The path is not the page's (the masked id is missing, another prefix) or it was signed with a different configuration (key) from the CRUD's | Create it with `signedUrl()` or `Crud::maskId()`, and pass `$config` if the CRUD has its own. Check it with `kpcrudy:checkurl` |
| "This link is not valid." after editing it, or only from some email clients | It was modified or cut: the id, `kp_exp`, `kp_sig`, `&amp;` instead of `&` | Copy the whole link; it cannot be extended by hand, create a new one |
| "This link has expired." | `kp_exp` is in the past (navigation inherits the entry link's expiry) | Create a new link, with a longer `$ttl` if needed |
| No link works after a deploy, or they work on one server and not on another | The key is different (see [Context and key](#context-and-key)) | The same key in every environment (`$maskKey`) |
| The signed list shows everything | `where()` is missing: a filter or a search in the link limits nothing | Limit what the visitor can see with `where()` |
| The signed list shows nothing | `where()` is too strict (e.g. future dates) or the link carries a search (`?q=`) | Review the `where()`; create the link without parameters |
| The detail view has no back button, or an error shows a 404 instead of going back to the list | Expected behaviour: a detail view's link gives no access to the list | If the visitor must see the list, share the list's link |
| Other operations open without a signature | `requireSignature()` with a list: the rest stays open | `requireSignature()` without parameters, `unset*()` or an authentication filter |
| Your custom page says `'invalid'` with a link made with `$lockQuery = false` | By default `checkSignature()` requires the parameters to be signed | `Crud::checkSignature(allowFreeQuery: true)` |
| `kpcrudy:checkurl` says `signed by a CRUD while browsing` | The link was copied from a page of the signed list (page 2, a details page): it is correct, but only that CRUD accepts it | Normal; it expires together with the entry link |
| Exception "l'operació '...' no existeix" (the operation does not exist) | A misspelt name in `requireSignature()` | Use the names in [`ID_OPERATIONS` and `OPERATIONS`](#crudid_operations--crudoperations), or `list` |

See also the [Signed links](howto_EN.md#share-a-read-only-list-without-login) recipes and the `kpcrudy:signurl` and `kpcrudy:checkurl` commands in [commands_EN.md](commands_EN.md).

---

## Spark commands

### `php spark kpcrudy:make [table] [options]`

Generates the controller method for a CRUD and its route from a table's structure. Without the table, it asks for all the values. It detects 1-N relations (foreign keys), N-N relations (junction tables), unique fields and the types that can be guessed from the name.

| Option | Default | Description |
|---|---|---|
| `--controller` | the table in *StudlyCase* | Class relative to `App\Controllers` (`Admin/Clients`). It is created if it does not exist; if it exists, the method is added to it |
| `--method` | `index` | Method name |
| `--route` | based on the controller | Route registered with `Crud::routes()` |
| `--filter` | none | Route filter |
| `--model` | no | Also generates a model in `app/Models` and uses `setModel()` |
| `--dry-run` | no | Shows the code without writing anything |
| `--force` | no | Replaces the method and the model if they already exist |

```bash
php spark kpcrudy:make products --controller Admin/Shop --method products --route admin/products --filter auth:admin
```

Before writing any file it checks everything, and stops without touching anything if something is wrong: tables or columns whose names are not only letters, numbers and `_` (they cannot be safely put in the generated code), a method that starts with `_` or that the base controller already has (`initController`...), a route that already points to another controller or a model with the same name that belongs to another table. `--dry-run` runs these checks too.

See the recipe [Generate a table's CRUD with a command](howto_EN.md#generate-a-tables-crud-with-a-command).

### `php spark kpcrudy:demo <install|status|reset|uninstall>`

Installs and uninstalls the demo application. See [Install the demo in your project](../README_EN.md#installing-the-demo-in-your-project).

### `php spark kpcrudy:maskid <context> <id> [<id>...] [--config Class]`

Shows the [masked](#masked-ids) code of one or more ids, as it appears in the URLs of a CRUD with `maskIds()`. The context is the table name, or the value passed to `maskIds(context: ...)`.

### `php spark kpcrudy:unmaskid <context> <code> [<code>...] [--config Class]`

Shows the real id of one or more masked codes: use it to find which record a URL or a log line refers to. Codes that are not valid for this context are flagged in the table, and the command ends with exit code 1.

| Option | Default | Description |
|---|---|---|
| `--config` | `app/Config/Kpcrudy.php` | Config class passed to the CRUD, if it is a different one (e.g. `"Kpcrudy\Demo\Config\CrudConfig"` for the demo, which has its own key) |

```bash
php spark kpcrudy:maskid invoices 5 6 7
php spark kpcrudy:unmaskid invoices Xk3f9QaLm2PwE7

# Demo
php spark kpcrudy:unmaskid kpdemo_events <code> --config "Kpcrudy\Demo\Config\CrudConfig"
```

```text
Context: invoices
+----------------+----+
| Masked code    | Id |
+----------------+----+
| Xk3f9QaLm2PwE7 | 5  |
+----------------+----+
```

See the recipe [Find which record a masked code refers to](howto_EN.md#find-which-record-a-masked-code-refers-to).

### `php spark kpcrudy:signurl <url>` · `php spark kpcrudy:checkurl <url>`

Create and check [signed URLs](#signed-urls) from the command line: to send a link by hand or to find out why a link does not work (valid, expired, modified, and when it expires). All the options are in [commands_EN.md](commands_EN.md).

### `php spark kpcrudy:publish <config|theme|lang> [name]`

Copies the configuration (`app/Config/Kpcrudy.php`, with every option), a theme (`theme w3css --as intranet`, optionally only some templates with `--only`) or the texts of a language (`lang ca`) into the project, to adapt them without touching the library. It overwrites nothing without `--force`, and with `--dry-run` it only shows what it would do. See [kpcrudy:publish](commands_EN.md#kpcrudypublish).

---

## Constants

### `Crud::ID_OPERATIONS` · `Crud::OPERATIONS`

URL words the CRUD interprets as an operation: `ID_OPERATIONS` are followed by an id (`edit`, `show`, `delete`, `restore`, `purge`); `OPERATIONS` are not (`add`, `trash`, `export`, `import`, `bulk`, `emptytrash`).

```php
// Find out whether we are on the main list
$segments = explode('/', uri_string());
$isList   = ! array_intersect($segments, [...Crud::ID_OPERATIONS, ...Crud::OPERATIONS]);
```

---

## Global configuration

To change the configuration of all the CRUDs in the project, create `app/Config/Kpcrudy.php`:

```php
<?php

namespace Config;

class Kpcrudy extends \Kpcrudy\Config\Kpcrudy
{
    public string|false $layout = 'layouts/admin';
    public int $perPage         = 10;
}
```

`php spark kpcrudy:publish config` creates it with every option, its default value and its comment, as a reference: change only what you need. The options that are not in it take the library's value (see [kpcrudy:publish](commands_EN.md#kpcrudypublish)).

| Property | Default | Description |
|---|---|---|
| `string $theme` | `'bootstrap5'` | Default theme |
| `array $themes` | `[]` | Additional themes: `['name' => directory]` or `['name' => ['path' => directory, 'parent' => 'w3css']]` to extend another theme |
| `string\|false $layout` | `'default'` | Default layout (see `setLayout`) |
| `int $perPage` | `25` | Records per page |
| `array $perPageOptions` | `[10, 25, 50, 100]` | Options of the "Per page" selector |
| `string $dateFormat` | `'d/m/Y'` | Date format in the list, the detail view and the CSV |
| `string $datetimeFormat` | `'d/m/Y H:i'` | Format of dates with time |
| `int $listTruncate` | `60` | Maximum characters of texts in the list (`0` = no limit) |
| `bool $inferRules` | `true` | Infer validation rules from the table structure |
| `string $csvDelimiter` | `';'` | CSV delimiter (`;` is read correctly by Excel in Catalan and Spanish) |
| `bool $csvBom` | `true` | Add the UTF-8 BOM so that Excel detects accented characters |
| `int $exportMaxRows` | `100000` | Maximum rows of a CSV export (`0` = no limit). If the filtered list has more, the user is asked to filter it |
| `bool $checkRoutes` | `true` | In development, check that no project route conflicts with those of `Crud::routes()` (see [`Crud::checkRoutes()`](#crudcheckroutes-void-static)). Disable it only if you have a route that replaces a CRUD operation on purpose |
| `bool $csrfProtection` | `true` | Verify the CSRF token of the CRUD's POST requests when CodeIgniter's `csrf` filter (or a subclass of `CodeIgniter\Filters\CSRF`) is not active for the request. Disable it only if the project protects requests in another way |
| `bool $maskIds` | `false` | [Mask the ids](#masked-ids) in the URLs of every CRUD (`edit/Xk3f9...` instead of `edit/5`). Each CRUD can change it with `maskIds()` |
| `string $maskKey` | `''` | The library key: it masks the ids and signs [signed URLs](#signed-urls). It must be at least 32 characters long (e.g. `bin2hex(random_bytes(32))`); if it is shorter, an exception is thrown. If empty, CodeIgniter's encryption key (`encryption.key`) and, if there is none, a random key stored in `writable/kpcrudy/mask.key`. If it changes, every code changes and old links stop working, signed ones too |
| `string $idMasker` | `Kpcrudy\DefaultIdMasker` | Class that masks the ids; it must implement [`Kpcrudy\IdMasker`](#interface-kpcrudyidmasker) |
| `array $sensitiveFields` | `password`, `passwd`, `pass`, `pwd`, `contrasenya`, `contrasena`, `secret`, `token`, `salt`, `hash`, `apikey`, `otp`, `totp`, `2fa` | Names of [sensitive fields](#columns-and-fields), which are hidden everywhere by default |
| `int $importMaxRows` | `1000` | Maximum rows of an import file (can be changed per CRUD with `enableImport()`) |
| `int $importMaxSize` | `2048` | Maximum size of an import file, in KB |
| `string $uploadPath` | `FCPATH . 'uploads'` | Base directory of uploaded files |
| `string $uploadUrl` | `'uploads'` | Base URL of the files, relative to `base_url()` |
| `int $uploadMaxSize` | `2048` | Default maximum size, in KB |
| `array $uploadAllowed` | images, PDF, office documents, `txt`, `csv`, `zip` | Default allowed extensions for `upload` fields |
| `array $imageAllowed` | `jpg`, `jpeg`, `png`, `gif`, `webp` | Default allowed extensions for `image` fields |
| `string $bootstrapCss` | Bootstrap 5.3.3 (jsDelivr) | CSS loaded by the `default` layout |
| `string $bootstrapJs` | Bootstrap 5.3.3 (jsDelivr) | JS loaded by the `default` layout |
| `string $iconsCss` | Bootstrap Icons 1.11.3 (jsDelivr) | Icons loaded by the `default` layout |

With `$inferRules` enabled, these rules are inferred (only for fields without their own rules):

| Structure | Rule |
|---|---|
| `NOT NULL` without a default value | `required` (also for passwords when adding; not when editing) |
| `VARCHAR(n)` (text, email, url) | `max_length[n]` |
| integer | `integer` |
| decimal | `decimal` |
| date | `valid_date[Y-m-d]` |
| date and time | `valid_date[Y-m-d H:i:s]` |
| time | `regex_match` for `HH:MM` or `HH:MM:SS` |
| color | `regex_match` for `#rrggbb` |
| name containing `email` | `valid_email` |
| name `url`, `web`, `website` | `valid_url_strict[http,https]` |

---

## Templates

A theme's templates are PHP files. Inside a template, `$this` is a `Kpcrudy\Renderer`. The variables each template receives are documented in its header, in [src/Views/bootstrap5](../src/Views/bootstrap5). The guide [new_theme_EN.md](new_theme_EN.md) explains how to create a theme.

### `Renderer::render(string $view, array $data = []): string`

Renders another template of the theme (or of the parent theme, or of `bootstrap5`, if the current theme does not have it).

```php
<?= $this->render('partials/flash', ['flash' => $flash]) ?>
<?= $this->render('partials/button', ['action' => $action, 'compact' => true]) ?>
```

### `Renderer::renderParent(string $view, array $data = []): string`

Renders **the same template from the parent theme**: the version that would be used if the current theme did not have it. It is used to extend a template without copying it. It works across several levels (a theme → its parent → `bootstrap5`).

```php
<!-- partials/assets.php of a theme: adds CSS and keeps the parent's JavaScript -->
<style>
    .kpcrudy .kp-yes { background: #009688; color: #fff; }
</style>
<?= $this->renderParent('partials/assets') ?>
```

### `Renderer::locate(string $view): string`

Path of the file that would be used for a template.

```php
<!-- <?= esc($this->locate('list')) ?> -->
```

### `Renderer::hidden(array $values, string $prefix = ''): string`

Converts an array, even a nested one, into hidden form fields. It is used to keep the list state in a GET form.

```php
<form method="get" action="<?= esc($listUrl, 'attr') ?>">
    <?= $this->hidden(['sort' => 'name', 'f' => ['status' => 'paid']]) ?>
    <!-- sort=name and f[status]=paid -->
</form>
```

### `Renderer::attrs(array $attributes): string`

Generates escaped HTML attributes. `true` adds the attribute without a value, and `false` or `null` remove it.

```php
<input<?= $this->attrs(['type' => 'text', 'name' => 'q', 'required' => true, 'disabled' => false]) ?>>
<!-- <input type="text" name="q" required> -->
```
