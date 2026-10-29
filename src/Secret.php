<?php

declare(strict_types=1);

namespace Kpcrudy;

use Kpcrudy\Config\Kpcrudy as KpcrudyConfig;
use RuntimeException;

/**
 * Clau secreta de la llibreria, per emmascarar identificadors i signar URL.
 *
 * L'origen és, per ordre: Config\Kpcrudy::$maskKey, la clau d'encriptació de
 * CodeIgniter (php spark key:generate) i, si no n'hi ha cap, una clau aleatòria
 * que es desa a writable/kpcrudy/mask.key la primera vegada. De l'origen se'n
 * deriva una clau diferent per a cada ús, de manera que un codi emmascarat
 * no serveix mai com a signatura ni a l'inrevés.
 */
final class Secret
{
    /**
     * Clau de 32 bytes per a un ús concret ('mask-encrypt', 'mask-sign', 'url-sign'...).
     */
    public static function key(KpcrudyConfig $config, string $purpose): string
    {
        return hash_hmac('sha256', 'kpcrudy-' . $purpose, self::source($config), true);
    }

    private static function source(KpcrudyConfig $config): string
    {
        if ($config->maskKey !== '') {
            // Una clau curta es podria endevinar provant-ne moltes
            if (strlen($config->maskKey) < 32) {
                throw new RuntimeException(lang('Kpcrudy.exceptions.maskKeyTooShort'));
            }

            return $config->maskKey;
        }

        $encryption = config('Encryption');

        if ($encryption !== null && is_string($encryption->key) && $encryption->key !== '') {
            return $encryption->key;
        }

        return self::fileKey(WRITEPATH . 'kpcrudy' . DIRECTORY_SEPARATOR . 'mask.key');
    }

    /**
     * Clau aleatòria desada al servidor. Si s'esborra, canvien tots els codis i signatures.
     */
    private static function fileKey(string $file): string
    {
        if (! is_file($file)) {
            $dir = dirname($file);

            if (! is_dir($dir) && ! @mkdir($dir, 0700, true) && ! is_dir($dir)) {
                throw new RuntimeException(lang('Kpcrudy.exceptions.cannotCreateDir', [$dir]));
            }

            // Mode 'x': si dues peticions la creen alhora, només una l'escriu
            $handle = @fopen($file, 'xb');

            if ($handle !== false) {
                fwrite($handle, bin2hex(random_bytes(32)));
                fclose($handle);
                @chmod($file, 0600);
            }
        }

        // Una altra petició pot estar acabant d'escriure-la
        for ($try = 0; strlen($key = trim((string) @file_get_contents($file))) !== 64 && $try < 3; $try++) {
            usleep(50000);
        }

        if (strlen($key) !== 64 || ! ctype_xdigit($key)) {
            throw new RuntimeException(lang('Kpcrudy.exceptions.invalidKeyFile', [$file]));
        }

        return hex2bin($key);
    }
}
