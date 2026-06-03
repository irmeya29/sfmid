<?php

namespace App\Enums;

use App\Enums\Concerns\HasEnumOptions;

enum InvoiceStatus: string
{
    use HasEnumOptions;

    case Draft = 'draft';
    case PendingValidation = 'pending_validation';
    case Rejected = 'rejected';
    case Corrected = 'corrected';
    case Validated = 'validated';
    case Unpaid = 'unpaid';
    case PartiallyPaid = 'partially_paid';
    case Paid = 'paid';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Brouillon',
            self::PendingValidation => 'En attente de validation',
            self::Rejected => 'Rejetée',
            self::Corrected => 'Corrigée',
            self::Validated => 'Validée',
            self::Unpaid => 'Non payée',
            self::PartiallyPaid => 'Partiellement payée',
            self::Paid => 'Payée',
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
            self::Unpaid => 'bg-orange-100 text-orange-800',
            self::PartiallyPaid => 'bg-cyan-100 text-cyan-800',
            self::Paid => 'bg-emerald-100 text-emerald-800',
            self::Cancelled => 'bg-slate-200 text-slate-700',
        };
    }

    public function isPayable(): bool
    {
        return in_array($this, [
            self::Validated,
            self::Unpaid,
            self::PartiallyPaid,
        ], true);
    }

    public function isClosed(): bool
    {
        return in_array($this, [
            self::Paid,
            self::Cancelled,
        ], true);
    }
}
