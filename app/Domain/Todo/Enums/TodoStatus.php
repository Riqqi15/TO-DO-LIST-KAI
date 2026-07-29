<?php

namespace App\Domain\Todo\Enums;

enum TodoStatus: string
{
    case Pending = 'pending';
    case BelumSelesai = 'belum_selesai';
    case Selesai = 'selesai';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::BelumSelesai => 'Belum Selesai',
            self::Selesai => 'Selesai',
        };
    }
}
