# Demo screenshots

**English** · [Català](README_CA.md)

A quick tour of the kpcrudy demo application. All the management screens are automatic CRUDs: each one comes from a single controller method. To install the demo in your project, see [Installing the demo in your project](../README_EN.md#installing-the-demo-in-your-project).

## Login and home

**Login.** No password is needed: you choose the user with a click. Administrators see the whole application; users see the agenda, their registrations and their profile.

![Login page](01-login.png)

**Home.** Statistics, upcoming events and the demo map showing what each page demonstrates.

![Home page](02-inici.png)

## Administration

**Users.** List with photo, calculated columns (full name, registrations), N-N relation (interests), booleans, trash, per-row actions, bulk actions and CSV export.

![User list](03-usuaris.png)

**Events with filters.** Filters by list (category, status) and by range (date, price), which can be combined with search and sorting. The occupancy column is calculated for each row.

![Filtered events](04-esdeveniments-filtres.png)

**Detail page.** Resolved relations (category, creator), N-N attendee relation and custom actions: *Attendees*, *Duplicate*.

![Event detail page](05-esdeveniment-fitxa.png)

**Form.** Field types inferred from the database (text, relation drop-down, date and time, number, decimal, enum) and image upload with preview.

![Edit form](06-esdeveniment-formulari.png)

**Validation.** CodeIgniter rules (`is_unique` with `{id}`, `min_length`...) and required fields inferred from the table. Errors are shown next to each field.

![Form with validation errors](10-validacio.png)

**CSV import: uploading the file.** Enabled with `enableImport()`. You choose whether each row is always added or updates the existing record (here, by username), and what happens to rows with errors. On the right are the expected columns and a button to download a template.

![Uploading a CSV](15-importacio-pujada.png)

**CSV import: preview.** How the columns have been matched (`Notes` is ignored), how many rows are new, how many will be updated and which have errors, with the reason for each line. Passwords are not shown.

![Import preview](16-importacio-previsualitzacio.png)

**CSV import: result.** Summary and report of the skipped rows. The last one is an email repeated within the same file, which is only detected when importing.

![Import result](17-importacio-resultat.png)

**Trash.** Using CodeIgniter's *soft deletes*: restore, delete permanently or empty the trash.

![Event trash](07-paperera.png)

**Categories.** CRUD built from a CodeIgniter model, with a color field and no trash.

![Categories](08-categories.png)

**Attendance.** CRUD on the join table between users and events, opened already filtered by an event from its detail page (`?f[event_id]=...`).

![Attendance at an event](09-assistencia.png)

**Code for each page.** The *Code for this page* button shows the controller method that generates the CRUD and, in another tab, the features it demonstrates.

![Code panel](11-panell-codi.png)

## User area

**Agenda.** Read-only, and only published and upcoming events. The *Join* button only appears if there are places left and you are not registered.

![Agenda](12-agenda.png)

**My registrations.** Users only see their own. Upcoming ones can be cancelled one by one or in bulk.

![My registrations](13-meves-inscripcions.png)

**My profile.** Single-record CRUD with a custom theme for the detail page.

![Profile](14-perfil.png)

## Other themes

The same CRUD with the `w3css` and `tailwind` themes included in the library (`$crud->setTheme('w3css')`). The guide [docs/new_theme_EN.md](../docs/new_theme_EN.md) explains how they are built and how to create your own.

**W3.CSS.** List with the filters open and form with validation errors.

![W3.CSS theme: list](18-tema-w3css-llistat.png)

![W3.CSS theme: form](19-tema-w3css-formulari.png)

**Tailwind CSS.** The same pages.

![Tailwind theme: list](20-tema-tailwind-llistat.png)

![Tailwind theme: form](21-tema-tailwind-formulari.png)
