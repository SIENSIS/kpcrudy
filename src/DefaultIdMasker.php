<?php

declare(strict_types=1);

namespace Kpcrudy;

use Kpcrudy\Config\Kpcrudy as KpcrudyConfig;
use RuntimeException;

/**
 * Emmascarador per defecte: xifra l'id amb AES-256-CTR i fa servir com a
 * vector inicial un HMAC de l'id. Així el codi:
 *
 *  - és sempre el mateix per al mateix id i context (les URL no canvien),
 *  - no deixa veure l'id ni l'ordre dels registres,
 *  - no es pot fabricar ni modificar sense la clau (decode() retorna null),
 *  - és curt: 16 caràcters més la llargada de l'id, aproximadament.
 *
 * La clau surt de Kpcrudy\Secret (Config\Kpcrudy::$maskKey, la clau
 * d'encriptació de CodeIgniter o writable/kpcrudy/mask.key).
 */
class DefaultIdMasker implements IdMasker
{
    protected const CIPHER = 'aes-256-ctr';
    protected const TAG    = 12;

    protected ?string $encKey = null;
    protected ?string $macKey = null;

    public function __construct(protected KpcrudyConfig $config)
    {
    }

    public function encode(string $id, string $context): string
    {
        [$encKey, $macKey] = $this->keys();

        $tag    = $this->tag($id, $context, $macKey);
        $cipher = openssl_encrypt($id, self::CIPHER, $encKey, OPENSSL_RAW_DATA, $tag . "\0\0\0\0");

        if ($cipher === false) {
            throw new RuntimeException('Kpcrudy: no s\'ha pogut emmascarar l\'identificador.');
        }

        return rtrim(strtr(base64_encode($tag . $cipher), '+/', '-_'), '=');
    }

    public function decode(string $code, string $context): ?string
    {
        if ($code === '' || strlen($code) > 400 || ! preg_match('/^[A-Za-z0-9_-]+$/', $code)) {
            return null;
        }

        $raw = base64_decode(strtr($code, '-_', '+/'), true);

        if ($raw === false || strlen($raw) <= self::TAG) {
            return null;
        }

        [$encKey, $macKey] = $this->keys();

        $tag = substr($raw, 0, self::TAG);
        $id  = openssl_decrypt(substr($raw, self::TAG), self::CIPHER, $encKey, OPENSSL_RAW_DATA, $tag . "\0\0\0\0");

        // El vector inicial és la signatura: si no coincideix, el codi és fals o d'un altre context
        if ($id === false || ! hash_equals($this->tag($id, $context, $macKey), $tag)) {
            return null;
        }

        // Un mateix id només té un codi vàlid (base64 admet variants als darrers bits)
        return $this->encode($id, $context) === $code ? $id : null;
    }

    protected function tag(string $id, string $context, string $macKey): string
    {
        return substr(hash_hmac('sha256', $context . "\0" . $id, $macKey, true), 0, self::TAG);
    }

    /**
     * @return array{0: string, 1: string} [clau de xifratge, clau de signatura]
     */
    protected function keys(): array
    {
        if ($this->encKey === null) {
            if (! extension_loaded('openssl')) {
                throw new RuntimeException('Kpcrudy: per emmascarar els identificadors cal l\'extensió openssl de PHP.');
            }

            $this->encKey = Secret::key($this->config, 'mask-encrypt');
            $this->macKey = Secret::key($this->config, 'mask-sign');
        }

        return [$this->encKey, $this->macKey];
    }
}
