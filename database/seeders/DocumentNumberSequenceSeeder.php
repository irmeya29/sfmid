<?php

namespace Database\Seeders;

use App\Models\DocumentNumberSequence;
use Illuminate\Database\Seeder;

class DocumentNumberSequenceSeeder extends Seeder
{
    public function run(): void
    {
        $sequences = [
            [
                'document_type' => 'proforma',
                'prefix' => 'PRO',
                'next_number' => 1,
                'padding' => 5,
                'reset_period' => 'yearly',
            ],
            [
                'document_type' => 'delivery_note',
                'prefix' => 'BL',
                'next_number' => 1,
                'padding' => 5,
                'reset_period' => 'yearly',
            ],
            [
                'document_type' => 'invoice',
                'prefix' => 'FAC',
                'next_number' => 1,
                'padding' => 5,
                'reset_period' => 'yearly',
            ],
            [
                'document_type' => 'payment',
                'prefix' => 'REC',
                'next_number' => 1,
                'padding' => 5,
                'reset_period' => 'yearly',
            ],
            [
                'document_type' => 'expense',
                'prefix' => 'DEP',
                'next_number' => 1,
                'padding' => 5,
                'reset_period' => 'yearly',
            ],
            [
                'document_type' => 'purchase_request',
                'prefix' => 'DA',
                'next_number' => 1,
                'padding' => 5,
                'reset_period' => 'yearly',
            ],
            [
                'document_type' => 'supplier_purchase_order',
                'prefix' => 'BCF',
                'next_number' => 1,
                'padding' => 5,
                'reset_period' => 'yearly',
            ],
            [
                'document_type' => 'supplier_invoice',
                'prefix' => 'FAF',
                'next_number' => 1,
                'padding' => 5,
                'reset_period' => 'yearly',
            ],
            [
                'document_type' => 'supplier_payment',
                'prefix' => 'RFO',
                'next_number' => 1,
                'padding' => 5,
                'reset_period' => 'yearly',
            ],
        ];

        foreach ($sequences as $sequence) {
            DocumentNumberSequence::query()->updateOrCreate(
                ['document_type' => $sequence['document_type']],
                [
                    'prefix' => $sequence['prefix'],
                    'next_number' => $sequence['next_number'],
                    'padding' => $sequence['padding'],
                    'reset_period' => $sequence['reset_period'],
                    'last_generated_at' => null,
                ]
            );
        }
    }
}
