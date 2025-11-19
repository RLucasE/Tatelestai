<?php

namespace App\Enums;

enum ReportStatus: string
{
    case PENDING = 'pending';
    case REVIEWING = 'reviewing';
    case RESOLVED = 'resolved';
    case DISMISSED = 'dismissed';

    public function label(): string
    {
        return match($this) {
            self::PENDING => 'Pending',
            self::REVIEWING => 'Reviewing',
            self::RESOLVED => 'Resolved',
            self::DISMISSED => 'Dismissed',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

