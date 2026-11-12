<?php

namespace App\Http\Controllers;

use App\Exceptions\BusinessRuleException;
use App\Models\PurchaseClaim;
use App\Models\PurchaseClaimEvidence;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PurchaseClaimEvidenceController extends Controller
{
    /**
     * Sirve de forma segura un archivo privado de evidencia fotográfica validando propiedad o rol admin.
     */
    public function show(Request $request, PurchaseClaim $claim, PurchaseClaimEvidence $evidence): BinaryFileResponse
    {
        if ((int) $evidence->purchase_claim_id !== (int) $claim->id) {
            throw BusinessRuleException::notFound('La evidencia no pertenece a este reclamo.', 'EVIDENCE_NOT_FOUND');
        }

        $user = $request->user();
        if (! $user || (! $user->hasRole('admin') && (int) $claim->customer_id !== (int) $user->id)) {
            throw BusinessRuleException::forbidden('No tienes permiso para acceder a esta evidencia.', 'FORBIDDEN_EVIDENCE_ACCESS');
        }

        if (! Storage::disk('private')->exists($evidence->file_path)) {
            throw BusinessRuleException::notFound('El archivo de evidencia no fue encontrado.', 'EVIDENCE_FILE_NOT_FOUND');
        }

        $safeName = basename((string) ($evidence->original_name ?: 'evidencia.jpg'));
        $safeName = str_replace(['"', '\\', "\r", "\n"], '', $safeName) ?: 'evidencia.jpg';

        return response()->file(Storage::disk('private')->path($evidence->file_path), [
            'Content-Type' => $evidence->mime_type ?: 'image/jpeg',
            'Content-Disposition' => 'inline; filename="'.$safeName.'"',
        ]);
    }
}
