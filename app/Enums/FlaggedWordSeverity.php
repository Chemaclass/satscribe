<?php
declare(strict_types=1);

namespace App\Enums;

enum FlaggedWordSeverity: string
{
    case LOW = 'low';
    case MEDIUM = 'medium';
    case HIGH = 'high';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
