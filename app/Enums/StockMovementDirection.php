<?php

namespace App\Enums;

use App\Enums\Concerns\HasEnumOptions;

enum StockMovementDirection: string
{
    use HasEnumOptions;

    case In = 'in';
    case Out = 'out';
    case Transfer = 'transfer';
    case Suspense = 'suspense';
    case CloseSuspense = 'close_suspense';

    public function label(): string
    {
        return match ($this) {
            self::In => 'Entrée',
            self::Out => 'Sortie',
            self::Transfer => 'Transfert',
            self::Suspense => 'Mise en suspens',
            self::CloseSuspense => 'Clôture suspens',
        };
    }
}
