<?php

namespace App\Listeners;

use App\Events\SellerDeactivated as SellerDeactivatedEvent;
use App\Mail\SellerDeactivated as SellerDeactivatedMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

class SendSellerDeactivatedEmail implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(SellerDeactivatedEvent $event): void
    {
        $user = $event->user;

        if (! empty($user->email)) {
            Mail::to($user->email)->send(new SellerDeactivatedMail($user));
        }
    }
}
