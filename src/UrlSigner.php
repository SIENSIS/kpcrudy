<?php

declare(strict_types=1);

namespace Kpcrudy;

use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\URI;
use Kpcrudy\Config\Kpcrudy as KpcrudyConfig;

/**
 * Signa URL i en comprova la signatura.
 *
 * Una URL signada porta dos paràmetres: kp_sig (la signatura) i, si caduca,
 * kp_exp (moment de caducitat, en segons Unix). La signatura és un HMAC-SHA256
 * amb una clau derivada de Kpcrudy\Secret i cobreix:
 *
 *  - el mode: 'full' (camí, caducitat i tots els paràmetres: no es pot canviar
 *    res) o 'path' (camí i caducitat: els paràmetres ?q=, ?page=... es poden canviar);
 *  - l'abast: '' per a les URL signades amb Crud::signUrl() o signedUrl(), i
 *    'crud' / 'crud-trash' per a les que signa un CRUD mentre es navega. Una
 *    ruta pròpia només accepta l'abast '', de manera que un CRUD no pot fabricar
 *    signatures que serveixin per a una altra pàgina.
 *
 * El camí es compara tal com el veu CodeIgniter ($request->getPath()): sense el
 * domini, el directori base ni index.php. Una URL signada continua sent vàlida
 * si canvia el port o el domini, però no si es canvia una sola lletra del camí.
 */
class UrlSigner
{
    public const VALID   = 'valid';
    public const MISSING = 'missing';
    public const INVALID = 'invalid';
    public const EXPIRED = 'expired';

    /**
     * Paràmetres que fa servir la signatura.
     */
    public const PARAMS = ['kp_sig', 'kp_exp'];

    protected ?string $key = null;

    /**
     * Mode i abast de la darrera signatura vàlida (o caducada) comprovada.
     *
     * @var array{mode: string, scope: string}|null
     */
    protected ?array $matched = null;

    public function __construct(protected KpcrudyConfig $config)
    {
    }

    /**
     * Afegeix la signatura a una URL (absoluta o relativa al projecte).
     *
     * $expires:   moment de caducitat (segons Unix) o null perquè no caduqui.
     * $lockQuery: si la signatura cobreix també els paràmetres de la URL.
     * $scope:     '' (enllaços creats pel desenvolupador) o 'crud' / 'crud-trash'.
     */
    public function sign(string $url, ?int $expires, bool $lockQuery = true, string $scope = ''): string
    {
        [$base, $query, $fragment] = $this->split($url);
        parse_str($query, $params);

        foreach (self::PARAMS as $param) {
            unset($params[$param]);
        }

        if ($expires !== null) {
            $params['kp_exp'] = (string) $expires;
        }

        $path             = $this->path($base);
        $params['kp_sig'] = $lockQuery
            ? $this->compute('full', $scope, $path, $expires, $params)
            : $this->compute('path', $scope, $path, $expires, []);

        return $base . '?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986) . $fragment;
    }

    /**
     * Estat de la signatura de la petició: VALID, MISSING, INVALID o EXPIRED.
     *
     * $allowFreeQuery: accepta també les signatures que només cobreixen el camí.
     * $scopes:         abasts acceptats ('' = signades pel desenvolupador).
     *
     * @param list<string> $scopes
     */
    public function check(IncomingRequest $request, bool $allowFreeQuery = false, array $scopes = ['']): string
    {
        // getPath() ja està descodificat i normalitzat per CodeIgniter: no s'ha de tornar a descodificar
        return $this->checkParams(trim($request->getPath(), '/'), (array) $request->getGet(), $allowFreeQuery, $scopes);
    }

    /**
     * Com check(), però d'una URL escrita (p. ex. des de la línia de comandes).
     *
     * @param list<string> $scopes
     */
    public function checkUrl(string $url, bool $allowFreeQuery = false, array $scopes = ['']): string
    {
        [$base, $query] = $this->split($url);
        parse_str($query, $params);

        return $this->checkParams($this->path($base), $params, $allowFreeQuery, $scopes);
    }

    /**
     * Mode ('full' o 'path') i abast de la darrera signatura correcta comprovada.
     *
     * @return array{mode: string, scope: string}|null
     */
    public function matched(): ?array
    {
        return $this->matched;
    }

