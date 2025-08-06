<?php


namespace App\Actions\Sell;

use App\Models\Sell;

class getSellerSellAction
{
    public function __construct()
    {
    }

    public function execute(int $foodEstablishmentId): array
    {
        $sells = Sell::with(['sellDetails'])->get();
        return $sells->toArray();
    }
}
