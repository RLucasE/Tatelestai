<?php

namespace App\Exceptions\Product;

use Exception;

class ProductOwnershipException extends Exception
{
    protected $productIds;
    protected $establishmentId;

    public function __construct($message = "Uno o más productos no pertenecen a tu establecimiento", $code = 403)
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
            'product_ids' => $this->productIds,
            'establishment_id' => $this->establishmentId,
            'error' => "ProductOwnershipException"
        ];
    }

    /**
     * Establecer datos de contexto para la excepción.
     *
     * @param array $productIds IDs de los productos que no pertenecen al establecimiento
     * @param int|null $establishmentId ID del establecimiento del usuario
     * @return $this
     */
    public function setContext(array $productIds, ?int $establishmentId)
    {
        $this->productIds = $productIds;
        $this->establishmentId = $establishmentId;
        return $this;
    }
}