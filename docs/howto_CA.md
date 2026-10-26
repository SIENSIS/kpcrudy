# Receptes

[English](howto_EN.md) · **Català**

Solucions curtes per a casos habituals amb **kpcrudy**. Cada recepta és independent. La descripció de cada funció i de totes les seves opcions és a [functions_CA.md](functions_CA.md).

Als exemples, `$crud` és una instància de `Kpcrudy\Crud` dins d'un mètode de controlador que acaba amb `return $crud->render();`.

## Índex

**Primers passos**
- [CRUD mínim](#crud-mínim)
- [Generar el CRUD d'una taula amb una comanda](#generar-el-crud-duna-taula-amb-una-comanda)
- [Fer servir un model existent](#fer-servir-un-model-existent)
- [Ruta que acaba en una paraula reservada](#ruta-que-acaba-en-una-paraula-reservada)

**Contrasenyes**
- [Gestionar un camp de contrasenya](#gestionar-un-camp-de-contrasenya)
- [Demanar la contrasenya dues vegades](#demanar-la-contrasenya-dues-vegades)
- [Fer servir un altre algorisme de hash](#fer-servir-un-altre-algorisme-de-hash)
- [Contrasenya gestionada pel model](#contrasenya-gestionada-pel-model)
- [Botó per restablir la contrasenya](#botó-per-restablir-la-contrasenya)

**Enllaços i accions**
- [Enllaçar a una pàgina del projecte amb l'id](#enllaçar-a-una-pàgina-del-projecte-amb-lid)
- [Enllaçar a una pàgina externa amb l'id](#enllaçar-a-una-pàgina-externa-amb-lid)
- [Enllaçar amb l'id emmascarat](#enllaçar-amb-lid-emmascarat)
- [Accions que modifiquen dades](#accions-que-modifiquen-dades)
- [Mostrar una acció només en alguns registres](#mostrar-una-acció-només-en-alguns-registres)
- [Obrir el llistat ja filtrat o ordenat](#obrir-el-llistat-ja-filtrat-o-ordenat)
- [Accions massives pròpies](#accions-massives-pròpies)

**Importació CSV**
- [Importar dades des d'un CSV](#importar-dades-des-dun-csv)
- [Exportar, editar i tornar a importar](#exportar-editar-i-tornar-a-importar)
- [Actualitzar registres per un camp únic](#actualitzar-registres-per-un-camp-únic)
- [Importar usuaris amb contrasenya](#importar-usuaris-amb-contrasenya)
- [Importar només per a alguns usuaris](#importar-només-per-a-alguns-usuaris)
- [Valors en formats especials](#valors-en-formats-especials)

**Dades i seguretat**
- [Limitar el CRUD a l'usuari connectat](#limitar-el-crud-a-lusuari-connectat)
- [Desar qui ha creat el registre](#desar-qui-ha-creat-el-registre)
- [Permisos per rol i per registre](#permisos-per-rol-i-per-registre)
- [Validacions entre camps](#validacions-entre-camps)
- [Impedir esborrar si té registres relacionats](#impedir-esborrar-si-té-registres-relacionats)
- [Diversos CRUD sobre la mateixa taula](#diversos-crud-sobre-la-mateixa-taula)
- [Un perfil d'usuari: CRUD d'un sol registre](#un-perfil-dusuari-crud-dun-sol-registre)

**Relacions**
- [Relació 1-N amb opcions filtrades](#relació-1-n-amb-opcions-filtrades)
- [Relació N-N](#relació-n-n)
- [Taula intermèdia amb dades pròpies](#taula-intermèdia-amb-dades-pròpies)
- [CRUD dels fills d'un registre](#crud-dels-fills-dun-registre)

**Presentació**
- [Desplegables amb etiquetes](#desplegables-amb-etiquetes)
- [Formats: moneda, insígnies i textos](#formats-moneda-insígnies-i-textos)
- [Columnes calculades sense una consulta per fila](#columnes-calculades-sense-una-consulta-per-fila)
- [Pujar fitxers i imatges](#pujar-fitxers-i-imatges)

**Integració**
- [Integrar el CRUD al layout del projecte](#integrar-el-crud-al-layout-del-projecte)
- [CRUD dins d'una vista pròpia](#crud-dins-duna-vista-pròpia)
- [Canviar una plantilla](#canviar-una-plantilla)
- [Configuració diferent per a un CRUD](#configuració-diferent-per-a-un-crud)
- [Canviar un text o afegir un idioma](#canviar-un-text-o-afegir-un-idioma)

---

## Primers passos

### CRUD mínim

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

Amb això ja tens llistat, cerca, ordenació, paginació, exportació, alta, edició, fitxa i esborrat, i paperera si la taula té `deleted_at`.

### Generar el CRUD d'una taula amb una comanda

En lloc d'escriure el mètode a mà, `kpcrudy:make` el genera a partir de l'estructura de la taula i afegeix la ruta a `app/Config/Routes.php`.

**Mode interactiu.** Sense arguments, la comanda mostra les taules de la base de dades i pregunta cada valor, amb una proposta per defecte (n'hi ha prou de prémer Retorn):

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

**Amb arguments.** Per fer-ho d'una tirada (o des d'un script), passa la taula i les opcions; el que no s'indiqui pren el valor per defecte:

```bash
php spark kpcrudy:make clients --controller Admin/Clients --route admin/clients --filter auth
```

**Veure el codi abans d'escriure'l**, amb `--dry-run`:

```bash
php spark kpcrudy:make clients --dry-run
```

**Diversos CRUD al mateix controlador.** Si el controlador ja existeix, s'hi afegeix el mètode nou (i l'`use Kpcrudy\Crud;` si no hi era):

```bash
php spark kpcrudy:make cities --controller Admin/Catalog --method cities --route admin/cities
php spark kpcrudy:make tags   --controller Admin/Catalog --method tags   --route admin/tags
```

Si el mètode ja existeix, la comanda s'atura; amb `--force` el reemplaça (útil després de canviar la taula, però es perden els canvis que hi haguessis fet). Si alguna comprovació falla (per exemple, una columna amb un nom que no és només lletres, números i `_`), no s'escriu cap fitxer.

**Amb un model.** `--model` crea `app/Models/{Taula}Model.php` amb els `allowedFields`, els timestamps, els soft deletes i les regles de validació (`required`, `max_length`, `valid_email` i `is_unique` amb `{id}`), i el CRUD el fa servir amb `setModel()`. Si el model ja existeix, es reaprofita només si és de la mateixa taula (amb `--force` es reemplaça).

El codi generat és un punt de partida. Revisa sobretot les etiquetes (es generen a partir dels noms dels camps), les columnes del llistat i el camp que es mostra a cada relació.

### Fer servir un model existent

Si ja tens un model amb validació, es reaprofita tal qual:

```php
$crud->setModel(\App\Models\ClientModel::class)
     ->setSubject('client', 'Clients');
```

Si les regles del model fan servir `{id}`, la clau primària ha de tenir una regla pròpia (CodeIgniter 4.5+):

```php
protected $validationRules = [
    'id'    => 'permit_empty|is_natural_no_zero',
    'email' => 'required|valid_email|is_unique[clients.email,id,{id}]',
];
```

### Ruta que acaba en una paraula reservada

El CRUD fa servir els últims segments de la URL per saber l'operació (`add`, `edit/5`, `trash`...). Si la teva ruta acaba amb una d'aquestes paraules, indica'n la base:

```php
\Kpcrudy\Crud::routes($routes, 'warehouse/trash', 'Warehouse::trash');

// Controlador
$crud->setTable('waste_items')->setBaseUrl(site_url('warehouse/trash'));
```

---

## Contrasenyes

### Gestionar un camp de contrasenya

Un camp que es diu `password` (o `contrasenya`) ja és de tipus contrasenya. Si es diu d'una altra manera, indica-ho amb `fieldType()`.

```php
$crud->fieldType('pass_hash', 'password')
     ->setRules('pass_hash', 'required|min_length[8]')
     ->displayAs('pass_hash', 'Contrasenya');
```

Comportament:

- Es desa amb `password_hash()`.
- A l'**alta**, la regla `required` s'aplica (també sense `setRules()`, si la columna és `NOT NULL` sense valor per defecte). A l'**edició** s'ignora: si es deixa en blanc, la contrasenya no canvia, i sota el camp surt "Deixa-ho en blanc per mantenir la contrasenya actual".
- No surt al llistat, a la fitxa, a la cerca, als filtres ni a l'exportació. Si la poses explícitament a `columns()`, es mostra `••••••`.
- Els altres camps secrets de la taula (`reset_token`, `api_key`, `salt`... segons `$sensitiveFields`) tampoc surten per defecte, ni tan sols als formularis.
- El formulari mai mostra el valor desat.

Per verificar-la en fer login:

```php
if (password_verify($request->getPost('password'), $user['pass_hash'])) {
    // correcta
}
```

### Demanar la contrasenya dues vegades

Amb `callbackField()` es pinten els dos inputs, i amb un hook es comprova que coincideixin:

```php
$crud->callbackField('password', fn ($value, ?array $row) =>
    '<input type="password" name="password" class="form-control mb-2" autocomplete="new-password" placeholder="Contrasenya"' . ($row === null ? ' required' : '') . '>'
    . '<input type="password" name="password_confirm" class="form-control" autocomplete="new-password" placeholder="Repeteix la contrasenya">'
);

$sameTwice = function () {
    $request = service('request');

    return $request->getPost('password') === $request->getPost('password_confirm')
        ? null
        : 'Les dues contrasenyes no coincideixen.';
};

$crud->beforeInsert($sameTwice)
     ->beforeUpdate($sameTwice);
```

El hook compara els valors que arriben del formulari, perquè a `$data` la contrasenya ja hi és amb hash.

### Fer servir un altre algorisme de hash

Amb `'hash' => false` la llibreria no aplica cap hash, i el pots fer tu en un hook. En edició, si el camp es deixa en blanc, no arriba a `$data`:

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

### Contrasenya gestionada pel model

Si el model ja aplica el hash en els seus callbacks (`beforeInsert`, `beforeUpdate`), desactiva el de la llibreria perquè no es faci dues vegades:

```php
$crud->setModel(\App\Models\UserModel::class)
     ->fieldType('password', 'password', ['hash' => false]);
```

### Botó per restablir la contrasenya

L'administrador no ha de veure ni escriure contrasenyes: un botó genera una contrasenya temporal i l'envia per correu.

```php
// Controlador del CRUD
$crud->addAction('Restableix la contrasenya', 'admin/users-reset-password/{id}', [
    'icon'    => 'bi bi-key',
    'method'  => 'post',
    'confirm' => 'Es generarà una contrasenya nova i s\'enviarà per correu. Continuar?',
]);
```

```php
// Routes.php
$routes->post('admin/users-reset-password/(:num)', 'Admin\Users::resetPassword/$1');

// Controlador
public function resetPassword(int $id)
{
    $user     = db_connect()->table('users')->where('id', $id)->get()->getRowArray();
    $password = bin2hex(random_bytes(6));

    db_connect()->table('users')->where('id', $id)->update(['password' => password_hash($password, PASSWORD_DEFAULT)]);

    service('email')->setTo($user['email'])
        ->setSubject('La teva contrasenya nova')
        ->setMessage("Contrasenya temporal: {$password}")
        ->send();

    return redirect()->back()->with('message', 'S\'ha enviat una contrasenya nova a ' . $user['email']);
}
```

Les accions amb `'method' => 'post'` envien el token CSRF; la ruta ha de ser `post`.

---

## Enllaços i accions

### Enllaçar a una pàgina del projecte amb l'id

`{id}` és la clau primària, i `{camp}` qualsevol camp del registre. Les URL relatives passen per `site_url()`:

```php
$crud->addAction('Factures', 'invoices/client/{id}', ['icon' => 'bi bi-receipt']);
$crud->addAction('Comandes', 'orders?client={id}&year={year}', ['icon' => 'bi bi-cart']);
```

Si cal construir la URL amb lògica, fes servir una closure:

```php
$crud->addAction('Fitxa pública', fn (array $row) => site_url('catalog/' . $row['category_slug'] . '/' . $row['slug']));
```

### Enllaçar a una pàgina externa amb l'id

Les URL que comencen per `http://`, `https://`, `//` o `/` es deixen tal qual. Els valors de `{...}` es codifiquen per a la URL:

```php
// Sistema extern que rep l'id
$crud->addAction('Veure al CRM', 'https://crm.example.com/contacts/{external_id}', [
    'icon'   => 'bi bi-box-arrow-up-right',
    'target' => '_blank',
]);

// Un camp com a paràmetre de cerca
$crud->addAction('Mapa', 'https://www.openstreetmap.org/search?query={address}', [
    'icon'   => 'bi bi-geo-alt',
    'target' => '_blank',
]);
```

Per combinar diversos camps en un sol paràmetre, fes servir una closure:

```php
$crud->addAction('Mapa', fn (array $row) =>
    'https://www.openstreetmap.org/search?query=' . rawurlencode($row['address'] . ', ' . $row['city']),
    ['target' => '_blank']
);
```

### Enllaçar amb l'id emmascarat

Quan l'enllaç surt del CRUD (una pàgina pública, un correu, un sistema extern) potser no vols que l'id es vegi o es pugui endevinar. La URL es construeix amb una closure, i la pàgina de destí desfà el canvi. Hi ha tres opcions:

| Opció | URL | Per a què |
|---|---|---|
| A. Xifrat | llarga, canvia cada vegada | Que l'id no es pugui conèixer de cap manera |
| B. Ofuscat (Sqids) | curta i fixa: `/invoice/Uk9pQ2` | Que no es vegi un número consecutiu |
| C. Signat | id visible + signatura: `/invoice/123/9f2c...` | Que l'id no es pugui canviar, i que l'enllaç caduqui |

Cap de les tres substitueix la comprovació de permisos a la pàgina de destí.

**A. Xifrat amb l'Encrypter de CodeIgniter.** Cal una clau: `php spark key:generate`.

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
// Controlador del CRUD
helper('mask');

$crud->addAction('Enllaç per al client', fn (array $row) => site_url('invoice/' . mask_id($row['id'])), [
    'icon'   => 'bi bi-link-45deg',
    'target' => '_blank',
]);
```

```php
// Routes.php
$routes->get('invoice/(:segment)', 'PublicInvoice::show/$1');

// Controlador de destí
public function show(string $token)
{
    helper('mask');
    $id = unmask_id($token) ?? throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();

    $invoice = model('InvoiceModel')->find($id);
    // ...
}
```

**B. Ofuscat amb Sqids.** Dona URL curtes i sempre iguals per al mateix id. No és xifratge: amb l'alfabet es pot desfer, però no es veu l'ordre ni la quantitat de registres.

```bash
composer require sqids/sqids
```

```php
$sqids = new \Sqids\Sqids(alphabet: 'k3G7QAe51FCsPW92uEOyq4Bg6Sp8YzVTmnU0liwDdHXLajZrfxNhobJIRcMvKt', minLength: 6);

$crud->addAction('Enllaç curt', fn (array $row) => site_url('i/' . $sqids->encode([(int) $row['id']])));

// Destí
$id = $sqids->decode($code)[0] ?? null;
```

Desa l'alfabet en un lloc compartit (una configuració) i barreja'l amb un ordre propi perquè els codis no coincideixin amb els d'altres projectes.

**C. Enllaç signat i amb caducitat.** L'id es veu, però si algú el canvia, la signatura deixa de ser vàlida. És útil per enviar enllaços per correu o a un sistema extern que comparteix el secret.

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
// Controlador del CRUD
helper('signed');
$crud->addAction('Enllaç de 24 h', fn (array $row) => signed_url('download', $row['id']));

// Routes.php
$routes->get('download/(:num)/(:num)/(:alphanum)', 'Downloads::get/$1/$2/$3');

// Destí
public function get(string $id, string $expires, string $sign)
{
    helper('signed');

    if (! signed_ok('download', $id, $expires, $sign)) {
        throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
    }
    // ...
}
```

> Aquestes tècniques són per als enllaços que **surten** del CRUD. Les URL internes del CRUD (`edit/5`, `show/5`) fan servir l'id real; la protecció d'aquestes URL la donen els permisos i `where()`.

### Accions que modifiquen dades

Una acció que canvia dades no ha de ser un enllaç GET: un cercador o una previsualització podria executar-la. Amb `'method' => 'post'` el botó envia un formulari amb el token CSRF:

```php
$crud->addAction('Duplica', 'admin/events-duplicate/{id}', [
    'icon'    => 'bi bi-copy',
    'method'  => 'post',
    'confirm' => 'Vols crear una còpia d\'aquest esdeveniment?',
]);
```

```php
// Routes.php: una ruta POST que no comenci com les del CRUD
$routes->post('admin/events-duplicate/(:num)', 'Admin\Events::duplicate/$1');

// Controlador
public function duplicate(int $id)
{
    // ... crea la còpia ...
    return redirect()->to(site_url('admin/events/edit/' . $newId))->with('message', 'Còpia creada.');
}
```

La ruta no pot ser `admin/events/duplicate/5`, perquè `admin/events/(:any)` ja és del CRUD. Fes servir un altre prefix, com `admin/events-duplicate/5`.

### Mostrar una acció només en alguns registres

```php
$crud->addAction('Paga', 'invoices/pay/{id}', [
    'icon'    => 'bi bi-credit-card',
    'class'   => 'btn-success',
    'method'  => 'post',
    'visible' => fn (array $row) => $row['status'] === 'pending',
]);
```

Si la condició depèn d'altres taules, calcula-ho abans del CRUD (vegeu [Columnes calculades sense una consulta per fila](#columnes-calculades-sense-una-consulta-per-fila)):

```php
$registered = array_column(db_connect()->table('attendances')->select('event_id')->where('user_id', $userId)->get()->getResultArray(), 'event_id');

$crud->addAction('Apunta-m\'hi', 'events-join/{id}', [
    'method'  => 'post',
    'visible' => fn (array $row) => ! in_array($row['id'], $registered),
]);
```

`visible` només amaga el botó. La ruta de destí ha de tornar a comprovar que l'acció és permesa.

### Obrir el llistat ja filtrat o ordenat

L'estat del llistat va a la URL, de manera que es pot enllaçar directament:

| Paràmetre | Exemple | Efecte |
|---|---|---|
| `q` | `?q=garcia` | Cerca |
| `f[camp]` | `?f[status]=paid` | Filtre de llista o de text |
| `f[camp][from]`, `f[camp][to]` | `?f[created_at][from]=2026-01-01` | Filtre de rang |
| `sort`, `dir` | `?sort=name&dir=desc` | Ordre |
| `page`, `per_page` | `?page=2&per_page=50` | Paginació |

El camp ha de ser a `filters()` (o als filtres per defecte) i a les columnes ordenables.

```php
// Al CRUD de clients, un botó per veure'n les comandes ja filtrades
$crud->addAction('Comandes', 'admin/orders?f[client_id]={id}&sort=date&dir=desc', ['icon' => 'bi bi-cart']);
```

```php
// A una vista qualsevol
<a href="<?= site_url('admin/orders?f[status]=pending') ?>">Comandes pendents</a>
```

### Accions massives pròpies

```php
$crud->addBulkAction('publish', 'Publica', function (array $ids) {
    db_connect()->table('posts')->whereIn('id', $ids)->update(['status' => 'published']);

    return count($ids) . ' articles publicats.';
});

// Amb confirmació, i disponible a la paperera
$crud->addBulkAction('anonymize', 'Anonimitza', function (array $ids) {
    db_connect()->table('users')->whereIn('id', $ids)->update(['email' => null, 'phone' => null]);

    return 'Dades personals eliminades.';
}, ['confirm' => 'No es pot desfer. Continuar?', 'trash' => true]);
```

`$ids` ja està filtrat per l'abast del CRUD (`where()`): no cal comprovar que els registres siguin "teus".

---

## Importació CSV

### Importar dades des d'un CSV

La importació no està activada per defecte, perquè modifica moltes dades de cop. S'activa a cada CRUD:

```php
$crud->setTable('products')
     ->enableImport();
```

Al llistat apareix el botó **Importa CSV**. L'usuari:

1. puja el fitxer i tria què cal fer amb cada fila (*afegir sempre* o *actualitzar si ja existeix*) i amb les files amb errors (*no importar res* o *saltar-les*);
2. veu la previsualització: columnes associades, files noves, files per actualitzar i errors per línia;
3. confirma la importació.

Un fitxer com aquest és vàlid, encara que les columnes estiguin en un altre ordre o n'hi hagi de més (s'ignoren):

```csv
Name;Category;Price;Active;Notes
Blue mug;Kitchen;12,50;Yes;from the old shop
Red mug;Kitchen;12.50;1;
```

Les columnes es reconeixen pel nom del camp (`category_id`) o per l'etiqueta (`Category`), i els desplegables i les relacions, per l'id o pel text (`Kitchen`). El botó **Descarrega la plantilla** de la pàgina d'importació genera un CSV amb la capçalera correcta.

| 1. Pujar el fitxer | 2. Previsualització |
|---|---|
| ![Pujar el CSV](../screenshots/15-importacio-pujada.png) | ![Previsualització](../screenshots/16-importacio-previsualitzacio.png) |

### Exportar, editar i tornar a importar

Amb la clau per defecte (la clau primària), un fitxer exportat té la columna `Id`, i en tornar-lo a importar amb *actualitzar si ja existeix* es modifiquen els registres corresponents:

1. **Exporta CSV** des del llistat (es pot filtrar abans).
2. Edita el fitxer amb un full de càlcul: canvia preus, estats, categories...
3. **Importa CSV** → *Actualitzar el registre si el camp "Id" ja existeix* → previsualitza → importa.

Les columnes calculades de l'exportació (columnes virtuals, relacions N-N de només lectura) s'ignoren soles. Les files sense `Id` o amb un `Id` que no existeix s'afegeixen com a registres nous. L'apòstrof que l'exportació posa davant dels valors que semblen fórmules (`'=...`) es treu en importar. En una actualització només es canvien els camps del formulari d'edició (`editFields()`).

Perquè el preu exportat es pugui tornar a llegir, exporta'l com a número:

```php
$crud->callbackColumn('price', fn ($value) => esc(number_format((float) $value, 2, ',', '.')) . ' €')   // llistat
     ->callbackExport('price', fn ($value) => number_format((float) $value, 2, '.', ''));             // CSV
```

### Actualitzar registres per un camp únic

Si el fitxer ve d'un altre sistema i no porta els ids de la base de dades, fes servir com a clau un camp únic:

```php
$crud->setTable('students')
     ->enableImport(['key' => 'email']);
```

Amb *actualitzar si ja existeix*, cada fila es busca per l'email: si hi és, s'actualitzen les columnes del fitxer (les altres no es toquen); si no hi és, s'afegeix. La cerca respecta el `where()` del CRUD, de manera que mai s'actualitza un registre de fora de l'abast.

Si la clau és un camp de només lectura en edició (`readonlyFields`), serveix per trobar el registre però no es modifica. Si el valor de la clau coincideix amb més d'un registre, la fila té un error: la clau ha de ser única.

### Importar usuaris amb contrasenya

Els camps de tipus `password` es desen amb `password_hash()`, com al formulari:

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

- `nora.bell` és nova: la contrasenya és obligatòria (s'aplica la regla `required`).
- `alice` ja existeix: amb la contrasenya buida no es canvia, i s'actualitzen l'email i el rol.

No envieu mai fitxers amb contrasenyes reals per correu. El més segur és importar sense contrasenyes i que cada usuari en creï una (vegeu [Botó per restablir la contrasenya](#botó-per-restablir-la-contrasenya)).

### Importar només per a alguns usuaris

La importació té el seu propi permís, `import`, que decideix qui veu el botó i la pàgina:

```php
// Només els administradors poden importar
$crud->enableImport()
     ->setPermission('import', auth()->user()->inGroup('admin'));
```

A més, cada fila comprova el permís de l'operació que farà: `add` per a les files noves i `edit` (amb el registre) per a les actualitzacions. Per exemple, perquè els editors puguin importar però només actualitzar els seus registres:

```php
$isAdmin = auth()->user()->inGroup('admin');

$crud->enableImport()
     ->setPermission('add', $isAdmin)
     ->setPermission('edit', fn (?array $row) => $isAdmin || $row === null || $row['owner_id'] == session('user_id'));
```

Un editor pot pujar un fitxer, però les files noves i les dels registres d'altres persones surten com a errors a la previsualització ("No tens permís per afegir o modificar aquest registre").

### Valors en formats especials

La conversió automàtica entén els formats habituals: `Sí/No` i `1/0`, dates `AAAA-MM-DD` o en el format de pantalla, decimals amb coma o punt... Per a qualsevol altre format, transforma el valor amb `callbackImport()`:

```php
// Dates en format americà
$crud->callbackImport('birthdate', function (string $value) {
    $date = DateTime::createFromFormat('!m/d/Y', $value);

    return $date ? $date->format('Y-m-d') : $value;
});

// Un codi de país a partir del nom
$countries = ['Spain' => 'ES', 'France' => 'FR', 'Portugal' => 'PT'];
$crud->callbackImport('country', fn (string $value) => $countries[$value] ?? strtoupper($value));
```

El valor que retorna el callback es valida amb les regles del camp, de manera que un valor incorrecte surt igualment com a error a la previsualització. Als camps de relació, `enum`, `select` i N-N, el valor retornat ha de ser una opció vàlida. Un text que coincideix amb més d'una opció és un error: fes servir l'id. Els números només admeten xifres, separadors, el signe menys, espais i `€`, `$` o `£`; amb lletres (`12 EUR`), transforma'ls amb un callback.

Els camps amb valor fix (`fieldType($camp, 'hidden', ['value' => ...])`) i els de les condicions d'igualtat simples de `where()` es desen sempre amb aquest valor, encara que el fitxer porti la columna. Així, en un CRUD limitat a una empresa, totes les files importades són d'aquesta empresa:

```php
$crud->where('company_id', $companyId)
     ->enableImport();
```

---

## Dades i seguretat

### Limitar el CRUD a l'usuari connectat

`where()` limita què es veu i què es pot tocar. Amb una condició d'igualtat simple (`camp => valor`), a més, el valor s'assigna sol als registres nous i editats:

```php
$userId = session('user_id');

$crud->setTable('notes')
     ->where('user_id', $userId);
```

L'usuari no pot veure, editar, esborrar ni exportar notes d'altres usuaris, encara que en canviï l'id a la URL o manipuli el formulari d'accions massives.

El camp `user_id` no surt al formulari i es desa sempre amb el valor del `where()`, tant a l'alta i l'edició com a la importació CSV, encara que no sigui a `fields()` i encara que algú afegeixi `user_id` al formulari. Si després de desar el registre quedés fora de l'abast, el canvi es desfà.

El camp ocult amb valor fix que calia abans continua funcionant, però ja no és necessari:

```php
$crud->where('user_id', $userId)
     ->fieldType('user_id', 'hidden', ['value' => $userId]);   // opcional
```

Sí que cal (o un hook o `setDefault()`) si la condició té operador (`where('user_id >', 0)`) o és un text SQL, perquè aleshores no hi ha cap valor per assignar.

El mateix serveix per a aplicacions amb diverses empreses o centres:

```php
$crud->where('school_id', $schoolId);
```

### Desar qui ha creat el registre

Amb un camp ocult, el valor s'escriu a l'alta **i** a l'edició. Si només s'ha d'escriure en crear, fes servir un hook:

```php
$crud->beforeInsert(function (array $data) {
    $data['created_by'] = session('user_id');

    return $data;
});

// I qui l'ha modificat per últim cop
$crud->beforeUpdate(function (array $data) {
    $data['updated_by'] = session('user_id');

    return $data;
});
```

Per mostrar-lo amb el nom, afegeix-hi una relació i posa'l a la fitxa:

```php
$crud->setRelation('created_by', 'users', '{first_name} {last_name}')
     ->showFields(['title', 'body', 'created_by', 'created_at']);
```

### Permisos per rol i per registre

Recepta bàsica; a [functions_CA.md](functions_CA.md#permisos) hi ha més exemples i la taula de què controla cada operació.

```php
$user    = auth()->user();
$isAdmin = $user->inGroup('admin');

// Per rol: només els administradors creen, esborren i veuen la paperera
$crud->setPermission('add', $isAdmin)
     ->setPermission('delete', $isAdmin)
     ->setPermission('trash', $isAdmin);

// Per registre: els editors només editen el que és seu
$crud->setPermission('edit', fn (?array $row) =>
    $isAdmin || $row === null || $row['author_id'] == $user->id
);
```

Recorda: la closure rep `null` quan la decisió no és sobre un registre concret (el botó "Afegeix", les accions massives). Si retorna `false` amb `null`, l'opció desapareix.

La closure d'`edit` es torna a avaluar en desar, amb el registre tal com quedaria: un editor no pot canviar l'`author_id` d'un article seu per un altre (el desament es rebutja).

### Validacions entre camps

Les regles de CodeIgniter validen camp a camp. Per comparar camps entre si, fes servir un hook que retorni el missatge d'error:

```php
$checkDates = function (array $data) {
    if (! empty($data['ends_at']) && $data['ends_at'] < $data['starts_at']) {
        return 'La data de final ha de ser posterior a la d\'inici.';
    }

    return null;
};

$crud->beforeInsert($checkDates)
     ->beforeUpdate($checkDates);
```

El formulari es torna a mostrar amb les dades introduïdes i el missatge a dalt.

### Impedir esborrar si té registres relacionats

Si hi ha una clau forana, la base de dades ja ho impedeix, però amb un missatge tècnic. Amb `beforeDelete` l'usuari rep una explicació:

```php
$crud->beforeDelete(function (string $id) {
    $total = db_connect()->table('products')->where('category_id', $id)->countAllResults();

    return $total > 0 ? "No es pot esborrar: hi ha {$total} productes en aquesta categoria." : null;
});
```

Si la taula té paperera i només vols impedir l'esborrat **definitiu**, fes servir `$purge`:

```php
$crud->beforeDelete(fn (string $id, array $row, bool $purge) =>
    $purge && $row['invoiced'] ? 'Un client facturat no es pot esborrar definitivament.' : null
);
```

### Diversos CRUD sobre la mateixa taula

Cada mètode de controlador pot configurar la mateixa taula d'una manera diferent. Per exemple, l'administració i una agenda pública:

```php
// Admin\Events::index — gestió completa
$crud->setTable('events')
     ->fields(['title', 'category_id', 'starts_at', 'status', 'poster']);

// Agenda::index — només consulta, només publicats i futurs
$crud->setTable('events')
     ->where('status', 'published')
     ->where('starts_at >=', date('Y-m-d H:i:s'))
     ->readOnly()
     ->unsetTrash()
     ->unsetBulk();
```

Cada CRUD té la seva ruta i recorda per separat l'estat del llistat (pàgina, filtres...).

### Un perfil d'usuari: CRUD d'un sol registre

L'usuari connectat edita les seves dades i res més:

```php
public function profile()
{
    $userId = session('user_id');

    // No hi ha llistat: la pàgina és la fitxa
    if (uri_string() === 'profile') {
        return redirect()->to(site_url('profile/show/' . $userId));
    }

    $crud = new Crud();
    $crud->setTable('users')
         ->setSubject('perfil', 'El meu perfil')
         ->where('id', $userId)
         ->setBackUrl(site_url('profile/show/' . $userId))
         ->unsetAdd()->unsetDelete()->unsetTrash()->unsetExport()->unsetBulk()
         ->editFields(['first_name', 'last_name', 'email', 'avatar', 'password'])
         ->showFields(['avatar', 'username', 'first_name', 'last_name', 'email']);

    return $crud->render();
}
```

`where('id', ...)` fa que `profile/edit/7` d'un altre usuari no funcioni, i `setBackUrl()` fa que després de desar es torni a la fitxa en lloc d'un llistat.

![Perfil de la demo](../screenshots/14-perfil.png)

---

## Relacions

### Relació 1-N amb opcions filtrades

```php
// Només els comercials actius, ordenats per cognom
$crud->setRelation('seller_id', 'users', '{last_name}, {first_name}', [
    'where' => ['role' => 'seller', 'active' => 1, 'deleted_at' => null],
    'order' => 'last_name',
]);
```

El `where` també limita els valors que s'accepten en desar: no es pot enviar l'id d'un usuari que no és comercial.

### Relació N-N

```sql
CREATE TABLE post_tag (post_id INT, tag_id INT, PRIMARY KEY (post_id, tag_id));
```

```php
$crud->setTable('posts')
     ->setRelationNN('tags', 'post_tag', 'tags', 'post_id', 'tag_id', 'name')
     ->columns(['title', 'tags'])
     ->fields(['title', 'body', 'tags'])
     ->filters(['tags'])
     ->setDefault('tags', [1]);   // etiqueta preseleccionada a l'alta
```

### Taula intermèdia amb dades pròpies

Si la taula de lligam guarda més que les dues claus (estat, data, nota...), no la facis servir com a camp N-N editable: en desar el formulari es reescriurien les files i es perdrien aquestes dades. Dona-li una clau primària pròpia i fes-ne un CRUD:

```php
// attendances: id, user_id, event_id, status, registered_at, notes
$crud->setTable('attendances')
     ->setRelation('user_id', 'users', '{first_name} {last_name}')
     ->setRelation('event_id', 'events', 'title')
     ->editFields(['user_id', 'event_id', 'status', 'notes'])
     ->readonlyFields(['user_id', 'event_id']);
```

I al CRUD d'esdeveniments, mostra-la com a N-N **només de lectura** (a les columnes o a la fitxa, no als `fields()`):

```php
$crud->setRelationNN('attendees', 'attendances', 'users', 'event_id', 'user_id', '{first_name} {last_name}')
     ->showFields(['title', 'starts_at', 'attendees']);
```

![CRUD de la taula d'assistència a la demo](../screenshots/09-assistencia.png)

### CRUD dels fills d'un registre

Les notes d'un client, a `clients/5/notes`. `Crud::routes()` accepta rutes amb paràmetres:

```php
// Routes.php
\Kpcrudy\Crud::routes($routes, 'clients/(:num)/notes', 'Clients::notes/$1');
```

```php
// Controlador: el primer paràmetre és l'id del client
public function notes(int $clientId)
{
    $client = model('ClientModel')->find($clientId) ?? throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();

    $crud = new Crud();
    $crud->setTable('client_notes')
         ->setSubject('nota', 'Notes de ' . $client['name'])
         ->where('client_id', $clientId)
         ->fieldType('client_id', 'hidden', ['value' => $clientId])
         ->columns(['created_at', 'text'])
         ->addHeaderAction('Torna al client', 'admin/clients/show/' . $clientId, ['icon' => 'bi bi-arrow-left']);

    return $crud->render();
}
```

I des del CRUD de clients:

```php
$crud->addAction('Notes', 'clients/{id}/notes', ['icon' => 'bi bi-journal-text']);
```

---

## Presentació

### Desplegables amb etiquetes

```php
// Valors desats a la BD => text que veu l'usuari
$crud->fieldType('status', 'select', ['options' => [
    'draft'     => 'Esborrany',
    'published' => 'Publicat',
    'archived'  => 'Arxivat',
]]);
```

Als `ENUM` de MySQL, si no indiques `options`, el desplegable surt sol amb els valors de la columna. L'etiqueta es fa servir al formulari, al llistat, als filtres i a l'exportació, i els valors que arriben es validen contra la llista.

### Formats: moneda, insígnies i textos

```php
// Moneda al llistat i a la fitxa; número "net" al CSV
$crud->callbackColumn('price', fn ($value) => esc(number_format((float) $value, 2, ',', '.')) . ' €')
     ->callbackExport('price', fn ($value) => number_format((float) $value, 2, ',', ''));

// Insígnia de color segons l'estat
$colors = ['draft' => 'secondary', 'published' => 'success', 'archived' => 'dark'];
$crud->callbackColumn('status', fn ($value) =>
    '<span class="badge text-bg-' . $colors[$value] . '">' . esc(ucfirst($value)) . '</span>'
);

// Columna virtual: nom complet
$crud->columns(['full_name', 'email'])
     ->callbackColumn('full_name', fn ($value, array $row) => esc($row['first_name'] . ' ' . $row['last_name']))
     ->displayAs('full_name', 'Nom');
```

`callbackColumn` retorna HTML: **escapa sempre** les dades amb `esc()`. Les columnes amb `callbackColumn` no es fan servir per cercar si no les poses a `searchFields()`: si vols poder buscar per `status`, afegeix-lo-hi.

Els formats de data són a la configuració (`$dateFormat` i `$datetimeFormat`), i la llargada dels textos al llistat, a `$listTruncate`.

### Columnes calculades sense una consulta per fila

Una consulta dins de `callbackColumn` s'executa per a cada fila (i per a cada fila de l'exportació). Amb poques files no importa, però és millor calcular-ho tot un sol cop abans de configurar el CRUD:

```php
$totals = array_column(
    db_connect()->table('orders')->select('client_id, COUNT(*) AS total')->groupBy('client_id')->get()->getResultArray(),
    'total',
    'client_id',
);

$crud->columns(['name', 'orders'])
     ->callbackColumn('orders', fn ($value, array $row) => (string) ($totals[$row['id']] ?? 0));
```

### Pujar fitxers i imatges

```php
// Imatge: miniatura al llistat, previsualització al formulari
$crud->fieldType('photo', 'image', [
    'path'    => FCPATH . 'uploads/products',
    'url'     => base_url('uploads/products'),
    'maxSize' => 1024,   // KB
]);

// Document PDF en una carpeta per client
$crud->fieldType('contract', 'upload', [
    'allowed' => ['pdf'],
    'subdir'  => 'client-{client_id}',
]);

// Imatges en carpetes per any i mes
$crud->fieldType('poster', 'image', [
    'subdir' => fn (array $data) => date('Y/m'),
]);
```

Com funciona:

- El fitxer es desa amb un nom aleatori, i la base de dades guarda la ruta relativa (`2026/11/1730000000_ab12cd.png`).
- En substituir-lo o marcar "Elimina el fitxer", l'antic s'esborra.
- En esborrar definitivament el registre, també s'esborra el fitxer (amb `'deleteFiles' => false` es conserva).
- Es comprova l'extensió real del fitxer, i no només la del nom.

Si el camp és obligatori (`requiredFields(['photo'])`), en editar no cal tornar a pujar el fitxer si ja n'hi ha un.

---

## Integració

### Integrar el CRUD al layout del projecte

```php
// app/Config/Kpcrudy.php: per a tots els CRUD
public string|false $layout = 'layouts/admin';

// O només per a un
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

El tema per defecte necessita Bootstrap 5 (CSS i JS) i Bootstrap Icons.

### CRUD dins d'una vista pròpia

Amb `setLayout(false)`, `render()` retorna només l'HTML del CRUD. Però també pot retornar una **resposta** (una redirecció després de desar, una descàrrega CSV), que s'ha de retornar tal qual:

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

### Canviar una plantilla

Un tema propi només ha de contenir les plantilles que canvien; la resta es carrega del tema `bootstrap5`.

1. Copia la plantilla, per exemple [src/Views/bootstrap5/show.php](../src/Views/bootstrap5/show.php), a `app/Views/kpcrudy/compact/show.php`, i modifica-la. Les variables que rep estan documentades a la capçalera del fitxer.
2. Registra el tema:

```php
// app/Config/Kpcrudy.php
public array $themes = ['compact' => APPPATH . 'Views/kpcrudy/compact'];
```

3. Fes-lo servir per a tots els CRUD (`public string $theme = 'compact';`) o només per a un:

```php
$crud->setTheme('compact');
```

Dins de la plantilla, `$this->render('partials/button', [...])` reaprofita els parcials del tema base.

### Configuració diferent per a un CRUD

La configuració és compartida. Per canviar-la només en un CRUD, passa'n una còpia modificada al constructor:

```php
$config = clone config('Kpcrudy');
$config->csvDelimiter = ',';
$config->csvBom       = false;
$config->listTruncate = 0;

$crud = new Crud($config);
```

### Canviar un text o afegir un idioma

Per canviar algun text de la llibreria, crea al projecte un fitxer amb només les claus que vols sobreescriure:

```php
// app/Language/ca/Kpcrudy.php
<?php

return [
    'add'       => 'Nou registre',
    'noRecords' => 'Encara no hi ha res. Comença creant-ne un!',
];
```

Per afegir un idioma, copia [src/Language/en/Kpcrudy.php](../src/Language/en/Kpcrudy.php) a `app/Language/{codi}/Kpcrudy.php` i tradueix-ne els valors, mantenint els paràmetres `{0}`, `{1}`... L'idioma és el de la petició (`app.defaultLocale` o el negociat per CodeIgniter).

Els missatges de validació són de CodeIgniter. Per tenir-los traduïts, instal·la `composer require codeigniter4/translations` (inclou castellà, francès, portuguès, alemany i molts altres, però **no el català**). Per al català, crea `app/Language/ca/Validation.php` amb les traduccions; pots partir del fitxer castellà del paquet.
