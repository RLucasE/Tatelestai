<?php

namespace App\Enums;

enum ClaimStatus: string
{
    case PENDING = 'pending';
    case UNDER_REVIEW = 'under_review';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pendiente de revisión',
            self::UNDER_REVIEW => 'En análisis',
            self::APPROVED => 'Aprobado',
            self::REJECTED => 'Rechazado',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
