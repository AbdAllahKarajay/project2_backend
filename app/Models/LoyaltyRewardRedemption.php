<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class LoyaltyRewardRedemption extends Model
{
    protected $fillable = [
        'user_id',
        'loyalty_reward_id',
        'points_spent',
        'status',
        'metadata',
        'expires_at',
        'used_at',
    ];

    protected $casts = [
        'points_spent' => 'integer',
        'metadata' => 'array',
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reward(): BelongsTo
    {
        return $this->belongsTo(LoyaltyReward::class, 'loyalty_reward_id');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isUsed(): bool
    {
        return $this->status === 'used';
    }

    public function isExpired(): bool
    {
        return $this->status === 'expired';
    }

    public function isExpiredByTime(): bool
    {
        return $this->expires_at && Carbon::now()->gt($this->expires_at);
    }

    public function canBeUsed(): bool
    {
        return $this->isActive() && !$this->isExpiredByTime();
    }

    public function markAsUsed(): void
    {
        $this->update([
            'status' => 'used',
            'used_at' => Carbon::now(),
        ]);
    }

    public function markAsExpired(): void
    {
        $this->update([
            'status' => 'expired',
        ]);
    }

    public function getFormattedExpiryAttribute(): string
    {
        if (!$this->expires_at) {
            return 'Never expires';
        }
        
        return $this->expires_at->format('M d, Y H:i');
    }
}
