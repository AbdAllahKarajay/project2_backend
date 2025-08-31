<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletTransaction extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'amount',
        'balance_before',
        'balance_after',
        'reference',
        'description',
        'metadata',
        'status'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'metadata' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isTopup(): bool
    {
        return $this->type === 'topup';
    }

    public function isPayment(): bool
    {
        return $this->type === 'payment';
    }

    public function isRefund(): bool
    {
        return $this->type === 'refund';
    }

    public function isBonus(): bool
    {
        return $this->type === 'bonus';
    }

    public function isDeduction(): bool
    {
        return $this->type === 'deduction';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function getFormattedAmountAttribute(): string
    {
        $prefix = $this->isTopup() || $this->isRefund() || $this->isBonus() ? '+' : '-';
        return $prefix . '$' . number_format($this->amount, 2);
    }

    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            'topup' => 'Top Up',
            'payment' => 'Payment',
            'refund' => 'Refund',
            'bonus' => 'Bonus',
            'deduction' => 'Deduction',
            default => ucfirst($this->type),
        };
    }
}
