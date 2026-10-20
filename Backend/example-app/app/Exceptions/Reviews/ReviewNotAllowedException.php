<?php

namespace App\Exceptions\Reviews;

use App\Exceptions\ApiException;
use Throwable;

class ReviewNotAllowedException extends ApiException
{
    protected int $httpStatus = 422;

    protected string $errorCode = 'REVIEW_NOT_ALLOWED';

    public function __construct(string $message = 'No se puede calificar la compra.', int $code = 422, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
