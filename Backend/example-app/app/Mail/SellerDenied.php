<?php

namespace App\Mail;

use App\DTOs\BasicUserDTO;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SellerDenied extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public BasicUserDTO $user)
    {
    }

    public function build(): self
    {
        return $this
            ->subject('Solicitud de vendedor denegada')
            ->view('emails.seller_denied', [
                'user' => $this->user,
            ]);
    }
}

