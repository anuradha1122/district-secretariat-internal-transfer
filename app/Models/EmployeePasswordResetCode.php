<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeePasswordResetCode extends Model
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
}
