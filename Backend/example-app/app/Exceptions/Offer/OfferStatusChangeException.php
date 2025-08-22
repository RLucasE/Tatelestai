<?php

namespace App\Exceptions\Offer;

use Exception;

class OfferStatusChangeException extends Exception
{
    protected $offerId;
    protected $currentStatus;
    protected $requestedStatus;

    public function __construct($message = "No se pudo cambiar el estado de la oferta", $code = 422, $offerId = null, $currentStatus = null, $requestedStatus = null)
    {
        parent::__construct($message, $code);

        $this->offerId = $offerId;
        $this->currentStatus = $currentStatus;
        $this->requestedStatus = $requestedStatus;
    }

    /**
     * Obtener información contextual de la excepción.
     *
     * @return array<string, mixed>
     */
    public function context(): array
    {
        return [
            'offer_id' => $this->offerId,
            'current_status' => $this->currentStatus,
            'requested_status' => $this->requestedStatus,
            'error' => "OfferStatusChangeException"
        ];
    }

    /**
     * Crear excepción para cuando el estado es el mismo
     */
    public static function sameStatus($offerId, $status): self
    {
        return new self(
            "La oferta ya tiene el estado '{$status}'",
            422,
            $offerId,
            $status,
            $status
        );
    }

    /**
     * Crear excepción para cuando el estado no es válido
     */
    public static function invalidStatus($offerId, $currentStatus, $requestedStatus): self
    {
        return new self(
            "El estado '{$requestedStatus}' no es válido",
            422,
            $offerId,
            $currentStatus,
            $requestedStatus
        );
    }
}
