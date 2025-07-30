<?php

namespace App\Http\Controllers;

use App\Actions\Offers\Customer\GetCustomerOffersAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OfferCustomerController extends Controller
{
    public function __construct(
        private GetCustomerOffersAction $getCustomerOffers
    ) {}

    public function index(): JsonResponse
    {
        $offers = $this->getCustomerOffers->execute();
        return response()->json($offers);
    }
}
