<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable {
    use HasApiTokens, HasFactory;

    protected $fillable = [
        'name', 'email', 'phone', 'password', 'role', 'wallet_balance'
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'wallet_balance' => 'decimal:2',
    ];

    public function serviceRequests(): HasMany
    {
        return $this->hasMany(ServiceRequest::class);
    }

    public function locations(): HasMany
    {
        return $this->hasMany(Location::class);
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

    public function walletTransactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class);
    }

    public function loyaltyRewardRedemptions(): HasMany
    {
        return $this->hasMany(LoyaltyRewardRedemption::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isCustomer(): bool
    {
        return $this->role === 'customer';
    }

    public function hasSufficientBalance(float $amount): bool
    {
        return $this->wallet_balance >= $amount;
    }

    public function deductFromWallet(float $amount): bool
    {
        if (!$this->hasSufficientBalance($amount)) {
            return false;
        }

        $this->wallet_balance -= $amount;
        return $this->save();
    }

    public function addToWallet(float $amount): bool
    {
        $this->wallet_balance += $amount;
        return $this->save();
    }

    public function getTotalLoyaltyPointsAttribute(): int
    {
        return $this->loyaltyPoints()->sum('points');
    }

    public function hasEnoughPoints(int $requiredPoints): bool
    {
        return $this->total_loyalty_points >= $requiredPoints;
    }

    public function addLoyaltyPoints(int $points, int $sourceRequestId = null): void
    {
        $this->loyaltyPoints()->create([
            'points' => $points,
            'source_request_id' => $sourceRequestId,
        ]);
    }

    public function deductLoyaltyPoints(int $points, int $sourceRequestId = null): void
    {
        $this->loyaltyPoints()->create([
            'points' => -$points,
            'source_request_id' => $sourceRequestId,
        ]);
    }
}