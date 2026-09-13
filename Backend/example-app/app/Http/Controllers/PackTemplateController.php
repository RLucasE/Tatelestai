<?php

namespace App\Http\Controllers;

use App\Actions\Offers\GetUserEstablishmentAction;
use App\Actions\Packs\CreatePackTemplateAction;
use App\Actions\Packs\UpdatePackTemplateAction;
use App\Actions\Packs\ValidatePackTemplateOwnershipAction;
use App\DTOs\PackTemplateDTO;
use App\Http\Requests\StorePackTemplateRequest;
use App\Http\Requests\UpdatePackTemplateRequest;
use App\Http\Resources\PackTemplateResource;
use App\Models\PackTemplate;
use Exception;
use Illuminate\Http\JsonResponse;

class PackTemplateController extends Controller
{
    public function __construct(
        private readonly CreatePackTemplateAction $createPackTemplateAction,
        private readonly UpdatePackTemplateAction $updatePackTemplateAction,
        private readonly ValidatePackTemplateOwnershipAction $validatePackTemplateOwnership,
        private readonly GetUserEstablishmentAction $getUserEstablishmentAction,
    ) {}

    public function index(): JsonResponse
    {
        $establishment = $this->getUserEstablishmentAction->execute();

        if (! $establishment) {
            return response()->json([
                'message' => 'No se encontró un establecimiento asociado al usuario',
            ], 404);
        }

        $templates = PackTemplate::where('food_establishment_id', $establishment->id)
            ->withCount('offers')
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'message' => 'Plantillas de packs obtenidas exitosamente',
            'data' => PackTemplateResource::collection($templates),
        ], 200);
    }

    public function store(StorePackTemplateRequest $request): JsonResponse
    {
        try {
            $template = $this->createPackTemplateAction->execute(
                PackTemplateDTO::fromRequest($request)
            );

            return response()->json([
                'message' => 'Plantilla de pack creada exitosamente',
                'data' => new PackTemplateResource($template),
            ], 201);
        } catch (Exception $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], $exception->getCode() ?: 422);
        }
    }

    public function show(int $id): JsonResponse
    {
        $template = PackTemplate::withCount('offers')->find($id);

        if (! $template) {
            return response()->json([
                'message' => 'Plantilla de pack no encontrada',
            ], 404);
        }

        if (! $this->validatePackTemplateOwnership->execute($template)) {
            return response()->json([
                'message' => 'No tienes permiso para acceder a esta plantilla',
            ], 403);
        }

        return response()->json([
            'message' => 'Plantilla de pack obtenida exitosamente',
            'data' => new PackTemplateResource($template),
        ], 200);
    }

    public function update(UpdatePackTemplateRequest $request, int $id): JsonResponse
    {
        $template = PackTemplate::find($id);

        if (! $template) {
            return response()->json([
                'message' => 'Plantilla de pack no encontrada',
            ], 404);
        }

        if (! $this->validatePackTemplateOwnership->execute($template)) {
            return response()->json([
                'message' => 'No tienes permiso para modificar esta plantilla',
            ], 403);
        }

        try {
            $template = $this->updatePackTemplateAction->execute(
                $template,
                PackTemplateDTO::fromRequest($request)
            );

            return response()->json([
                'message' => 'Plantilla de pack actualizada exitosamente',
                'data' => new PackTemplateResource($template),
            ], 200);
        } catch (Exception $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        $template = PackTemplate::find($id);

        if (! $template) {
            return response()->json([
                'message' => 'Plantilla de pack no encontrada',
            ], 404);
        }

        if (! $this->validatePackTemplateOwnership->execute($template)) {
            return response()->json([
                'message' => 'No tienes permiso para eliminar esta plantilla',
            ], 403);
        }

        $template->delete();

        return response()->json([
            'message' => 'Plantilla de pack eliminada correctamente',
        ], 200);
    }
}
