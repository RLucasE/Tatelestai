<?php

namespace App\Actions\Cart;

use App\Http\Controllers\CartController;
use App\Models\User;

class AssignFirstCartAction
{
    protected $cartController;

    public function __construct(CartController $cartController)
    {
        $this->cartController = $cartController;
    }

    public function handle(User $user)
    {
        $cart = $this->cartController->getLastActiveCart($user);
        if (! $cart) {
            $cart = $this->cartController->newCart($user);
        }

        return $cart;
    }
}
