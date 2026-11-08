<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransferApplicationChild extends Model
{
    protected $fillable = [
        'transfer_application_id',
        'name',
        'age',
        'school',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(
            TransferApplication::class,
            'transfer_application_id'
        );
    }
}
