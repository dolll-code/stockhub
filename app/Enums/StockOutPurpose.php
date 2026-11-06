<?php

namespace App\Enums;

enum StockOutPurpose: string
{
    case USAGE = 'usage';
    case SALE = 'sale';
    case SHIPMENT = 'shipment';
    case TRANSFER_TO_PARTY = 'transfer_to_party';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::USAGE => 'Pemakaian Internal',
            self::SALE => 'Penjualan',
            self::SHIPMENT => 'Pengiriman',
            self::TRANSFER_TO_PARTY => 'Pemindahan ke Pihak Lain',
            self::OTHER => 'Lainnya',
        };
    }
}
