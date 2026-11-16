<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MutualMatchDecision extends Model
{
    protected $fillable = [
        'mutual_match_run_id',
        'transfer_cycle_id',
        'application_a_id',
        'application_b_id',
        'reviewer_id',
        'outcome',
        'remarks',
        'decided_at',
    ];

    protected function casts(): array
    {
        return [
            'decided_at' => 'datetime',
        ];
    }

    public function run(): BelongsTo
    {
        return $this->belongsTo(
            MutualMatchRun::class,
            'mutual_match_run_id'
        );
    }

    public function applicationA(): BelongsTo
    {
        return $this->belongsTo(
            TransferApplication::class,
            'application_a_id'
        );
    }

    public function applicationB(): BelongsTo
    {
        return $this->belongsTo(
            TransferApplication::class,
            'application_b_id'
        );
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }
}
