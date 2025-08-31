<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceRequest extends Model {
    protected $fillable = [
        'user_id', 'service_id', 'location_id', 'scheduled_at', 'status', 'total_price', 'special_instructions'
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'total_price' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(Rating::class);
    }

    public function couponUsages(): HasMany
    {
        return $this->hasMany(CouponUsage::class);
    }

    public function loyaltyPoints(): HasMany
    {
        return $this->hasMany(LoyaltyPoints::class);
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['pending', 'assigned']);
    }

    public function canBeUpdated(): bool
    {
        return in_array($this->status, ['pending', 'assigned']);
    }
}