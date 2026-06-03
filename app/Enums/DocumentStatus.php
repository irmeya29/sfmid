<?php

namespace App\Enums;

use App\Enums\Concerns\HasEnumOptions;

enum DocumentStatus: string
{
    use HasEnumOptions;

    case Draft = 'draft';
    case PendingValidation = 'pending_validation';
    case Rejected = 'rejected';
    case Corrected = 'corrected';
    case Validated = 'validated';
    case Converted = 'converted';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Brouillon',
            self::PendingValidation => 'En attente de validation',
            self::Rejected => 'Rejeté',
            self::Corrected => 'Corrigé',
            self::Validated => 'Validé',
            self::Converted => 'Converti',
            self::Cancelled => 'Annulé',
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
            self::Converted => 'bg-indigo-100 text-indigo-800',
            self::Cancelled => 'bg-slate-200 text-slate-700',
        };
    }

    public function isEditable(): bool
    {
        return in_array($this, [
            self::Draft,
            self::Rejected,
            self::Corrected,
        ], true);
    }

    public function canBeSubmitted(): bool
    {
        return in_array($this, [
            self::Draft,
            self::Corrected,
            self::Rejected,
        ], true);
    }

    public function canBeValidated(): bool
    {
        return $this === self::PendingValidation;
    }

    public function canBeRejected(): bool
    {
        return $this === self::PendingValidation;
    }

    public function canBeCancelled(): bool
    {
        return ! in_array($this, [
            self::Cancelled,
            self::Converted,
        ], true);
    }
}
