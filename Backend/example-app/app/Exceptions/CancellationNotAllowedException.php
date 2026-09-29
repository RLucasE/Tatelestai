<?php

namespace App\Exceptions;

use DomainException;
use Throwable;

class CancellationNotAllowedException extends DomainException
{
    public function __construct(string $message = 'No se puede cancelar la compra.', int $code = 422, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
