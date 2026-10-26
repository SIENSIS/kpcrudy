<?php

declare(strict_types=1);

namespace Kpcrudy\Exceptions;

use RuntimeException;

/**
 * Atura una importació "tot o res" perquè alguna fila té errors.
 * Fa que la transacció es desfaci; no arriba mai a l'usuari.
 */
final class ImportAbortedException extends RuntimeException
{
}
