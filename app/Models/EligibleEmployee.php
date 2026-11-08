<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class EligibleEmployee extends Model
{
    protected $fillable = [
        'nic',
        'is_active',
        'imported_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function importer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'imported_by');
    }

    public function activationCode(): HasOne
    {
        return $this->hasOne(EmployeeActivationCode::class);
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }
}
