# Referència de comandes

[English](commands_EN.md) · **Català**

La llibreria afegeix set comandes de `spark` al grup **Kpcrudy**. S'executen des de l'arrel del projecte CodeIgniter, com qualsevol altra comanda (`php spark ...`). Els missatges són en anglès, com la resta de comandes de CodeIgniter.

Les sortides d'aquest document són reals, obtingudes al projecte de la [demo](../demo/README_CA.md) (`http://localhost:8080`), excepte les de `kpcrudy:publish`, que s'han obtingut en un projecte de prova (a la demo hi crearia fitxers). S'ha omès la primera línia que escriu sempre CodeIgniter (`CodeIgniter v4.x.x Command Line Tool - Server Time: ...`). Els codis emmascarats, les signatures i les dates canvien a cada instal·lació i a cada execució.

La descripció de les funcions equivalents (`maskIds()`, `Crud::maskId()`, `requireSignature()`, `Crud::signUrl()`...) és a [functions_CA.md](functions_CA.md), i les receptes de programació, a [howto_CA.md](howto_CA.md).

## Índex

- [Referència ràpida](#referència-ràpida)
- [Abans de començar](#abans-de-començar): la clau, `--config`, les cometes i els codis de sortida
- [kpcrudy:make](#kpcrudymake): generar el CRUD d'una taula
- [kpcrudy:demo](#kpcrudydemo): instal·lar, consultar, reiniciar i treure la demo
- [kpcrudy:maskid](#kpcrudymaskid): codi emmascarat d'un id
- [kpcrudy:unmaskid](#kpcrudyunmaskid): id real d'un codi emmascarat
- [kpcrudy:signurl](#kpcrudysignurl): crear una URL signada
- [kpcrudy:checkurl](#kpcrudycheckurl): comprovar una URL signada
- [kpcrudy:publish](#kpcrudypublish): copiar al projecte la configuració, un tema o els textos d'un idioma
- [Receptes amb comandes](#receptes-amb-comandes)
  - [Generar un CRUD i comprovar-lo](#generar-un-crud-i-comprovar-lo)
  - [Instal·lar i treure la demo](#installar-i-treure-la-demo)
  - [Saber a quin registre es refereix una URL](#saber-a-quin-registre-es-refereix-una-url)
  - [Enviar per correu un enllaç signat vàlid 7 dies](#enviar-per-correu-un-enllaç-signat-vàlid-7-dies)
  - [Diagnosticar un "el meu enllaç no funciona"](#diagnosticar-un-el-meu-enllaç-no-funciona)
  - [Adaptar la configuració i el tema sense tocar la llibreria](#adaptar-la-configuració-i-el-tema-sense-tocar-la-llibreria)

---

## Referència ràpida

| Comanda | Per a què serveix | Exemple |
|---|---|---|
| `kpcrudy:make [taula]` | Escriu el mètode del controlador, la ruta i, opcionalment, el model d'un CRUD a partir d'una taula | `php spark kpcrudy:make clients --controller Admin/Clients --route admin/clients --filter auth` |
| `kpcrudy:demo <acció>` | Instal·la (`install`), consulta (`status`), reinicia (`reset`) o treu (`uninstall`) la demo | `php spark kpcrudy:demo status` |
| `kpcrudy:maskid <context> <id>...` | Mostra el codi emmascarat d'un o més ids (`maskIds()`) | `php spark kpcrudy:maskid invoices 5 6` |
| `kpcrudy:unmaskid <context> <codi>...` | Mostra l'id real d'un o més codis emmascarats | `php spark kpcrudy:unmaskid invoices aNiPDZ8eyg3JeFxXeA` |
| `kpcrudy:signurl <url>` | Signa una URL, amb caducitat o sense | `php spark kpcrudy:signurl shared/agenda --ttl 604800 --free-query` |
| `kpcrudy:checkurl <url>` | Diu si una URL signada és vàlida, si ha caducat o si s'ha modificat | `php spark kpcrudy:checkurl "http://.../shared/agenda?kp_exp=...&kp_sig=..."` |
| `kpcrudy:publish <config\|theme\|lang> [nom]` | Copia al projecte la configuració (`app/Config/Kpcrudy.php`), un tema (`app/Views/kpcrudy/...`) o els textos d'un idioma (`app/Language/...`), per adaptar-los sense tocar la llibreria | `php spark kpcrudy:publish theme w3css --as intranet` |

| Opció | Comandes | Descripció |
|---|---|---|
| `--config <classe>` | `maskid`, `unmaskid`, `signurl`, `checkurl` | Classe de configuració del CRUD, si no és `app/Config/Kpcrudy.php` |
| `--ttl <segons>` | `signurl` | Durada de l'enllaç (per defecte, `86400` = 1 dia; `0` = no caduca) |
| `--free-query` | `signurl` | Només signa el camí: els paràmetres (`?q=`, `?page=`...) es poden canviar |
| `--controller`, `--method`, `--route`, `--filter`, `--model`, `--dry-run`, `--force` | `make` | Vegeu [kpcrudy:make](#kpcrudymake) |
| `--force`, `--yes`, `--allow-production` | `demo` | Vegeu [kpcrudy:demo](#kpcrudydemo) |
| `--as`, `--only`, `--path`, `--force`, `--dry-run` | `publish` | Vegeu [kpcrudy:publish](#kpcrudypublish) |

---

## Abans de començar

### La clau: la mateixa que l'aplicació web

Els codis emmascarats i les signatures es calculen amb una clau secreta. `maskid`, `unmaskid`, `signurl` i `checkurl` només donen el mateix resultat que la web si fan servir **la mateixa clau i la mateixa configuració** que el CRUD:

- **Per defecte**, la de `app/Config/Kpcrudy.php` del projecte (o la de la llibreria, si el projecte no en té). La clau surt, per ordre, de `$maskKey`, de la clau d'encriptació de CodeIgniter (`encryption.key`, `php spark key:generate`) o del fitxer `writable/kpcrudy/mask.key`, que es crea sol la primera vegada.
- **Amb `--config`**, la d'una altra classe. Cal quan el CRUD rep una configuració pròpia al constructor (`new Crud(new AltraConfig())`). La classe ha d'estendre `Kpcrudy\Config\Kpcrudy`.

La demo n'és l'exemple: tots els seus CRUD fan servir `new Crud(new CrudConfig())`, amb una clau pròpia desada a `writable/kpcrudy-demo.json`. Per això, al projecte de la demo, aquestes quatre comandes s'han d'executar **sempre** amb:

```bash
--config "Kpcrudy\Demo\Config\CrudConfig"
```

Sense `--config`, la comanda fa servir la clau del projecte: els codis no coincideixen (`invalid code for this context`), les signatures no són vàlides (`Invalid signature`) i, si el projecte no té cap clau, es crea `writable/kpcrudy/mask.key`.

**Compte**: si canvies la clau (o esborres `writable/kpcrudy/mask.key`), canvien tots els codis emmascarats i deixen de funcionar tots els enllaços signats que s'hagin enviat.

### Cometes a la URL

Una URL signada porta `&` (`?kp_exp=...&kp_sig=...`), que el shell interpreta: a bash i zsh envia la comanda al fons, a `cmd` en separa una altra comanda, i PowerShell dona un error. **Posa sempre la URL entre cometes**:

```bash
php spark kpcrudy:checkurl "http://localhost:8080/kpcrudy-demo/shared/agenda?kp_exp=1793621771&kp_sig=5oHTP3ugjCNNcfdWSIQujwXVeXQWsMe6" --config "Kpcrudy\Demo\Config\CrudConfig"
```

Les cometes dobles funcionen a bash, zsh, PowerShell i `cmd`. També cal posar entre cometes el nom de classe de `--config`, perquè porta `\`.

### Codis de sortida

Totes les comandes acaben amb `0` si han anat bé i amb `1` si hi ha hagut un error (o, a `checkurl`, si la signatura no és vàlida). Es poden fer servir en scripts:

```bash
php spark kpcrudy:checkurl "$URL" --config "Kpcrudy\Demo\Config\CrudConfig" && echo "L'enllaç funciona"
```

### Veure l'ajuda

```bash
php spark list --simple          # totes les comandes; les de kpcrudy comencen per "kpcrudy:"
php spark help kpcrudy:signurl   # sintaxi, arguments i opcions d'una comanda
```

---

## kpcrudy:make

Llegeix l'estructura d'una taula (camps, claus primàries i foranes, índexs únics, taules intermèdies) i escriu el codi d'un CRUD que hi funciona: el mètode del controlador (creant el controlador si no existeix), la línia de `app/Config/Routes.php` i, si es demana, un model. El codi inclou les relacions 1-N i N-N, les etiquetes, les columnes del llistat, els tipus de camp que s'endevinen pel nom (imatge, fitxer, color, contrasenya) i les regles `is_unique`. És un punt de partida per adaptar, no un CRUD definitiu. Fa totes les comprovacions abans d'escriure res, de manera que un error no deixa fitxers a mitges.

### Sintaxi

```bash
php spark kpcrudy:make [taula] [--controller Classe] [--method mètode] [--route ruta] [--filter filtre] [--model] [--dry-run] [--force]
```

Sense la taula, pregunta tots els valors un per un.

### Arguments i opcions

| Argument / opció | Descripció | Per defecte |
|---|---|---|
| `taula` | Taula de la base de dades (connexió `default`). Només lletres, números i `_` | Es pregunta |
| `--controller` | Classe, relativa a `App\Controllers` (`Admin/Clients`). Si no existeix es crea; si existeix, s'hi afegeix el mètode | El nom de la taula en *StudlyCase* (`kpdemo_categories` → `KpdemoCategories`) |
| `--method` | Mètode del controlador. Ha de començar amb minúscula i no pot ser un mètode del controlador base | `index` |
| `--route` | Ruta del CRUD. Minúscules, números, `-`, `_` i `/`; no pot acabar amb una operació (`add`, `edit`, `trash`...) | A partir del controlador i el mètode (`Admin/Clients` → `admin/clients`; amb `--method list_all` → `admin/clients/list-all`) |
| `--filter` | Filtre de la ruta (`auth`, `session`, `auth:admin`...) | Cap |
| `--model` | Genera també `app/Models/{Taula}Model.php` (amb `allowedFields`, timestamps, soft deletes i regles) i fa servir `setModel()` | No |
| `--dry-run` | Mostra el codi i les comprovacions sense escriure cap fitxer | No |
| `--force` | Reemplaça el mètode (i el model) si ja existeixen | No |

### Exemples

```bash
# Veure què generaria, sense tocar res
php spark kpcrudy:make kpdemo_categories --dry-run

# CRUD d'administració amb login i model
php spark kpcrudy:make clients --controller Admin/Clients --route admin/clients --filter auth --model

# Un segon CRUD sobre la mateixa taula, en un altre mètode del mateix controlador
php spark kpcrudy:make clients --controller Admin/Clients --method archived

# Tornar a generar un mètode que ja existeix (es perden els canvis fets a mà)
php spark kpcrudy:make clients --controller Admin/Clients --force

# Mode interactiu
php spark kpcrudy:make
```

### Sortida

`php spark kpcrudy:make kpdemo_categories --dry-run`:

```
Table kpdemo_categories: 6 fields, primary key id
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
            ->fields(['name', 'color', 'description', 'users'])
            ->displayAs([
                'id'          => 'Id',
                'name'        => 'Name',
                'color'       => 'Color',
                'description' => 'Description',
                'created_at'  => 'Created at',
                'updated_at'  => 'Updated at',
                'users'       => 'Users',
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

`php spark kpcrudy:make kpdemo_events --controller Admin/Events --route admin/events --filter auth --model --dry-run` (resum, relacions 1-N, model i ruta amb filtre; s'ha retallat el codi del controlador):

```
Table kpdemo_events: 16 fields, primary key id
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
    protected $allowedFields  = ['category_id', 'title', 'summary', 'description', 'starts_at', 'ends_at', 'location', 'capacity', 'price', 'poster', 'status', 'created_by'];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'category_id' => ['label' => 'Category', 'rules' => 'required'],
        'title'       => ['label' => 'Title', 'rules' => 'required|max_length[150]'],
        'summary'     => ['label' => 'Summary', 'rules' => 'required|max_length[255]'],
        'starts_at'   => ['label' => 'Starts at', 'rules' => 'required'],
        'location'    => ['label' => 'Location', 'rules' => 'permit_empty|max_length[150]'],
    ];
}

--- app/Config/Routes.php ---
\Kpcrudy\Crud::routes($routes, 'admin/events', 'Admin\Events::index', ['filter' => 'auth']);

Dry run: no files have been written.
```

En mode interactiu (`php spark kpcrudy:make --dry-run`), entre claudàtors hi ha el valor que es fa servir si es prem Retorn:

```
Tables:
  [1] kpdemo_attendances
  [2] kpdemo_categories
  [3] kpdemo_events
  [4] kpdemo_user_interests
  [5] kpdemo_users
Table (number or name) : 2
Controller (relative to App\Controllers)  [KpdemoCategories]: Admin/Categories
Method  [index]:
Route  [admin/categories]:
Route filter (empty for none) : auth
Generate a model too? [n, y]: n
Table kpdemo_categories: 6 fields, primary key id
...
--- app/Config/Routes.php ---
\Kpcrudy\Crud::routes($routes, 'admin/categories', 'Admin\Categories::index', ['filter' => 'auth']);

Dry run: no files have been written.
```

Sense `--dry-run`, en lloc del codi mostra els fitxers que ha escrit. La paraula entre parèntesis és `created` (controlador nou o mètode nou), `added` (mètode afegit a un controlador existent) o `replaced` (amb `--force`):

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

### Codis de sortida

| Codi | Quan |
|---|---|
| `0` | Codi generat (o mostrat, amb `--dry-run`) |
| `1` | Qualsevol error dels de sota; no s'ha escrit cap fitxer |

### Errors habituals

| Missatge | Causa i solució |
|---|---|
| `The table "clients" does not exist.` | La taula no és a la connexió `default`. Comprova el nom (`php spark db:table --show`) i la base de dades del `.env` |
| `Cannot read the database: ...` | No es pot connectar. Revisa `database.default.*` al `.env` i que el servidor estigui en marxa |
| `The route cannot end with "edit": the CRUD uses that word as an operation.` | La ruta acaba amb `add`, `edit`, `show`, `trash`... Tria'n una altra amb `--route` |
| `The route "kpcrudy-demo/admin/categories" already exists and points to \Kpcrudy\Demo\Controllers\Admin\Categories::index. Use --route to choose another path.` | Una altra ruta ja fa servir aquest camí. Tria'n un altre amb `--route` |
| `Invalid method name "_remap": it must start with a lowercase letter and contain only letters, numbers and _.` | Canvia `--method`. Tampoc es poden fer servir noms de mètodes del controlador base (`initController`...) |
| `Invalid controller name "..."` | Cada segment de `--controller` ha de començar amb majúscula (la comanda ja posa la primera lletra en majúscula) i contenir només lletres, números i `_` |
| `The method index() already exists in Clients. Use --force to replace it.` | El mètode ja existeix. Fes servir un altre `--method`, o `--force` si el vols reemplaçar |
| `The model ClientModel already exists and is not for the table "clients" (it uses "...")...` | Hi ha un model amb aquest nom per a una altra taula. Fes servir `--force` o genera el CRUD sense `--model` |
| `The table "..." has columns whose names cannot be used in generated code: ...` | Alguna columna té caràcters que no poden anar al codi. Escriu el CRUD a mà |

---

## kpcrudy:demo

Gestiona l'[aplicació de demostració](../demo/README_CA.md), que és un mòdul de la llibreria: `install` crea les taules `kpdemo_*` amb dades de prova, les imatges de `public/uploads/kpcrudy-demo` i el fitxer de marca `writable/kpcrudy-demo.json`, que activa les rutes `/kpcrudy-demo`; `status` diu què hi ha instal·lat; `reset` torna a les dades inicials, i `uninstall` ho esborra tot i deixa el projecte com estava. No copia ni modifica cap fitxer del projecte. Com que la demo no té autenticació real, amb `CI_ENVIRONMENT = production` `install` i `reset` es neguen a funcionar, llevat que s'hi afegeixi `--allow-production`.

### Sintaxi

```bash
php spark kpcrudy:demo <install|status|reset|uninstall> [--force] [--yes] [--allow-production]
```

### Arguments i opcions

| Argument / opció | Descripció |
|---|---|
| `install` | Crea les taules, les dades, les imatges i el fitxer de marca. Si falla a mig camí, ho desfà tot |
| `status` | Mostra si està instal·lada, la data, l'URL, si les rutes són actives, els registres de cada taula i els fitxers |
| `reset` | Torna a les dades inicials (demana confirmació) |
| `uninstall` | Esborra taules, imatges i fitxer de marca (demana confirmació). Funciona també després d'una instal·lació incompleta |
| `--force` | `install`: esborra les taules que hagin quedat d'una instal·lació anterior i torna a començar |
| `--yes` | `reset`, `uninstall`: no demana confirmació |
| `--allow-production` | `install`, `reset`: funcionen encara que l'entorn sigui `production`; amb `install`, les rutes de la demo també s'hi registren (qualsevol pot entrar com a administrador!) |

Sense acció (o amb una de desconeguda), mostra l'ajuda.

### Exemples

```bash
php spark kpcrudy:demo install            # instal·lar
php spark kpcrudy:demo install --force    # instal·lar esborrant les taules d'un intent anterior
php spark kpcrudy:demo status             # què hi ha instal·lat
php spark kpcrudy:demo reset --yes        # tornar a les dades inicials, sense preguntar (scripts)
php spark kpcrudy:demo uninstall          # treure-la (demana confirmació)
```

### Sortida

`php spark kpcrudy:demo status`:

```
kpcrudy demo
  Installed: yes
  Date:      2026-10-23T17:40:40+02:00
  URL:       http://localhost:8080/kpcrudy-demo
  Routes:    active
  Tables:
    kpdemo_categories       7 rows
    kpdemo_users            25 rows
    kpdemo_user_interests   46 rows
    kpdemo_events           36 rows
    kpdemo_attendances      353 rows
  Files:     52 in public/uploads/kpcrudy-demo
```

`php spark kpcrudy:demo install`:

```
Installing the kpcrudy demo...
Done!

  Tables:   kpdemo_categories, kpdemo_users, kpdemo_user_interests, kpdemo_events, kpdemo_attendances
  Images:   public/uploads/kpcrudy-demo
  URL:      http://localhost:8080/kpcrudy-demo

To remove it: php spark kpcrudy:demo uninstall
```

`php spark kpcrudy:demo uninstall`:

```
The demo will be removed:
  - Tables: kpdemo_categories, kpdemo_users, kpdemo_user_interests, kpdemo_events, kpdemo_attendances
  - Files:  public/uploads/kpcrudy-demo
  - Routes: /kpcrudy-demo
Continue? [n, y]: y
The demo has been removed. The project is back to how it was.
```

`php spark kpcrudy:demo reset --yes`:

```
The demo data has been reset: http://localhost:8080/kpcrudy-demo
```

### Codis de sortida

| Codi | Quan |
|---|---|
| `0` | Acció feta; també si es respon `n` a la confirmació (`Cancelled.`) o si `uninstall` no troba res per esborrar |
| `1` | Error, acció desconeguda o sense acció (es mostra l'ajuda) |

### Errors habituals

| Missatge | Causa i solució |
|---|---|
| `The demo is already installed.` | `install` no es pot executar dues vegades. Fes servir `reset` per tornar a les dades inicials |
| `Some demo tables already exist: ...` | Han quedat taules d'una instal·lació incompleta. `uninstall` les esborra; `install --force` les esborra i torna a instal·lar |
| `The demo is not installed. Use "php spark kpcrudy:demo install".` | `reset` sense la demo instal·lada |
| `Refusing to install the demo: ENVIRONMENT is "production".` | L'entorn és `production`. Instal·la-la en desenvolupament o, si de debò cal, afegeix `--allow-production` |
| `Cannot write to public/.` / `Cannot write to writable/.` | Permisos d'escriptura de les carpetes del projecte |
| `The demo could not be installed: ...` | Error de base de dades (connexió, permisos...). Tot el que s'havia creat s'ha esborrat; corregeix-lo i torna-ho a provar |
| `Warning: the GD extension is not available; ...` | Avís: s'instal·la sense imatges. Activa `extension=gd` al `php.ini` i fes `reset` |
| `Warning: route discovery for modules is disabled in this project.` | Avís: afegeix `\Kpcrudy\Demo\Demo::routes($routes);` a `app/Config/Routes.php` (i treu-la quan la desinstal·lis) |

---

## kpcrudy:maskid

Mostra el codi emmascarat que correspon a un o més identificadors reals, tal com surt a les URL d'un CRUD amb `maskIds()` (`edit/aNiPDZ8eyg3JeFxXeA` en lloc d'`edit/1`). Serveix per construir a mà una URL a un registre concret, per comprovar que un enllaç apunta al registre correcte o per preparar dades de prova. El codi d'un registre és sempre el mateix, però depèn del context i de la clau: per obtenir el de la web, la comanda ha de fer servir la mateixa configuració que el CRUD (vegeu [La clau](#la-clau-la-mateixa-que-laplicació-web)).

### Sintaxi

```bash
php spark kpcrudy:maskid <context> <id> [<id>...] [--config Classe]
```

### Arguments i opcions

| Argument / opció | Descripció |
|---|---|
| `context` | El nom de la taula del CRUD, o el valor passat a `maskIds(context: ...)` si n'hi ha |
| `id` | Un o més identificadors reals |
| `--config` | Classe de configuració del CRUD, si no és `app/Config/Kpcrudy.php` (a la demo, `"Kpcrudy\Demo\Config\CrudConfig"`) |

### Exemples

```bash
php spark kpcrudy:maskid invoices 5                       # projecte amb la configuració per defecte
php spark kpcrudy:maskid invoices 5 6 7                   # diversos ids alhora
php spark kpcrudy:maskid customer-invoices 5              # CRUD amb maskIds(context: 'customer-invoices')
php spark kpcrudy:maskid kpdemo_events 1 2 --config "Kpcrudy\Demo\Config\CrudConfig"   # demo
```

### Sortida

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

La fitxa de l'esdeveniment 1 és, per tant, `kpcrudy-demo/admin/events/show/aNiPDZ8eyg3JeFxXeA`.

### Codis de sortida

| Codi | Quan |
|---|---|
| `0` | Tots els ids s'han convertit |
| `1` | Falten arguments, la classe de `--config` no és vàlida o la clau no és correcta |

### Errors habituals

| Missatge | Causa i solució |
|---|---|
| `Usage: php spark kpcrudy:maskid <context> <id>...` | Falta el context o l'id |
| `The class "App\Config\Nope" does not exist or does not extend Kpcrudy\Config\Kpcrudy.` | Nom de classe mal escrit (amb el namespace complet i entre cometes) o la classe no estén `Kpcrudy\Config\Kpcrudy` |
| `Kpcrudy: $maskKey ha de tenir com a mínim 32 caràcters ...` | La clau de la configuració és massa curta. Genera'n una amb `bin2hex(random_bytes(32))` |
| `The kpcrudy demo is not installed (php spark kpcrudy:demo install).` | S'ha fet servir la configuració de la demo sense tenir-la instal·lada |
| El codi no coincideix amb el de la web | Context o clau diferents: comprova el context (`maskIds(context: ...)`) i afegeix `--config` si el CRUD rep una configuració pròpia |

---

## kpcrudy:unmaskid

Fa el contrari que `maskid`: diu quin identificador real correspon a un o més codis emmascarats. És útil quan un usuari envia una URL (`.../edit/aNiPDZ8eyg3JeFxXeA`), quan un codi apareix en un registre d'errors o en una incidència, o per saber quin registre obre un enllaç. Un codi fals, modificat o d'un altre context no dona cap id: surt `invalid code for this context`. Com `maskid`, ha de fer servir la mateixa configuració que el CRUD.

### Sintaxi

```bash
php spark kpcrudy:unmaskid <context> <codi> [<codi>...] [--config Classe]
```

### Arguments i opcions

| Argument / opció | Descripció |
|---|---|
| `context` | El nom de la taula del CRUD, o el valor passat a `maskIds(context: ...)` |
| `codi` | Un o més codis emmascarats: el segment de la URL que va després de `edit/`, `show/`... |
| `--config` | Classe de configuració del CRUD, si no és `app/Config/Kpcrudy.php` (a la demo, `"Kpcrudy\Demo\Config\CrudConfig"`) |

### Exemples

```bash
php spark kpcrudy:unmaskid invoices Xk3f9QaLm2PwE7
php spark kpcrudy:unmaskid kpdemo_events aNiPDZ8eyg3JeFxXeA J2ghJzT9uHikdfUq1A --config "Kpcrudy\Demo\Config\CrudConfig"
```

### Sortida

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

El mateix codi en un altre context (`php spark kpcrudy:unmaskid kpdemo_users aNiPDZ8eyg3JeFxXeA --config "Kpcrudy\Demo\Config\CrudConfig"`):

```
Context: kpdemo_users
+--------------------+-------------------------------+
| Masked code        | Id                            |
+--------------------+-------------------------------+
| aNiPDZ8eyg3JeFxXeA | invalid code for this context |
+--------------------+-------------------------------+
```

### Codis de sortida

| Codi | Quan |
|---|---|
| `0` | Tots els codis són vàlids |
| `1` | Algun codi no és vàlid per a aquest context (la taula mostra els que sí que ho són), falten arguments o la classe de `--config` no és vàlida |

### Errors habituals

| Missatge | Causa i solució |
|---|---|
| `invalid code for this context` | El codi és d'una altra taula o context, s'ha copiat malament (amb `?` o `/` de més) o s'ha calculat amb una altra clau. Comprova el context i, si el CRUD rep una configuració pròpia, afegeix `--config` |
| `Usage: php spark kpcrudy:unmaskid <context> <code>...` | Falta el context o el codi |
| `The class "..." does not exist or does not extend Kpcrudy\Config\Kpcrudy.` | Vegeu [kpcrudy:maskid](#kpcrudymaskid) |

---

## kpcrudy:signurl

Crea una URL signada: hi afegeix `kp_sig` (la signatura) i, si caduca, `kp_exp` (el moment de caducitat). Serveix per compartir una pàgina protegida amb `requireSignature()` (com l'agenda compartida de la demo) o una ruta pròpia que comprova la signatura amb `Crud::checkSignature()`, sense que qui la rep hagi d'iniciar sessió: per correu, en un missatge, en un codi QR... La signatura cobreix el camí i la caducitat (i, sense `--free-query`, tots els paràmetres): canviar-ne una sola lletra la invalida, i la caducitat no es pot allargar. No cobreix el domini ni el port. És l'equivalent de `Crud::signUrl()` i ha de fer servir la mateixa clau que la web (vegeu [La clau](#la-clau-la-mateixa-que-laplicació-web)).

### Sintaxi

```bash
php spark kpcrudy:signurl <url> [--ttl segons] [--free-query] [--config Classe]
```

### Arguments i opcions

| Argument / opció | Descripció | Per defecte |
|---|---|---|
| `url` | URL absoluta o camí relatiu al projecte (es completa amb `site_url()`, és a dir, amb `app.baseURL`). Entre cometes si porta `?` o `&` | — |
| `--ttl` | Segons fins que caduca: `3600` (1 hora), `86400` (1 dia), `604800` (7 dies)... `0` = no caduca mai | `86400` |
| `--free-query` | Només signa el camí: els paràmetres (`?q=`, `?page=`, `?sort=`...) es poden canviar. Fes-lo servir per a pàgines de CRUD amb `requireSignature()` | Se signa tota la URL |
| `--config` | Classe de configuració del CRUD, si no és `app/Config/Kpcrudy.php` (a la demo, `"Kpcrudy\Demo\Config\CrudConfig"`) | — |

Quan fer servir `--free-query`:

- **Pàgina d'un CRUD amb `requireSignature()`**: amb `--free-query`. El visitant hi podrà cercar, filtrar i canviar de pàgina; el que pot veure el limita `where()`, no la signatura.
- **Ruta pròpia amb `Crud::checkSignature()`** (una descàrrega, una confirmació...): sense `--free-query`, perquè els paràmetres (`?file=...`) no es puguin canviar. `checkSignature()` només accepta URL senceres llevat que se li passi `allowFreeQuery: true`.

### Exemples

```bash
# Agenda compartida de la demo, vàlida 7 dies; el visitant pot cercar i paginar
php spark kpcrudy:signurl kpcrudy-demo/shared/agenda --ttl 604800 --free-query --config "Kpcrudy\Demo\Config\CrudConfig"

# Fitxa d'un sol esdeveniment de la demo, vàlida 1 hora (codi de kpcrudy:maskid)
php spark kpcrudy:signurl kpcrudy-demo/shared/agenda/show/aNiPDZ8eyg3JeFxXeA --ttl 3600 --config "Kpcrudy\Demo\Config\CrudConfig"

# Descàrrega d'una ruta pròpia: el paràmetre file queda bloquejat
php spark kpcrudy:signurl "invoices/download?file=2026-001.pdf" --ttl 3600

# Enllaç que no caduca (només es pot anul·lar canviant la clau)
php spark kpcrudy:signurl shared/catalog --ttl 0 --free-query

# URL absoluta (per a un altre domini del mateix projecte)
php spark kpcrudy:signurl "https://intranet.example.com/shared/catalog" --ttl 86400 --free-query
```

### Sortida

`php spark kpcrudy:signurl kpcrudy-demo/shared/agenda --ttl 604800 --free-query --config "Kpcrudy\Demo\Config\CrudConfig"`:

```
http://localhost:8080/kpcrudy-demo/shared/agenda?kp_exp=1793621771&kp_sig=5oHTP3ugjCNNcfdWSIQujwXVeXQWsMe6
Valid until 2026-11-02 13:16:11.
```

Sense `--free-query` avisa que tota la URL queda bloquejada (`php spark kpcrudy:signurl kpcrudy-demo/shared/agenda/show/aNiPDZ8eyg3JeFxXeA --ttl 3600 --config "Kpcrudy\Demo\Config\CrudConfig"`):

```
http://localhost:8080/kpcrudy-demo/shared/agenda/show/aNiPDZ8eyg3JeFxXeA?kp_exp=1793020571&kp_sig=keNdCuyeT3NR3KPqgDIaAQgMj3MSEAfe
Valid until 2026-10-26 14:16:11.
The whole URL is signed: changing any parameter makes it invalid.
```

Amb `--ttl 0` no hi ha `kp_exp`:

```
http://localhost:8080/kpcrudy-demo/shared/agenda?kp_sig=mSa4Ovjxmc5GoEnjnZXm7lptEPnoWvXY
It never expires.
```

L'hora es mostra amb la zona horària de l'aplicació (`app.appTimezone`).

### Codis de sortida

| Codi | Quan |
|---|---|
| `0` | URL signada |
| `1` | Falta la URL, `--ttl` no és un nombre o la configuració no és vàlida |

### Errors habituals

| Missatge o símptoma | Causa i solució |
|---|---|
| `--ttl must be a number of seconds (0 = the link never expires).` | `--ttl` porta unitats (`7d`) o és negatiu. Escriu els segons: `604800` per a 7 dies |
| `Usage: php spark kpcrudy:signurl <url> ...` | Falta la URL |
| La URL comença per `http://localhost:8080/` en lloc del domini real | El camí relatiu es completa amb `app.baseURL` del `.env`. Posa-hi l'adreça pública o passa una URL absoluta (la signatura no depèn del domini) |
| La web diu *This link is not valid.* | La comanda ha fet servir una altra clau (falta `--config`), el camí no és el que veu CodeIgniter (p. ex. sense el prefix `kpcrudy-demo/`) o la ruta pròpia no accepta `--free-query`. Comprova-ho amb [kpcrudy:checkurl](#kpcrudycheckurl) |
| La comanda s'atura o el shell es queixa de `&` | La URL porta `&` i no està entre cometes |

---

## kpcrudy:checkurl

Comprova la signatura d'una URL i en mostra la caducitat: diu si és vàlida, si ha caducat (però és correcta), si no està signada o si no és vàlida (s'ha modificat, s'ha signat amb una altra clau o és d'un altre projecte). És l'eina per diagnosticar un "el meu enllaç no funciona" sense haver d'obrir-lo: només cal que l'usuari t'enviï la URL. Diu què cobreix la signatura (la URL sencera o només el camí) i qui l'ha feta: el desenvolupador (`kpcrudy:signurl`, `Crud::signUrl()`, `signedUrl()`) o un CRUD mentre es navegava (la pàgina 2, una fitxa...). Ha de fer servir la mateixa clau que la web (vegeu [La clau](#la-clau-la-mateixa-que-laplicació-web)).

### Sintaxi

```bash
php spark kpcrudy:checkurl "<url>" [--config Classe]
```

### Arguments i opcions

| Argument / opció | Descripció |
|---|---|
| `url` | URL signada, absoluta o relativa al projecte. **Entre cometes**: porta `&` |
| `--config` | Classe de configuració del CRUD, si no és `app/Config/Kpcrudy.php` (a la demo, `"Kpcrudy\Demo\Config\CrudConfig"`) |

### Exemples

```bash
# Enllaç de l'agenda compartida de la demo
php spark kpcrudy:checkurl "http://localhost:8080/kpcrudy-demo/shared/agenda?kp_exp=1793621771&kp_sig=5oHTP3ugjCNNcfdWSIQujwXVeXQWsMe6" --config "Kpcrudy\Demo\Config\CrudConfig"

# També es pot passar sense domini
php spark kpcrudy:checkurl "/kpcrudy-demo/shared/agenda?kp_exp=1793621771&kp_sig=5oHTP3ugjCNNcfdWSIQujwXVeXQWsMe6" --config "Kpcrudy\Demo\Config\CrudConfig"

# En un script: acaba amb 0 només si és vàlida
php spark kpcrudy:checkurl "$URL" || echo "Cal enviar un enllaç nou"
```

### Sortida

Enllaç vàlid signat amb `--free-query` (també si s'hi han afegit paràmetres, com `&q=jazz&page=2`):

```
Valid signature (path only: the parameters can change; signed with signUrl() or signedUrl())
Expires: 2026-11-02 13:16:11
```

Enllaç vàlid signat sencer:

```
Valid signature (path and parameters; signed with signUrl() or signedUrl())
Expires: 2026-10-26 14:16:11
```

Enllaç vàlid sense caducitat (`--ttl 0`):

```
Valid signature (path and parameters; signed with signUrl() or signedUrl())
It never expires.
```

La mateixa URL amb el `kp_exp` canviat per allargar-la, o amb un altre codi a la fitxa:

```
Invalid signature (modified URL, another key or another project)
```

Enllaç caducat:

```
Expired (the signature is correct)
Expired: 2026-10-26 13:16:33
```

URL sense signatura (`http://localhost:8080/kpcrudy-demo/shared/agenda`):

```
Not signed (no kp_sig parameter)
```

### Codis de sortida

| Codi | Quan |
|---|---|
| `0` | `Valid signature` |
| `1` | `Expired`, `Not signed`, `Invalid signature`, falta la URL o la configuració no és vàlida |

### Errors habituals

| Missatge o símptoma | Causa i solució |
|---|---|
| `Invalid signature` amb un enllaç que acabes de crear | Falta `--config` (o no és la mateixa que a `signurl`): la comprovació fa servir una altra clau. Al projecte de la demo, sense `--config` surt sempre `Invalid signature` |
| `signed by a CRUD while browsing` | És un enllaç copiat de dins d'una pàgina (la pàgina 2, una fitxa): és correcte, però només l'accepta aquell CRUD, mai una ruta pròpia amb `Crud::checkSignature()` | Normal. Si el problema és que ha caducat, caduca alhora que l'enllaç original amb què es va entrar |
| `Invalid signature` d'un enllaç que abans funcionava | S'ha canviat la clau (`$maskKey`, `encryption.key` o `writable/kpcrudy/mask.key`) o l'enllaç s'ha tallat en copiar-lo (el client de correu ha partit la línia). Envia'n un de nou |
| `Expired` | Ha passat la data de `kp_exp`. No es pot allargar: crea'n un altre amb `kpcrudy:signurl` |
| `Not signed` | S'ha perdut la part `?kp_exp=...&kp_sig=...` (sovint per no posar la URL entre cometes) |
| `Usage: php spark kpcrudy:checkurl "<url>" [--config Class]` | Falta la URL |

---

## kpcrudy:publish

Copia al projecte una part de la llibreria per adaptar-la sense tocar `vendor/` (els canvis fets a `vendor/` es perden en actualitzar):

- **`config`**: crea `app/Config/Kpcrudy.php`, una classe `Config\Kpcrudy` que estén `\Kpcrudy\Config\Kpcrudy` i que porta **totes** les opcions, amb el valor per defecte i el comentari de la llibreria, com a referència. A partir d'aquí, `config('Kpcrudy')` retorna aquesta classe. Les opcions que esborris del fitxer agafen el valor de la llibreria, també les que s'hi afegeixin en versions futures: n'hi ha prou de deixar-hi les que canviïs.
- **`theme <tema>`**: copia les plantilles d'un tema (`bootstrap5`, `w3css`, `tailwind` o un dels registrats a `$themes`) a `app/Views/kpcrudy/<nou>` i registra el tema nou a `$themes` d'`app/Config/Kpcrudy.php` (si aquest fitxer no existeix, el crea com `config`). El nom del tema nou és el de `--as` (per defecte, `<tema>-custom`) i no pot ser el d'un tema de la llibreria.
  - **Còpia sencera**: el pare del tema nou és el pare del tema original (`bootstrap5`), de manera que les plantilles que amplien el pare amb `renderParent()` continuen funcionant igual.
  - **Còpia parcial** (`--only`): el pare és el tema original, que dona les plantilles que no s'han copiat. Una plantilla que amplia el seu pare amb `renderParent()` (com `partials/assets` de `w3css` i `tailwind`) no es duplica: s'hi escriu una ampliació (`renderParent()` i un `<style>` buit) on afegir només el que vulguis.
  - Després, fes-lo servir en un CRUD amb `->setTheme('<nou>')` o en tots amb `$theme = '<nou>'` a `app/Config/Kpcrudy.php`. Com es fa un tema, a [new_theme_CA.md](new_theme_CA.md).
- **`lang <idioma>`**: copia `src/Language/<idioma>/Kpcrudy.php` de la llibreria a `app/Language/<idioma>/Kpcrudy.php`, amb un comentari al principi. CodeIgniter combina els dos fitxers: els textos del projecte tenen preferència i els que hi faltin surten de la llibreria. Per tant, pots esborrar les línies que no canviïs (i els textos nous de versions futures continuaran apareixent).

No sobreescriu mai cap fitxer sense `--force`. L'única excepció és `app/Config/Kpcrudy.php` quan s'hi registra un tema: s'actualitza afegint-hi una línia a `$themes`. Amb `--dry-run` només mostra què faria. Els fitxers s'escriuen de manera atòmica (es desen a part i es reanomenen): un error no deixa cap fitxer a mitges.

### Sintaxi

```bash
php spark kpcrudy:publish config [--force] [--dry-run]
php spark kpcrudy:publish theme <tema> [--as nom] [--only plantilla,plantilla...] [--path carpeta] [--force] [--dry-run]
php spark kpcrudy:publish lang <idioma> [--force] [--dry-run]
```

Sense arguments, pregunta què es vol publicar (`config`, `theme` o `lang`) i, si cal, el tema o l'idioma.

### Arguments i opcions

| Argument / opció | Descripció | Per defecte |
|---|---|---|
| `config` / `theme` / `lang` | Què es copia: la configuració, un tema o els textos d'un idioma | Es pregunta |
| `tema` (amb `theme`) | Tema que es copia: `bootstrap5`, `w3css`, `tailwind` o un dels registrats a `$themes` | Es pregunta |
| `idioma` (amb `lang`) | Idioma que es copia: `ca`, `es`, `en`, `fr`, `pt` o `de` | Es pregunta |
| `--as` | `theme`: nom del tema nou. Minúscules, números, `-` i `_`; no pot ser `bootstrap5`, `w3css` ni `tailwind` | `<tema>-custom` |
| `--only` | `theme`: copia només aquestes plantilles, separades per comes i sense `.php` (`list,partials/pagination`). La resta surt del tema original | Totes |
| `--path` | `theme`: carpeta de destinació | `app/Views/kpcrudy/<nom>` |
| `--force` | Reemplaça els fitxers que ja existeixen. Amb `config`, torna a generar `app/Config/Kpcrudy.php` des de zero: es perden els canvis i els temes registrats | No |
| `--dry-run` | Mostra els fitxers que es crearien (`create`), s'actualitzarien (`update`) o se saltarien (`skip`), sense escriure res | No |

Les plantilles d'un tema (els noms que accepta `--only`) són `form`, `import`, `layout`, `list`, `show`, `partials/assets`, `partials/button`, `partials/field`, `partials/filters`, `partials/flash` i `partials/pagination`.

### Exemples

```bash
# Configuració del projecte, amb totes les opcions com a referència
php spark kpcrudy:publish config

# Un tema propi a partir de w3css (còpia sencera); primer, veure què faria
php spark kpcrudy:publish theme w3css --as intranet --dry-run
php spark kpcrudy:publish theme w3css --as intranet

# Canviar només el llistat i afegir CSS al tema tailwind (la resta surt de tailwind)
php spark kpcrudy:publish theme tailwind --as tw --only list,partials/assets

# Un tema en una altra carpeta
php spark kpcrudy:publish theme bootstrap5 --as admin --path app/Views/admin/kpcrudy

# Textos en català propis del projecte
php spark kpcrudy:publish lang ca

# Mode interactiu
php spark kpcrudy:publish
```

**Afegir una opció de paginació.** Després de `php spark kpcrudy:publish config`, canvia aquesta línia d'`app/Config/Kpcrudy.php`:

```php
public array $perPageOptions = [10, 25, 50, 100, 250];
```

Tots els CRUD que fan servir la configuració del projecte ja ofereixen 250 registres per pàgina.

### Sortida

`php spark kpcrudy:publish config`:

```
  create    app/Config/Kpcrudy.php

Edit app/Config/Kpcrudy.php: config('Kpcrudy') now returns this class.
It contains every option with its default value and comment; change only what you need.
```

`php spark kpcrudy:publish theme w3css --as intranet --dry-run` (còpia sencera; `update` vol dir que el tema s'afegiria a `$themes`):

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

`php spark kpcrudy:publish theme tailwind --as tw --only list,partials/assets` (còpia parcial):

```
  create    app/Views/kpcrudy/tw/list.php
  create    app/Views/kpcrudy/tw/partials/assets.php
  update    app/Config/Kpcrudy.php

Theme "tw": 2 template(s) in app/Views/kpcrudy/tw (the rest comes from "tailwind").
...
```

Després de les dues comandes (sense `--dry-run`), `$themes` d'`app/Config/Kpcrudy.php` queda així:

```php
    public array $themes = [
        'intranet' => ['path' => APPPATH . 'Views/kpcrudy/intranet', 'parent' => 'bootstrap5'],
        'tw' => ['path' => APPPATH . 'Views/kpcrudy/tw', 'parent' => 'tailwind'],
    ];
```

`intranet` és una còpia sencera de `w3css`: el seu pare és el de `w3css` (`bootstrap5`). `tw` és una còpia parcial: el seu pare és `tailwind`, que dona la resta de plantilles. Com que `partials/assets` de `tailwind` ja amplia el seu pare, a `tw/partials/assets.php` s'hi ha escrit una ampliació en lloc d'una còpia:

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

Si un fitxer ja existeix, no es toca (amb `--force`, es reemplaça):

```
  skip      app/Views/kpcrudy/intranet/list.php (it exists; use --force to replace it)
```

### Codis de sortida

| Codi | Quan |
|---|---|
| `0` | Fitxers escrits (o mostrats, amb `--dry-run`); també si se n'ha saltat algun perquè ja existia |
| `1` | Qualsevol error dels de sota que no és un avís |

### Errors habituals

| Missatge | Causa i solució |
|---|---|
| `"bootstrap5" is a theme of the library: choose another name with --as.` | El tema nou no pot tenir el nom d'un tema de la llibreria. Tria'n un altre amb `--as` |
| `Invalid theme name "..."` | `--as` només admet minúscules, números, `-` i `_`, i ha de començar amb una lletra |
| `The theme "nope" does not exist. Themes: bootstrap5, w3css, tailwind, intranet, tw.` | Tema mal escrit. La llista inclou els temes registrats a `$themes` |
| `Unknown templates: nolist. Templates of w3css: form, import, layout, list, partials/assets, partials/button, partials/field, partials/filters, partials/flash, partials/pagination, show.` | `--only` porta un nom que no és cap plantilla del tema. Fes servir els noms de la llista, sense `.php` |
| `  skip      <fitxer> (it exists; use --force to replace it)` | Avís: el fitxer ja existeix i no s'ha tocat. Afegeix `--force` per reemplaçar-lo (es perden els canvis) |
| `app/Config/Kpcrudy.php already exists (use --force to replace it).` | Avís: la configuració ja és al projecte. `--force` la torna a generar des de zero (es perden els canvis i els temes registrats) |
| `The theme "intranet" is already registered in app/Config/Kpcrudy.php: check its path and parent.` | Avís: el tema ja era a `$themes` i no s'hi ha canviat res. Comprova que el camí i el pare són els que vols |
| `Add this line to $themes in app/Config/Kpcrudy.php:` | Avís: no s'ha trobat `public array $themes = [...]` al fitxer (s'ha modificat a mà). Afegeix-hi la línia que es mostra |
| `The language "xx" does not exist. Languages: ca, de, en, es, fr, pt.` | La llibreria no té aquest idioma. Per afegir-ne un, vegeu [howto_CA.md](howto_CA.md) |
| `Cannot write ...` / `Cannot create the folder ...` | Permisos d'escriptura d'`app/` (o de la carpeta de `--path`) |

---

## Receptes amb comandes

### Generar un CRUD i comprovar-lo

1. Mira què generarà, sense escriure res:

   ```bash
   php spark kpcrudy:make clients --controller Admin/Clients --route admin/clients --filter auth --dry-run
   ```

2. Revisa el resum (`1-N`, `N-N`, `unique`): si falta una relació, és que la taula no té la clau forana. Quan estigui bé, torna-ho a executar sense `--dry-run`.
3. Comprova la ruta i obre l'URL que ha mostrat la comanda:

   ```bash
   php spark routes | grep admin/clients        # PowerShell: php spark routes | Select-String admin/clients
   php spark serve
   ```

4. Adapta el codi generat (etiquetes, filtres, permisos...). Si el vols tornar a generar des de zero, afegeix `--force` (es perden els canvis fets al mètode).

### Instal·lar i treure la demo

```bash
php spark kpcrudy:demo install          # crea taules, dades, imatges i el fitxer de marca
php spark kpcrudy:demo status           # comprova-ho: "Installed: yes" i "Routes: active"
php spark serve                         # obre http://localhost:8080/kpcrudy-demo
php spark kpcrudy:demo reset --yes      # després de fer proves, dades inicials
php spark kpcrudy:demo uninstall --yes  # treure-la del tot
php spark kpcrudy:demo status           # "Installed: no" i totes les taules "missing"
```

Si `install` diu `Some demo tables already exist`, fes `php spark kpcrudy:demo install --force`.

### Saber a quin registre es refereix una URL

Un usuari envia `http://localhost:8080/kpcrudy-demo/admin/events/edit/J2ghJzT9uHikdfUq1A`. El context és la taula del CRUD de `admin/events` (o el de `maskIds(context: ...)`, si en té), i el codi, l'últim segment:

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

En un projecte propi, sense `--config` si el CRUD fa servir `app/Config/Kpcrudy.php`. A l'inrevés, per construir l'URL d'un registre, `kpcrudy:maskid`.

### Enviar per correu un enllaç signat vàlid 7 dies

7 dies són `7 × 86400 = 604800` segons. Per a una pàgina de CRUD amb `requireSignature()`, fes servir `--free-query` perquè el destinatari hi pugui cercar i paginar:

```bash
php spark kpcrudy:signurl kpcrudy-demo/shared/agenda --ttl 604800 --free-query --config "Kpcrudy\Demo\Config\CrudConfig"
```

```
http://localhost:8080/kpcrudy-demo/shared/agenda?kp_exp=1793621771&kp_sig=5oHTP3ugjCNNcfdWSIQujwXVeXQWsMe6
Valid until 2026-11-02 13:16:11.
```

- Comprova abans que `app.baseURL` és l'adreça pública: és la que surt a l'enllaç.
- Enganxa l'enllaç sencer al correu; si el client el parteix en dues línies, la signatura no serà vàlida.
- Qualsevol que tingui l'enllaç hi pot entrar fins que caduqui. No el facis servir per a dades que només ha de veure una persona.
- Per enviar-lo des del codi (per exemple, en un correu automàtic), fes servir `Crud::signUrl()` o `$crud->signedUrl()`: vegeu [functions_CA.md](functions_CA.md).

### Diagnosticar un "el meu enllaç no funciona"

1. Demana la URL **exacta** que fa servir l'usuari i comprova-la, entre cometes:

   ```bash
   php spark kpcrudy:checkurl "<url de l'usuari>" --config "Kpcrudy\Demo\Config\CrudConfig"
   ```

2. Segons el resultat:

   | Resultat | Què ha passat | Què fer |
   |---|---|---|
   | `Valid signature` | L'enllaç és bo | El problema és un altre: la ruta no existeix en aquest entorn, el registre ja no compleix el `where()` (p. ex. l'esdeveniment ja ha passat) o l'usuari obre una altra adreça |
   | `Expired` | Ha caducat (a la data que indica) | Envia'n un de nou amb `kpcrudy:signurl` |
   | `Invalid signature` | S'ha modificat (o tallat), és d'un altre entorn o s'ha canviat la clau | Compara-la amb la que es va enviar; si és la mateixa, la clau ha canviat: envia'n una de nova |
   | `Not signed` | Falta `kp_sig` | L'usuari ha copiat l'adreça sense els paràmetres |

3. Si la URL és la fitxa d'un registre, mira de quin es tracta amb `kpcrudy:unmaskid` (el codi és el segment que va després de `show/`):

   ```bash
   php spark kpcrudy:unmaskid kpdemo_events aNiPDZ8eyg3JeFxXeA --config "Kpcrudy\Demo\Config\CrudConfig"
   ```

`checkurl` també reconeix els enllaços copiats de dins d'una pàgina del CRUD (la pàgina 2, una fitxa): surten com a `signed by a CRUD while browsing` i caduquen alhora que l'enllaç original amb què s'hi va entrar.

### Adaptar la configuració i el tema sense tocar la llibreria

1. Crea la configuració del projecte i canvia-hi només el que necessitis (per exemple, `$perPageOptions` o `$dateFormat`):

   ```bash
   php spark kpcrudy:publish config
   ```

2. Fes un tema propi a partir del que s'assembli més al que vols. Si només cal canviar una o dues plantilles, copia només aquestes amb `--only`: la resta continua sortint del tema original i rep les millores de la llibreria.

   ```bash
   php spark kpcrudy:publish theme w3css --as intranet --only list,partials/assets --dry-run
   php spark kpcrudy:publish theme w3css --as intranet --only list,partials/assets
   ```

3. Fes-lo servir a tots els CRUD amb `$theme = 'intranet';` a `app/Config/Kpcrudy.php`, o en un de sol amb `->setTheme('intranet')`, i edita les plantilles d'`app/Views/kpcrudy/intranet`.
4. Si vols canviar algun text, copia els de l'idioma i deixa-hi només les línies que canviïs:

   ```bash
   php spark kpcrudy:publish lang ca
   ```

En actualitzar la llibreria, aquests fitxers no es toquen, i les opcions i els textos que hagis esborrat agafen els valors nous de la llibreria.
