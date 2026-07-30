<?php

namespace App\Domain\Todo\Enums;

enum TodoStatus: string
{
    case BelumDikerjakan = 'belum_dikerjakan';
    case SedangDikerjakan = 'sedang_dikerjakan';
    case Selesai = 'selesai';

    public function label(): string
    {
        return match ($this) {
            self::BelumDikerjakan => 'Belum Dikerjakan',
            self::SedangDikerjakan => 'Sedang Dikerjakan',
            self::Selesai => 'Selesai',
        };
    }
}
