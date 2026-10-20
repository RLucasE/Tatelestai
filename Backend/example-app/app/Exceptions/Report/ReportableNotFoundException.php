<?php

namespace App\Exceptions\Report;

use App\Exceptions\ApiException;

class ReportableNotFoundException extends ApiException
{
    protected int $httpStatus = 404;

    protected string $errorCode = 'REPORTABLE_NOT_FOUND';

    protected $reportableType;

    protected $reportableId;

    public function __construct(
        $message = 'The element to report does not exist',
        $code = 404,
        $reportableType = null,
        $reportableId = null
    ) {
        parent::__construct($message, $code);
        $this->reportableType = $reportableType;
        $this->reportableId = $reportableId;
    }

    /**
     * Contexto público seguro expuesto al cliente.
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
        ];
    }
}
