<?php

namespace App\Enums;

use App\Enums\Concerns\HasEnumOptions;

enum ExpenseStatus: string
{
    use HasEnumOptions;

    case Draft = 'draft';
    case PendingValidation = 'pending_validation';
    case Rejected = 'rejected';
    case Corrected = 'corrected';
    case Validated = 'validated';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Brouillon',
            self::PendingValidation => 'En attente de validation',
            self::Rejected => 'Rejetée',
            self::Corrected => 'Corrigée',
            self::Validated => 'Validée',
            self::Cancelled => 'Annulée',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Draft => 'bg-gray-100 text-gray-800',
            self::PendingValidation => 'bg-yellow-100 text-yellow-800',
            self::Rejected => 'bg-red-100 text-red-800',
            self::Corrected => 'bg-blue-100 text-blue-800',
            self::Validated => 'bg-green-100 text-green-800',
            self::Cancelled => 'bg-slate-200 text-slate-700',
        };
    }
}
