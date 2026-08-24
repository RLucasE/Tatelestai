<?php

namespace App\Listeners;

use App\Events\PurchaseCompleted;
use App\Mail\PurchaseConfirmation;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

class SendPurchaseConfirmationEmail implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(PurchaseCompleted $event): void
    {
        $sell = $event->sell;

        if (!$sell->relationLoaded('customer')) {
            $sell->load(['customer', 'foodEstablishment', 'sellDetails']);
        }

        if ($sell->customer && $sell->customer->email) {
            //Mail::to($sell->customer->email)->send(new PurchaseConfirmation($sell)); 
            Mail::to("lucascabjnmro2@gmail.com")->send(new PurchaseConfirmation($sell)); 
        }
    }
}

