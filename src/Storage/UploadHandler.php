<?php

declare(strict_types=1);

namespace Kpcrudy\Storage;

use CodeIgniter\HTTP\Files\UploadedFile;

/**
 * Desa, esborra i dona l'URL dels fitxers dels camps 'upload' i 'image' en lloc
 * de kpcrudy (que, sense gestor, els mou a Config\Kpcrudy::$uploadPath).
 *
 * Es configura per a tots els CRUD a Config\Kpcrudy::$uploadHandler o per camp:
 * fieldType('photo', 'image', ['handler' => MyStorage::class]). kpcrudy continua
 * validant el fitxer (extensions, tipus, mida) abans de cridar store().
 *
 * $context conté:
 *  - 'crud'      la instància de Kpcrudy\Crud
 *  - 'resource'  el recurs del CRUD (getResource()) i 'table' la taula
 *  - 'field'     el camp i 'type' ('upload' o 'image')
 *  - 'options'   les opcions del camp (fieldType()), incloses les pròpies
 *  - 'operation' 'add', 'edit', 'delete' (esborrat definitiu), 'list', 'show' o 'form'
 *  - 'data'      (store) els valors que s'estan desant; 'record' el registre actual o null
 */
interface UploadHandler
{
    /**
     * Desa el fitxer i retorna el valor que es guarda a la columna (un camí, una clau...).
     * Per refusar-lo amb un missatge per a l'usuari: throw new UploadRejectedException('...').
     *
     * @param array<string, mixed> $context
     */
    public function store(UploadedFile $file, string $field, array $context): string;

    /**
     * Esborra un fitxer desat (en substituir-lo, treure'l o esborrar el registre definitivament).
     *
     * @param array<string, mixed> $context
     */
    public function delete(string $stored, string $field, array $context): void;

    /**
     * URL per mostrar o descarregar el fitxer, o null si no en té (es mostra només el nom).
     *
     * @param array<string, mixed> $context
     */
    public function url(string $stored, string $field, array $context): ?string;
}
