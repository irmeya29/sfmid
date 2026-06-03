<?php

namespace App\Enums;

use App\Enums\Concerns\HasEnumOptions;

enum ClientStatus: string
{
    use HasEnumOptions;

    case Active = 'active';
    case Inactive = 'inactive';
    case Blocked = 'blocked';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Actif',
            self::Inactive => 'Inactif',
            self::Blocked => 'Bloqué',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Active => 'bg-green-100 text-green-800',
            self::Inactive => 'bg-gray-100 text-gray-800',
            self::Blocked => 'bg-red-100 text-red-800',
        };
    }
}
