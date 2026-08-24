<?php

namespace App\Listeners;

use App\Events\SellerActivated as SellerActivatedEvent;
use App\Mail\SellerActivated as SellerActivatedMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

class SendSellerActivatedEmail implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(SellerActivatedEvent $event): void
    {
        $user = $event->user;

        if (!empty($user->email)) {
            Mail::to($user->email)->send(new SellerActivatedMail($user));
        }
    }
}

