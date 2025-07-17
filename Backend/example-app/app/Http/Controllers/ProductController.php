<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\FoodEstablishment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string'
        ]);

        // Obtener el establecimiento del vendedor autenticado
        $establishment = FoodEstablishment::where('user_id', Auth::id())->first();

        if (!$establishment) {
            return response()->json([
                'message' => 'No se encontró un establecimiento asociado a este usuario'
            ], 404);
        }

        try {
            $product = new Product();
            $product->name = $request->name;
            $product->description = $request->description;
            $product->food_establishment_id = $establishment->id;
            $product->save();

            return response()->json([
                'message' => 'Producto creado exitosamente',
                'product' => $product
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al crear el producto',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show()
    {
        try {
            $establishment = FoodEstablishment::where('user_id', Auth::id())->first();

            if (!$establishment) {
                return response()->json([
                    'message' => 'No se encontró el establecimiento del vendedor'
                ], 404);
            }

            $products = Product::where('food_establishment_id', $establishment->id)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'products' => $products
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener los productos',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $product = Product::find($id);

            $establishment = FoodEstablishment::where('user_id', Auth::id())->first();

            if (!$establishment) {
                return response()->json([
                    'message' => 'No se encontró el establecimiento del vendedor'
                ], 404);
            }

            if (!$product || $establishment->id !== $product->food_establishment_id) {
                return response()->json([
                    'message' => 'Producto no encontrado'
                ], 404);
            }

            $product->update(
                [
                    'name' => $request->name,
                    'description' => $request->description
                ]
            );
            $product->save();

            return response()->json([
                'message' => 'Producto actualizado exitosamente',
                'product' => $product
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener el producto',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        return response()->json([
            'message' => 'No se ah podido eliminar el producto'
        ]);
    }
}
