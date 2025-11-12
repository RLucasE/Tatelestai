<?php

namespace App\Mail;

use App\Models\Sell;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PurchaseConfirmation extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Sell $sell)
    {
    }

    public function build(): self
    {
        return $this
            ->subject('Confirmación de tu compra - Código de retiro')
            ->view('emails.purchase_confirmation', [
                'sell' => $this->sell,
            ]);
    }
}

