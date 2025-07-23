<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Offer;
use Illuminate\Http\Request;


class OfferCustomerController extends Controller
{
    //
    public function index(Request $request)
    {
        return Offer::with('products')
            ->where("expiration_datetime", ">=", now(null))
            ->get();
    }
}
