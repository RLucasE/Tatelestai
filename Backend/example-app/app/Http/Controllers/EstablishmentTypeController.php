<?php

namespace App\Http\Controllers;

use App\DTOs\EstablishmentTypeDTO;
use App\Models\EstablishmentType;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Http\JsonResponse;

class EstablishmentTypeController extends Controller
{
    public function index(): JsonResponse
    {
        $establishmentTypes = EstablishmentType::all();

        $establishmentTypesDTO = $establishmentTypes->map(function ($type) {
            return EstablishmentTypeDTO::fromModel($type);
        });

        return response()->json([
            'success' => true,
            'data' => $establishmentTypesDTO
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:establishment_types,name',
            'description' => 'nullable|string|max:500',
        ]);

        $slug = Str::slug($request->name);

        // Verificar que el slug sea único
        $originalSlug = $slug;
        $counter = 1;
        while (EstablishmentType::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        $establishmentType = EstablishmentType::create([
            'name' => $request->name,
            'slug' => $slug,
            'description' => $request->description,
        ]);

        $dto = EstablishmentTypeDTO::fromModel($establishmentType);

        return response()->json([
            'success' => true,
            'message' => 'Tipo de establecimiento creado exitosamente',
            'data' => $dto
        ], 201);
    }

    public function show($id): JsonResponse
    {
        $establishmentType = EstablishmentType::find($id);

        if (!$establishmentType) {
            return response()->json([
                'success' => false,
                'message' => 'Tipo de establecimiento no encontrado'
            ], 404);
        }

        $dto = EstablishmentTypeDTO::fromModel($establishmentType);

        return response()->json([
            'success' => true,
            'data' => $dto
        ]);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $establishmentType = EstablishmentType::find($id);

        if (!$establishmentType) {
            return response()->json([
                'success' => false,
                'message' => 'Tipo de establecimiento no encontrado'
            ], 404);
        }

        $request->validate([
            'name' => 'required|string|max:255|unique:establishment_types,name,' . $id,
            'description' => 'nullable|string|max:500',
        ]);

        $slug = Str::slug($request->name);

        // Verificar que el slug sea único (excluyendo el actual)
        $originalSlug = $slug;
        $counter = 1;
        while (EstablishmentType::where('slug', $slug)->where('id', '!=', $id)->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        $establishmentType->update([
            'name' => $request->name,
            'slug' => $slug,
            'description' => $request->description,
        ]);

        $dto = EstablishmentTypeDTO::fromModel($establishmentType);

        return response()->json([
            'success' => true,
            'message' => 'Tipo de establecimiento actualizado exitosamente',
            'data' => $dto
        ]);
    }

    public function destroy($id): JsonResponse
    {
        $establishmentType = EstablishmentType::find($id);

        if (!$establishmentType) {
            return response()->json([
                'success' => false,
                'message' => 'Tipo de establecimiento no encontrado'
            ], 404);
        }

        $establishmentType->delete();


        return response()->json([
            'success' => true,
            'message' => 'Tipo de establecimiento eliminado exitosamente'
        ]);
    }

    public function restore($id): JsonResponse
    {
        $establishmentType = EstablishmentType::withTrashed()->find($id);

        if (!$establishmentType) {
            return response()->json([
                'success' => false,
                'message' => 'Tipo de establecimiento no encontrado'
            ], 404);
        }

        if (!$establishmentType->trashed()) {
            return response()->json([
                'success' => false,
                'message' => 'El tipo de establecimiento no está eliminado'
            ], 400);
        }

        $establishmentType->restore();

        $dto = EstablishmentTypeDTO::fromModel($establishmentType);

        return response()->json([
            'success' => true,
            'message' => 'Tipo de establecimiento restaurado exitosamente',
            'data' => $dto
        ]);
    }

    public function trashed(): JsonResponse
    {
        $trashedTypes = EstablishmentType::onlyTrashed()->get();

        $trashedTypesDTO = $trashedTypes->map(function ($type) {
            return EstablishmentTypeDTO::fromModel($type);
        });

        return response()->json([
            'success' => true,
            'data' => $trashedTypesDTO
        ]);
    }

}
