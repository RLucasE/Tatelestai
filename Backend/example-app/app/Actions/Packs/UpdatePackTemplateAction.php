<?php

namespace App\Actions\Packs;

use App\DTOs\PackTemplateDTO;
use App\Models\PackTemplate;

class UpdatePackTemplateAction
{
    /**
     * Actualiza los campos informados de una plantilla de pack.
     */
    public function execute(PackTemplate $template, PackTemplateDTO $dto): PackTemplate
    {
        $template->update($dto->toAttributes());

        return $template->refresh();
    }
}
