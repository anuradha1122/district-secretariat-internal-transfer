<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeActivationCode extends Model
{
    protected $fillable = [
        'eligible_employee_id',
        'code_hash',
        'expires_at',
        'used_at',
        'issued_by',
    ];

    protected $hidden = [
        'code_hash',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
        ];
    }

    public function eligibleEmployee(): BelongsTo
    {
        return $this->belongsTo(EligibleEmployee::class);
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }
}