    /**
     * Caducitat (segons Unix) d'una petició, o null si no en té.
     */
    public function expires(IncomingRequest $request): ?int
    {
        $exp = $request->getGet('kp_exp');

        return is_string($exp) && ctype_digit($exp) && strlen($exp) <= 12 ? (int) $exp : null;
    }

    /**
     * @param array<array-key, mixed> $get
     * @param list<string>            $scopes
     */
    protected function checkParams(string $path, array $get, bool $allowFreeQuery, array $scopes): string
    {
        $this->matched = null;

        $sig = $get['kp_sig'] ?? null;
        $exp = $get['kp_exp'] ?? null;

        if ($sig === null) {
            return self::MISSING;
        }

        if (! is_string($sig) || strlen($sig) !== 32 || ($exp !== null && (! is_string($exp) || ! ctype_digit($exp) || strlen($exp) > 12))) {
            return self::INVALID;
        }

        $expires = $exp === null ? null : (int) $exp;
        $params  = $get;
        unset($params['kp_sig']);

        foreach ($scopes as $scope) {
            foreach ($allowFreeQuery ? ['full', 'path'] : ['full'] as $mode) {
                if (hash_equals($this->compute($mode, $scope, $path, $expires, $mode === 'full' ? $params : []), $sig)) {
                    $this->matched = ['mode' => $mode, 'scope' => $scope];

                    // La caducitat només es comprova si la signatura és bona (no dona pistes a qui la falsifica)
                    return $expires !== null && $expires < time() ? self::EXPIRED : self::VALID;
                }
            }
        }

        return self::INVALID;
    }

    /**
     * @param array<array-key, mixed> $params
     */
    protected function compute(string $mode, string $scope, string $path, ?int $expires, array $params): string
    {
        $payload = $mode . "\n" . $scope . "\n" . $path . "\n" . ($expires ?? '');

        if ($mode === 'full') {
            unset($params['kp_exp'], $params['kp_sig']);
            $payload .= "\n" . http_build_query($this->sortKeys($params), '', '&', PHP_QUERY_RFC3986);
        }

        $this->key ??= Secret::key($this->config, 'url-sign');

        return substr(rtrim(strtr(base64_encode(hash_hmac('sha256', $payload, $this->key, true)), '+/', '-_'), '='), 0, 32);
    }

    /**
     * Camí de la URL tal com el veurà CodeIgniter a $request->getPath(): relatiu
     * al projecte, sense index.php, descodificat, sense segments "." i ".." i
     * amb la mateixa codificació (vegeu CodeIgniter\HTTP\URI::filterPath()).
     */
    protected function path(string $url): string
    {
        $path = (string) parse_url($url, PHP_URL_PATH);

        if (parse_url($url, PHP_URL_HOST) !== null || str_starts_with($url, '/')) {
            $root = rtrim((string) parse_url(base_url(), PHP_URL_PATH), '/') . '/';
            $path = str_starts_with($path . '/', $root) ? substr($path, strlen($root)) : $path;
        }

        $path = trim($path, '/');
        $page = config('App')->indexPage ?? '';

        if ($page !== '' && ($path === $page || str_starts_with($path, $page . '/'))) {
            $path = substr($path, strlen($page));
        }

        $path = URI::removeDotSegments(urldecode($path));
        $path = (string) preg_replace_callback(
            '/(?:[^' . URI::CHAR_UNRESERVED . ':@&=\+\$,\/;%]+|%(?![A-Fa-f0-9]{2}))/',
            static fn (array $matches): string => rawurlencode($matches[0]),
            $path,
        );

        return trim($path, '/');
    }

    /**
     * @return array{0: string, 1: string, 2: string} [URL sense paràmetres, paràmetres, #fragment]
     */
    protected function split(string $url): array
    {
        $fragment = '';

        if (($hash = strpos($url, '#')) !== false) {
            $fragment = substr($url, $hash);
            $url      = substr($url, 0, $hash);
        }

        [$base, $query] = array_pad(explode('?', $url, 2), 2, '');

        return [$base, $query, $fragment];
    }

    /**
     * @param array<array-key, mixed> $params
     *
     * @return array<array-key, mixed>
     */
    protected function sortKeys(array $params): array
    {
        ksort($params, SORT_STRING);

        foreach ($params as $key => $value) {
            if (is_array($value)) {
                $params[$key] = $this->sortKeys($value);
            }
        }

        return $params;
    }
}
