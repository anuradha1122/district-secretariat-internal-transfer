<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DistrictReviewDecision extends Model
{
    protected $fillable = [
        'transfer_application_id',
        'office_review_decision_id',
        'reviewer_id',
        'outcome',
        'remarks',
        'decided_at',
    ];

    protected function casts(): array
    {
        return [
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

    public function officeReviewDecision(): BelongsTo
    {
        return $this->belongsTo(
            OfficeReviewDecision::class,
            'office_review_decision_id'
        );
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }
}
