<?php

namespace App\Providers;

use App\Contracts\Payment\PaymentServiceInterface;
use App\Contracts\Search\SearchServiceInterface;
use App\Events\PurchaseCompleted;
use App\Events\SellerActivated;
use App\Events\SellerDeactivated;
use App\Events\SellerDenied;
use App\Listeners\SendPurchaseConfirmationEmail;
use App\Listeners\SendSellerActivatedEmail;
use App\Listeners\SendSellerDeactivatedEmail;
use App\Listeners\SendSellerDeniedEmail;
use App\Payment\Adapters\MercadoPagoPaymentAdapter;
use App\Search\Adapters\NullSearchAdapter;
use App\Search\Adapters\TypesenseSearchAdapter;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $searchDriver = config('scout.driver', 'typesense');

        $this->app->bind(SearchServiceInterface::class, match ($searchDriver) {
            'typesense' => TypesenseSearchAdapter::class,
            'null' => NullSearchAdapter::class,
            default => TypesenseSearchAdapter::class,
        });

        $this->app->bind(PaymentServiceInterface::class, MercadoPagoPaymentAdapter::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        ResetPassword::createUrlUsing(function (object $notifiable, string $token) {
            return config('app.frontend_url')."/password-reset/$token?email={$notifiable->getEmailForPasswordReset()}";
        });

        Event::listen(
            PurchaseCompleted::class,
            SendPurchaseConfirmationEmail::class
        );

        Event::listen(
            PurchaseCompleted::class,
            \App\Listeners\SendSellerNewSaleNotification::class
        );

        Event::listen(
            SellerActivated::class,
            SendSellerActivatedEmail::class
        );

        Event::listen(
            SellerDeactivated::class,
            SendSellerDeactivatedEmail::class
        );

        Event::listen(
            SellerDenied::class,
            SendSellerDeniedEmail::class
        );
    }
}
