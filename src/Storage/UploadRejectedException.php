<?php

declare(strict_types=1);

namespace Kpcrudy\Storage;

use RuntimeException;

/**
 * Un UploadHandler refusa el fitxer: el missatge es mostra a l'usuari com a error del camp
 * i no es desa res.
 */
class UploadRejectedException extends RuntimeException
{
}
