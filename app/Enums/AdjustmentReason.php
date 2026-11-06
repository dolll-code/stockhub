<?php

namespace App\Enums;

enum AdjustmentReason: string
{
    case DAMAGED = 'damaged';
    case LOST = 'lost';
    case RECORDING_ERROR = 'recording_error';
    case COUNT_ERROR = 'count_error';
    case EXPIRED = 'expired';
    case FOUND = 'found';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::DAMAGED => 'Barang Rusak',
            self::LOST => 'Barang Hilang',
            self::RECORDING_ERROR => 'Kesalahan Pencatatan',
            self::COUNT_ERROR => 'Kesalahan Perhitungan Fisik',
            self::EXPIRED => 'Kedaluwarsa',
            self::FOUND => 'Barang Ditemukan Kembali',
            self::OTHER => 'Alasan Lainnya',
        };
    }
}
