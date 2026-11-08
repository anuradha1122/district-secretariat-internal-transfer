<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransferApplicationWorkHistory extends Model
{
    protected $table = 'transfer_application_work_histories';

    protected $fillable = [
        'transfer_application_id',
        'workplace_name',
        'from_date',
        'to_date',
    ];

    protected function casts(): array
    {
        return [
            'from_date' => 'date',
            'to_date' => 'date',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(
            TransferApplication::class,
            'transfer_application_id'
        );
    }
}
