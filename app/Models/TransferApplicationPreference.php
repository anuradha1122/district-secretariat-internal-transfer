<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransferApplicationPreference extends Model
{
    protected $fillable = [
        'transfer_application_id',
        'office_id',
        'priority',
        'reason',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(
            TransferApplication::class,
            'transfer_application_id'
        );
    }

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }
}
