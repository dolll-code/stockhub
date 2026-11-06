<?php

namespace App\Enums;

enum StockInSource: string
{
    case PURCHASE = 'purchase';
    case INTERNAL = 'internal';
    case RETURN = 'return';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::PURCHASE => 'Pembelian',
            self::INTERNAL => 'Internal',
            self::RETURN => 'Retur',
            self::OTHER => 'Lainnya',
        };
    }
}
