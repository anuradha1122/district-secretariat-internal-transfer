<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfficeReviewDecision extends Model
{
    protected $fillable = [
        'transfer_application_id',
        'reviewer_id',
        'office_id',
        'outcome',
        'recommendation',
        'insufficient_service',
        'replacement_required',
        'pending_matters',
        'other_circumstances',
        'signed_paper_verified',
        'remarks',
        'application_snapshot',
        'decided_at',
    ];

    protected function casts(): array
    {
        return [
            'insufficient_service' => 'boolean',
            'replacement_required' => 'boolean',
            'pending_matters' => 'boolean',
            'other_circumstances' => 'boolean',
            'signed_paper_verified' => 'boolean',
            'application_snapshot' => 'array',
            'decided_at' => 'datetime',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(
            TransferApplication::class,
            'transfer_application_id'
        );
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class, 'office_id');
    }
}
