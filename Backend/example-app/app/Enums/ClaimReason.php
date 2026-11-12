<?php

namespace App\Enums;

enum ClaimReason: string
{
    case SPOILED_FOOD = 'spoiled_food';
    case NOT_AS_DESCRIBED = 'not_as_described';
    case UNDECLARED_ALLERGEN = 'undeclared_allergen';
    case MISSING_ITEMS = 'missing_items';
    case PICKUP_ISSUE = 'pickup_issue';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::SPOILED_FOOD => 'Comida en mal estado o vencida',
            self::NOT_AS_DESCRIBED => 'Contenido distinto a lo publicado',
            self::UNDECLARED_ALLERGEN => 'Contiene un alérgeno declarado como ausente',
            self::MISSING_ITEMS => 'Faltan productos o porciones del pack',
            self::PICKUP_ISSUE => 'Problema en el retiro o entrega',
            self::OTHER => 'Otro motivo justificado',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
