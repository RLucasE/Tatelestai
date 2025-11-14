<?php

namespace App\Enums;

enum SellState: string
{
    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case READY = 'ready';
    case PICKED_UP = 'picked_up';
    case CANCELLED = 'cancelled';
    case EXPIRED = 'expired';

    public function label(): string
    {
        return match($this) {
            self::PENDING => 'Pendiente',
            self::CONFIRMED => 'Confirmado',
            self::READY => 'Listo para retirar',
            self::PICKED_UP => 'Retirado',
            self::CANCELLED => 'Cancelado',
            self::EXPIRED => 'Expirado',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

