<?php

namespace App\Enums;

enum ReportReason: string
{
    case INAPPROPRIATE = 'inappropriate';
    case FRAUD = 'fraud';
    case FALSE_INFORMATION = 'false_information';
    case SPAM = 'spam';
    case POOR_QUALITY = 'poor_quality';
    case HYGIENE_ISSUES = 'hygiene_issues';
    case MISLEADING_PRICING = 'misleading_pricing';
    case EXPIRED_PRODUCTS = 'expired_products';
    case OTHER = 'other';

    public function label(): string
    {
        return match($this) {
            self::INAPPROPRIATE => 'Inappropriate Content',
            self::FRAUD => 'Fraud',
            self::FALSE_INFORMATION => 'False Information',
            self::SPAM => 'Spam',
            self::POOR_QUALITY => 'Poor Quality',
            self::HYGIENE_ISSUES => 'Hygiene Issues',
            self::MISLEADING_PRICING => 'Misleading Pricing',
            self::EXPIRED_PRODUCTS => 'Expired Products',
            self::OTHER => 'Other',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

