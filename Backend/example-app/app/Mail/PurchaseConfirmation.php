<?php

namespace App\Mail;

use App\Models\Sell;
use chillerlan\QRCode\Output\QRGdImagePNG;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PurchaseConfirmation extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Sell $sell) {}

    public function build(): self
    {
        $qrBinary = null;
        try {
            $payload = "TAT:{$this->sell->pickup_code}:{$this->sell->id}";
            $options = new QROptions([
                'outputInterface' => QRGdImagePNG::class,
                'outputBase64' => false,
                'scale' => 6,
            ]);
            $qrBinary = (new QRCode($options))->render($payload);
        } catch (\Throwable) {
            // Si falla la generación gráfica por cualquier motivo, el correo mantiene el código en texto
        }

        return $this
            ->subject('Confirmación de tu compra - Código de retiro')
            ->view('emails.purchase_confirmation', [
                'sell' => $this->sell,
                'qrBinary' => $qrBinary,
            ]);
    }
}
