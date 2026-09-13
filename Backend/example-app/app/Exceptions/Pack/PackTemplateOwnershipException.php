<?php

namespace App\Exceptions\Pack;

use Exception;

class PackTemplateOwnershipException extends Exception
{
    protected ?int $packTemplateId = null;

    protected ?int $establishmentId = null;

    public function __construct($message = 'La plantilla de pack no pertenece a tu establecimiento', $code = 403)
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
