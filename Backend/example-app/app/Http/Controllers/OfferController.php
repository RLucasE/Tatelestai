<?php

namespace App\Http\Controllers;

use App\Models\Offer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\FoodEstablishment;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Contracts\Database\Eloquent\Builder;

class OfferController extends Controller
{
    //
    protected function getOfferValidationRules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'expiration_date' => 'required|date',
            'products' => 'required|array|min:1',
            'products.*.id' => 'required|integer|exists:products,id',
            'products.*.quantity' => 'required|integer|min:1',
            'products.*.price' => 'required|numeric|min:1'
        ];
    }

    protected function getUserEstablishment(): ?FoodEstablishment
    {
        return FoodEstablishment::where('user_id', Auth::id())->first();
    }

    protected function validateProductOwnership(array $productIds): bool
    {
        $establishment = $this->getUserEstablishment();

        if (!$establishment) {
            return false;
        }

        $validProductsCount = Product::where('food_establishment_id', $establishment->id)
            ->whereIn('id', $productIds)
            ->count();

        return $validProductsCount === count($productIds);
    }

    protected function offerBelongTo(Offer $offer, User $user)
    {
        $establishment = $this->getUserEstablishment();

        if (!$establishment || !$user) {
            return false;
        }

        if ($offer->food_establishment_id !== $establishment->id) {
            return false;
        }

        return true;
    }

    protected function createOfferWithProducts(Request $request): Offer
    {
        /**
         * @var FoodEstablishment $establishment
         */
        $establishment = $this->getUserEstablishment();

        if (!$establishment) {
            throw new \Exception('No se encontró establecimiento asociado al usuario');
        }

        $offer = Offer::create([
            'title' => $request->string('title'),
            'description' => $request->string('description'),
            'expiration_date' => $request->date('expiration_date')->toDateString(),
            'time' => $request->date('expiration_date')->toTimeString(),
            'expiration_datetime' => $request->date('expiration_date')->toDateTimeString(),
            'food_establishment_id' => $establishment->getAttribute('id'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->attachProductsToOffer($offer, $request->array('products'));

        return $offer;
    }


    protected function productsToProductsIDs(array $products): array
    {
        return array_map(function ($product) {
            return $product['id'];
        }, $products);
    }

    protected function attachProductsToOffer(Offer $offer, array $products): void
    {
        $productOfferData = array_map(function ($product) use ($offer) {
            return [
                'product_id' => $product['id'],
                'quantity' => $product['quantity'],
                'price' => $product['price'],
                'offer_id' => $offer->id,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }, $products);

        DB::table('product_offers')->insert($productOfferData);
    }

    protected function invalidProductsResponse()
    {
        return response()->json([
            'message' => 'Uno o más productos no pertenecen a tu establecimiento'
        ], 403);
    }

    protected function userOffers(int $userID)
    {
        $establishment = $this->getUserEstablishment();
        return Offer::where('food_establishment_id', $establishment->id)->orderBy('expiration_datetime', 'desc')->get();
    }

    protected function getOffer($offerID): null|Offer
    {
        return Offer::find($offerID);
    }

    protected function getFullOffer(Offer $offer): null | Offer
    {
        $offer = Offer::with(['products' => function ($query) {
            $query->select('products.id', 'products.name', 'products.description')
                ->withPivot('price', 'quantity');
        }])->find($offer->id);

        return $offer;
    }

    // public function store(Request $request)
    // {
    //     $request->validate([
    //         'title' => 'required|string|max:255',
    //         'description' => 'required|string',
    //         'expiration_date' => 'required|date',
    //         'products' => 'required|array|min:1',
    //         'products.*.id' => 'required|integer|exists:products,id',
    //         'products.*.quantity' => 'required|integer|min:1',
    //         'products.*.price' => 'required|numeric|min:1'
    //     ]);

    //     $products = $request->array('products');
    //     $productIDs = array_map(function ($product) {
    //         return $product['id'];
    //     }, $products);

    //     if ($this->validateProductOwnership($productIDs)) {
    //         try {
    //             $offer = null;
    //             $offer = Offer::create([
    //                 'title' => $request->string('title'),
    //                 'description' => $request->string('description'),
    //                 'expiration_date' => $request->date('expiration_date')->toDateString(),
    //                 'time' => $request->date('expiration_date')->toTimeString(),
    //                 'food_establishment_id' => $this->userEstablishment->id,
    //                 'created_at' => now(),
    //                 'updated_at' => now(),
    //             ]);

    //             $products = array_map(function ($product) use ($offer) {
    //                 return [
    //                     'product_id' => $product['id'],
    //                     'quantity' => $product['quantity'],
    //                     'price' => $product['price'],
    //                     'offer_id' => $offer->id,
    //                     'created_at' => now(),
    //                     'updated_at' => now(),
    //                 ];
    //             }, $products);
    //             DB::table('product_offers')->insert($products);
    //         } catch (\Exception $e) {
    //             return response()->json([
    //                 'mesagge' => 'Error al crear la oferta',
    //                 'error' => $e->getMessage()
    //             ], 500);
    //         }
    //     }
    // }
}
