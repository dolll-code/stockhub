<?php

namespace App\Enums;

enum StockStatus: string
{
    case HABIS = 'habis';
    case MINIMUM = 'minimum';
    case AMAN = 'aman';

    public function label(): string
    {
        return match ($this) {
            self::HABIS => 'Habis',
            self::MINIMUM => 'Minimum',
            self::AMAN => 'Aman',
        };
    }

    public static function fromStock(float|int|string $currentStock, float|int|string $minStock): self
    {
        $current = (float) $currentStock;
        $min = (float) $minStock;

        if ($current <= 0) {
            return self::HABIS;
        }

        if ($current <= $min) {
            return self::MINIMUM;
        }

        return self::AMAN;
    }
}
