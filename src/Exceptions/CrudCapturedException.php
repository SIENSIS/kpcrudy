<?php

declare(strict_types=1);

namespace Kpcrudy\Exceptions;

use Kpcrudy\Crud;
use RuntimeException;

/**
 * Atura render() mentre Crud::capture() està actiu i porta el CRUD construït.
 * Crud::capture() la captura; no arriba mai a l'usuari.
 */
final class CrudCapturedException extends RuntimeException
{
    public function __construct(public readonly Crud $crud)
    {
        parent::__construct('kpcrudy: CRUD captured (' . $crud->getResource() . ').');
    }
}
