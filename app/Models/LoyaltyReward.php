<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class LoyaltyReward extends Model
{
    protected $fillable = [
        'name',
        'description',
        'points_required',
        'type',
        'value',
        'code',
        'is_active',
        'max_redemptions',
        'current_redemptions',
        'valid_from',
        'valid_until',
    ];

    protected $casts = [
        'points_required' => 'integer',
        'value' => 'decimal:2',
        'is_active' => 'boolean',
        'max_redemptions' => 'integer',
        'current_redemptions' => 'integer',
        'valid_from' => 'date',
        'valid_until' => 'date',
    ];

    public function redemptions(): HasMany
    {
        return $this->hasMany(LoyaltyRewardRedemption::class);
    }

    public function isAvailable(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->max_redemptions && $this->current_redemptions >= $this->max_redemptions) {
            return false;
        }

        $now = Carbon::now();
        
        if ($this->valid_from && $now->lt($this->valid_from)) {
            return false;
        }

        if ($this->valid_until && $now->gt($this->valid_until)) {
            return false;
        }

        return true;
    }

    public function canBeRedeemed(): bool
    {
        return $this->isAvailable();
    }

    public function getFormattedValueAttribute(): string
    {
        if ($this->type === 'discount') {
            return $this->value . '%';
        } elseif (in_array($this->type, ['upgrade', 'cashback'])) {
            return '$' . number_format($this->value, 2);
        }
        
        return 'N/A';
    }

    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            'discount' => 'Discount',
            'free_service' => 'Free Service',
            'upgrade' => 'Service Upgrade',
            'cashback' => 'Cash Back',
            default => ucfirst($this->type),
        };
    }

    public function incrementRedemptions(): void
    {
        $this->increment('current_redemptions');
    }
}
