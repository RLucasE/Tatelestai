<?php

namespace App\Actions\Sell;

use App\Models\Sell;
use Exception;

class ValidateMaxPickupDatetimeAction
{
    public function execute(Sell $sell): Sell
    {
        if ($sell->max_pickup_datetime && $sell->max_pickup_datetime < now()) {
            throw new Exception('El tiempo para recoger esta venta ha expirado', 410);
        }

        return $sell;
    }
}

