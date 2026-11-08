<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TransferApplication extends Model
{
    protected $fillable = [
        'transfer_cycle_id',
        'user_id',
        'reference_no',
        'full_name',
        'name_with_initials_sinhala',
        'name_with_initials_english',
        'date_of_birth',
        'gender',
        'permanent_address',
        'temporary_address',
        'office_phone',
        'personal_phone',
        'marital_status',
        'spouse_name',
        'spouse_occupation',
        'spouse_workplace',
        'post',
        'grade',
        'appointment_letter_no',
        'is_confirmed',
        'current_office_id',
        'service_id',
        'reported_to_current_office_on',
        'accept_nearby_office',
        'transfer_reason',
        'declaration_accepted',
        'status',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'reported_to_current_office_on' => 'date',
            'is_confirmed' => 'boolean',
            'accept_nearby_office' => 'boolean',
            'declaration_accepted' => 'boolean',
            'submitted_at' => 'datetime',
        ];
    }

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(
            TransferCycle::class,
            'transfer_cycle_id'
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function currentOffice(): BelongsTo
    {
        return $this->belongsTo(Office::class, 'current_office_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function children(): HasMany
    {
        return $this->hasMany(
            TransferApplicationChild::class,
            'transfer_application_id'
        );
    }

    public function workHistory(): HasMany
    {
        return $this->hasMany(
            TransferApplicationWorkHistory::class,
            'transfer_application_id'
        );
    }

    public function preferences(): HasMany
    {
        return $this->hasMany(
            TransferApplicationPreference::class,
            'transfer_application_id'
        )->orderBy('priority');
    }
}
