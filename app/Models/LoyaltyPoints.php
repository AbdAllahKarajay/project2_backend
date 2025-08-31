<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoyaltyPoints extends Model
{
    protected $fillable = [
        'user_id',
        'points',
        'source_request_id'
    ];

    protected $casts = [
        'points' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sourceRequest(): BelongsTo
    {
        return $this->belongsTo(ServiceRequest::class, 'source_request_id');
    }

    public function isPositive(): bool
    {
        return $this->points > 0;
    }

    public function isNegative(): bool
    {
        return $this->points < 0;
    }

    public function getFormattedPointsAttribute(): string
    {
        $prefix = $this->isPositive() ? '+' : '';
        return $prefix . $this->points;
    }

    public function getPointsLabelAttribute(): string
    {
        return $this->points === 1 ? 'point' : 'points';
    }
}
