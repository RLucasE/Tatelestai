<?php

namespace App\Exceptions\Report;

use Exception;

class DuplicateReportException extends Exception
{
    protected $reportableType;
    protected $reportableId;
    protected $userId;

    public function __construct(
        $message = "You have already reported this element",
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

