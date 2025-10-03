<?php

namespace App\Exceptions\Offer;

use Exception;

class InvalidExpirationDateException extends Exception
{
    protected $offerExpirationDate;
    protected $productExpirationDate;
    protected $productId;

    public function __construct($message = "Fecha de expiración inválida", $code = 422, $offerExpirationDate = null, $productExpirationDate = null, $productId = null)
    {
        parent::__construct($message, $code);

        $this->offerExpirationDate = $offerExpirationDate;
        $this->productExpirationDate = $productExpirationDate;
        $this->productId = $productId;
    }

    /**
     * Obtener información contextual de la excepción.
     *
     * @return array<string, mixed>
     */
    public function context(): array
    {
        return [
            'offer_expiration_date' => $this->offerExpirationDate,
            'product_expiration_date' => $this->productExpirationDate,
            'product_id' => $this->productId,
            'error' => "InvalidExpirationDateException"
        ];
    }

    /**
     * Crear excepción para cuando la fecha de la oferta es menor o igual a ahora
     */
    public static function offerDateTooEarly($offerExpirationDate): self
    {
        return new self(
            "La fecha de expiración de la oferta debe ser mayor a la fecha actual",
            422,
            $offerExpirationDate
        );
    }

    /**
     * Crear excepción para cuando la fecha del producto es mayor a la de la oferta
     */
    public static function productDateAfterOfferDate($productId, $productExpirationDate, $offerExpirationDate): self
    {
        return new self(
            "La fecha de expiración del producto no puede ser posterior a la fecha de expiración de la oferta",
            422,
            $offerExpirationDate,
            $productExpirationDate,
            $productId
        );
    }

    /**
     * Crear excepción para cuando la fecha del producto está vacía
     */
    public static function productDateEmpty($productId): self
    {
        return new self(
            "El producto debe tener una fecha de expiración",
            422,
            null,
            null,
            $productId
        );
    }
}

