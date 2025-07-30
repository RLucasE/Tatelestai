<?php

namespace App\Exceptions\Cart;

use Exception;

class OfferQuantityExceededException extends Exception
{
    protected $offerId;
    protected $requestedQuantity;
    protected $availableQuantity;
    protected $alreadyInCart;

    public function __construct($message = "La cantidad solicitada excede la disponible ", $code = 400)
    {
        parent::__construct($message, $code);
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
            'error' => "OfferQuantityExceded"
        ];
    }

    /**
     * Establecer datos de contexto para la excepción.
     *
     * @param int $offerId
     * @param int $requestedQuantity
     * @param int $availableQuantity
     * @param int $alreadyInCart
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
}
