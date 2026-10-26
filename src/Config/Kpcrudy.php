<?php

declare(strict_types=1);

namespace Kpcrudy\Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Configuració global de Kpcrudy.
 *
 * Per canviar-la des del projecte crea app/Config/Kpcrudy.php amb una classe
 * Config\Kpcrudy que estengui aquesta.
 */
class Kpcrudy extends BaseConfig
{
    /**
     * Tema per defecte: 'bootstrap5', 'w3css', 'tailwind' o un de $themes.
     */
    public string $theme = 'bootstrap5';

    /**
     * Temes addicionals: nom => directori amb les plantilles, o bé
     * nom => ['path' => directori, 'parent' => tema pare].
     * Les plantilles que no existeixin en un tema es carreguen del pare i,
     * al final, de 'bootstrap5'.
     *
     * Exemples:
     *     'admin'  => APPPATH . 'Views/kpcrudy/admin'
     *     'w3teal' => ['path' => APPPATH . 'Views/kpcrudy/w3teal', 'parent' => 'w3css']
     *
     * @var array<string, string|array{path: string, parent?: string}>
     */
    public array $themes = [];

    /**
     * Layout que embolcalla el CRUD:
     *  - 'default': pàgina HTML completa del tema (layout.php)
     *  - false:     només el fragment HTML del CRUD
     *  - qualsevol altre valor: nom d'una vista de CodeIgniter que rebrà
     *    les variables $kpcrudy (HTML del CRUD) i $title.
     */
    public string|false $layout = 'default';

    public int $perPage = 25;

    /**
     * @var list<int>
     */
    public array $perPageOptions = [10, 25, 50, 100];

    public string $dateFormat     = 'd/m/Y';
    public string $datetimeFormat = 'd/m/Y H:i';

    /**
     * Nombre màxim de caràcters dels textos al llistat (0 = sense límit).
     */
    public int $listTruncate = 60;

    /**
     * Dedueix regles de validació a partir de l'estructura de la taula
     * (NOT NULL => required, VARCHAR(n) => max_length[n], tipus...).
     */
    public bool $inferRules = true;

    public string $csvDelimiter = ';';
    public bool $csvBom         = true;

    /**
     * Nombre màxim de files d'una exportació CSV (0 = sense límit). Si el
     * llistat filtrat en té més, es demana a l'usuari que el filtri.
     */
    public int $exportMaxRows = 100000;

    /**
     * Verifica el token CSRF de les peticions POST del CRUD quan el filtre
     * 'csrf' de CodeIgniter no està actiu per a la ruta. Desactiva-ho només
     * si el projecte protegeix les peticions d'una altra manera.
     */
    public bool $csrfProtection = true;

    /**
     * Noms de camp (o parts del nom separades per "_") que es consideren
     * secrets: no surten per defecte al llistat, la fitxa, la cerca,
     * l'exportació ni els formularis. Es poden mostrar indicant-los explícitament.
     *
     * @var list<string>
     */
    public array $sensitiveFields = [
        'password', 'passwd', 'pass', 'pwd', 'contrasenya', 'contrasena',
        'secret', 'token', 'salt', 'hash', 'apikey', 'otp', 'totp', '2fa',
    ];

    /**
     * Límits per defecte de la importació CSV (enableImport()).
     */
    public int $importMaxRows = 1000;
    public int $importMaxSize = 2048;

    /**
     * Directori i URL base per als fitxers pujats. A cada camp s'hi afegeix
     * una subcarpeta amb el nom de la taula, si no s'indica 'path' al camp.
     */
    public string $uploadPath = FCPATH . 'uploads';
    public string $uploadUrl  = 'uploads';

    /**
     * Mida màxima per defecte dels fitxers pujats, en KB.
     */
    public int $uploadMaxSize = 2048;

    /**
     * Extensions permeses per defecte als camps 'upload'.
     *
     * @var list<string>
     */
    public array $uploadAllowed = [
        'jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'txt', 'csv', 'zip',
        'doc', 'docx', 'xls', 'xlsx', 'odt', 'ods', 'odp', 'ppt', 'pptx',
    ];

    /**
     * Extensions permeses per defecte als camps 'image'.
     *
     * @var list<string>
     */
    public array $imageAllowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    /**
     * Recursos CDN que carrega el layout per defecte.
     */
    public string $bootstrapCss = 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css';
    public string $bootstrapJs  = 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js';
    public string $iconsCss     = 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css';
}
