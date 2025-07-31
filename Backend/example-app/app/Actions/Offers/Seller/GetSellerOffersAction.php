<?php

namespace App\Actions\Offers\Seller;

use App\Actions\Offers\GetUserOffersAction;
use Illuminate\Database\Eloquent\Collection;

class GetSellerOffersAction
{
    private GetUserOffersAction $getUserOffersAction;

    public function __construct(GetUserOffersAction $getUserOffersAction)
    {
        $this->getUserOffersAction = $getUserOffersAction;
    }

    /**
     * Obtiene todas las ofertas del vendedor
     *
     * @return Collection
     */
    public function execute(): Collection
    {
        return $this->getUserOffersAction->execute();
    }
}