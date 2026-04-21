<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InstrumentQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'instrument_id',
        'prompt',
        'response_type',
        'sort_order',
    ];

    public function instrument(): BelongsTo
    {
        return $this->belongsTo(Instrument::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(SubmissionAnswer::class, 'instrument_question_id');
    }
}
