<?php

namespace App\Listeners;

use App\Events\SellerDenied as SellerDeniedEvent;
use App\Mail\SellerDenied as SellerDeniedMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

class SendSellerDeniedEmail implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(SellerDeniedEvent $event): void
    {
        $user = $event->user;

        if (! empty($user->email)) {
            Mail::to($user->email)->send(new SellerDeniedMail($user));
        }
    }
}
