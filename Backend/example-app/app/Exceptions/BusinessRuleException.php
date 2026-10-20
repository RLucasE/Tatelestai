<?php

namespace App\Exceptions;

use Throwable;

class BusinessRuleException extends ApiException
{
    /**
     * @param  array<string, mixed>  $publicContext
     * @param  array<string, mixed>  $internalContext
     */
    public function __construct(
        string $message,
        int $status = 422,
        string $errorCode = 'BUSINESS_RULE_VIOLATION',
        array $publicContext = [],
        array $internalContext = [],
        ?Throwable $previous = null
    ) {
        $this->httpStatus = $status;
        $this->errorCode = $errorCode;
        $this->publicContextData = $publicContext;
        $this->internalLogContext = $internalContext;

        parent::__construct($message, $status, $previous);
    }

    /**
     * @param  array<string, mixed>  $publicContext
     */
    public static function unprocessable(
        string $message,
        string $errorCode = 'BUSINESS_RULE_VIOLATION',
        array $publicContext = []
    ): self {
        return new self($message, 422, $errorCode, $publicContext);
    }

    /**
     * @param  array<string, mixed>  $publicContext
     */
    public static function badRequest(
        string $message,
        string $errorCode = 'BAD_REQUEST',
        array $publicContext = []
    ): self {
        return new self($message, 400, $errorCode, $publicContext);
    }

    /**
     * @param  array<string, mixed>  $publicContext
     */
    public static function forbidden(
        string $message,
        string $errorCode = 'FORBIDDEN',
        array $publicContext = []
    ): self {
        return new self($message, 403, $errorCode, $publicContext);
    }

    /**
     * @param  array<string, mixed>  $publicContext
     */
    public static function notFound(
        string $message,
        string $errorCode = 'RESOURCE_NOT_FOUND',
        array $publicContext = []
    ): self {
        return new self($message, 404, $errorCode, $publicContext);
    }

    /**
     * @param  array<string, mixed>  $publicContext
     */
    public static function conflict(
        string $message,
        string $errorCode = 'CONFLICT',
        array $publicContext = []
    ): self {
        return new self($message, 409, $errorCode, $publicContext);
    }
}
