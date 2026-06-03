<?php

namespace App\Models;

use App\Enums\ValidationAction;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ValidationHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_type',
        'document_id',
        'action',
        'from_status',
        'to_status',
        'comment',
        'reason',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'action' => ValidationAction::class,
        ];
    }

    public function document(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
