<?php

namespace App\Http\Controllers;

use App\Actions\Reviews\CreateEstablishmentReviewAction;
use App\Actions\Reviews\GetEstablishmentRatingSummaryAction;
use App\Exceptions\BusinessRuleException;
use App\Http\Requests\EstablishmentReview\StoreEstablishmentReviewRequest;
use App\Http\Resources\EstablishmentRatingSummaryResource;
use App\Http\Resources\EstablishmentReviewResource;
use App\Models\FoodEstablishment;
use App\Models\Sell;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

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
        $rating = (int) $request->validated('rating');
        $customer = Auth::user();

        $review = $this->createReviewAction->execute($sell, $customer, $rating);

        return response()->json([
            'message' => '¡Gracias por calificar tu compra!',
            'data' => (new EstablishmentReviewResource($review))->resolve(),
        ], 201);
    }

    /**
     * Retorna el resumen cuantitativo de reputación (ambas medias y distribución de estrellas).
     */
    public function showSummary(int $establishmentId): JsonResponse
    {
        $establishment = FoodEstablishment::find($establishmentId);
        if (! $establishment) {
            throw BusinessRuleException::notFound('Establecimiento no encontrado', 'ESTABLISHMENT_NOT_FOUND');
        }

        $summary = $this->getSummaryAction->execute($establishment);

        return response()->json([
            'data' => (new EstablishmentRatingSummaryResource($summary))->resolve(),
        ], 200);
    }
}
