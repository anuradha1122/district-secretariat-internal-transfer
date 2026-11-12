<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MutualMatchRun extends Model
{
    protected $fillable = [
        'transfer_cycle_id',
        'service_id',
        'created_by',
        'algorithm_version',
        'input_count',
        'candidate_count',
        'input_snapshot',
        'candidate_snapshot',
        'generated_at',
    ];

    protected function casts(): array
    {
        return [
            'input_snapshot' => 'array',
            'candidate_snapshot' => 'array',
            'generated_at' => 'datetime',
        ];
    }

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(
            TransferCycle::class,
            'transfer_cycle_id'
        );
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
