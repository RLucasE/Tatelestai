<?php

namespace App\Exceptions;

use DomainException;
use Illuminate\Contracts\Debug\ShouldntReport;
use Throwable;

abstract class ApiException extends DomainException implements ShouldntReport
{
    protected int $httpStatus = 422;

    protected string $errorCode = 'API_ERROR';

    /**
     * @var array<string, mixed>
     */
    protected array $publicContextData = [];

    /**
     * @var array<string, mixed>
     */
    protected array $internalLogContext = [];

    public function __construct(
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null
    ) {
        $resolvedCode = $code > 0 ? $code : $this->httpStatus;
        if ($resolvedCode >= 400 && $resolvedCode < 600) {
            $this->httpStatus = $resolvedCode;
        }

        parent::__construct($message, $resolvedCode, $previous);
    }

    /**
     * Código de estado HTTP que debe devolver la respuesta.
     */
    public function status(): int
    {
        $code = $this->getCode();

        if (is_int($code) && $code >= 400 && $code < 600) {
            return $code;
        }

        return $this->httpStatus;
    }

    /**
     * Código estable de error legible por máquinas (SCREAMING_SNAKE_CASE).
     */
    public function errorCode(): string
    {
        return $this->errorCode;
    }

    /**
     * Contexto público seguro que puede enviarse al cliente en el bloque "context" del JSON.
     *
     * @return array<string, mixed>
     */
    public function publicContext(): array
    {
        return $this->publicContextData;
    }

    /**
     * Campos adicionales en el nivel raíz del JSON para retrocompatibilidad con contratos previos.
     *
     * @return array<string, mixed>
     */
    public function rootFields(): array
    {
        return [];
    }

    /**
     * Contexto técnico interno para los logs del servidor (no se expone automáticamente al cliente).
     *
     * @return array<string, mixed>
     */
    public function context(): array
    {
        return array_merge([
            'error_code' => $this->errorCode(),
            'http_status' => $this->status(),
        ], $this->internalLogContext);
    }
}
