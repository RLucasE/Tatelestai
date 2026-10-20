<?php

namespace App\Exceptions\Pack;

use App\Exceptions\ApiException;

class PackTemplateOwnershipException extends ApiException
{
    protected int $httpStatus = 403;

    protected string $errorCode = 'PACK_TEMPLATE_OWNERSHIP_ERROR';

    protected ?int $packTemplateId = null;

    protected ?int $establishmentId = null;

    public function __construct($message = 'La plantilla de pack no pertenece a tu establecimiento', $code = 403)
    {
        parent::__construct($message, $code);
    }

    /**
     * Contexto público seguro expuesto al cliente.
     *
     * @return array<string, mixed>
     */
    public function publicContext(): array
    {
        return [
            'pack_template_id' => $this->packTemplateId,
            'establishment_id' => $this->establishmentId,
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
            'pack_template_id' => $this->packTemplateId,
            'establishment_id' => $this->establishmentId,
            'error' => 'PackTemplateOwnershipException',
        ];
    }

    /**
     * Establecer datos de contexto para la excepción.
     *
     * @return $this
     */
    public function setContext(?int $packTemplateId, ?int $establishmentId)
    {
        $this->packTemplateId = $packTemplateId;
        $this->establishmentId = $establishmentId;

        return $this;
    }
}
