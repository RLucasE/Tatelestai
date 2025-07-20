<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\FoodEstablishment;
use Illuminate\Support\Facades\Auth;

class SellerController extends Controller
{
    protected $userEstablishment;

    public function __construct()
    {
        $this->userEstablishment = FoodEstablishment::where('user_id', Auth::id())->first();
    }

    /**
     * Obtener el establecimiento del usuario autenticado
     */
    protected function getUserEstablishment()
    {
        return $this->userEstablishment;
    }

    /**
     * Verificar si el usuario tiene un establecimiento
     */
    protected function hasEstablishment()
    {
        return !is_null($this->userEstablishment);
    }

    /**
     * Respuesta de error cuando no tiene establecimiento
     */
    protected function noEstablishmentResponse()
    {
        return response()->json([
            'error' => 'No tienes un establecimiento asociado'
        ], 403);
    }

    /**
     * Verificar que los productos pertenecen al establecimiento del usuario
     */
    protected function validateProductOwnership(array $productIDs)
    {
        if (!$this->hasEstablishment()) {
            return false;
        }

        $validProductsIDs = $this->userEstablishment->products()->whereIn('id', $productIDs)->pluck(column: 'id')->toArray();

        return count($validProductsIDs) === count($productIDs);
    }

    /**
     * Respuesta de error para productos no válidos
     */
    protected function invalidProductsResponse()
    {
        return response()->json([
            'error' => 'Algunos productos no pertenecen a tu establecimiento'
        ], 403);
    }

    /**
     * Verificar que un producto específico pertenece al establecimiento
     */
    protected function validateSingleProductOwnership($productId)
    {
        if (!$this->hasEstablishment()) {
            return false;
        }

        return $this->userEstablishment
            ->products()
            ->where('id', $productId)
            ->exists();
    }

    /**
     * Obtener todos los productos del establecimiento del usuario
     */
    protected function getUserProducts()
    {
        if (!$this->hasEstablishment()) {
            return collect();
        }

        return $this->userEstablishment->products();
    }

    /**
     * Obtener todas las ofertas del establecimiento del usuario
     */
    protected function getUserOffers()
    {
        if (!$this->hasEstablishment()) {
            return collect();
        }

        return $this->userEstablishment->offers();
    }
}
