<?php

namespace App\Events;

use App\DTOs\BasicUserDTO;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SellerDenied
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(public BasicUserDTO $user)
    {
    }
}
