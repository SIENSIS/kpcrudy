# Captures de la demo

[English](README_EN.md) · **Català**

Un recorregut ràpid per l'aplicació de demostració de kpcrudy. Totes les pantalles de gestió són CRUD automàtics: cadascuna surt d'un sol mètode de controlador. Per instal·lar la demo al teu projecte, vegeu [Instal·lar la demo al projecte](../README_CA.md#installar-la-demo-al-projecte).

## Entrada i inici

**Entrada.** No cal contrasenya: es tria l'usuari amb un clic. Els administradors veuen tota l'aplicació; els usuaris, l'agenda, les seves inscripcions i el perfil.

![Pàgina d'entrada](01-login.png)

**Inici.** Estadístiques, propers esdeveniments i el mapa de la demo amb què ensenya cada pàgina.

![Pàgina d'inici](02-inici.png)

## Administració

**Usuaris.** Llistat amb foto, columnes calculades (nom complet, inscripcions), relació N-N (interessos), booleans, paperera, accions per fila, accions massives i exportació CSV.

![Llistat d'usuaris](03-usuaris.png)

**Esdeveniments amb filtres.** Filtres per llista (categoria, estat) i per rang (data, preu), combinables amb la cerca i l'ordenació. La columna d'ocupació es calcula per a cada fila.

![Esdeveniments filtrats](04-esdeveniments-filtres.png)

**Fitxa de detall.** Relacions resoltes (categoria, creador), relació N-N d'assistents i accions pròpies: *Attendees*, *Duplicate*.

![Fitxa d'un esdeveniment](05-esdeveniment-fitxa.png)

**Formulari.** Tipus de camp deduïts de la base de dades (text, desplegable de relació, data i hora, número, decimal, enum) i pujada d'imatge amb previsualització.

![Formulari d'edició](06-esdeveniment-formulari.png)

**Validació.** Regles de CodeIgniter (`is_unique` amb `{id}`, `min_length`...) i camps obligatoris deduïts de la taula. Els errors surten al costat de cada camp.

![Formulari amb errors de validació](10-validacio.png)

**Importació CSV: pujar el fitxer.** S'activa amb `enableImport()`. Es tria si cada fila s'afegeix sempre o actualitza el registre existent (aquí, pel nom d'usuari), i què passa amb les files amb errors. A la dreta hi ha les columnes que s'esperen i un botó per descarregar una plantilla.

![Pujar un CSV](15-importacio-pujada.png)

**Importació CSV: previsualització.** Com s'han associat les columnes (`Notes` s'ignora), quantes files són noves, quantes s'actualitzaran i quines tenen errors, amb el motiu per línia. Les contrasenyes no es mostren.

![Previsualització de la importació](16-importacio-previsualitzacio.png)

**Importació CSV: resultat.** Resum i informe de les files saltades. L'última és un email repetit dins del mateix fitxer, que només es detecta en importar.

![Resultat de la importació](17-importacio-resultat.png)

**Paperera.** Amb els *soft deletes* de CodeIgniter: restaurar, esborrar definitivament o buidar la paperera.

![Paperera d'esdeveniments](07-paperera.png)

**Categories.** CRUD fet a partir d'un model de CodeIgniter, amb camp de color i sense paperera.

![Categories](08-categories.png)

**Assistència.** CRUD sobre la taula de lligam entre usuaris i esdeveniments, obert ja filtrat per un esdeveniment des de la seva fitxa (`?f[event_id]=...`).

![Assistència a un esdeveniment](09-assistencia.png)

**Codi de cada pàgina.** El botó *Code for this page* mostra el mètode del controlador que genera el CRUD i, en una altra pestanya, les funcionalitats que s'hi veuen.

![Panell de codi](11-panell-codi.png)

## Àrea d'usuari

**Agenda.** Només consulta i només esdeveniments publicats i futurs. El botó *Join* només surt si queden places i no hi estàs inscrit.

![Agenda](12-agenda.png)

**Les meves inscripcions.** L'usuari només veu les seves. Les futures es poden anul·lar d'una en una o en bloc.

![Les meves inscripcions](13-meves-inscripcions.png)

**El meu perfil.** CRUD d'un sol registre amb un tema propi per a la fitxa.

![Perfil](14-perfil.png)

## Altres temes

El mateix CRUD amb els temes `w3css` i `tailwind` que inclou la llibreria (`$crud->setTheme('w3css')`). La guia [docs/new_theme_CA.md](../docs/new_theme_CA.md) explica com estan fets i com crear-ne un.

**W3.CSS.** Llistat amb els filtres oberts i formulari amb errors de validació.

![Tema W3.CSS: llistat](18-tema-w3css-llistat.png)

![Tema W3.CSS: formulari](19-tema-w3css-formulari.png)

**Tailwind CSS.** Les mateixes pàgines.

![Tema Tailwind: llistat](20-tema-tailwind-llistat.png)

![Tema Tailwind: formulari](21-tema-tailwind-formulari.png)
