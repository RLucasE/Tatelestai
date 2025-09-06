<?php

namespace App\Actions\Offers;

use App\Models\Offer;
use Illuminate\Database\Eloquent\Collection;
use Ramsey\Uuid\Type\Integer;

class GetUserOffersAction
{
    private GetUserEstablishmentAction $getUserEstablishmentAction;

    public function __construct(GetUserEstablishmentAction $getUserEstablishmentAction)
    {
        $this->getUserEstablishmentAction = $getUserEstablishmentAction;
    }

    /**
     * Obtiene todas las ofertas del usuario autenticado
     *
     * @return Collection
     */
    public function execute(int $paginationQuantity = 20): Collection
    {
        $establishment = $this->getUserEstablishmentAction->execute();

        if (!$establishment) {
            return new Collection();
        }

        return Offer::where('food_establishment_id', $establishment->id)
            ->orderBy('expiration_datetime', 'desc')
            ->limit($paginationQuantity)
            ->get();
    }
}
