<?php

namespace App\Enums;

use App\Enums\Concerns\HasEnumOptions;

enum ValidationAction: string
{
    use HasEnumOptions;

    case Submit = 'submit';
    case Validate = 'validate';
    case Reject = 'reject';
    case Correct = 'correct';
    case Cancel = 'cancel';
    case Convert = 'convert';
    case MarkDelivered = 'mark_delivered';
    case MarkPaid = 'mark_paid';

    public function label(): string
    {
        return match ($this) {
            self::Submit => 'Soumission',
            self::Validate => 'Validation',
            self::Reject => 'Rejet',
            self::Correct => 'Correction',
            self::Cancel => 'Annulation',
            self::Convert => 'Conversion',
            self::MarkDelivered => 'Marqué livré',
            self::MarkPaid => 'Marqué payé',
        };
    }
}
