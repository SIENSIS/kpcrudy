# Referència de funcions

[English](functions_EN.md) · **Català**

Totes les funcions i propietats públiques de **kpcrudy**, amb un exemple de cada una.

Gairebé tots els mètodes de configuració retornen la mateixa instància (`static`), de manera que es poden encadenar:

```php
$crud = new \Kpcrudy\Crud();
$crud->setTable('clients')
     ->setSubject('client', 'Clients')
     ->columns(['name', 'email']);

return $crud->render();
```

Per a receptes completes de casos habituals, vegeu [howto_CA.md](howto_CA.md).

## Índex

- [Creació i execució](#creació-i-execució): `__construct`, `render`, `routes`
- [Origen de dades](#origen-de-dades): `setTable`, `setModel`, `setDatabase`, `setPrimaryKey`, `setSubject`, `softDeletes`, `where`
- [Columnes i camps](#columnes-i-camps): `columns`, `fields`, `addFields`, `editFields`, `showFields`, `searchFields`, `filters`, `exportColumns`, `unsortable`, `displayAs`
- [Tipus de camp i validació](#tipus-de-camp-i-validació): `fieldType`, `setRules`, `requiredFields`, `readonlyFields`, `setDefault`, `setHelp`
- [Relacions](#relacions): `setRelation`, `setRelationNN`
- [Callbacks de presentació](#callbacks-de-presentació): `callbackColumn`, `callbackField`, `callbackExport`
- [Hooks](#hooks): `beforeInsert`, `afterInsert`, `beforeUpdate`, `afterUpdate`, `beforeDelete`, `afterDelete`, `afterRestore`
- [Accions](#accions): `addAction`, `addHeaderAction`, `addBulkAction`
- [Importació CSV](#importació-csv): `enableImport`, `callbackImport`
- [Permisos](#permisos): `setPermission`, `unsetAdd`, `unsetEdit`, `unsetDelete`, `unsetShow`, `unsetExport`, `unsetTrash`, `unsetPurge`, `unsetSearch`, `unsetFilters`, `unsetBulk`, `readOnly`
- [Presentació i navegació](#presentació-i-navegació): `setPerPage`, `defaultOrder`, `setBaseUrl`, `setBackUrl`, `setTheme`, `setLayout`
- [Accés des dels callbacks](#accés-des-dels-callbacks): `getModel`, `getTable`, `getPrimaryKey`, `getBaseUrl`, `url`
- [Constants](#constants): `ID_OPERATIONS`, `OPERATIONS`
- [Comandes de spark](#comandes-de-spark): `kpcrudy:make`, `kpcrudy:demo`
- [Configuració global](#configuració-global): propietats de `Config\Kpcrudy`
- [Plantilles](#plantilles): `Renderer::render`, `Renderer::renderParent`, `Renderer::locate`, `Renderer::hidden`, `Renderer::attrs`

---

## Creació i execució

### `__construct(?KpcrudyConfig $config = null)`

Crea un CRUD. Sense paràmetres, fa servir la configuració del projecte (`app/Config/Kpcrudy.php`, si existeix) o la de la llibreria. També es pot passar una configuració concreta, que ha d'estendre `Kpcrudy\Config\Kpcrudy`.

```php
use Kpcrudy\Crud;

$crud = new Crud();

// Amb una configuració pròpia per a una zona de l'aplicació
$crud = new Crud(new \Config\KpcrudyPublic());
```

### `render(): ResponseInterface|string`

Executa l'operació que correspon a la URL actual (llistat, formulari, fitxa, esborrat, exportació...) i en retorna el resultat. Pot ser HTML o una resposta (una redirecció o una descàrrega), així que el controlador l'ha de retornar tal qual.

```php
public function clients()
{
    $crud = new Crud();
    $crud->setTable('clients');

    return $crud->render();
}
```

Si vols posar el CRUD dins d'una vista pròpia, comprova primer si és una resposta (vegeu la recepta [CRUD dins d'una vista pròpia](howto_CA.md#crud-dins-duna-vista-pròpia)).

### `Crud::routes(RouteCollection $routes, string $path, string $handler, array $options = []): void` (estàtica)

Registra les dues rutes que necessita un CRUD, `path` i `path/(:any)`, per a GET i POST. `$options` són les opcions de ruta de CodeIgniter (`filter`, `as`...).

```php
// app/Config/Routes.php
use Kpcrudy\Crud;

Crud::routes($routes, 'admin/clients', 'Admin::clients');
Crud::routes($routes, 'admin/invoices', 'Admin\Invoices::index', ['filter' => 'auth:admin']);

// Dins d'un grup, les opcions del grup s'hereten
$routes->group('', ['filter' => 'auth'], static function ($routes) {
    Crud::routes($routes, 'my/orders', 'Orders::mine');
});

// La ruta pot tenir paràmetres: el controlador els rep primer
Crud::routes($routes, 'clients/(:num)/notes', 'Clients::notes/$1');   // notes(int $clientId)
```

---

## Origen de dades

### `setTable(string $table): static`

El CRUD treballa directament amb la taula. Es crea un model dinàmic que detecta:

- la **clau primària** (camp `PRIMARY KEY`, o `id` si no se'n troba cap);
- els **timestamps** (`created_at` i `updated_at`, si existeixen);
- els **soft deletes** (`deleted_at`, si existeix), que activen la paperera.

```php
$crud->setTable('clients');
```

### `setModel(Model|string $model): static`

El CRUD treballa amb un model de CodeIgniter. Fa servir la seva taula, la clau primària, els `allowedFields`, la validació (regles i missatges), els timestamps, els soft deletes i els callbacks del model.

```php
$crud->setModel(\App\Models\ClientModel::class);

// O una instància ja creada
$crud->setModel(model('ClientModel'));
```

> Si les regles del model fan servir `{id}` (per exemple `is_unique[clients.email,id,{id}]`), a CodeIgniter 4.5+ el camp de la clau primària també ha de tenir una regla: `'id' => 'permit_empty|is_natural_no_zero'`. Kpcrudy hi afegeix l'identificador en editar.

### `setDatabase(BaseConnection|string $db): static`

Grup o connexió de base de dades. Només s'aplica en mode taula; en mode model, mana el model.

```php
$crud->setTable('legacy_products')->setDatabase('legacy');
```

### `setPrimaryKey(string $primaryKey): static`

Força la clau primària quan no es pot detectar (per exemple, en una vista o en una taula sense `PRIMARY KEY`).

Un id de la URL o del formulari que no pot ser un valor vàlid de la clau (p. ex. `5abc` en una clau entera) es tracta com un registre inexistent.

```php
$crud->setTable('countries')->setPrimaryKey('iso_code');
```

### `setSubject(string $singular, ?string $plural = null): static`

Noms del tipus de registre per als títols ("Nou: client", "Edita: client") i els missatges. El plural és el títol del llistat. Si no se'n posa, es fa servir el nom de la taula.

```php
$crud->setSubject('client', 'Clients');
```

### `softDeletes(bool $enabled = true, string $field = 'deleted_at'): static`

Activa o desactiva la paperera en mode taula. Per defecte s'activa sola si la taula té el camp `deleted_at`. En mode model, mana `useSoftDeletes` del model.

```php
// La taula fa servir un altre nom de camp
$crud->setTable('posts')->softDeletes(true, 'removed_at');

// Té deleted_at, però vols esborrats definitius
$crud->setTable('logs')->softDeletes(false);
```

### `where(array|string $field, mixed $value = null): static`

Limita els registres que gestiona el CRUD. La condició s'aplica a **tot**: llistat, cerca, exportació, fitxa, edició, esborrat i accions massives. Un usuari no pot accedir a un registre de fora de l'abast encara que en canviï l'id a la URL.

Accepta el format del `where()` del Query Builder, operadors inclosos. Es pot cridar més d'una vegada.

```php
$crud->where('company_id', session('company_id'));
$crud->where('price >', 0);
$crud->where(['status' => 'published', 'deleted_by' => null]);
```

Les condicions d'**igualtat simples** (`camp => valor` amb un valor escalar o `null`, sense operador, amb el nom de la taula al davant o sense) també s'apliquen en **desar**:

- el camp no surt als formularis d'alta i d'edició;
- es desa sempre amb el valor de la condició, tant des del formulari com des de la importació CSV (encara que el camp no sigui a `fields()` i encara que un hook `before*` hi posi un altre valor);
- després de desar, el registre ha de continuar dins l'abast del CRUD; si no hi és (p. ex. per una condició amb operador), es desfà el canvi i es mostra l'error "No tens permís per fer aquesta operació".

Als exemples anteriors, `company_id`, `status` i `deleted_by` es desen sempre amb el valor indicat; `price >` no assigna res, però un preu que no la compleixi no es pot desar.

> Per a les condicions amb operador, el valor dels registres nous l'has d'assignar tu, amb `fieldType('camp', 'hidden', ['value' => ...])`, `setDefault()` o un hook (vegeu la recepta [Limitar el CRUD a l'usuari connectat](howto_CA.md#limitar-el-crud-a-lusuari-connectat)).

---

## Columnes i camps

Si no es configuren, aquests són els valors per defecte:

| Llista | Per defecte |
|---|---|
| Columnes del llistat | Tots els camps menys `deleted_at` i els camps secrets |
| Camps dels formularis | Tots menys la clau primària, els timestamps i els camps secrets (les contrasenyes sí), més les relacions N-N |
| Camps de la fitxa | Tots menys `deleted_at` i els camps secrets, més les relacions N-N |
| Cerca | Columnes de text, número, enum i relació 1-N, menys les que tenen `callbackColumn` |
| Filtres | Columnes de tipus relació, enum, select i booleà |
| Exportació | Les columnes del llistat |

Els **camps secrets** són els que tenen un nom de [`$sensitiveFields`](#configuració-global) (`password`, `token`, `secret`, `salt`, `hash`, `apikey`, `otp`...): el nom sencer, el nom sense `_` (`api_key`) o qualsevol part separada per `_` (`reset_token`, `password_hash`). Per defecte no surten al llistat, la fitxa, la cerca, l'ordenació, els filtres, l'exportació ni els formularis; els camps de tipus `password` sí que surten als formularis (es desen amb hash). Si en necessites algun, indica'l explícitament a `columns()`, `showFields()`, `fields()`, `searchFields()`, `exportColumns()`... A la previsualització de la importació, els seus valors surten emmascarats.

### `columns(array $columns): static`

Columnes del llistat, en ordre. Hi pots posar camps de la taula, relacions i **columnes virtuals** (noms que no existeixen a la taula i que es pinten amb `callbackColumn()`).

```php
$crud->columns(['photo', 'name', 'email', 'city_id', 'total_orders']);
```

### `fields(array $fields): static`

Camps dels formularis d'alta **i** d'edició.

```php
$crud->fields(['name', 'email', 'city_id', 'notes']);
```

### `addFields(array $fields): static` · `editFields(array $fields): static`

Camps diferents per a l'alta i per a l'edició.

```php
$crud->addFields(['username', 'password', 'email'])
     ->editFields(['email', 'password', 'active']);
```

### `showFields(array $fields): static`

Camps de la fitxa de detall.

```php
$crud->showFields(['name', 'email', 'city_id', 'tags', 'notes', 'created_at']);
```

### `searchFields(array $fields): static`

Camps on busca el cercador general. Si hi poses una relació 1-N, es busca pel text que es mostra (p. ex. el nom de la ciutat, no l'id). Els caràcters `%` i `_` del text cercat es busquen literalment, no com a comodins.

Per defecte no es busca a les columnes amb `callbackColumn()`, perquè el callback pot amagar el valor real (p. ex. mostrar `****`) i la cerca el revelaria. Si vols que s'hi busqui, posa-les aquí explícitament.

```php
$crud->searchFields(['name', 'email', 'city_id']);
```

### `filters(array $fields): static`

Camps amb filtre al panell de filtres. El tipus de filtre depèn del tipus de camp:

- **desplegable**: relacions 1-N i N-N, enum, select i booleà;
- **rang** (des de / fins a): data, data i hora, número i decimal;
- **text** (conté): la resta.

Amb un array buit no hi ha filtres. Els valors de filtre no vàlids (un rang que no és numèric, un valor de desplegable que no és cap de les opcions) s'ignoren.

```php
$crud->filters(['city_id', 'status', 'active', 'tags', 'created_at', 'price']);
```

### `exportColumns(array $columns): static`

Columnes del CSV, si han de ser diferents de les del llistat.

L'exportació necessita els permisos `export` i `list`, llegeix els registres per blocs i té un límit de files ([`$exportMaxRows`](#configuració-global)): si el llistat filtrat en té més, es torna al llistat amb un avís perquè l'usuari el filtri. Els valors que comencen per `=`, `+`, `-` o `@` (també amb espais o salts de línia al davant, i les variants d'amplada completa) es neutralitzen amb un apòstrof per evitar la injecció de fórmules.

```php
$crud->exportColumns(['id', 'name', 'email', 'phone', 'city_id', 'created_at']);
```

### `unsortable(array $columns): static`

Columnes que no es poden ordenar fent clic a la capçalera. Les columnes virtuals i les relacions N-N ja no són ordenables. Les columnes amb `callbackColumn()` sí que ho són: si l'ordre podria revelar el valor que el callback amaga, posa-les aquí.

```php
$crud->unsortable(['notes', 'photo']);
```

### `displayAs(array|string $field, ?string $label = null): static`

Etiqueta d'un camp o d'una columna. Sense etiqueta, es fa servir el nom del camp amb la primera lletra en majúscula, i a les relacions es treu el sufix `_id` (`city_id` → "City").

```php
$crud->displayAs('city_id', 'Ciutat');

$crud->displayAs([
    'name'    => 'Nom',
    'email'   => 'Correu',
    'city_id' => 'Ciutat',
]);
```

---

## Tipus de camp i validació

### `fieldType(string $field, string $type, array $options = []): static`

Força el tipus d'un camp. Si no es força, es dedueix de la base de dades (enter, decimal, data, `TINYINT(1)`, `ENUM`, `TEXT`...) i del nom (`email`, `password`, `url`).

| Tipus | Formulari | Llistat |
|---|---|---|
| `text` | input de text | text retallat |
| `textarea` | àrea de text | text retallat (complet a la fitxa) |
| `number` | número enter | número |
| `decimal` | número amb decimals | número |
| `email` | input email | enllaç `mailto:` |
| `url` | input url | enllaç (només si és `http`/`https`; si no, text) |
| `password` | contrasenya (amb hash) | no es mostra |
| `date` | selector de data | `dateFormat` |
| `datetime` | data i hora | `datetimeFormat` |
| `time` | hora | hora |
| `color` | selector de color | mostra de color (només colors `#rrggbb`) |
| `boolean` | interruptor | Sí / No |
| `enum` | desplegable | etiqueta |
| `select` | desplegable | etiqueta |
| `hidden` | camp ocult | valor |
| `readonly` | només lectura | valor |
| `upload` | fitxer | enllaç |
| `image` | imatge | miniatura |

Opcions:

| Opció | Tipus | Descripció |
|---|---|---|
| `options` | `enum`, `select` | `[valor => etiqueta]`. Als `ENUM` de MySQL, si no s'indica, s'agafen els valors de la columna |
| `value` | `hidden` | Valor que es desa sempre, a l'alta i a l'edició. El camp no surt al formulari, però ha de ser a la llista de camps (`fields()` o la per defecte) |
| `hash` | `password` | `true` (per defecte): es desa amb `password_hash()`. `false`: es desa tal qual |
| `path` | `upload`, `image` | Directori on es desen els fitxers. Per defecte, `uploadPath/{taula}` |
| `url` | `upload`, `image` | URL pública d'aquest directori |
| `subdir` | `upload`, `image` | Subcarpeta per a cada fitxer nou: text amb `{camp}` o `fn (array $data, ?array $record): string` |
| `allowed` | `upload`, `image` | Extensions permeses (array o text separat per comes) |
| `maxSize` | `upload`, `image` | Mida màxima en KB |
| `deleteFiles` | `upload`, `image` | `true` (per defecte): esborra el fitxer en esborrar definitivament el registre. Només s'esborren fitxers que siguin dins del directori del camp (`path`) |
| `attrs` | tots | Atributs HTML de l'input: `['placeholder' => ..., 'min' => 0]` |

```php
$crud->fieldType('notes', 'textarea', ['attrs' => ['rows' => 8]])
     ->fieldType('status', 'select', ['options' => ['draft' => 'Esborrany', 'published' => 'Publicat']])
     ->fieldType('company_id', 'hidden', ['value' => session('company_id')])
     ->fieldType('photo', 'image', ['maxSize' => 1024])
     ->fieldType('contract', 'upload', ['allowed' => ['pdf'], 'subdir' => '{client_id}'])
     ->fieldType('price', 'decimal', ['attrs' => ['min' => 0, 'step' => '0.01']])
     ->fieldType('code', 'readonly');
```

### `setRules(array|string $field, array|string|null $rules = null): static`

Regles de validació de CodeIgniter. `{id}` se substitueix per l'identificador del registre que s'edita (buit a l'alta). Les regles es poden passar com a text separat per `|` o com a array; fes servir l'array si una regla conté `|`, com ara `regex_match`.

Si un camp té regles pròpies, no se n'hi dedueixen d'automàtiques (vegeu `$inferRules`).

```php
$crud->setRules('email', 'required|valid_email|is_unique[clients.email,id,{id}]');

$crud->setRules([
    'name'  => 'required|min_length[3]',
    'phone' => ['permit_empty', 'regex_match[/^[0-9 +]{9,15}$/]'],
]);
```

> En edició, la regla `required` d'un camp `password` s'ignora: deixar-lo en blanc vol dir no canviar la contrasenya. A l'alta, si la columna és `NOT NULL` sense valor per defecte, la contrasenya és obligatòria (regla deduïda).

### `requiredFields(array $fields): static`

Afegeix la regla `required` als camps indicats, a més de les que ja tinguin.

```php
$crud->requiredFields(['city_id', 'phone']);
```

### `readonlyFields(array $fields): static`

Camps que es mostren al formulari d'**edició** però no es poden modificar. A l'alta són editables. Per a un camp que no s'ha de poder editar mai, fes servir `fieldType($camp, 'readonly')`.

```php
$crud->readonlyFields(['username', 'created_by']);
```

### `setDefault(string $field, mixed $value): static`

Valor inicial d'un camp al formulari d'**alta**. L'usuari el pot canviar. Si no se'n posa cap, es fa servir el valor per defecte de la columna a la base de dades.

```php
$crud->setDefault('status', 'draft')
     ->setDefault('date', date('Y-m-d'))
     ->setDefault('tags', [1, 3]);   // relació N-N: ids seleccionats
```

### `setHelp(string $field, string $text): static`

Text d'ajuda sota el camp al formulari.

```php
$crud->setHelp('capacity', 'Deixa-ho en blanc si no hi ha límit de places.');
```

---

## Relacions

### `setRelation(string $field, string $table, string $display, array $options = []): static`

Relació **1-N**: el camp `$field` és una clau forana cap a `$table`. Al formulari surt un desplegable, i al llistat, a la fitxa, a la cerca, a l'ordenació i al CSV es mostra el text de la taula relacionada.

`$display` pot ser un camp (`'name'`) o una plantilla amb diversos camps (`'{last_name}, {first_name}'`).

| Opció | Per defecte | Descripció |
|---|---|---|
| `pk` | `'id'` | Clau primària de la taula relacionada |
| `where` | `[]` | Condicions per a les opcions del desplegable |
| `order` | primer camp de `$display` | Ordre de les opcions |

Els valors que arriben del formulari es validen contra les opcions permeses (incloent-hi el `where`).

```php
$crud->setRelation('city_id', 'cities', 'name');

$crud->setRelation('manager_id', 'users', '{last_name}, {first_name}', [
    'where' => ['role' => 'manager', 'deleted_at' => null],
    'order' => 'last_name',
]);
```

### `setRelationNN(string $name, string $junction, string $otherTable, string $thisKey, string $otherKey, string $display, array $options = []): static`

Relació **N-N** a través d'una taula intermèdia. `$name` és un camp virtual que pots posar a `columns()`, `fields()`, `showFields()`, `filters()` i `exportColumns()`. Al formulari surt un desplegable múltiple; en desar, es reescriuen les files de la taula intermèdia que corresponen a opcions que l'usuari podia triar (respectant el `where` de la relació). Els enllaços amb registres de fora d'aquestes opcions es mantenen.

| Paràmetre | Exemple | Descripció |
|---|---|---|
| `$name` | `'tags'` | Nom del camp virtual |
| `$junction` | `'client_tag'` | Taula intermèdia |
| `$otherTable` | `'tags'` | Taula relacionada |
| `$thisKey` | `'client_id'` | Camp de la intermèdia que apunta a aquesta taula |
| `$otherKey` | `'tag_id'` | Camp de la intermèdia que apunta a la relacionada |
| `$display` | `'name'` | Camp o plantilla a mostrar |
| `$options` | | `pk`, `where`, `order` (com a `setRelation`) |

```php
$crud->setRelationNN('tags', 'client_tag', 'tags', 'client_id', 'tag_id', 'name');
$crud->columns(['name', 'tags'])->filters(['tags']);
```

> En esborrar definitivament un registre, se n'esborren les files de la taula intermèdia. Si la intermèdia té més dades que les dues claus (estat, data...), no la posis als `fields()`: en desar el formulari es perdrien. Fes-ne un CRUD propi (vegeu la recepta [Taula intermèdia amb dades pròpies](howto_CA.md#taula-intermèdia-amb-dades-pròpies)).

---

## Callbacks de presentació

### `callbackColumn(string $column, Closure $callback): static`

Personalitza com es mostra un valor al **llistat** i a la **fitxa**: `fn (mixed $value, array $row): string`. Retorna **HTML**, així que has d'escapar les dades amb `esc()`. També serveix per crear columnes virtuals.

Una columna amb `callbackColumn` no es fa servir per cercar si no la poses a `searchFields()` (el callback pot amagar el valor real). Continua sent ordenable; si l'ordre podria revelar el valor, afegeix-la a `unsortable()`.

```php
// Format de moneda
$crud->callbackColumn('price', fn ($value) => esc(number_format((float) $value, 2, ',', '.')) . ' €');

// Insígnia de color segons l'estat
$crud->callbackColumn('status', fn ($value) => match ($value) {
    'paid'    => '<span class="badge text-bg-success">Pagada</span>',
    'pending' => '<span class="badge text-bg-warning">Pendent</span>',
    default   => esc($value),
});

// Columna virtual que combina camps
$crud->columns(['full_name', 'email'])
     ->callbackColumn('full_name', fn ($value, array $row) => esc($row['first_name'] . ' ' . $row['last_name']));
```

### `callbackField(string $field, Closure $callback): static`

Substitueix l'input d'un camp al formulari: `fn (mixed $value, ?array $row, array $field): string`. `$row` és el registre en edició (`null` a l'alta) i `$field` conté les dades que faria servir la plantilla (`label`, `required`, `error`, `options`...). L'input ha de fer servir `name="{camp}"`.

```php
$crud->callbackField('rating', fn ($value) =>
    '<input type="range" class="form-range" min="1" max="5" name="rating" value="' . esc((string) ($value ?? 3), 'attr') . '">'
);
```

### `callbackExport(string $column, Closure $callback): static`

Valor d'una columna al CSV: `fn (mixed $value, array $row): string`. Si no n'hi ha, es fa servir `callbackColumn` sense l'HTML, o el text que es veu al llistat.

```php
// Al llistat "1.234,50 €", al CSV un número que el full de càlcul entengui
$crud->callbackExport('price', fn ($value) => number_format((float) $value, 2, ',', ''));
```

---

## Hooks

Els hooks s'executen en desar o esborrar. Els `before*` poden aturar l'operació:

| Retorn | Efecte |
|---|---|
| `null` | Continua sense canvis |
| `array` | Continua amb aquestes dades (només `beforeInsert` i `beforeUpdate`) |
| `string` | S'atura i es mostra el text com a error |
| `false` | S'atura amb el missatge genèric "S'ha cancel·lat l'operació" |

Les dades que reben són les que s'escriuran a la base de dades: la contrasenya ja amb hash, les dates en format `Y-m-d H:i:s`, i sense els camps de només lectura ni les relacions N-N.

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

`fn (string $id, array $data): void`. S'executa després de desar, amb l'id del registre nou.

```php
$crud->afterInsert(fn (string $id, array $data) => log_message('info', "Client {$id} creat: {$data['name']}"));
```

### `beforeUpdate(Closure $callback): static`

`fn (array $data, string $id, array $old): array|string|false|null`. `$old` és el registre tal com era abans de desar.

```php
$crud->beforeUpdate(function (array $data, string $id, array $old) {
    if ($old['status'] === 'closed') {
        return 'Una factura tancada no es pot modificar.';
    }

    return null;
});
```

### `afterUpdate(Closure $callback): static`

`fn (string $id, array $data, array $old): void`

```php
$crud->afterUpdate(function (string $id, array $data, array $old) {
    if (($data['status'] ?? null) === 'shipped' && $old['status'] !== 'shipped') {
        service('email')->setTo($old['email'])->setSubject('Comanda enviada')->send();
    }
});
```

### `beforeDelete(Closure $callback): static`

`fn (string $id, array $row, bool $purge): string|false|null`. `$purge` és `true` si l'esborrat és definitiu (o la taula no té paperera) i `false` si el registre va a la paperera.

```php
$crud->beforeDelete(function (string $id, array $row, bool $purge) {
    $orders = db_connect()->table('orders')->where('client_id', $id)->countAllResults();

    return $orders > 0 ? "No es pot esborrar: té {$orders} comandes." : null;
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

`fn (string $id, array $row): void`. S'executa en restaurar un registre de la paperera.

```php
$crud->afterRestore(fn (string $id) => log_message('info', "Client {$id} restaurat"));
```

---

## Accions

### `addAction(string $label, Closure|string $url, array $options = []): static`

Afegeix un botó a **cada fila** del llistat i a la **fitxa**. Se'n poden afegir tants com calgui.

La URL pot ser:

- un **text** amb `{id}` (clau primària) i `{camp}` (qualsevol camp del registre), que es codifiquen per a la URL. Si és relativa, passa per `site_url()`;
- una **closure** `fn (array $row): string` que retorna la URL completa. Només es fa servir si és relativa o comença per `http:`, `https:`, `mailto:` o `tel:`; si no (p. ex. `javascript:`), el botó porta a `#`.

| Opció | Per defecte | Descripció |
|---|---|---|
| `icon` | cap | Classe de la icona, p. ex. `'bi bi-receipt'` ([Bootstrap Icons](https://icons.getbootstrap.com/)). Amb icona, el botó del llistat només mostra la icona |
| `class` | `'btn-outline-secondary'` | Classe del botó |
| `target` | cap | `'_blank'` per obrir en una pestanya nova. L'enllaç porta `rel="noopener noreferrer"` |
| `confirm` | cap | Missatge de confirmació abans d'executar l'acció |
| `visible` | sempre | `fn (array $row): bool` per mostrar-la només en alguns registres |
| `method` | `'get'` | `'post'` per a accions que modifiquen dades. S'envia un formulari amb el token CSRF |

```php
$crud->addAction('Factures', 'invoices/client/{id}', ['icon' => 'bi bi-receipt']);

$crud->addAction('Mapa', 'https://www.openstreetmap.org/search?query={address}', [
    'icon' => 'bi bi-geo-alt', 'target' => '_blank',
]);

$crud->addAction('Envia recordatori', 'clients/remind/{id}', [
    'method'  => 'post',
    'confirm' => 'Vols enviar el recordatori?',
    'visible' => fn (array $row) => $row['balance'] > 0,
]);
```

### `addHeaderAction(string $label, string $url, array $options = []): static`

Afegeix un botó a la **capçalera** del llistat, al costat d'"Afegeix". Accepta les mateixes opcions que `addAction()`. `visible` rep `null`.

```php
$crud->addHeaderAction('Importa CSV', 'clients/import', ['icon' => 'bi bi-upload']);
$crud->addHeaderAction('Recalcula saldos', 'clients/recalculate', ['method' => 'post', 'confirm' => 'Pot trigar uns minuts. Continuar?']);
```

### `addBulkAction(string $key, string $label, Closure $callback, array $options = []): static`

Acció sobre els registres **seleccionats** amb les caselles: `fn (array $ids, Crud $crud): string|ResponseInterface|null`.

`$ids` només conté identificadors que existeixen i que són dins de l'abast del CRUD (`where()`), encara que algú manipuli el formulari. Si el callback retorna un text, es mostra com a missatge; si retorna una resposta (una descàrrega, una redirecció...), es retorna tal qual.

| Opció | Per defecte | Descripció |
|---|---|---|
| `confirm` | cap | Missatge de confirmació |
| `trash` | `false` | `true` per oferir l'acció a la paperera en lloc del llistat |

```php
$crud->addBulkAction('activate', 'Activa', function (array $ids) {
    db_connect()->table('clients')->whereIn('id', $ids)->update(['active' => 1]);

    return count($ids) . ' clients activats.';
});

// Retornar una descàrrega
$crud->addBulkAction('labels', 'Etiquetes en PDF', function (array $ids) {
    $pdf = (new \App\Libraries\Labels())->render($ids);

    return service('response')->download('etiquetes.pdf', $pdf);
});
```

Les accions massives pròpies no comproven els permisos registre a registre: si cal, fes-ho dins el callback.

---

## Importació CSV

### `enableImport(array $options = []): static`

Activa la importació des d'un fitxer CSV. Al llistat apareix el botó **Importa CSV**, que porta a `base/import`. El procés té tres passos:

1. **Pujar el fitxer** i triar:
   - què cal fer amb cada fila: *afegir sempre un registre nou* o *actualitzar el registre si la clau ja existeix, i si no, afegir-lo*;
   - què cal fer si hi ha files amb errors: *no importar res* (tot en una transacció) o *importar les correctes i saltar les altres*.
2. **Previsualitzar**: es mostra com s'han associat les columnes, quantes files són noves, quantes s'actualitzaran, quines tenen errors (per línia) i les primeres files.
3. **Confirmar**. Si tot va bé, es torna al llistat amb el resum; si hi ha files que no s'han pogut importar, es mostra un informe.

![Previsualització d'una importació](../screenshots/16-importacio-previsualitzacio.png)

| Opció | Per defecte | Descripció |
|---|---|---|
| `fields` | camps del formulari d'alta | Camps que es poden importar. No s'hi inclouen mai fitxers, camps virtuals, camps `readonly` ni camps `hidden` amb valor fix |
| `key` | clau primària | Camp que identifica un registre existent per actualitzar-lo (p. ex. `email`, `username`, `code`) |
| `maxRows` | `$importMaxRows` (1000) | Nombre màxim de files |
| `maxSize` | `$importMaxSize` (2048) | Mida màxima del fitxer, en KB |

```php
$crud->enableImport();

$crud->enableImport([
    'key'     => 'email',
    'fields'  => ['name', 'email', 'phone', 'city_id', 'tags', 'active'],
    'maxRows' => 300,
]);
```

**Com es llegeix el fitxer:**

- La primera fila és la capçalera. Cada columna s'associa a un camp pel **nom** (`city_id`) o per l'**etiqueta** (`Ciutat`), sense tenir en compte majúscules. Les columnes que no corresponen a cap camp s'ignoren.
- La codificació (UTF-8, amb BOM o sense, o Windows-1252) i el separador (`;`, `,` o tabulador) es detecten sols.
- La pàgina de pujada té un botó per descarregar una **plantilla** amb les columnes.
- Una fila amb menys columnes de les necessàries és un error.
- La lectura s'atura tan bon punt se supera `maxRows`.
- Si un valor comença per un apòstrof seguit d'un caràcter de fórmula (`'=`, `'+`...), l'apòstrof que hi afegeix l'exportació es treu.

**Com es converteixen els valors:**

| Tipus | Accepta |
|---|---|
| relació 1-N, `enum`, `select` | l'id o el text que es mostra (`3` o `Barcelona`) |
| relació N-N | diversos ids o textos separats per comes (`Música, Esports`) |
| `boolean` | `1`/`0`, `yes`/`no`, `true`/`false`, `sí`/`no` i el text "Sí/No" de l'idioma actual |
| `date`, `datetime` | `AAAA-MM-DD [HH:MM[:SS]]` o el format de pantalla (`$dateFormat`, `$datetimeFormat`) |
| `number`, `decimal` | `1234.5`, `1234,5`, `1.234,50`, `€ 12.00`... Només xifres, separadors, un signe menys al davant, espais i els símbols `€`, `$` i `£`: amb lletres, el valor no és vàlid |
| `password` | el text, que es desa amb hash. En una actualització, si és buit, no es canvia |

Gràcies a això, un fitxer generat amb **Exporta CSV** es pot editar amb un full de càlcul i tornar a importar sense canvis.

Si un text coincideix amb més d'una opció (dues ciutats amb el mateix nom), la fila té un error ("coincideix amb més d'un registre"): fes servir l'id. El mateix passa si, amb *actualitzar si ja existeix*, el valor de la clau coincideix amb més d'un registre.

**Què es comprova a cada fila:**

- Les mateixes **regles de validació** que el formulari (les configurades i les deduïdes de la taula).
- Els **permisos**: `add` per afegir i `edit` (amb el registre) per actualitzar. La closure d'`edit` es torna a avaluar amb el registre tal com quedaria després de la importació.
- L'abast de **`where()`**: només s'actualitzen registres de dins l'abast, els camps amb una condició d'igualtat simple es desen sempre amb el valor de la condició i, després de desar, el registre ha de continuar dins l'abast.
- En una actualització, només es canvien les columnes que hi ha al fitxer i que són al formulari d'edició (`editFields()`), i mai els `readonlyFields`. A l'alta es fan servir els camps d'importació (`fields`).
- A l'alta, els camps que no hi són agafen el valor de `setDefault()` o el de la base de dades.

Mentre es mostra la previsualització, el fitxer es guarda a `writable/uploads/kpcrudy-import/` amb un nom aleatori, permisos privats (`0600`) i lligat a la sessió de l'usuari. S'esborra en importar, en pujar-ne un altre, en tornar a la pàgina de pujada o, si s'abandona, al cap d'una hora. A la previsualització, els valors dels [camps secrets](#columnes-i-camps) surten emmascarats.

En importar, s'executen els **hooks** (`beforeInsert`, `afterInsert`, `beforeUpdate`, `afterUpdate`) com si es desés el formulari, i es reescriuen les relacions N-N que hi hagi al fitxer. Els hooks posteriors (`afterInsert`, `afterUpdate`) s'executen només quan la transacció s'ha confirmat; amb *no importar res* si hi ha errors, només si tota la importació ha anat bé. La previsualització no executa els hooks ni detecta duplicats dins del mateix fitxer (dues files amb el mateix email): aquests errors surten en importar.

### `callbackImport(string $field, Closure $callback): static`

Transforma el valor d'una columna abans de validar-lo: `fn (string $value, array $row): mixed`. `$row` conté els valors de tota la fila (camp => text del CSV). El valor que retorna es valida i es desa tal qual, sense la conversió automàtica. Als camps de relació, `enum`, `select` i N-N, el resultat ha de ser igualment una opció vàlida.

```php
// Telèfons només amb xifres
$crud->callbackImport('phone', fn (string $value) => preg_replace('/\D/', '', $value));

// Acceptar "Free" com a preu 0
$crud->callbackImport('price', fn (string $value) => strcasecmp($value, 'free') === 0 ? '0.00' : $value);

// Dates en format americà (m/d/Y), que la conversió automàtica no reconeix
$crud->callbackImport('birthdate', function (string $value) {
    $date = DateTime::createFromFormat('!m/d/Y', $value);

    return $date ? $date->format('Y-m-d') : $value;   // si no és vàlida, la validació ho dirà
});
```

El callback només s'executa si la columna és al fitxer.

---

## Permisos

### `setPermission(string $operation, bool|Closure $allowed): static`

Permet o denega una operació. Totes les operacions estan permeses per defecte, excepte `trash`, `restore` i `purge`, que només existeixen si la taula té paperera.

Els permisos es comproven al **servidor**, no només a la interfície: si una acció no està permesa, no surt el botó i, si algú hi accedeix per URL, rep un error.

| Operació | Què controla | Rep `$row`? |
|---|---|---|
| `list` | Veure el llistat (si es denega, error 404) | no |
| `add` | Botó "Afegeix" i formulari d'alta | no |
| `edit` | Botó i formulari d'edició | sí |
| `show` | Botó i fitxa de detall | sí |
| `delete` | Botó d'esborrar i acció massiva | sí, i `null` per a l'acció massiva |
| `export` | Botó d'exportar i acció massiva (també cal `list`) | no |
| `import` | Botó i pàgina d'importació (cal `enableImport()`). Cada fila necessita, a més, `add` o `edit` | no (les files, via `add` i `edit`) |
| `trash` | Paperera (també cal per a `restore` i `purge`) | sí, i `null` per a l'enllaç |
| `restore` | Restaurar de la paperera | sí, i `null` per a l'acció massiva |
| `purge` | Esborrar definitivament i buidar la paperera | sí, i `null` per a l'acció massiva i "Buida la paperera" |
| `search` | Cercador | no |
| `filters` | Panell de filtres | no |
| `bulk` | Caselles i accions massives | no |

`$allowed` pot ser:

- un **booleà**: permès o denegat per a tothom;
- una **closure** `fn (?array $row, string $operation): bool`, que decideix registre a registre.

La closure rep el registre (`$row`) quan n'hi ha un de concret, i `null` quan la decisió és general: si es mostra el botó "Afegeix", si s'ofereix l'acció massiva, etc. **Si retorna `false` amb `null`, l'opció desapareix del tot.** Per això la majoria de closures comencen amb `$row === null ||`.

La closure d'**`edit`** s'avalua dues vegades: abans d'editar, amb el registre desat, i en desar (formulari i importació CSV), amb el registre tal com quedaria després del canvi (el desat combinat amb les dades enviades). Si la segona vegada retorna `false`, no es desa. Així, a l'exemple 3, un usuari no pot canviar el `user_id` d'una nota seva per passar-la a un altre usuari.

**Exemple 1. Desactivar una operació per a tothom**

```php
$crud->setPermission('export', false);   // equival a unsetExport()
```

**Exemple 2. Segons el rol de l'usuari** (un booleà calculat abans)

```php
$isAdmin = auth()->user()->inGroup('admin');   // amb CodeIgniter Shield

$crud->setPermission('add', $isAdmin)
     ->setPermission('delete', $isAdmin)
     ->setPermission('trash', $isAdmin);
```

**Exemple 3. Només el propietari pot editar o esborrar el registre**

```php
$userId = session('user_id');
$owner  = fn (?array $row) => $row === null || (int) $row['user_id'] === (int) $userId;

$crud->setPermission('edit', $owner)
     ->setPermission('delete', $owner);
```

Amb `$row === null` retornant `true`, l'acció massiva "Esborra" continua disponible, però en aplicar-la només s'esborren els registres propis: la llibreria torna a comprovar el permís per a cada registre seleccionat.

**Exemple 4. Segons l'estat del registre**

```php
// Les factures tancades no es poden modificar ni esborrar
$crud->setPermission('edit', fn (?array $row) => $row === null || $row['status'] !== 'closed')
     ->setPermission('delete', fn (?array $row) => $row === null || $row['status'] === 'draft');
```

**Exemple 5. Una sola closure per a diverses operacions, fent servir `$operation`**

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

**Exemple 6. Amb una condició de temps**

```php
// Els comentaris només es poden editar durant les primeres 24 hores
$crud->setPermission('edit', fn (?array $row) =>
    $row === null || strtotime($row['created_at']) > strtotime('-24 hours')
);
```

**Exemple 7. Amagar l'opció a la interfície però decidir registre a registre**

```php
// No hi ha esborrat massiu (null => false), però cada fila esborrable té el seu botó
$crud->setPermission('delete', fn (?array $row) => $row !== null && $row['locked'] == 0);
```

### Dreceres

Totes equivalen a `setPermission($operació, false)`:

| Mètode | Operació |
|---|---|
| `unsetAdd()` | `add` |
| `unsetEdit()` | `edit` |
| `unsetDelete()` | `delete` |
| `unsetShow()` | `show` |
| `unsetExport()` | `export` |
| `unsetTrash()` | `trash` (i per tant `restore` i `purge`) |
| `unsetPurge()` | `purge` |
| `unsetSearch()` | `search` |
| `unsetFilters()` | `filters` |
| `unsetBulk()` | `bulk` |

```php
$crud->unsetAdd()->unsetBulk()->unsetExport();
```

### `readOnly(): static`

Només consulta: equival a `unsetAdd()->unsetEdit()->unsetDelete()->unsetPurge()` i `setPermission('restore', false)`, de manera que tampoc es pot restaurar, esborrar definitivament ni buidar la paperera. Continuen disponibles el llistat, la fitxa, la cerca, els filtres i l'exportació.

```php
$crud->setTable('logs')->readOnly()->unsetTrash();
```

---

## Presentació i navegació

### `setPerPage(int $perPage, ?array $options = null): static`

Registres per pàgina i, opcionalment, les opcions del selector "Per pàgina".

```php
$crud->setPerPage(50);
$crud->setPerPage(5, [5, 10, 20]);
```

### `defaultOrder(string $field, string $direction = 'asc'): static`

Ordre inicial del llistat. Pot ser una relació 1-N (s'ordena pel text). Per defecte, per la clau primària ascendent.

```php
$crud->defaultOrder('created_at', 'desc');
$crud->defaultOrder('city_id');
```

### `setBaseUrl(string $url): static`

URL base del CRUD. Normalment es dedueix sola de la URL actual. Només cal indicar-la si la ruta acaba amb una paraula que el CRUD fa servir com a operació (`add`, `trash`, `export`, `import`, `bulk`, `emptytrash`, `edit`, `show`, `delete`, `restore`, `purge`).

```php
// Ruta "admin/trash": "trash" és una operació
Crud::routes($routes, 'admin/trash', 'Admin::trash');

$crud->setBaseUrl(site_url('admin/trash'));
```

### `setBackUrl(string $url): static`

On es torna després de desar, esborrar o cancel·lar, i on porta el botó "Torna al llistat". Per defecte, al llistat amb la pàgina, la cerca i els filtres que l'usuari hi havia deixat.

```php
// Després d'editar el perfil, tornar a la fitxa
$crud->setBackUrl(site_url('profile/show/' . $userId));
```

### `setTheme(string $theme): static`

Tema de plantilles per a aquest CRUD: un dels inclosos (`bootstrap5`, `w3css`, `tailwind`) o un de registrat a `$themes`. Vegeu [new_theme_CA.md](new_theme_CA.md).

```php
$crud->setTheme('w3css');
$crud->setTheme('compact');   // tema propi
```

### `setLayout(string|false $layout): static`

Com s'embolcalla l'HTML del CRUD:

| Valor | Resultat |
|---|---|
| `'default'` | Pàgina HTML completa del tema (`layout.php`) amb Bootstrap des de CDN |
| `false` | Només el fragment HTML del CRUD |
| nom d'una vista | Vista de CodeIgniter que rep `$kpcrudy` (HTML del CRUD) i `$title` |

```php
$crud->setLayout('layouts/admin');
```

---

## Accés des dels callbacks

Aquests mètodes són útils dins dels callbacks, dels hooks i de les accions massives.

### `getModel(): Model`

El model que fa servir el CRUD: el teu, o el dinàmic en mode taula.

```php
$crud->addBulkAction('archive', 'Arxiva', function (array $ids, Crud $crud) {
    $crud->getModel()->update($ids, ['archived' => 1]);

    return 'Arxivats.';
});
```

### `getTable(): string` · `getPrimaryKey(): string`

Nom de la taula i de la clau primària.

```php
$crud->addBulkAction('touch', 'Marca com a revisats', function (array $ids, Crud $crud) {
    db_connect()->table($crud->getTable())
        ->whereIn($crud->getPrimaryKey(), $ids)
        ->update(['reviewed_at' => date('Y-m-d H:i:s')]);
});
```

### `getBaseUrl(): string` · `url(string $operation, ?string $id = null): string`

URL base del CRUD i URL d'una operació. Dins dels callbacks ja estan resoltes.

```php
$crud->addBulkAction('duplicate', 'Duplica', function (array $ids, Crud $crud) {
    $newId = (new \App\Libraries\Duplicator())->copy($ids[0]);

    return redirect()->to($crud->url('edit', (string) $newId));
});
```

---

## Comandes de spark

### `php spark kpcrudy:make [taula] [opcions]`

Genera el mètode de controlador d'un CRUD i la seva ruta a partir de l'estructura d'una taula. Sense la taula, pregunta tots els valors. Detecta les relacions 1-N (claus foranes), les N-N (taules intermèdies), els camps únics i els tipus que s'endevinen pel nom.

| Opció | Per defecte | Descripció |
|---|---|---|
| `--controller` | la taula en *StudlyCase* | Classe relativa a `App\Controllers` (`Admin/Clients`). Es crea si no existeix; si existeix, s'hi afegeix el mètode |
| `--method` | `index` | Nom del mètode |
| `--route` | a partir del controlador | Ruta que es registra amb `Crud::routes()` |
| `--filter` | cap | Filtre de la ruta |
| `--model` | no | Genera també un model a `app/Models` i fa servir `setModel()` |
| `--dry-run` | no | Mostra el codi sense escriure res |
| `--force` | no | Reemplaça el mètode i el model si ja existeixen |

```bash
php spark kpcrudy:make products --controller Admin/Shop --method products --route admin/products --filter auth:admin
```

Abans d'escriure cap fitxer ho comprova tot, i s'atura sense tocar res si alguna cosa no quadra: taules o columnes amb noms que no siguin només lletres, números i `_` (no es poden posar sense risc al codi generat), un mètode que comenci amb `_` o que ja tingui el controlador base (`initController`...), una ruta que ja apunti a un altre controlador o un model amb el mateix nom que sigui d'una altra taula. Amb `--dry-run` també fa aquestes comprovacions.

Vegeu la recepta [Generar el CRUD d'una taula amb una comanda](howto_CA.md#generar-el-crud-duna-taula-amb-una-comanda).

### `php spark kpcrudy:demo <install|status|reset|uninstall>`

Instal·la i desinstal·la l'aplicació de demostració. Vegeu [Instal·lar la demo al projecte](../README_CA.md#installar-la-demo-al-projecte).

---

## Constants

### `Crud::ID_OPERATIONS` · `Crud::OPERATIONS`

Paraules de la URL que el CRUD interpreta com a operació: `ID_OPERATIONS` porten un id al darrere (`edit`, `show`, `delete`, `restore`, `purge`); `OPERATIONS`, no (`add`, `trash`, `export`, `import`, `bulk`, `emptytrash`).

```php
// Saber si estem al llistat principal
$segments = explode('/', uri_string());
$isList   = ! array_intersect($segments, [...Crud::ID_OPERATIONS, ...Crud::OPERATIONS]);
```

---

## Configuració global

Per canviar la configuració de tots els CRUD del projecte, crea `app/Config/Kpcrudy.php`:

```php
<?php

namespace Config;

class Kpcrudy extends \Kpcrudy\Config\Kpcrudy
{
    public string|false $layout = 'layouts/admin';
    public int $perPage         = 10;
}
```

| Propietat | Per defecte | Descripció |
|---|---|---|
| `string $theme` | `'bootstrap5'` | Tema per defecte |
| `array $themes` | `[]` | Temes addicionals: `['nom' => directori]` o `['nom' => ['path' => directori, 'parent' => 'w3css']]` per ampliar un altre tema |
| `string\|false $layout` | `'default'` | Layout per defecte (vegeu `setLayout`) |
| `int $perPage` | `25` | Registres per pàgina |
| `array $perPageOptions` | `[10, 25, 50, 100]` | Opcions del selector "Per pàgina" |
| `string $dateFormat` | `'d/m/Y'` | Format de les dates al llistat, la fitxa i el CSV |
| `string $datetimeFormat` | `'d/m/Y H:i'` | Format de les dates amb hora |
| `int $listTruncate` | `60` | Caràcters màxims dels textos al llistat (`0` = sense límit) |
| `bool $inferRules` | `true` | Deduir regles de validació de l'estructura de la taula |
| `string $csvDelimiter` | `';'` | Separador del CSV (`;` el llegeix bé l'Excel en català i castellà) |
| `bool $csvBom` | `true` | Afegir el BOM UTF-8 perquè l'Excel detecti els accents |
| `int $exportMaxRows` | `100000` | Files màximes d'una exportació CSV (`0` = sense límit). Si el llistat filtrat en té més, es demana a l'usuari que el filtri |
| `bool $csrfProtection` | `true` | Verificar el token CSRF de les peticions POST del CRUD quan el filtre `csrf` de CodeIgniter (o una subclasse de `CodeIgniter\Filters\CSRF`) no està actiu per a la petició. Desactiva-ho només si el projecte protegeix les peticions d'una altra manera |
| `array $sensitiveFields` | `password`, `passwd`, `pass`, `pwd`, `contrasenya`, `contrasena`, `secret`, `token`, `salt`, `hash`, `apikey`, `otp`, `totp`, `2fa` | Noms de [camps secrets](#columnes-i-camps), que per defecte no es mostren enlloc |
| `int $importMaxRows` | `1000` | Files màximes d'un fitxer d'importació (es pot canviar a cada CRUD amb `enableImport()`) |
| `int $importMaxSize` | `2048` | Mida màxima d'un fitxer d'importació, en KB |
| `string $uploadPath` | `FCPATH . 'uploads'` | Directori base dels fitxers pujats |
| `string $uploadUrl` | `'uploads'` | URL base dels fitxers, relativa a `base_url()` |
| `int $uploadMaxSize` | `2048` | Mida màxima per defecte, en KB |
| `array $uploadAllowed` | imatges, PDF, ofimàtica, `txt`, `csv`, `zip` | Extensions permeses per defecte als camps `upload` |
| `array $imageAllowed` | `jpg`, `jpeg`, `png`, `gif`, `webp` | Extensions permeses per defecte als camps `image` |
| `string $bootstrapCss` | Bootstrap 5.3.3 (jsDelivr) | CSS que carrega el layout `default` |
| `string $bootstrapJs` | Bootstrap 5.3.3 (jsDelivr) | JS que carrega el layout `default` |
| `string $iconsCss` | Bootstrap Icons 1.11.3 (jsDelivr) | Icones que carrega el layout `default` |

Amb `$inferRules` activat, es dedueixen aquestes regles (només als camps sense regles pròpies):

| Estructura | Regla |
|---|---|
| `NOT NULL` sense valor per defecte | `required` (també a les contrasenyes en l'alta; en edició, no) |
| `VARCHAR(n)` (text, email, url) | `max_length[n]` |
| enter | `integer` |
| decimal | `decimal` |
| data | `valid_date[Y-m-d]` |
| data i hora | `valid_date[Y-m-d H:i:s]` |
| hora | `regex_match` de `HH:MM` o `HH:MM:SS` |
| color | `regex_match` de `#rrggbb` |
| nom amb `email` | `valid_email` |
| nom `url`, `web`, `website` | `valid_url_strict[http,https]` |

---

## Plantilles

Les plantilles d'un tema són fitxers PHP. Dins d'una plantilla, `$this` és un `Kpcrudy\Renderer`. Les variables que rep cada plantilla estan documentades a la seva capçalera, a [src/Views/bootstrap5](../src/Views/bootstrap5). La guia [new_theme_CA.md](new_theme_CA.md) explica com crear un tema.

### `Renderer::render(string $view, array $data = []): string`

Renderitza una altra plantilla del tema (o del tema pare, o de `bootstrap5`, si el tema actual no la té).

```php
<?= $this->render('partials/flash', ['flash' => $flash]) ?>
<?= $this->render('partials/button', ['action' => $action, 'compact' => true]) ?>
```

### `Renderer::renderParent(string $view, array $data = []): string`

Renderitza **la mateixa plantilla del tema pare**: la versió que hi hauria si el tema actual no la tingués. Serveix per ampliar una plantilla sense copiar-la. Funciona amb diversos nivells (un tema → el seu pare → `bootstrap5`).

```php
<!-- partials/assets.php d'un tema: hi afegeix CSS i manté el JavaScript del pare -->
<style>
    .kpcrudy .kp-yes { background: #009688; color: #fff; }
</style>
<?= $this->renderParent('partials/assets') ?>
```

### `Renderer::locate(string $view): string`

Ruta del fitxer que es faria servir per a una plantilla.

```php
<!-- <?= esc($this->locate('list')) ?> -->
```

### `Renderer::hidden(array $values, string $prefix = ''): string`

Converteix un array, fins i tot niat, en camps ocults de formulari. Serveix per conservar l'estat del llistat en un formulari GET.

```php
<form method="get" action="<?= esc($listUrl, 'attr') ?>">
    <?= $this->hidden(['sort' => 'name', 'f' => ['status' => 'paid']]) ?>
    <!-- sort=name i f[status]=paid -->
</form>
```

### `Renderer::attrs(array $attributes): string`

Genera atributs HTML escapats. `true` posa l'atribut sense valor, i `false` o `null` el treuen.

```php
<input<?= $this->attrs(['type' => 'text', 'name' => 'q', 'required' => true, 'disabled' => false]) ?>>
<!-- <input type="text" name="q" required> -->
```
