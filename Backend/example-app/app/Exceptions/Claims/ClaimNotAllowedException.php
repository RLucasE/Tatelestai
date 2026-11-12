<?php

namespace App\Exceptions\Claims;

use App\Exceptions\ApiException;
use Throwable;

class ClaimNotAllowedException extends ApiException
{
    protected int $httpStatus = 422;

    protected string $errorCode = 'CLAIM_NOT_ALLOWED';

    public function __construct(string $message = 'No se puede iniciar un reclamo para esta compra.', int $code = 422, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
