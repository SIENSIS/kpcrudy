# kpcrudy · aplicació de demostració

[English](README_EN.md) · **Català**

Aplicació de gestió d'esdeveniments feta per ensenyar totes les funcionalitats de la llibreria [kpcrudy](../README_CA.md). Cada pantalla de gestió és un CRUD automàtic configurat en un sol mètode de controlador.

La demo és en anglès (dades, textos i missatges de la comanda), per poder-la integrar en qualsevol projecte.

Cada pàgina té el botó **Code for this page** a la barra superior. Obre un panell amb el codi del controlador que la genera i la llista de funcionalitats que s'hi demostren.

La demo és un **mòdul de la llibreria** ([src/Demo](../src/Demo)) i s'instal·la amb una comanda de `spark`, tant en un projecte propi com en el projecte d'aquesta carpeta.

---

## Instal·lar-la al teu projecte

Requisits: un projecte CodeIgniter 4 amb kpcrudy instal·lat, la base de dades configurada i, per a les imatges d'exemple, l'extensió `gd`.

```bash
php spark kpcrudy:demo install
```

Obre `http://el-teu-projecte/kpcrudy-demo`.

La comanda **no copia ni modifica cap fitxer del projecte**:

| Què crea | On |
|---|---|
| 5 taules amb dades de prova | `kpdemo_categories`, `kpdemo_users`, `kpdemo_user_interests`, `kpdemo_events`, `kpdemo_attendances` |
| Avatars i cartells | `public/uploads/kpcrudy-demo/` |
| Fitxer de marca | `writable/kpcrudy-demo.json` |

Les rutes (`/kpcrudy-demo/...`) les registra la llibreria mateixa: CodeIgniter descobreix el fitxer [src/Config/Routes.php](../src/Config/Routes.php) del mòdul, i aquest només les activa si existeix el fitxer de marca i no és producció (vegeu les notes). Els prefixos `kpdemo_` i `kpcrudy-demo` eviten qualsevol col·lisió amb les taules, les URL, els fitxers i la sessió del projecte (per exemple, amb una taula `users` pròpia o la de Shield).

### Altres accions

```bash
php spark kpcrudy:demo status       # què hi ha instal·lat i quants registres té cada taula
php spark kpcrudy:demo reset        # torna a les dades inicials (també des del botó "Reset data" de la pàgina d'inici, fora de producció)
php spark kpcrudy:demo uninstall    # esborra taules, imatges i marca: el projecte queda com estava
```

| Opció | Descripció |
|---|---|
| `--yes` | No demana confirmació (`uninstall`, `reset`) |
| `--force` | `install`: si han quedat taules d'una instal·lació anterior, les esborra i torna a començar |
| `--allow-production` | `install`, `reset`: funcionen encara que `ENVIRONMENT` sigui `production`; amb `install`, les rutes de la demo també s'hi registren |

`uninstall` funciona també si una instal·lació s'ha quedat a mitges.

**Notes:**

- La demo fa servir la connexió de base de dades `default`.
- Les migracions de la demo no queden a l'historial de migracions del projecte: les executa l'instal·lador, perquè `php spark migrate:rollback` del projecte no les toqui mai.
- Si el projecte té desactivat el descobriment de rutes (`Config\Modules::$aliases` sense `routes`), la comanda t'indica la línia que has d'afegir a `app/Config/Routes.php`. La línia no registra res si la demo no està instal·lada, però treu-la quan la desinstal·lis.
- En producció (`CI_ENVIRONMENT = production`), `install` i `reset` es neguen a funcionar i les rutes de la demo no es registren, llevat que s'hagi instal·lat amb `--allow-production`. El reset des del web no existeix mai en producció.
- No pugis `writable/kpcrudy-demo.json` al repositori ni a producció: és el que activa les rutes de la demo.
- La demo es mostra en anglès encara que el projecte faci servir un altre idioma, incloent-hi els textos de la llibreria.

---

## Executar el projecte d'aquesta carpeta

Aquesta carpeta és un projecte CodeIgniter 4 buit preparat per executar la demo mentre es desenvolupa la llibreria. Carrega kpcrudy directament de `../src`, registrant el namespace a [app/Config/Autoload.php](app/Config/Autoload.php):

