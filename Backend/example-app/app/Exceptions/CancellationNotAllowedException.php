<?php

namespace App\Exceptions;

use Throwable;

class CancellationNotAllowedException extends ApiException
{
    protected int $httpStatus = 422;

    protected string $errorCode = 'CANCELLATION_NOT_ALLOWED';

    public function __construct(string $message = 'No se puede cancelar la compra.', int $code = 422, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
