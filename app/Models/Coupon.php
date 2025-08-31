<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Coupon extends Model {
    protected $fillable = [
        'code', 'type', 'value', 'min_spend', 'expiry_date', 'usage_limit'
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'min_spend' => 'decimal:2',
        'expiry_date' => 'date',
        'usage_limit' => 'integer',
    ];

    public function usages(): HasMany
    {
        return $this->hasMany(CouponUsage::class);
    }

    public function isExpired(): bool
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }

    public function isActive(): bool
    {
        return !$this->isExpired();
    }
}