```php
public $psr4 = [
    APP_NAMESPACE => APPPATH,
    'Kpcrudy'     => ROOTPATH . '../src',
];
```

Així, qualsevol canvi a la llibreria es veu immediatament. No es fa servir un repositori Composer de tipus `path` perquè Composer no pot enllaçar un paquet dins d'una subcarpeta d'ell mateix.

```bash
cd demo
composer install

# Base de dades (amb XAMPP: usuari root, sense contrasenya; configuració a .env)
mysql -u root -e "CREATE DATABASE demo_kpcrudy CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"

php spark kpcrudy:demo install
php spark serve
```

Obre <http://localhost:8080>: l'arrel redirigeix a `/kpcrudy-demo`. Si fas servir un altre port o una altra URL, canvia `app.baseURL` al fitxer [.env](.env).

---

## Entrar a la demo

Per facilitar les proves, la pàgina d'entrada **no demana contrasenya**: mostra la llista d'usuaris i n'hi ha prou de fer clic en un per entrar amb el seu perfil.

- **Administradors** (`admin`, `john.smith`): accés a totes les pàgines.
- **Usuaris** (per exemple `alice`): agenda, inscripcions pròpies i perfil.

Els usuaris desactivats o a la paperera no surten a la llista i no poden entrar. N'hi ha dos de cada a les dades de prova.

