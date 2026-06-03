<?php

namespace App\Enums;

use App\Enums\Concerns\HasEnumOptions;

enum ProductStatus: string
{
    use HasEnumOptions;

    case Active = 'active';
    case Inactive = 'inactive';
    case Obsolete = 'obsolete';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Actif',
            self::Inactive => 'Inactif',
            self::Obsolete => 'Obsolète',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Active => 'bg-green-100 text-green-800',
            self::Inactive => 'bg-gray-100 text-gray-800',
            self::Obsolete => 'bg-orange-100 text-orange-800',
        };
    }
}
