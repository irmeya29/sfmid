<?php

namespace App\Enums;

use App\Enums\Concerns\HasEnumOptions;

enum ClientType: string
{
    use HasEnumOptions;

    case Mine = 'mine';
    case Btp = 'btp';
    case Industry = 'industry';
    case Agriculture = 'agriculture';
    case Individual = 'individual';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Mine => 'Mine',
            self::Btp => 'BTP',
            self::Industry => 'Industrie',
            self::Agriculture => 'Agriculture',
            self::Individual => 'Particulier',
            self::Other => 'Autre',
        };
    }
}
