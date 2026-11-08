<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransferCycle extends Model
{
    protected $fillable = [
        'name',
        'opens_on',
        'closes_on',
        'status',
        'description',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'opens_on' => 'date',
            'closes_on' => 'date',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
