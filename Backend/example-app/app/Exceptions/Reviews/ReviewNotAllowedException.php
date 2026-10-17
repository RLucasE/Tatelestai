<?php

namespace App\Exceptions\Reviews;

use DomainException;
use Throwable;

class ReviewNotAllowedException extends DomainException
{
    public function __construct(string $message = 'No se puede calificar la compra.', int $code = 422, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
