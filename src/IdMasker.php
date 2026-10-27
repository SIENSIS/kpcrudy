<?php

declare(strict_types=1);

namespace Kpcrudy;

/**
 * Converteix els identificadors reals en codis per a les URL i a l'inrevés.
 *
 * El context (per defecte, el nom de la taula) fa que el mateix id doni codis
 * diferents en CRUD diferents. Es pot substituir per una implementació pròpia
 * (p. ex. amb Sqids) a Config\Kpcrudy::$idMasker.
 */
interface IdMasker
{
    /**
     * Codi que es posa a la URL en lloc de l'id.
     */
    public function encode(string $id, string $context): string;

    /**
     * Id real d'un codi, o null si el codi no és vàlid per a aquest context.
     */
    public function decode(string $code, string $context): ?string;
}
