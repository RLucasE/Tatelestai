<?php

namespace App\Exceptions\Cart;

use App\Exceptions\ApiException;

class OfferQuantityExceededException extends ApiException
{
    protected int $httpStatus = 400;

    protected string $errorCode = 'OFFER_QUANTITY_EXCEEDED';

    protected $offerId;

    protected $requestedQuantity;

    protected $availableQuantity;

    protected $alreadyInCart;

    public function __construct($message = 'La cantidad solicitada excede la disponible ', $code = 400)
    {
        parent::__construct($message, $code);
    }

    /**
     * Contexto público seguro expuesto en response.data.context.
     *
     * @return array<string, mixed>
     */
    public function publicContext(): array
    {
        return [
            'offer_id' => $this->offerId,
            'requested_quantity' => $this->requestedQuantity,
            'available_quantity' => $this->availableQuantity,
            'already_in_cart' => $this->alreadyInCart,
        ];
    }

    /**
     * Campos en el nivel raíz del JSON por retrocompatibilidad con clientes previos.
     *
     * @return array<string, mixed>
     */
    public function rootFields(): array
    {
        return [
            'requested_quantity' => $this->requestedQuantity,
            'available_quantity' => $this->availableQuantity,
            'already_in_cart' => $this->alreadyInCart,
        ];
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
            'requested_quantity' => $this->requestedQuantity,
            'available_quantity' => $this->availableQuantity,
            'already_in_cart' => $this->alreadyInCart,
            'error' => 'OfferQuantityExceded',
        ];
    }

    /**
     * Establecer datos de contexto para la excepción.
     *
     * @return $this
     */
    public function setContext(int $offerId, int $requestedQuantity, int $availableQuantity, int $alreadyInCart)
    {
        $this->offerId = $offerId;
        $this->requestedQuantity = $requestedQuantity;
        $this->availableQuantity = $availableQuantity;
        $this->alreadyInCart = $alreadyInCart;

        return $this;
    }

    public function setOfferId(int $offerId)
    {
        $this->offerId = $offerId;
    }
}
