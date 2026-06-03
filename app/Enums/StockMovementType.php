<?php

namespace App\Enums;

use App\Enums\Concerns\HasEnumOptions;

enum StockMovementType: string
{
    use HasEnumOptions;

    case PurchaseEntry = 'purchase_entry';
    case CustomerReturn = 'customer_return';
    case PositiveAdjustment = 'positive_adjustment';
    case DeliveryExit = 'delivery_exit';
    case InternalUse = 'internal_use';
    case LossOrDamage = 'loss_or_damage';
    case NegativeAdjustment = 'negative_adjustment';
    case TransferIn = 'transfer_in';
    case TransferOut = 'transfer_out';
    case SuspenseOpen = 'suspense_open';
    case SuspenseClose = 'suspense_close';

    public function label(): string
    {
        return match ($this) {
            self::PurchaseEntry => 'Entrée achat fournisseur',
            self::CustomerReturn => 'Retour client',
            self::PositiveAdjustment => 'Ajustement positif',
            self::DeliveryExit => 'Sortie livraison client',
            self::InternalUse => 'Utilisation interne',
            self::LossOrDamage => 'Casse ou perte',
            self::NegativeAdjustment => 'Ajustement négatif',
            self::TransferIn => 'Transfert entrant',
            self::TransferOut => 'Transfert sortant',
            self::SuspenseOpen => 'Ouverture stock en suspens',
            self::SuspenseClose => 'Clôture stock en suspens',
        };
    }

    public function isSensitive(): bool
    {
        return in_array($this, [
            self::NegativeAdjustment,
            self::LossOrDamage,
            self::DeliveryExit,
            self::SuspenseClose,
        ], true);
    }
}
