<?php

namespace App\Exceptions\Report;

use Exception;

class ReportableNotFoundException extends Exception
{
    protected $reportableType;
    protected $reportableId;

    public function __construct(
        $message = "The element to report does not exist",
        $code = 404,
        $reportableType = null,
        $reportableId = null
    ) {
        parent::__construct($message, $code);
        $this->reportableType = $reportableType;
        $this->reportableId = $reportableId;
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

