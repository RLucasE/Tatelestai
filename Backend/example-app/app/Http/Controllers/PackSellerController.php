<?php

namespace App\Http\Controllers;

use App\Actions\Offers\GetUserEstablishmentAction;
use App\Actions\Offers\ValidateOfferOwnershipAction;
use App\Actions\Packs\PublishPackAction;
use App\Actions\Packs\UpdatePackAction;
use App\DTOs\PackDTO;
use App\Enums\OfferState;
use App\Exceptions\ApiException;
use App\Exceptions\BusinessRuleException;
use App\Http\Requests\StorePackRequest;
use App\Http\Requests\UpdatePackRequest;
use App\Http\Resources\PackOfferResource;
use App\Models\Offer;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
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
        } catch (ApiException|ValidationException|QueryException $exception) {
            throw $exception;
        } catch (Exception $exception) {
            $status = (is_int($exception->getCode()) && $exception->getCode() >= 400 && $exception->getCode() < 600)
                ? $exception->getCode()
                : 422;

            throw new BusinessRuleException($exception->getMessage(), $status, 'PACK_PUBLISH_FAILED');
        }
    }

    public function index(): JsonResponse
    {
        $establishment = $this->getUserEstablishmentAction->execute();

        if (! $establishment) {
            throw BusinessRuleException::notFound('No se encontró un establecimiento asociado al usuario', 'ESTABLISHMENT_NOT_FOUND');
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
            throw BusinessRuleException::notFound('Pack no encontrado', 'PACK_NOT_FOUND');
        }

        if (! $this->validateOfferOwnership->execute($pack, Auth::user())) {
            throw BusinessRuleException::forbidden('No tienes permiso para acceder a este pack', 'PACK_OWNERSHIP_ERROR');
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
            throw BusinessRuleException::notFound('Pack no encontrado', 'PACK_NOT_FOUND');
        }

        if (! $this->validateOfferOwnership->execute($pack, Auth::user())) {
            throw BusinessRuleException::forbidden('No tienes permiso para modificar este pack', 'PACK_OWNERSHIP_ERROR');
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
        } catch (ApiException|ValidationException|QueryException $exception) {
            throw $exception;
        } catch (InvalidArgumentException|Exception $exception) {
            throw BusinessRuleException::unprocessable($exception->getMessage(), 'PACK_UPDATE_FAILED');
        }
    }

    public function destroy(int $id): JsonResponse
    {
        $pack = Offer::find($id);

        if (! $pack) {
            throw BusinessRuleException::notFound('Pack no encontrado', 'PACK_NOT_FOUND');
        }

        if (! $this->validateOfferOwnership->execute($pack, Auth::user())) {
            throw BusinessRuleException::forbidden('No tienes permiso para eliminar este pack', 'PACK_OWNERSHIP_ERROR');
        }

        $pack->state = OfferState::INACTIVE->value;
        $pack->save();

        return response()->json([
            'message' => 'Pack dado de baja correctamente',
        ], 200);
    }
}
