<?php

namespace App\Enums;

use App\Enums\Concerns\HasEnumOptions;

enum ProductStockKind: string
{
    use HasEnumOptions;

    case Commercial = 'commercial';
    case Tool = 'tool';

    public function label(): string
    {
        return match ($this) {
            self::Commercial => 'Stock commercial',
            self::Tool => 'Stock outil',
        };
    }
}
