<?php

namespace App\Mail;

use App\DTOs\BasicUserDTO;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SellerActivated extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public BasicUserDTO $user)
    {
    }

    public function build(): self
    {
        return $this
            ->subject('Tu cuenta de vendedor ha sido activada')
            ->view('emails.seller_activated', [
                'user' => $this->user,
            ]);
    }
}
