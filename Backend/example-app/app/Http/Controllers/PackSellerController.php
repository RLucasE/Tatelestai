<?php

namespace App\Http\Controllers;

use App\Actions\Offers\GetUserEstablishmentAction;
use App\Actions\Offers\ValidateOfferOwnershipAction;
use App\Actions\Packs\PublishPackAction;
use App\Actions\Packs\UpdatePackAction;
use App\DTOs\PackDTO;
use App\Enums\OfferState;
use App\Exceptions\Pack\PackTemplateOwnershipException;
use App\Http\Requests\StorePackRequest;
use App\Http\Requests\UpdatePackRequest;
use App\Http\Resources\PackOfferResource;
use App\Models\Offer;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;

class PackSellerController extends Controller
{
    public function __construct(
        private readonly PublishPackAction $publishPackAction,
        private readonly UpdatePackAction $updatePackAction,
        private readonly ValidateOfferOwnershipAction $validateOfferOwnership,
        private readonly GetUserEstablishmentAction $getUserEstablishmentAction,
    ) {}

    public function store(StorePackRequest $request): JsonResponse
    {
        try {
            $pack = $this->publishPackAction->execute(
                PackDTO::fromRequest($request)
            );

            return response()->json([
                'message' => 'Pack publicado exitosamente',
                'data' => new PackOfferResource($pack),
            ], 201);
        } catch (PackTemplateOwnershipException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
                'context' => $exception->context(),
            ], $exception->getCode());
        } catch (Exception $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], $exception->getCode() ?: 422);
        }
    }

    public function index(): JsonResponse
    {
        $establishment = $this->getUserEstablishmentAction->execute();

        if (! $establishment) {
            return response()->json([
                'message' => 'No se encontró un establecimiento asociado al usuario',
            ], 404);
        }

        $packs = Offer::where('food_establishment_id', $establishment->id)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        return response()->json([
            'message' => 'Packs obtenidos exitosamente',
            'data' => PackOfferResource::collection($packs),
        ], 200);
    }

    public function show(int $id): JsonResponse
    {
        $pack = Offer::find($id);

        if (! $pack) {
            return response()->json([
                'message' => 'Pack no encontrado',
            ], 404);
        }

        if (! $this->validateOfferOwnership->execute($pack, Auth::user())) {
            return response()->json([
                'message' => 'No tienes permiso para acceder a este pack',
            ], 403);
        }

        return response()->json([
            'message' => 'Pack obtenido exitosamente',
            'data' => new PackOfferResource($pack),
        ], 200);
    }

    public function update(UpdatePackRequest $request, int $id): JsonResponse
    {
        $pack = Offer::find($id);

        if (! $pack) {
            return response()->json([
                'message' => 'Pack no encontrado',
            ], 404);
        }

        if (! $this->validateOfferOwnership->execute($pack, Auth::user())) {
            return response()->json([
                'message' => 'No tienes permiso para modificar este pack',
            ], 403);
        }

        try {
            $pack = $this->updatePackAction->execute(
                $pack,
                PackDTO::fromRequest($request)
            );

            return response()->json([
                'message' => 'Pack actualizado exitosamente',
                'data' => new PackOfferResource($pack),
            ], 200);
        } catch (InvalidArgumentException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        } catch (Exception $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        $pack = Offer::find($id);

        if (! $pack) {
            return response()->json([
                'message' => 'Pack no encontrado',
            ], 404);
        }

        if (! $this->validateOfferOwnership->execute($pack, Auth::user())) {
            return response()->json([
                'message' => 'No tienes permiso para eliminar este pack',
            ], 403);
        }

        $pack->state = OfferState::INACTIVE->value;
        $pack->save();

        return response()->json([
            'message' => 'Pack dado de baja correctamente',
        ], 200);
    }
}
