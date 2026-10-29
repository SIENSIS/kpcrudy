# Changelog

All notable changes to kpcrudy. The format follows [Keep a Changelog](https://keepachangelog.com/).
Documentation is in English and Catalan: [README_EN.md](README_EN.md) · [README_CA.md](README_CA.md).

## [Unreleased]

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

The library is installed from GitHub (`repositories` entry in `composer.json`). Recommended next step:
tag releases (`v1.0.0`, ...) following semantic versioning and submit the repository to
[Packagist](https://packagist.org/packages/submit), with the GitHub hook so new tags are published
automatically. Then projects can `composer require siensis/kpcrudy:^1.0` and get updates with
`composer update`.
