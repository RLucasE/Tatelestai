<?php

namespace App\Exceptions\Report;

use App\Exceptions\ApiException;

class DuplicateReportException extends ApiException
{
    protected int $httpStatus = 409;

    protected string $errorCode = 'DUPLICATE_REPORT';

    protected $reportableType;

    protected $reportableId;

    protected $userId;

    public function __construct(
        $message = 'You have already reported this element',
        $code = 409,
        $reportableType = null,
        $reportableId = null,
        $userId = null
    ) {
        parent::__construct($message, $code);
        $this->reportableType = $reportableType;
        $this->reportableId = $reportableId;
        $this->userId = $userId;
    }

    /**
     * Contexto público seguro expuesto al cliente (sin exponer user_id interno).
     *
     * @return array<string, mixed>
     */
    public function publicContext(): array
    {
        return array_filter([
            'reportable_type' => $this->reportableType ? class_basename((string) $this->reportableType) : null,
            'reportable_id' => $this->reportableId,
        ], fn ($value) => $value !== null);
    }

    /**
     * Obtener información contextual de la excepción.
     *
     * @return array<string, mixed>
     */
    public function context(): array
    {
        return [
            'reportable_type' => $this->reportableType,
            'reportable_id' => $this->reportableId,
            'user_id' => $this->userId,
        ];
    }
}
