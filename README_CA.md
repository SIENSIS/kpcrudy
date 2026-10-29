# kpcrudy

[English](README_EN.md) · **Català**

Generador automàtic de CRUD per a **CodeIgniter 4** (4.4 o superior, PHP 8.1+), a l'estil de GroceryCRUD.

Crees una instància, li indiques la taula (o el model) i què vols mostrar, i la llibreria s'encarrega de tota la interacció amb l'usuari. Només cal una ruta i un mètode al controlador.

Funcionalitats:

- Llistat amb **paginació**, **cerca**, **ordenació per columna** i **filtres**
- **Exportació a CSV** del llistat filtrat o dels registres seleccionats
- **Importació des de CSV** amb previsualització: afegir o actualitzar registres, amb la mateixa validació que el formulari
- **Paperera** amb els *soft deletes* de CodeIgniter: restaurar, esborrar definitivament, buidar
- **Fitxa de detall** de cada registre
- **Enllaços personalitzats** a cada fila amb l'`id` (o qualsevol camp), tants com calgui
- Formularis d'alta i edició amb **validació** (regles de CodeIgniter i deduïdes de la taula)
- **Relacions 1-N** (desplegable) i **N-N** (selecció múltiple amb taula intermèdia)
- Tipus de camp: text, textarea, número, decimal, email, url, data, data i hora, hora, color, booleà, enum/select, contrasenya, ocult, només lectura, **pujada de fitxers i imatges**
- **Callbacks** de columna, de camp i d'exportació, i **hooks** abans i després de desar o esborrar
- **Accions massives** (esborrar, restaurar, exportar i les teves pròpies)
- **Operacions pròpies** a la mateixa ruta del CRUD (`addOperation()`), amb el mateix CSRF, ids emmascarats, permisos i signatures que les del CRUD
- **Permisos centralitzats**: una sola classe `PermissionResolver` pot decidir totes les operacions de tots els CRUD (p. ex. a partir de CodeIgniter Shield), i l'opció `permission` a les accions
- Camps **multiselecció** (diversos valors en una columna) i **camps traduïts** desats en una taula filla, amb una pestanya per idioma
- **Permisos** per operació, globals o registre a registre
- **Identificadors emmascarats** a les URL (`edit/Xk3f9...` en lloc d'`edit/5`), opcionals, per CRUD o per a tot el projecte
- **URL signades amb caducitat** per compartir pàgines de consulta sense iniciar sessió (`requireSignature()`): el visitant hi pot cercar i navegar, però no pot canviar el camí ni allargar la caducitat
- **Plantilles intercanviables** (tema Bootstrap 5 per defecte) i layout configurable, també com a secció del layout del projecte
- Funciona amb una **Content-Security-Policy** estricta (nonces de CodeIgniter, sense gestors d'esdeveniments ni atributs `style` en línia)
- Textos en **català, castellà, anglès, francès, portuguès i alemany**

---

## Documentació

- [docs/functions_CA.md](docs/functions_CA.md): referència de totes les funcions i propietats públiques, amb exemples.
- [docs/howto_CA.md](docs/howto_CA.md): receptes per a casos habituals (contrasenyes, enllaços amb l'id, id emmascarat, permisos, relacions, fitxers, integració...).
- [docs/commands_CA.md](docs/commands_CA.md): referència de les comandes de `spark` (`kpcrudy:make`, `kpcrudy:demo`, `kpcrudy:maskid`, `kpcrudy:unmaskid`, `kpcrudy:signurl`, `kpcrudy:checkurl`, `kpcrudy:publish`), amb sortides reals i receptes.
- [docs/new_theme_CA.md](docs/new_theme_CA.md): com crear un tema, amb els exemples de W3.CSS i Tailwind CSS.

## Demo

La llibreria inclou una aplicació completa de gestió d'esdeveniments que fa servir totes les funcionalitats. S'instal·la al teu projecte amb `php spark kpcrudy:demo install` i es desinstal·la sense deixar rastre. Els passos són a [Instal·lar la demo al projecte](#installar-la-demo-al-projecte), i a [screenshots](screenshots/README_CA.md) en tens un recorregut amb captures.

![Llistat d'esdeveniments de la demo](screenshots/04-esdeveniments-filtres.png)

---

## Instal·lació

La llibreria encara no és a Packagist (es recomana publicar-la-hi, amb versions etiquetades: així `composer require siensis/kpcrudy` funcionaria sense l'entrada `repositories`; vegeu [CHANGELOG.md](CHANGELOG.md)). Afegeix el repositori al `composer.json` del projecte:

```json
"repositories": [
    { "type": "vcs", "url": "https://github.com/SIENSIS/kpcrudy" }
]
```

i instal·la-la:

```bash
composer require siensis/kpcrudy:dev-main
```

Per desenvolupar-la en local pots fer servir un repositori de tipus `path`:

```json
"repositories": [
    { "type": "path", "url": "../kpcrudy", "options": { "symlink": true } }
]
```

CodeIgniter detecta sol el namespace `Kpcrudy` (vistes, idiomes i configuració) gràcies a l'autoload de Composer.

> Els missatges de validació (p. ex. "The Nom field is required") són de CodeIgniter, no de kpcrudy. El paquet `composer require codeigniter4/translations` els tradueix a molts idiomes (castellà, francès, portuguès, alemany...), però **no al català**: per tenir-los en català, crea `app/Language/ca/Validation.php` amb les traduccions (pots partir de `vendor/codeigniter4/translations/Language/es/Validation.php`).

---

## Instal·lar la demo al projecte

La demo és una aplicació de gestió d'esdeveniments (usuaris, categories, esdeveniments i inscripcions) on cada pantalla és un CRUD de kpcrudy. Cada pàgina té un botó **Code for this page** que mostra el codi del controlador que la genera i les funcionalitats que s'hi veuen. La demo és en anglès (dades i textos), per poder-la integrar en qualsevol projecte.

Ve dins de la llibreria (a `src/Demo`) i s'instal·la al projecte on ja tens kpcrudy amb una comanda de `spark`. **No copia ni modifica cap fitxer del projecte**, i en desinstal·lar-la el projecte queda exactament com estava.

### Requisits

- kpcrudy instal·lat al projecte (vegeu [Instal·lació](#installació)).
- La base de dades configurada a `.env` (grup `default`) i accessible.
- Permís d'escriptura a `public/` i `writable/`.
- Opcional: l'extensió `gd` de PHP, per generar les imatges d'exemple. Sense GD, la demo s'instal·la igualment, però sense avatars ni cartells.

### Pas 1. Comprova que la comanda hi és

```bash
php spark list
```

A la llista hi ha d'aparèixer el grup **Kpcrudy** amb set comandes: `kpcrudy:demo` (la que ara cal), `kpcrudy:make` (generar un CRUD a partir d'una taula), `kpcrudy:maskid` i `kpcrudy:unmaskid` (fer i desfer els identificadors emmascarats), `kpcrudy:signurl` i `kpcrudy:checkurl` (crear i comprovar URL signades), i `kpcrudy:publish` (copiar al projecte la configuració, un tema o els textos d'un idioma per adaptar-los). Totes es descriuen, amb exemples i sortides reals, a la [referència de comandes](docs/commands_CA.md). Per veure l'ajuda de la demo:

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

### Pas 2. Instal·la la demo

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

Si alguna cosa falla a mig camí, la comanda desfà el que hagi creat i mostra l'error.

### Pas 3. Obre-la

Arrenca el projecte com sempre (amb Apache o amb `php spark serve`) i obre l'adreça que ha mostrat la comanda:

```bash
php spark serve
# http://localhost:8080/kpcrudy-demo
```

La pàgina d'entrada mostra la llista d'usuaris de prova: **fes clic en un per entrar**, sense contrasenya.

- `admin` o `john.smith`: administradors, amb accés a totes les pàgines.
- `alice` o qualsevol altre: usuaris, amb l'agenda, les seves inscripcions i el seu perfil.

### Què crea la instal·lació

| Què | On |
|---|---|
| 5 taules amb dades de prova (25 usuaris, 6 categories, 36 esdeveniments, 353 inscripcions) | `kpdemo_categories`, `kpdemo_users`, `kpdemo_user_interests`, `kpdemo_events`, `kpdemo_attendances` |
| Avatars i cartells d'exemple, i els fitxers que pugis des de la demo | `public/uploads/kpcrudy-demo/` |
| Fitxer de marca que activa la demo | `writable/kpcrudy-demo.json` |
| Pàgines de la demo | `/kpcrudy-demo/...` |

Els prefixos `kpdemo_` i `kpcrudy-demo` eviten qualsevol col·lisió amb el projecte: no es toca cap taula pròpia (per exemple, una taula `users` o la de Shield), cap ruta, cap fitxer ni el login del projecte, perquè la demo fa servir una clau de sessió pròpia.

**Com s'activen les rutes.** CodeIgniter descobreix sol el fitxer `src/Config/Routes.php` de la llibreria (és el mecanisme de mòduls, actiu per defecte). Aquest fitxer només registra les rutes de la demo si existeix `writable/kpcrudy-demo.json`; sense la demo instal·lada, no afegeix res. Amb `CI_ENVIRONMENT = production` tampoc no les registra (vegeu **Producció** més avall).

### Consultar l'estat

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

### Tornar a les dades inicials

Després de fer proves (esborrar, editar, pujar imatges...), pots tornar a l'estat inicial:

```bash
php spark kpcrudy:demo reset          # demana confirmació
php spark kpcrudy:demo reset --yes    # sense confirmació
```

També es pot fer des de la mateixa demo: a la pàgina d'inici, un administrador té el botó **Reset data** (excepte en producció, on aquest botó i la seva ruta no existeixen). Si el reset falla, la demo continua instal·lada i es pot tornar a provar.

### Desinstal·lar la demo

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

S'esborren les taules `kpdemo_*`, la carpeta `public/uploads/kpcrudy-demo` (i `public/uploads`, si l'havia creat la demo i ha quedat buida) i el fitxer de marca, amb la qual cosa les rutes `/kpcrudy-demo` deixen d'existir. Amb `--yes` no es demana confirmació, per exemple en un script:

```bash
php spark kpcrudy:demo uninstall --yes
```

### Resum de comandes

| Comanda | Què fa |
|---|---|
| `php spark kpcrudy:demo install` | Crea les taules, les dades i les imatges, i activa `/kpcrudy-demo` |
| `php spark kpcrudy:demo install --force` | El mateix, però abans esborra les taules que hagin quedat d'una instal·lació anterior |
| `php spark kpcrudy:demo status` | Mostra si està instal·lada, els registres de cada taula i els fitxers |
| `php spark kpcrudy:demo reset [--yes]` | Torna a les dades inicials |
| `php spark kpcrudy:demo uninstall [--yes]` | Ho esborra tot i deixa el projecte com estava |
| `php spark help kpcrudy:demo` | Ajuda de la comanda |

Totes les opcions, les sortides i els errors de `kpcrudy:demo` (i de la resta de comandes) són a la [referència de comandes](docs/commands_CA.md#kpcrudydemo).

### Situacions especials

**"La demo ja està instal·lada".** `install` no es pot executar dues vegades. Per tornar a començar, fes servir `reset`.

**"Ja existeixen taules de la demo".** Han quedat taules d'una instal·lació anterior que no es va acabar o es va esborrar a mà (`status` ho avisa). Tens dues opcions:

```bash
php spark kpcrudy:demo uninstall          # les esborra
php spark kpcrudy:demo install --force    # les esborra i torna a instal·lar
```

**El projecte té desactivat el descobriment de rutes.** Si a `app/Config/Modules.php` l'array `$aliases` no inclou `'routes'`, la comanda ho detecta i t'indica la línia que has d'afegir a `app/Config/Routes.php`:

```php
\Kpcrudy\Demo\Demo::routes($routes);
```

La línia és inofensiva: només registra rutes mentre la demo està instal·lada (i mai en producció, llevat de `--allow-production`). Tot i així, treu-la quan desinstal·lis la demo; `uninstall` t'ho recorda.

**Migracions.** Les taules de la demo es creen amb migracions de CodeIgniter ([src/Demo/Database/Migrations](src/Demo/Database/Migrations)), però les executa l'instal·lador i **no queden a l'historial de migracions del projecte**. Així `php spark migrate`, `migrate:rollback` o `migrate:refresh` del projecte no les toquen mai, i desinstal·lar la demo no afecta les migracions pròpies.

**Idioma.** La demo (dades, textos i missatges de la comanda) és en **anglès**, per poder-la integrar en qualsevol projecte. Es mostra en anglès encara que el projecte faci servir un altre idioma, incloent-hi els textos de la llibreria.

**CSRF.** Les rutes de la demo verifiquen el token CSRF tant si el projecte té el filtre `csrf` actiu com si no (si ja és actiu, no el verifiquen dues vegades).

**URL, port i `index.php`.** Totes les adreces de la demo (enllaços, formularis, accions i imatges) es construeixen a cada petició amb `site_url()` i `base_url()`, i a la base de dades només es guarden rutes relatives. Per tant, la demo segueix funcionant si canvies `app.baseURL`, el port, la carpeta on és el projecte o `app.indexPage`, sense tornar-la a instal·lar. L'URL que mostren `install` i `status` es calcula amb la configuració del moment.

**Producció.** La demo és per aprendre i provar: entra sense contrasenya, i qualsevol pot entrar com a administrador. Per això, amb `CI_ENVIRONMENT = production`:

- `install` i `reset` es neguen a funcionar i ho avisen.
- Les rutes `/kpcrudy-demo` no es registren, encara que existeixi `writable/kpcrudy-demo.json` (per exemple, si s'ha pujat per error).
- El peu de la demo no mostra les versions de CodeIgniter i PHP.

Si de debò la vols en un entorn de producció (una màquina de proves, per exemple), instal·la-la amb `php spark kpcrudy:demo install --allow-production`: queda anotat al fitxer de marca i només llavors s'hi registren les rutes (sense el reset des del web). `reset` també necessita `--allow-production`. No la instal·lis mai en un servidor públic, i **no pugis `writable/kpcrudy-demo.json`** al repositori. Si ja hi és, `uninstall` ho deixa net.

Per saber què demostra cada pàgina i com està feta per dins, vegeu el [README de la demo](demo/README_CA.md).

---

## Ús ràpid

**Ruta** (`app/Config/Routes.php`):

```php
\Kpcrudy\Crud::routes($routes, 'admin/clients', 'Admin::clients');
```

Això registra `admin/clients` i `admin/clients/(:any)` per a GET i POST. També accepta opcions de ruta, com ara un filtre d'autenticació:

```php
\Kpcrudy\Crud::routes($routes, 'admin/clients', 'Admin::clients', ['filter' => 'session']);
```

**Controlador**:

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

Amb això ja tens llistat, alta, edició, detall, esborrat, cerca, exportació i, si la taula té el camp `deleted_at`, paperera.

### Generar-lo amb una comanda

La comanda `kpcrudy:make` llegeix l'estructura d'una taula i escriu el mètode del controlador i la ruta:

```bash
php spark kpcrudy:make                     # pregunta la taula, el controlador, el mètode, la ruta...
php spark kpcrudy:make clients             # amb els valors per defecte
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

El codi generat ja inclou:

- les **relacions 1-N**, a partir de les claus foranes, amb el camp que descriu millor l'altra taula (`name`, `title`, `{first_name} {last_name}`...);
- les **relacions N-N**, a partir de les taules intermèdies;
- les **etiquetes** (`displayAs`) i les **columnes** del llistat, sense els textos llargs;
- el **tipus de camp** dels que s'endevinen pel nom: `photo`/`avatar`/`image` (imatge), `document`/`file` (fitxer), `color`, contrasenyes;
- les **regles `is_unique`** dels camps amb índex únic.

| Opció | Descripció |
|---|---|
| `--controller` | Classe, relativa a `App\Controllers` (`Admin/Clients`). Si ja existeix, s'hi afegeix el mètode |
| `--method` | Nom del mètode (per defecte, `index`) |
| `--route` | Ruta (per defecte, a partir del controlador: `admin/clients`) |
| `--filter` | Filtre de la ruta (`auth`, `session`, `auth:admin`...) |
| `--model` | Genera també un model a `app/Models` (amb `allowedFields`, timestamps, soft deletes i regles) i fa servir `setModel()` |
| `--dry-run` | Mostra el codi sense escriure cap fitxer |
| `--force` | Reemplaça el mètode (i el model) si ja existeixen |

És un punt de partida: obre el controlador i adapta-hi el que calgui (etiquetes en el teu idioma, filtres, accions, permisos...). Més exemples, el mode interactiu i els errors habituals, a [kpcrudy:make](docs/commands_CA.md#kpcrudymake).

### URL que gestiona

| URL                         | Mètode | Operació                         |
|-----------------------------|--------|----------------------------------|
| `base`                      | GET    | Llistat                          |
| `base/trash`                | GET    | Paperera                         |
| `base/add`                  | GET/POST | Formulari d'alta               |
| `base/edit/{id}`            | GET/POST | Formulari d'edició             |
| `base/show/{id}`            | GET    | Fitxa de detall                  |
| `base/export`               | GET    | Exportació CSV                   |
| `base/import`               | GET/POST | Importació CSV (si s'ha activat amb `enableImport()`) |
| `base/delete/{id}`          | POST   | Esborrar (a la paperera si n'hi ha) |
| `base/restore/{id}`         | POST   | Restaurar de la paperera         |
| `base/purge/{id}`           | POST   | Esborrar definitivament          |
| `base/emptytrash`           | POST   | Buidar la paperera               |
| `base/bulk`                 | POST   | Acció massiva                    |

La URL base es dedueix sola. Si la ruta acaba amb una d'aquestes paraules (p. ex. `admin/trash`), indica-la amb `setBaseUrl(site_url('admin/trash'))`.

Totes les operacions que modifiquen dades van per POST i inclouen el token CSRF. Si el filtre `csrf` de CodeIgniter està actiu per a la ruta, és ell qui el comprova; si no, el CRUD verifica el token pel seu compte (es pot desactivar amb `$csrfProtection = false` a la [configuració](#configuració)).

**Identificadors emmascarats.** Amb `$crud->maskIds()` (o `$maskIds = true` a la [configuració](#configuració), per a tots els CRUD), el `{id}` d'aquestes URL és un codi (`edit/Xk3f9QaLm2PwE7` en lloc d'`edit/5`), també a les caselles del llistat i a les redireccions. Fa servir les mateixes dues rutes; un codi fals o un id real a la URL es tracten com un registre inexistent, i els hooks, els permisos i els callbacks reben l'id real. Fora del CRUD, `Crud::maskId()` i `Crud::unmaskId()` fan i desfan el codi, i a les accions `{kp_id}` posa l'id emmascarat. Per a les rutes pròpies que han de funcionar tant amb els ids emmascarats com sense, `Crud::idForUrl()` i `Crud::idFromUrl()` fan i desfan el codi només si l'emmascarament és actiu (i la ruta, amb `(:segment)`, no `(:num)`). Les comandes `php spark kpcrudy:maskid <taula> <id>` i `php spark kpcrudy:unmaskid <taula> <codi>` diuen a quin registre correspon un codi (amb `--config` si el CRUD rep una configuració pròpia; vegeu la [referència de comandes](docs/commands_CA.md#kpcrudyunmaskid)). Vegeu [Identificadors emmascarats](docs/functions_CA.md#identificadors-emmascarats) i la recepta [Enllaçar amb l'id emmascarat](docs/howto_CA.md#enllaçar-amb-lid-emmascarat).

**Conflictes de rutes.** `Crud::routes()` registra `base/(:any)`, que captura totes les URL que comencen per `base/`. Una ruta pròpia amb el mateix prefix definida després (p. ex. `admin/invoices/(:segment)` per a una pàgina pública) no s'executaria mai, i una de definida abans que capturi URL del CRUD (`admin/invoices/add`...) el deixaria sense funcionar. En desenvolupament, `render()` ho detecta i llança una excepció que explica el problema. Fes servir un altre prefix per a les pàgines pròpies (`invoice/(:segment)`).

---

## Exemple complet

```php
public function clients()
{
    $crud = new Crud();

    $crud->setTable('clients')
        ->setSubject('Client', 'Clients')

        // Què es veu on
        ->columns(['id', 'photo', 'name', 'email', 'city_id', 'status', 'active', 'tags'])
        ->fields(['name', 'email', 'city_id', 'status', 'active', 'birthdate', 'notes', 'photo', 'password', 'tags'])
        ->showFields(['name', 'email', 'city_id', 'status', 'tags', 'notes', 'created_at'])
        ->displayAs(['name' => 'Nom', 'city_id' => 'Ciutat', 'tags' => 'Etiquetes'])

        // Relacions
        ->setRelation('city_id', 'cities', '{name} ({province})')
        ->setRelationNN('tags', 'client_tag', 'tags', 'client_id', 'tag_id', 'name')

        // Tipus i validació
        ->fieldType('photo', 'image', ['maxSize' => 1024])
        ->fieldType('status', 'select', ['options' => ['lead' => 'Potencial', 'active' => 'Actiu']])
        ->setRules('email', 'permit_empty|valid_email|is_unique[clients.email,id,{id}]')
        ->requiredFields(['city_id'])
        ->readonlyFields(['email'])
        ->setHelp('notes', 'Informació interna, el client no la veu.')

        // Llistat
        ->filters(['city_id', 'status', 'active', 'tags', 'birthdate'])
        ->defaultOrder('name')
        ->setPerPage(25, [10, 25, 50])

        // Enllaços a cada fila
        ->addAction('Factures', 'factures/client/{kp_id}', ['icon' => 'bi bi-receipt', 'class' => 'btn-outline-info'])
        ->addAction('Escriu-li', fn ($row) => 'mailto:' . $row['email'], [
            'icon'    => 'bi bi-envelope',
            'visible' => fn ($row) => ! empty($row['email']),
        ])

        // Acció massiva pròpia
        ->addBulkAction('activate', 'Activa', function (array $ids) {
            db_connect()->table('clients')->whereIn('id', $ids)->update(['active' => 1]);

            return count($ids) . ' clients activats';
        })

        // Permisos
        ->unsetPurge()
        ->setPermission('delete', fn (?array $row) => $row === null || $row['status'] !== 'active');

    return $crud->render();
}
```

---

## Referència

### Origen de dades

| Mètode | Descripció |
|---|---|
| `setTable(string $table)` | Treballa directament amb la taula. Es crea un model dinàmic que detecta la clau primària, els timestamps (`created_at`, `updated_at`) i els soft deletes (`deleted_at`). |
| `setModel(Model\|string $model)` | Treballa amb un model de CodeIgniter. Es fan servir la seva taula, clau primària, `allowedFields`, validació, timestamps, soft deletes i callbacks. |
| `setDatabase(string\|BaseConnection $db)` | Grup o connexió de base de dades (mode taula). |
| `setPrimaryKey(string $key)` | Força la clau primària. |
| `softDeletes(bool $enabled = true, string $field = 'deleted_at')` | Activa o desactiva la paperera (mode taula). En mode model, mana el model. |
| `where(string\|array $field, $value = null)` | Limita els registres que gestiona el CRUD. S'aplica al llistat, al detall, a l'edició, a l'esborrat i a les accions massives. |
| `setSubject(string $singular, ?string $plural)` | Noms que surten als títols i missatges. |

> **Model amb `{id}` a les regles**: a CodeIgniter 4.5+ el camp de la clau primària ha de tenir una regla pròpia per poder fer servir `{id}`, per exemple `'id' => 'permit_empty|is_natural_no_zero'`. Kpcrudy hi afegeix l'identificador automàticament en editar (l'id real, encara que la URL porti un codi emmascarat).

### Camps i columnes

Per defecte:

- **Llistat**: tots els camps menys `deleted_at` i els camps secrets (contrasenyes, tokens...).
- **Formularis**: tots menys la clau primària, els camps de timestamp i els camps secrets que no són contrasenyes, més les relacions N-N.
- **Detall**: tots menys `deleted_at` i els camps secrets, més les relacions N-N.

Els camps secrets són els que tenen un nom de `$sensitiveFields` (`password`, `token`, `secret`, `hash`, `apikey`...), sencer o com a part separada per `_` (`api_token`). Si en necessites algun, posa'l explícitament a `columns()`, `showFields()`, `fields()`...

| Mètode | Descripció |
|---|---|
| `columns(array)` | Columnes del llistat. Pots afegir columnes virtuals amb `callbackColumn()`. |
| `fields(array)` | Camps d'alta i d'edició. |
| `addFields(array)` / `editFields(array)` | Camps diferents per a l'alta i per a l'edició. |
| `showFields(array)` | Camps de la fitxa de detall. |
| `searchFields(array)` | Camps on busca el cercador. Per defecte, els camps de text i les relacions del llistat que no tenen `callbackColumn`. |
| `filters(array)` | Camps amb filtre. Per defecte, les columnes relació, enum, select i booleà. |
| `exportColumns(array)` | Columnes del CSV. Per defecte, les del llistat. |
| `unsortable(array)` | Columnes que no es poden ordenar. |
| `displayAs(string\|array $field, ?string $label)` | Etiquetes. |
| `setRules(string\|array $field, string\|array $rules)` | Regles de validació de CodeIgniter. `{id}` és l'identificador (real) en edició. |
| `requiredFields(array)` | Camps obligatoris. |
| `readonlyFields(array)` | Camps que es veuen però no es poden modificar en edició. |
| `hideFields(array)` | Amaga camps de tot arreu (llistat, fitxa, formularis, cerca, filtres, exportació, importació); no es desen encara que arribin per POST. P. ex. `if (! $user->can('staff.salary')) $crud->hideFields(['salary'])`. |
| `setDefault(string $field, $value)` | Valor inicial a l'alta. |
| `setHelp(string $field, string $text)` | Text d'ajuda sota el camp. |
| `fieldType(string $field, string $type, array $options = [])` | Força el tipus d'un camp (vegeu la taula següent). |

Si no hi defineixes regles, Kpcrudy en dedueix a partir de l'estructura de la taula: `NOT NULL` sense valor per defecte → `required`, `VARCHAR(n)` → `max_length[n]`, enter → `integer`, decimal → `decimal`, data → `valid_date`, hora → `HH:MM[:SS]`, color → `#rrggbb`, i email o url (només `http`/`https`) segons el nom. Es pot desactivar amb `$inferRules = false` a la configuració.

#### Tipus de camp

El tipus es dedueix de la base de dades i del nom del camp (`email`, `password`, `url`...), però es pot forçar:

| Tipus | Opcions | Notes |
|---|---|---|
| `text`, `textarea`, `email`, `url`, `color` | | |
| `number`, `decimal` | | `decimal` accepta decimals |
| `date`, `datetime`, `time` | | Es mostren amb `$dateFormat` i `$datetimeFormat` |
| `boolean` | | Interruptor. `TINYINT(1)` es detecta sol |
| `enum` | `options` | Els `ENUM` de MySQL es detecten sols |
| `select` | `options` => `[valor => etiqueta]` | |
| `password` | `hash` (per defecte `true`) | Es desa amb `password_hash()`. En edició, si es deixa en blanc no canvia |
| `hidden` | `value` | Amb `value` es desa sempre aquest valor (p. ex. l'usuari actual) |
| `readonly` | | Només es mostra |
| `upload` | `path`, `url`, `allowed`, `maxSize` (KB), `deleteFiles`, `subdir` | Desa la ruta del fitxer relativa a `path`. Per defecte a `public/uploads/{taula}` |
| `image` | igual que `upload` | Només imatges, amb miniatura al llistat |

**Fitxers en subcarpetes.** Amb `subdir`, cada fitxer nou es desa en una subcarpeta de `path`, i el camp guarda la ruta relativa (`2026/10/abc123.png`). Pot ser un string amb `{camp}` o una closure que rep les dades que es desaran i el registre actual:

```php
$crud->fieldType('poster', 'image', [
    'path'   => FCPATH . 'uploads/posters',
    'url'    => base_url('uploads/posters'),
    // public/uploads/posters/2026/10/abc123.png
    'subdir' => fn (array $data) => date('Y/m', strtotime($data['starts_at'])),
]);

$crud->fieldType('document', 'upload', ['subdir' => 'clients/{client_id}']);
```

Les carpetes es creen soles. Per seguretat, només s'hi admeten lletres, números, guions i barres (mai `..`). Si el valor canvia més endavant, els fitxers ja desats es queden on eren: la ruta és al registre.

Qualsevol tipus accepta `attrs` => `[atribut => valor]` per afegir atributs a l'input (`placeholder`, `min`, `pattern`...).

### Relacions

```php
// 1-N: clients.city_id -> cities.id
$crud->setRelation('city_id', 'cities', 'name');
$crud->setRelation('city_id', 'cities', '{name} ({province})', [
    'where' => ['active' => 1],   // només aquestes opcions
    'order' => 'name',
    'pk'    => 'id',
]);

// N-N: clients <-> client_tag <-> tags
$crud->setRelationNN('tags', 'client_tag', 'tags', 'client_id', 'tag_id', 'name');
```

`tags` és un camp virtual que pots posar a `columns()`, `fields()`, `showFields()` i `filters()`. Les relacions es poden cercar, ordenar (les 1-N) i filtrar. Els valors desats es validen contra les opcions permeses.

### Callbacks

```php
// Com es mostra una columna (HTML; l'has d'escapar tu)
$crud->callbackColumn('credit', fn ($value, array $row) => esc(number_format((float) $value, 2, ',', '.')) . ' €');

// Columna virtual
$crud->columns(['name', 'total'])
     ->callbackColumn('total', fn ($value, array $row) => esc(model('InvoiceModel')->totalFor($row['id'])));

// Input personalitzat al formulari (ha de fer servir name="camp")
$crud->callbackField('color', fn ($value, ?array $row, array $field) =>
    '<input type="color" name="color" class="form-control form-control-color" value="' . esc($value ?? '#000000', 'attr') . '">');

// Valor al CSV (si no n'hi ha, es fa servir el de callbackColumn sense HTML)
$crud->callbackExport('credit', fn ($value, array $row) => (string) $value);
```

### Hooks

```php
$crud->beforeInsert(function (array $data) {
    $data['created_by'] = session('user_id');
    return $data;            // dades modificades
});

$crud->beforeUpdate(function (array $data, string $id, array $old) {
    if ($old['locked']) {
        return 'Aquest registre està bloquejat.';   // missatge d'error: no es desa
    }
    return null;             // null: continua sense canvis
});

$crud->beforeDelete(fn (string $id, array $row, bool $purge) => $row['invoices'] > 0 ? 'Té factures.' : null);

$crud->afterInsert(fn (string $id, array $data) => log_message('info', "Client {$id} creat"));
$crud->afterUpdate(fn (string $id, array $data, array $old) => null);
$crud->afterDelete(fn (string $id, array $row, bool $purge) => null);
$crud->afterRestore(fn (string $id, array $row) => null);

// Després de qualsevol escriptura (formulari, accions massives, importació): p. ex. buidar una memòria cau o desar un registre d'auditoria
$crud->afterWrite(fn (string $operation, string $id, array $data, ?array $old) => cache()->delete('clients'));
```

Els hooks `before*` poden retornar un array (dades noves), `null` (sense canvis), un string (missatge d'error) o `false` (cancel·lar).

### Accions

```php
// A cada fila del llistat i a la fitxa. {id} (id real), {kp_id} (l'id com a les URL del CRUD:
// el codi si està emmascarat) i {qualsevol_camp} es substitueixen. {id} i {kp_id} són sempre la
// clau primària, encara que el camp es digui "codi" (no hi ha {kp_codi}).
// Per a pàgines pròpies, {kp_id}: la ruta, amb (:segment), n'obté l'id real amb Crud::idFromUrl().
// Per obrir un altre CRUD filtrat, {id}: 'admin/orders?f[client_id]={id}' (els filtres fan servir l'id real).
$crud->addAction('Factures', 'factures/client/{kp_id}', [
    'icon'    => 'bi bi-receipt',          // Bootstrap Icons
    'class'   => 'btn-outline-info',
    'target'  => '_blank',
    'confirm' => 'Vols obrir les factures?',
    'visible' => fn (array $row) => $row['active'] == 1,
]);

// Operacions que modifiquen dades: addOperation() les serveix des de la mateixa ruta del CRUD
// (POST admin/events/duplicate/{id emmascarat}), amb CSRF, permisos, where() i ids emmascarats.
// La closure retorna un missatge, una resposta, false (cancel·lat) o $crud->backToList('danger', '...')
$crud->addOperation('duplicate', function (string $id, array $row, Crud $crud) {
    unset($row['id']);
    db_connect()->table('events')->insert(['title' => $row['title'] . ' (còpia)'] + $row);

    return "S'ha creat una còpia.";
}, ['label' => 'Duplica', 'icon' => 'bi bi-copy', 'confirm' => 'Segur?']);

// Qualsevol acció pot dependre d'un permís (setPermission() o el resolutor de permisos)
$crud->addAction('Assistents', 'admin/attendances?f[event_id]={id}', ['permission' => 'attendees']);

// La URL també pot ser una closure
$crud->addAction('Mapa', fn (array $row) => 'https://maps.google.com/?q=' . urlencode($row['address']));

// Botons a la capçalera
$crud->addHeaderAction('Importa', 'admin/clients/import', ['icon' => 'bi bi-upload']);

// Accions massives sobre la selecció. Reben els id (ja filtrats per l'abast del CRUD).
$crud->addBulkAction('newsletter', 'Envia butlletí', function (array $ids, Crud $crud) {
    // ...
    return 'Butlletí enviat a ' . count($ids) . ' clients';   // o una ResponseInterface
}, ['confirm' => 'Segur?', 'trash' => false]);
```

Les URL relatives passen per `site_url()`.

### Importació CSV

La importació s'activa a cada CRUD amb `enableImport()`. Al llistat apareix el botó **Importa CSV**, que porta a un procés en tres passos:

1. **Pujar el fitxer** i triar què cal fer amb cada fila (*afegir sempre* o *actualitzar si ja existeix*) i amb les files amb errors (*no importar res* o *saltar-les*).
2. **Previsualització**: com s'han associat les columnes, quantes files són noves, quantes s'actualitzaran i quines tenen errors (per línia).
3. **Confirmar**. Es mostra el resum, o un informe amb les files que no s'han pogut importar.

```php
$crud->enableImport();                                   // clau: la clau primària
$crud->enableImport(['key' => 'email']);                 // actualitza els registres que tinguin el mateix email
$crud->enableImport([
    'fields'  => ['name', 'email', 'city_id', 'tags'],   // només aquestes columnes
    'maxRows' => 500,
    'maxSize' => 1024,                                   // KB
]);

// Transformar un valor abans de validar-lo
$crud->callbackImport('phone', fn (string $value, array $row) => preg_replace('/\D/', '', $value));
```

Cada fila passa per la mateixa conversió, validació, hooks i permisos que el formulari, i les contrasenyes es desen amb hash. Les columnes es reconeixen pel **nom del camp o per l'etiqueta**, i els valors de relacions i desplegables, **per l'id o pel text** que es mostra. Així, un CSV exportat es pot editar amb un full de càlcul i tornar a importar tal qual. Es detecten soles la codificació (UTF-8 o Windows-1252) i el separador (`;`, `,` o tabulador), i hi ha un botó per descarregar una plantilla amb les columnes.

![Previsualització d'una importació CSV](screenshots/16-importacio-previsualitzacio.png)

### Permisos

Operacions: `list`, `add`, `edit`, `show`, `delete`, `export`, `import`, `trash`, `restore`, `purge`, `search`, `filters`, `bulk`.

```php
$crud->unsetAdd()->unsetExport();       // també unsetEdit, unsetDelete, unsetShow,
                                        // unsetTrash, unsetPurge, unsetSearch,
                                        // unsetFilters, unsetBulk
$crud->readOnly();                      // sense add, edit, delete, restore ni purge

// Registre a registre: $row és null quan no hi ha cap registre concret
// (p. ex. per decidir si es mostra el botó o l'acció massiva)
$crud->setPermission('edit', fn (?array $row) => $row === null || $row['owner_id'] == session('user_id'));
```

Els permisos es comproven també al servidor, no només a la interfície. La closure d'`edit` es torna a avaluar en desar (formulari i importació) amb el registre tal com quedaria després del canvi: si retorna `false`, no es desa. L'exportació necessita, a més d'`export`, el permís `list`.

**Permisos centralitzats.** En lloc de repetir regles a cada controlador, una classe que implementi `Kpcrudy\PermissionResolver` pot decidir per a tots els CRUD (`$permissionResolver` a la configuració, o `setPermissionResolver()` en un CRUD). Cada CRUD s'identifica amb `setResource()` (per defecte, la seva taula):

```php
class ShieldPermissions implements \Kpcrudy\PermissionResolver
{
    public function allows(string $resource, string $operation, ?array $row, \Kpcrudy\Crud $crud): ?bool
    {
        // true permet, false denega (setPermission() no ho pot desfer), null: no opina
        return auth()->user()?->can("{$resource}.{$operation}") ?? false;
    }
}
```

Shield només és un exemple: el resolutor pot fer servir qualsevol autenticació. Amb `$denyByDefault = true`, una operació que ningú permet queda denegada. Vegeu "Permisos" a la [referència](docs/functions_CA.md) i `Libraries/DemoPermissions.php` de la demo.

- **Noms per a Shield**: anomena cada CRUD `<mòdul>-<recurs>` (`setResource('news-posts')`), de manera que els seus permisos són `news-posts.edit`... i `news-posts.*` concedeix tot el CRUD (Shield només expandeix la primera part).
- **Catàleg**: `$crud->permissionCatalogue()` llista totes les operacions per les quals el CRUD pot preguntar (les del CRUD, les operacions pròpies i els permisos de les accions), per declarar o comprovar els permisos; `Crud::impliedPermissions(['edit'])` hi afegeix les que pressuposa (`list`, `show`).
- **Registres propis**: no hi ha cap permís especial. Si l'usuari només ha de *veure* els seus registres, fes servir `where()`; si ho veu tot però només *modifica* els seus, una closure de `setPermission()` (o el resolutor), que rep el registre.

### Presentació

| Mètode | Descripció |
|---|---|
| `setPerPage(int $n, ?array $options)` | Registres per pàgina i opcions del selector. |
| `defaultOrder(string $field, string $dir = 'asc')` | Ordre inicial. |
| `setTheme(string $theme)` | Tema de plantilles. |
| `setLayout(string\|false $layout, ?string $section = null)` | `'default'`, `false` o una vista del projecte; amb `$section`, s'estén la vista (`$this->extend()`) i el CRUD va a aquesta secció. |
| `setLayoutData(array $data)` | Variables addicionals per al layout del projecte. |
| `showTitle(bool $show)` | `false` si el layout ja mostra el títol (disponible amb `getTitle()` o com a `$title`). |
| `setBaseUrl(string $url)` | URL base del CRUD. |
| `setBackUrl(string $url)` | On es torna després de desar, esborrar o cancel·lar (per defecte, el llistat tal com l'havies deixat). |

---

## Configuració

Crea `app/Config/Kpcrudy.php`:

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
    public int $exportMaxRows      = 100000;   // 0 = sense límit
    public bool $csrfProtection    = true;     // verifica el token si el filtre csrf no és actiu
    public ?string $layoutSection  = null;     // p. ex. 'content': el CRUD va a una secció de $layout
    public bool $showTitle         = true;
    public array $flashKeys        = [];       // p. ex. ['success' => 'message', 'danger' => 'error']
    public ?string $permissionResolver = null; // classe que implementa Kpcrudy\PermissionResolver
    public bool $denyByDefault     = false;
    public bool $maskIds           = false;    // true: identificadors emmascarats a totes les URL
    public string|false $layout    = 'layouts/admin';
    public array $themes           = ['admin' => APPPATH . 'Views/kpcrudy/admin'];
    public string $theme           = 'admin';
}
```

La manera més ràpida de crear-lo és `php spark kpcrudy:publish config`: escriu aquesta classe amb **totes** les opcions, el valor per defecte i el comentari de la llibreria, com a referència. Canvia-hi només el que necessitis; les opcions que esborris (i les que s'afegeixin en versions noves) agafen el valor de la llibreria. Vegeu [kpcrudy:publish](docs/commands_CA.md#kpcrudypublish).

Totes les opcions estan documentades a [src/Config/Kpcrudy.php](src/Config/Kpcrudy.php). Entre les de seguretat hi ha `$csrfProtection`, `$exportMaxRows`, `$maskIds` (amb `$maskKey` i `$idMasker`, vegeu [Identificadors emmascarats](docs/functions_CA.md#identificadors-emmascarats)) i `$sensitiveFields` (noms de camps secrets que no es mostren per defecte; si l'amplies, parteix de la llista original).

## Layouts i temes

**Layout.** Per defecte el CRUD es mostra en una pàgina completa amb Bootstrap 5 i Bootstrap Icons des de CDN. Per integrar-lo a la plantilla del projecte:

```php
$crud->setLayout('layouts/admin');
```

La vista `app/Views/layouts/admin.php` rep `$kpcrudy` (l'HTML del CRUD) i `$title`:

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

Amb `setLayout(false)`, `render()` retorna només el fragment HTML.

Si el layout del projecte fa servir seccions, indica el nom de la secció: s'estén la vista i el CRUD es mostra en aquesta secció. Amb `flashKeys`, els missatges del CRUD van a les claus de sessió que el layout ja mostra:

```php
$crud->setLayout('layouts/admin', 'content')     // <?= $this->renderSection('content') ?>
     ->setLayoutData(['menu' => 'events'])
     ->showTitle(false);                         // el layout mostra $title
```

**Content-Security-Policy.** Les plantilles no tenen gestors d'esdeveniments ni atributs `style` en línia, i els blocs `<script>` i `<style>` porten el nonce de CodeIgniter, de manera que el CRUD funciona amb `$CSPEnabled = true`. Permet el CDN del layout per defecte a `Config\ContentSecurityPolicy` (fonts de scripts, estils i tipus de lletra) i les imatges `data:` (vegeu `demo/app/Config/ContentSecurityPolicy.php`). Les confirmacions fan servir `window.confirm()` llevat que un listener cancel·li l'esdeveniment `kpcrudy:confirm` per mostrar el seu propi diàleg (vegeu les [receptes](docs/howto_CA.md)).

**Temes.** La llibreria inclou tres temes: `bootstrap5` (per defecte), `w3css` ([W3.CSS](https://www.w3schools.com/w3css/)) i `tailwind` ([Tailwind CSS](https://tailwindcss.com/)).

```php
$crud->setTheme('w3css');
```

Les plantilles de cada tema són a `src/Views/{tema}`:

```
layout.php          pàgina completa (layout 'default')
list.php            llistat i paperera
form.php            alta i edició
show.php            detall
import.php          importació CSV
partials/
    assets.php      CSS i JS propis
    button.php      botó d'acció
    field.php       un camp del formulari
    filters.php     panell de filtres
    flash.php       missatges
    pagination.php  paginació
```

Per fer un tema propi, registra'n el directori a `$themes` i copia-hi **només les plantilles que vulguis canviar**: les que falten es carreguen del tema pare (si n'hi ha) i, al final, de `bootstrap5`. Dins de les plantilles, `$this` és un `Kpcrudy\Renderer`, amb `render()` per incloure parcials, `renderParent()` per ampliar la plantilla del tema pare, `hidden()` i `attrs()`. Les variables que rep cada plantilla estan documentades a la capçalera de la versió de `bootstrap5`.

```php
// app/Config/Kpcrudy.php
public array $themes = [
    'admin'  => APPPATH . 'Views/kpcrudy/admin',                                  // pare: bootstrap5
    'w3teal' => ['path' => APPPATH . 'Views/kpcrudy/w3teal', 'parent' => 'w3css'], // amplia w3css
];
```

La comanda `php spark kpcrudy:publish theme w3css --as intranet` copia les plantilles d'un tema a `app/Views/kpcrudy/intranet` i el registra a `$themes`; amb `--only list,partials/pagination` en copia només algunes i la resta surt del tema original. Després, `->setTheme('intranet')` o `$theme = 'intranet'`. Per adaptar els textos, `php spark kpcrudy:publish lang ca` (vegeu [kpcrudy:publish](docs/commands_CA.md#kpcrudypublish)).

La guia [docs/new_theme_CA.md](docs/new_theme_CA.md) explica pas a pas com fer un tema, amb els exemples de W3.CSS i Tailwind.

| W3.CSS (`w3css`) | Tailwind CSS (`tailwind`) |
|---|---|
| ![Tema W3.CSS](screenshots/18-tema-w3css-llistat.png) | ![Tema Tailwind](screenshots/20-tema-tailwind-llistat.png) |

## Idiomes

Idiomes inclosos:

| Codi | Idioma |
|------|--------|
| `ca` | Català |
| `es` | Castellà |
| `en` | Anglès |
| `fr` | Francès |
| `pt` | Portuguès |
| `de` | Alemany |

Els textos són a `src/Language/{codi}/Kpcrudy.php` i segueixen el locale de la petició (`app.defaultLocale`, o el que negociï CodeIgniter si tens `negotiateLocale` activat i el codi a `supportedLocales`). Per canviar-ne algun, crea `app/Language/{codi}/Kpcrudy.php` amb les claus que vulguis sobreescriure (`php spark kpcrudy:publish lang ca` hi copia tots els textos de l'idioma: deixa-hi només els que canviïs).

Per afegir-hi un idioma nou, copia `src/Language/en/Kpcrudy.php` a la carpeta del codi corresponent i tradueix-ne els valors. Els `{0}`, `{1}`... són paràmetres i s'han de mantenir. El portuguès és el de Portugal: per al del Brasil, crea `app/Language/pt-BR/Kpcrudy.php`.

Si no hi ha traducció de kpcrudy per al locale, CodeIgniter prova el locale sense regió (`pt-BR` → `pt`) i després l'anglès. Els missatges d'error de programació (taula inexistent, resolutor de permisos no vàlid...) també estan traduïts (claus `exceptions`).

## Notes de seguretat

- Tot el text s'escapa a les plantilles. Els callbacks `callbackColumn` i `callbackField` retornen HTML tal qual: escapa-hi les dades.
- **CSRF**: totes les peticions POST del CRUD comproven el token. Si el filtre `csrf` de CodeIgniter (o una subclasse de `CodeIgniter\Filters\CSRF`) està actiu per a la petició, ho fa el filtre; si no, ho fa el CRUD mateix. Es pot desactivar amb `$csrfProtection = false` si el projecte protegeix les peticions d'una altra manera.
- **Abast de `where()`**: les condicions d'igualtat simples (`camp => valor`) també s'apliquen en desar: el camp no surt al formulari, es desa sempre amb aquest valor (també a la importació) i, si després de desar el registre queda fora de l'abast, es desfà el canvi.
- **Camps secrets**: les columnes amb noms com `password`, `token`, `secret`, `salt`, `hash`, `apikey`... (`$sensitiveFields`) no surten per defecte al llistat, la fitxa, la cerca, l'ordenació, els filtres, l'exportació ni els formularis (les contrasenyes sí que surten al formulari, i es desen amb hash). Només es mostren si les indiques explícitament.
- Les columnes amb `callbackColumn` no es fan servir per cercar si no les poses a `searchFields()`, perquè el callback pot amagar el valor real.
- Els valors de relacions, enum i select es validen contra les opcions permeses. Els valors de filtre no vàlids s'ignoren, els comodins `%` i `_` de la cerca es busquen literalment i un id que no pot ser una clau primària vàlida es tracta com a inexistent.
- **Identificadors emmascarats** (`maskIds()`): els codis són sempre els mateixos per a cada registre, xifrats i signats amb una clau del servidor; un codi fals, modificat o d'una altra taula es tracta com a inexistent. No substitueixen els permisos ni `where()`. En desenvolupament, el CRUD avisa de les rutes que xoquen amb les seves (`Crud::checkRoutes()`).
- **URL signades** (`requireSignature()`, `Crud::signUrl()`): la signatura és un HMAC amb una clau del servidor i cobreix el camí (operació i id) i la caducitat, de manera que un enllaç no es pot allargar ni fer apuntar a un altre registre; no cobreix el domini ni el port. En un CRUD els paràmetres (`?q=`, `?page=`) queden lliures, i per això el que pot veure el visitant s'ha de limitar amb `where()`. Les rutes pròpies són estrictes: `Crud::checkSignature()` només accepta (si no s'indica `allowFreeQuery`) URL signades senceres amb `signUrl()` o `signedUrl()`, mai les que signa un CRUD mentre es navega. Qualsevol que tingui l'enllaç hi pot entrar fins que caduqui; l'única manera d'anul·lar-lo abans és canviar la clau. Vegeu "URL signades" a [docs/functions_CA.md](docs/functions_CA.md) i [kpcrudy:signurl / kpcrudy:checkurl](docs/commands_CA.md#kpcrudysignurl).
- Les URL (camps `url` i URL d'accions retornades per closures) només es fan servir si són relatives o `http`, `https`, `mailto` o `tel`; els enllaços amb `target` porten `rel="noopener noreferrer"`.
- Els fitxers pujats es validen per extensió (la real i la declarada), tipus MIME (imatges) i mida, i es desen amb un nom aleatori. En substituir-los o esborrar-los, només s'esborren fitxers que siguin dins del directori de pujada del camp.
- L'exportació CSV neutralitza els valors que comencen per `=`, `+`, `-` o `@` (també amb espais o salts de línia al davant, i les variants d'amplada completa) per evitar la injecció de fórmules. Té un límit de files (`$exportMaxRows`) i llegeix els registres per blocs.
- El fitxer temporal de la importació és privat i s'esborra en tornar a la pàgina de pujada o al cap d'una hora.
- Fora de `production`, els errors de base de dades es mostren al missatge; en producció només es registren al log.
- Les plantilles funcionen amb una Content-Security-Policy estricta (nonces a `<script>` i `<style>`, sense gestors d'esdeveniments ni atributs `style` en línia), de manera que pots activar la CSP de CodeIgniter.
- Una operació pròpia (`addOperation()`) passa per les mateixes comprovacions que les del CRUD: mètode, CSRF, id emmascarat, `where()`, permisos (el seu `permission`, per defecte la clau), `visible` i signatures.
