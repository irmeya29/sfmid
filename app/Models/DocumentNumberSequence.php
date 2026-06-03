<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentNumberSequence extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_type',
        'prefix',
        'next_number',
        'padding',
        'reset_period',
        'last_generated_at',
    ];

    protected function casts(): array
    {
        return [
            'next_number' => 'integer',
            'padding' => 'integer',
            'last_generated_at' => 'datetime',
        ];
    }
}
