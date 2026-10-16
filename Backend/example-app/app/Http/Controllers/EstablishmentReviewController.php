<?php

namespace App\Http\Controllers;

use App\Actions\Reviews\CreateEstablishmentReviewAction;
use App\Actions\Reviews\GetEstablishmentRatingSummaryAction;
use App\Exceptions\Reviews\ReviewNotAllowedException;
use App\Http\Requests\EstablishmentReview\StoreEstablishmentReviewRequest;
use App\Http\Resources\EstablishmentRatingSummaryResource;
use App\Http\Resources\EstablishmentReviewResource;
use App\Models\FoodEstablishment;
use App\Models\Sell;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Throwable;

class EstablishmentReviewController extends Controller
{
    public function __construct(
        private readonly CreateEstablishmentReviewAction $createReviewAction,
        private readonly GetEstablishmentRatingSummaryAction $getSummaryAction
    ) {}

    /**
     * Registra la calificación por estrellas de una compra retirada.
     */
    public function store(StoreEstablishmentReviewRequest $request, Sell $sell): JsonResponse
    {
        try {
            $rating = (int) $request->validated('rating');
            $customer = Auth::user();

            $review = $this->createReviewAction->execute($sell, $customer, $rating);

            return response()->json([
                'message' => '¡Gracias por calificar tu compra!',
                'data' => (new EstablishmentReviewResource($review))->resolve(),
            ], 201);
        } catch (ReviewNotAllowedException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], $exception->getCode() ?: 422);
        } catch (Throwable) {
            return response()->json([
                'message' => 'Ocurrió un error inesperado al registrar la calificación.',
            ], 500);
        }
    }

    /**
     * Retorna el resumen cuantitativo de reputación (ambas medias y distribución de estrellas).
     */
    public function showSummary(int $establishmentId): JsonResponse
    {
        $establishment = FoodEstablishment::find($establishmentId);
        if (! $establishment) {
            return response()->json([
                'message' => 'Establecimiento no encontrado',
            ], 404);
        }

        $summary = $this->getSummaryAction->execute($establishment);

        return response()->json([
            'data' => (new EstablishmentRatingSummaryResource($summary))->resolve(),
        ], 200);
    }
}
