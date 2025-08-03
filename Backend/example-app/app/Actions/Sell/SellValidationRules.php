<?php

namespace App\Actions\Sell;

/**
 *
 */
trait SellValidationRules
{

    /**
     * @return string[]
     */
    protected function sellsRule(): array
    {
        return [
            'offers' => 'required|array',
            'food_establishment_id' => 'required|exists:food_establishments,id',
            'offers.*.id' => 'required|exists:offers,id',
            'offers.*.quantity' => 'required|integer|min:1',
        ];
    }
}
