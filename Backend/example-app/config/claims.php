<?php

return [
    // Ventana máxima en días corridos para iniciar un reclamo (desde el retiro o fin de franja)
    'claim_window_days' => (int) env('CLAIMS_WINDOW_DAYS', 14),

    // Cuotas anti-abuso configurables por usuario
    'max_open_claims_per_user' => (int) env('CLAIMS_MAX_OPEN_PER_USER', 2),
    'max_claims_per_30_days' => (int) env('CLAIMS_MAX_PER_30_DAYS', 3),
    'max_unpicked_claims_per_60_days' => (int) env('CLAIMS_MAX_UNPICKED_PER_60_DAYS', 1),

    // Días de bloqueo temporal tras un reclamo marcado como abusivo por un Administrador
    'abuse_cooldown_days' => (int) env('CLAIMS_ABUSE_COOLDOWN_DAYS', 30),

    // Límites de archivos adjuntos de evidencia
    'max_evidences_per_claim' => (int) env('CLAIMS_MAX_EVIDENCES', 5),
    'max_evidence_size_kb' => (int) env('CLAIMS_MAX_EVIDENCE_SIZE_KB', 5120),
];