Les contrasenyes continuen existint a la base de dades (`admin1234` per a l'administrador i `user1234` per a la resta) perquè el camp de contrasenya dels CRUD d'usuaris i de perfil tingui sentit, però el login no les fa servir.

---

## Model de dades

```
kpdemo_categories ──< kpdemo_events ──< kpdemo_attendances >── kpdemo_users ──< kpdemo_user_interests >── kpdemo_categories
```

| Taula | Contingut | Què permet demostrar |
|---|---|---|
| `kpdemo_users` | Usuari, contrasenya, nom, cognoms, correu, foto, data de naixement, presentació, rol, actiu | Paperera, imatges, contrasenyes, enum, booleà, regles úniques |
| `kpdemo_categories` | Nom, color i descripció | Mode model, color, taula sense paperera |
| `kpdemo_events` | Categoria, títol, descripció curta, descripció llarga, inici, final, lloc, aforament, preu, cartell, estat, creador | Relacions 1-N, data i hora, decimal, imatge, filtres per rang |
| `kpdemo_attendances` | Usuari, esdeveniment, estat (`registered`, `attended`, `absent`, `cancelled`), data d'inscripció i notes | Taula de lligam amb dades pròpies: té CRUD propi i també serveix de relació N-N |
| `kpdemo_user_interests` | Usuari i categoria | Relació N-N pura, editable des del formulari |

Les taules es creen amb les **migracions** de [src/Demo/Database/Migrations](../src/Demo/Database/Migrations), i les dades de prova, amb el **seeder** [src/Demo/Database/Seeds/DemoSeeder.php](../src/Demo/Database/Seeds/DemoSeeder.php). El seeder és determinista (sempre genera les mateixes dades) i crea els avatars i els cartells amb GD.

---

## Pàgines i funcionalitats

Totes les URL comencen per `/kpcrudy-demo/`. Els controladors són a [src/Demo/Controllers](../src/Demo/Controllers).

### Administració (rol `admin`)

#### Usuaris — `admin/users`
[Controllers/Admin/Users.php](../src/Demo/Controllers/Admin/Users.php)

- **Mode taula** (`setTable`): la llibreria detecta la clau primària, els timestamps i els soft deletes.
- **Paperera**: esborrar envia a la paperera, des d'on es pot restaurar o esborrar definitivament.
- **Imatge** (`fieldType('avatar', 'image')`) amb miniatura al llistat.
- **Contrasenya**: es desa amb `password_hash()`; en edició, si es deixa en blanc no canvia.
- **Relació N-N editable** (`setRelationNN`): interessos de l'usuari.
- **Columnes virtuals** (`callbackColumn`): nom complet i nombre d'inscripcions.
- **Regles amb `{id}`**: `is_unique[kpdemo_users.email,id,{id}]` ignora el registre que s'edita.
- **Camp només lectura** en edició (`readonlyFields`): el nom d'usuari.
- **Permís per registre** (`setPermission('delete', fn)`): no et pots esborrar a tu mateix.
- **Hook** `beforeUpdate`: no et pots treure el rol d'administrador.
- **Accions massives pròpies**: activar i desactivar.
- **Enllaç per fila** cap a les inscripcions de l'usuari, fent servir els filtres per URL.
- **Importació CSV** (`enableImport(['key' => 'username'])`): afegeix usuaris nous i actualitza els existents pel nom d'usuari. Les contrasenyes es desen amb hash i els interessos s'indiquen pel nom (`Music, Sports`).

![Usuaris](../screenshots/03-usuaris.png)

#### Categories — `admin/categories`
[Controllers/Admin/Categories.php](../src/Demo/Controllers/Admin/Categories.php) · [Models/CategoryModel.php](../src/Demo/Models/CategoryModel.php)

- **Mode model** (`setModel`): fa servir la validació, els missatges i els timestamps del model.
- Taula **sense `deleted_at`**: no hi ha paperera i esborrar és definitiu.
- Camp de tipus **color**.
- **Hook** `beforeDelete`: no es pot esborrar una categoria que té esdeveniments.
- Sense accions massives ni exportació (`unsetBulk`, `unsetExport`).

#### Esdeveniments — `admin/events`
[Controllers/Admin/Events.php](../src/Demo/Controllers/Admin/Events.php)

- **Relacions 1-N** amb plantilla (`'{first_name} {last_name}'`): categoria i creador.
- **Relació N-N** de només consulta: assistents a la fitxa i a l'exportació.
- Tipus **imatge, data i hora, decimal, número, textarea i select**.
- **Fitxers en subcarpetes** (opció `subdir`): cada cartell es desa a `posters/{any}/{mes}/` segons la data de l'esdeveniment, i el camp guarda la ruta relativa (`2026/11/abc123.png`). Els avatars dels usuaris, en canvi, van tots a la mateixa carpeta, per comparar els dos casos.
- **Filtres** per llista (categoria, estat) i per **rang** (data, preu).
- **Columna d'ocupació** calculada amb barra de progrés.
- `callbackColumn` per a l'estat i el preu, i `callbackExport` per al preu al CSV.
- **Hooks**: `beforeInsert` assigna el creador i valida que el final sigui posterior a l'inici.
- **Acció POST pròpia** amb confirmació: *Duplicate* (fora del CRUD, a `Events::duplicate`).
- **Accions massives** pròpies: publicar i cancel·lar.
- **Botó de capçalera** cap a l'agenda pública.
- **Columnes d'exportació** diferents de les del llistat (`exportColumns`).
- **Importació CSV** per id: es pot fer *Export CSV*, editar el fitxer amb un full de càlcul i importar-lo amb *Update the record if its "Id" already exists*. Les categories i els estats es llegeixen pel text, i les dates, en el format de pantalla.
- `callbackImport`: el fitxer pot posar `Free` com a preu.

![Fitxa d'un esdeveniment](../screenshots/05-esdeveniment-fitxa.png)

#### Assistència — `admin/attendances`
[Controllers/Admin/Attendances.php](../src/Demo/Controllers/Admin/Attendances.php)

- CRUD sobre una **taula de lligam amb dades pròpies**.
- Relacions amb **opcions filtrades i ordenades** (`where`, `order`): només usuaris actius i esdeveniments que no són esborranys.
- **Camps d'alta i d'edició diferents**, amb usuari i esdeveniment de només lectura en edició.
- **Valors per defecte** (`setDefault`).
- **Hook** `beforeInsert`: evita duplicats i controla l'aforament.
- **Filtres enllaçables**: des d'usuaris i esdeveniments s'hi arriba amb `?f[user_id]=…` o `?f[event_id]=…`.
- **Accions massives** pròpies: marcar assistència o absència.

![Assistència](../screenshots/09-assistencia.png)

### Àrea d'usuari (qualsevol usuari connectat)

#### Agenda — `agenda`
[Controllers/Agenda.php](../src/Demo/Controllers/Agenda.php)

- **Només consulta** (`readOnly`) sobre la mateixa taula que l'administració.
- `where()`: només esdeveniments **publicats i futurs**, també a la fitxa i a l'exportació.
- **Acció POST** *Join*, **visible només** si queden places i no hi estàs inscrit.
- **Columnes calculades** per a l'usuari connectat (places lliures, la meva inscripció). Les dades es calculen un sol cop abans del CRUD, per no fer una consulta per fila.

![Agenda](../screenshots/12-agenda.png)

#### Les meves inscripcions — `my/attendances`
[Controllers/MyAttendances.php](../src/Demo/Controllers/MyAttendances.php)

- `where('user_id', …)`: l'usuari només veu i modifica les seves inscripcions.
- **Acció per fila condicional**: anul·lar només les inscripcions actives d'esdeveniments futurs.
- **Acció massiva segura**: els identificadors que rep ja estan limitats pel `where()` del CRUD.

#### El meu perfil — `profile`
[Controllers/Profile.php](../src/Demo/Controllers/Profile.php) · [Views/kpcrudy/profile/show.php](../src/Demo/Views/kpcrudy/profile/show.php)

- **CRUD d'un sol registre**: `where('id', usuari connectat)`. Canviar l'id de la URL no dona accés a cap altre perfil.
- **Tema propi** `profile`: només substitueix `show.php`; la resta de plantilles són les del tema `bootstrap5`.
- `setBackUrl()`: després de desar es torna a la fitxa del perfil.
- **Camps limitats**: l'usuari no pot canviar-se el rol ni el nom d'usuari.
- **N-N editable**: els meus interessos. També s'hi pot canviar la foto i la contrasenya.

![Perfil](../screenshots/14-perfil.png)

---

## Com està muntada

Tot és a [src/Demo](../src/Demo), amb el namespace `Kpcrudy\Demo`:

| Element | Fitxer |
|---|---|
| Comanda `kpcrudy:demo` | [src/Commands/Demo.php](../src/Commands/Demo.php) |
| Instal·lació i desinstal·lació | [Installer.php](../src/Demo/Installer.php) |
| Noms, rutes, URL i carpetes | [Demo.php](../src/Demo/Demo.php) · [src/Config/Routes.php](../src/Config/Routes.php) |
| Configuració dels CRUD de la demo (layout, temes, paginació) | [Config/CrudConfig.php](../src/Demo/Config/CrudConfig.php) |
| Layout amb menú i panell de codi | [Views/layouts/main.php](../src/Demo/Views/layouts/main.php) |
| Panell de codi (fora dels controladors, perquè només continguin el codi del CRUD) | [Config/Showcase.php](../src/Demo/Config/Showcase.php) · [Controllers/BaseController.php](../src/Demo/Controllers/BaseController.php) |
| Autenticació senzilla per sessió | [Libraries/Auth.php](../src/Demo/Libraries/Auth.php) · [Filters/AuthFilter.php](../src/Demo/Filters/AuthFilter.php) |

Cada controlador crea el CRUD amb `new Crud(new CrudConfig())`, de manera que la demo no depèn de l'`app/Config/Kpcrudy.php` del projecte ni el modifica.

### Seguretat

- Tots els formularis i accions POST (també sortir) porten el token CSRF. Els filtres de la demo hereten de `CodeIgniter\Filters\CSRF` ([CsrfFilter](../src/Demo/Filters/CsrfFilter.php)): verifiquen el token si el projecte no té el filtre `csrf` global, i no fan res si ja el té (en aquest projecte ho està).
- Les pàgines públiques (entrar i sortir) fan servir `CsrfFilter`, les d'usuari `AuthFilter` i les d'administració `AdminFilter`, un sol filtre per ruta: com que es fa servir la classe, no cal registrar cap àlies a `app/Config/Filters.php`.
- L'usuari es torna a carregar a cada petició (també en mode worker): si un administrador el desactiva o l'envia a la paperera, perd l'accés a l'instant. En sortir, la sessió canvia d'identificador.
- La sessió de la demo fa servir la seva clau (`kpdemo_user_id`) i no interfereix amb el login del projecte.
- L'autenticació és deliberadament mínima perquè el focus és la llibreria: s'entra sense contrasenya triant l'usuari. **No la facis servir mai fora d'un entorn de proves.** En un projecte real cal fer servir [CodeIgniter Shield](https://shield.codeigniter.com/).
