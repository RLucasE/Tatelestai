<?php

namespace App\Http\Controllers;

use App\Http\Controllers\SellerController;
use App\Models\Offer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use function Laravel\Prompts\error;

class OfferController extends SellerController
{
    //
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'expiration_date' => 'required|date',
            'products' => 'required|array|min:1',
            'products.*.id' => 'required|integer|exists:products,id',
            'products.*.quantity' => 'required|integer|min:1',
            'products.*.price' => 'required|numeric|min:1'
        ]);

        $products = $request->array('products');
        $productIDs = array_map(function ($product) {
            return $product['id'];
        }, $products);

        if ($this->validateProductOwnership($productIDs)) {
            try {
                $offer = null;
                $offer = Offer::create([
                    'title' => $request->string('title'),
                    'description' => $request->string('description'),
                    'expiration_date' => $request->date('expiration_date')->toDateString(),
                    'food_establishment_id' => $this->userEstablishment->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $products = array_map(function ($product) use ($offer) {
                    return [
                        'product_id' => $product['id'],
                        'quantity' => $product['quantity'],
                        'price' => $product['price'],
                        'offer_id' => $offer->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }, $products);
                DB::table('product_offers')->insert($products);
            } catch (\Exception $e) {
                return response()->json([
                    'mesagge' => 'Error al crear la oferta',
                    'error' => $e->getMessage()
                ], 500);
            }
        }
    }
}
