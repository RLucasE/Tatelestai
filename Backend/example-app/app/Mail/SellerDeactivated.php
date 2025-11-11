<?php

namespace App\Mail;

use App\DTOs\BasicUserDTO;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SellerDeactivated extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public BasicUserDTO $user)
    {
    }

    public function build(): self
    {
        return $this
            ->subject('Tu cuenta de vendedor ha sido desactivada')
            ->view('emails.seller_deactivated', [
                'user' => $this->user,
            ]);
    }
}

