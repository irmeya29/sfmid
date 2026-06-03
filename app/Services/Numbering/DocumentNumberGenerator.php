<?php

namespace App\Services\Numbering;

use App\Models\DocumentNumberSequence;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DocumentNumberGenerator
{
    public function generate(string $documentType): string
    {
        return DB::transaction(function () use ($documentType): string {
            $sequence = DocumentNumberSequence::query()
                ->where('document_type', $documentType)
                ->lockForUpdate()
                ->first();

            if (! $sequence) {
                throw new RuntimeException("Séquence de numérotation introuvable pour {$documentType}.");
            }

            $year = now()->format('Y');
            $number = str_pad((string) $sequence->next_number, $sequence->padding, '0', STR_PAD_LEFT);

            $documentNumber = "{$sequence->prefix}-{$year}-{$number}";

            $sequence->forceFill([
                'next_number' => $sequence->next_number + 1,
                'last_generated_at' => now(),
            ])->save();

            return $documentNumber;
        });
    }
}
