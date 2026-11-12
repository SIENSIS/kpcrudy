# Changelog

All notable changes to kpcrudy. The format follows [Keep a Changelog](https://keepachangelog.com/).
Documentation is in English and Catalan: [README_EN.md](README_EN.md) · [README_CA.md](README_CA.md).

## [Unreleased]

### Added

- **AJAX search for `setRelationNN()`**: options `ajax`, `minChars` and `limit` (as `setRelation()`): a search box with
  the chosen values as removable tags instead of a multiple select with every record; suggestions from the built-in
  `lookup` operation with the relation's `where` and `order`, leaving out the chosen values (`exclude[]`). Saved ids are
  checked with one query and an invalid one is a field error; links outside the `where` are kept; the filter is a search
  box; the CSV import matches values with a query. New option `max` (most values, with or without `ajax`).
  `describe()` reports `ajax` for N-N relations. New texts `lookupRemove` and `tooManyValues` in every language.

### Changed

- The CSV import of relations with `ajax` (1-N and N-N) matches ids and texts with a query instead of loading every
  record of the related table.

### Documentation

- **`tree()`**: how it works with translated `label` columns, `callbackColumn()`, search and filters, `where()` scopes,
  the trash, pagination, a parent field with options of your own, export and CSV import.

### Fixed

- **`tree()` and CSV import**: an imported parent that would make a cycle (the record itself or one of its descendants,
  also across two rows of the same file) is refused like in the form.

## [1.3.0] - 2026-11-12

### Added

- **`where(Closure)`**: `where(fn (BaseBuilder $builder, Crud $crud) => …)` adds conditions of your own (`OR`, subqueries
  on other tables, conditions that depend on the user) to the CRUD's scope: list, search, filters, sorting, counts, export,
  detail view, edit, delete, bulk actions, trash and custom operations. Each closure goes in its own brackets (joined with
  `AND`), so an `OR` cannot widen the scope; a closure that adds nothing is ignored. A saved record must still be inside
  the scope, otherwise the save is rolled back. Same contract as kpapy 1.2. `describe()` reports `whereCallbacks`.
- **`attrs` option of `setTranslations()`**: HTML attributes for the inputs of a translated field in every language tab
  (`[field => [...]]` or `[field => fn (string $locale, string $field): array]`), e.g. to turn a translated body into a
  rich-text editor without taking it out of the language tabs. They are added to the library's attributes, never replace
  them. Replaces the undocumented `fieldType($field, 'translation', ['attrs' => …])`, which still works.

## [1.2.0] - 2026-11-09

### Added

- **Time zones for `datetime` fields**: `Config::$timezone` / `setTimezone()` show and edit `datetime` fields in a time zone
  (list, detail view, form, filters, CSV export and import) while the database keeps `Config::$storageTimezone` (PHP's by
  default, usually UTC). Field option `'timezone' => false` leaves a field out. `null` (default): no conversion, as before.

## [1.1.0] - 2026-11-09

### Added

- **Upload handlers**: `Kpcrudy\Storage\UploadHandler` (`store()`, `delete()`, `url()`) stores the files of `upload`/`image`
  fields through the project's own storage. `Config::$uploadHandler` (all CRUDs) or the field option `handler`;
  `UploadRejectedException` refuses a file with a message. `url()` may return `null` (private files: only the name is shown).
- **External assets**: `Config::$assetsMode = 'external'` links `kpcrudy.css`/`kpcrudy.js` from `$assetsUrl` (with a
  content hash) instead of printing nonce'd `<style>`/`<script>` blocks; `php spark kpcrudy:publish assets` copies them
  to `$assetsPath`. The CSS and JS now live in `src/Assets/` and the themes include them with `Renderer::asset()`.
- **Translation fallbacks**: `setTranslations(..., ['fallbacks' => ['ca']])` or per locale
  (`['es' => ['ca'], '*' => ['ca']]`) for the list, search, sorting, filters, export and detail view.
- **Translated fields in CSV import** (one column per locale: `title:ca`, `Title (Català)`, `Title (CA)`; validated like the
  form) and `exportTranslations()` to export one column per locale.
- **Responsive lists**: `columnPriority()` (hide columns below 576/768/992 px), `rowActionsMenu()` (row actions in a "⋯"
  `<details>` menu, no JavaScript needed) and `mobileCards()` (rows as cards on phones), for every theme.
- **AJAX relations**: `setRelation(..., ['ajax' => true, 'minChars' => 2, 'limit' => 20])` shows a search box instead of a
  dropdown in forms and filters, answered by the built-in GET operation `base/lookup` (only for users who can use the
  field); values are validated against the related table and its `where`.
- **Trees**: `tree($parentField, ['order', 'label', 'maxDepth'])` lists self-referencing tables in tree order with
  indentation, refuses parents that would create a cycle and shows the parent dropdown in tree order.
- **Custom search and filters**: `searchCallback()` adds conditions (e.g. subqueries on other tables) to the search box;
  `addFilter()` adds dropdown or text filters with a condition of your own.
- **Describing a CRUD**: `Crud::capture()` and `Crud::captureController()` return the CRUD a controller builds without
  running any operation; `describe()` returns its configuration (operations, permissions, fields, filters, relations...).
- `setRequest()`; a CRUD can be built from the CLI (`spark`, queues): it uses an empty GET request when the `request`
  service is not a web request.
- Every command (`kpcrudy:make`, `kpcrudy:publish`, `kpcrudy:signurl`...) reads its options from `command()` parameters as
  well as from the command line.
- New texts in the six languages (`moreActions`, `lookupPlaceholder`, `lookupEmpty`, `treeCycle` and exceptions).

### Changed

- `Crud::OPERATIONS` includes `lookup` (the AJAX search of relations): a route of your own can no longer end in `lookup`,
  and `lookup` is no longer a valid `addOperation()` key.
- The list cells carry `data-label` and the column classes of `columnPriority()`; a theme of your own that copies
  `list.php` keeps working, but needs these attributes to support the new options.

### Fixed

- The CSV export of CRUDs with N-N relations or translated fields on databases with a table prefix (`DBPrefix`): the
  queries of each chunk no longer lose the table aliases.

## [1.0.0] - 2026-11-03

### Added

- **Central permissions**: `Kpcrudy\PermissionResolver` interface, `Config\Kpcrudy::$permissionResolver`
  and `$denyByDefault`, `setPermissionResolver()`, `setResource()` / `getResource()` and `isAllowed()`.
  The resolver has the last word when it denies; `null` leaves the decision to `setPermission()`.
- `permission` option on `addAction()`, `addHeaderAction()` and `addBulkAction()` (bulk actions only
  receive the ids the user is allowed to act on).
- **Custom operations** with `addOperation()`, served from the CRUD's own route (`base/{key}/{id}`) with
  the same CSRF, masked ids, `where()`, permission and signature checks as the built-in operations;
  `backToList()` and `page()` to answer from them. `requireSignature()` accepts their keys.
- **`multiselect`** field type (JSON or comma-separated storage), with filters and CSV import.
- **Translated fields** stored in a child table with `setTranslations()` (one tab per language in the
  form, list locale for list/search/sort/export) and `postedTranslations()`.
- **`afterWrite()`** hook, called after every insert, update, delete and restore (form, bulk actions, import).
- **Layout integration**: `setLayout($view, $section)` / `Config::$layoutSection` to render the CRUD in a
  section of the project's layout, `setLayoutData()`, `showTitle()` / `Config::$showTitle`, `getTitle()`,
  and `Config::$flashKeys` to send the CRUD's messages to the project's session keys.
- **Content-Security-Policy** support: `Renderer::nonce()`, nonces on every `<script>`/`<style>`, no inline
  handlers or `style` attributes in any theme (`data-kp-*` attributes instead), and the cancelable
  `kpcrudy:confirm` JavaScript event to replace `window.confirm()` with a custom dialog.
- `kpcrudy:make` can generate into modules and packages: `--namespace`, `--path`, `--routes`, `--extends`
  and `--as` (route name).
- `hideFields()`: hides fields from the list, details, forms, search, filters, sorting, export and
  import in one call (they are not saved even if posted).
- `permissionCatalogue()` (every operation a CRUD can ask the permissions about) and
  `Crud::IMPLIED_PERMISSIONS` / `Crud::impliedPermissions()`, to build or check permission catalogues.
- The permission resolver's answers without a record are cached per CRUD instance.
- Documentation of the "own records" patterns (`where()` vs a per-record `setPermission()`) and of
  permission names for Shield (`<module>-<resource>.<operation>`).
- Exception messages are translated (`Kpcrudy.exceptions.*` in the six languages).
- Demo: editor role with a central permission resolver, custom operations instead of custom routes,
  multiselect (event audience), translated category texts, `afterWrite()`, `flashKeys`, named routes and
  a strict CSP enabled in the demo project. PHPUnit tests in `demo/tests/unit/Kpcrudy`.

### Changed

- `Crud::routes()` only gives the `as` name to the base route, so `route_to()` / `url_to()` work (before,
  the second route with the same name was silently dropped by CodeIgniter).
- Demo tables changed (`kpdemo_users.role` has `editor`, `kpdemo_events.audience`, new
  `kpdemo_category_translations`, `kpdemo_categories.description` moved to the translations):
  run `php spark kpcrudy:demo reset` after updating.

## Publishing

Published on [Packagist](https://packagist.org/packages/siensis/kpcrudy) from the Git tags
(`vX.Y.Z`, semantic versioning). Install with `composer require siensis/kpcrudy` (`^1.0`) and update
with `composer update siensis/kpcrudy`. The Composer package does not include the test application
(`demo/`) nor the screenshots (`.gitattributes`); the documentation (`docs/`) is included.

[1.3.0]: https://github.com/SIENSIS/kpcrudy/compare/v1.2.0...v1.3.0
[1.2.0]: https://github.com/SIENSIS/kpcrudy/compare/v1.1.0...v1.2.0
[1.1.0]: https://github.com/SIENSIS/kpcrudy/compare/v1.0.0...v1.1.0
[1.0.0]: https://github.com/SIENSIS/kpcrudy/releases/tag/v1.0.0
