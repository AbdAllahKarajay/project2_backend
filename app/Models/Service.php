<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model {
    protected $fillable = [
        'name', 'description', 'category', 'base_price', 'duration_minutes', 'average_rating'
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
        'duration_minutes' => 'integer',
        'average_rating' => 'decimal:2',
    ];

    public function serviceRequests(): HasMany
    {
        return $this->hasMany(ServiceRequest::class);
    }

    public function getFormattedPriceAttribute(): string
    {
        return '$' . number_format($this->base_price, 2);
    }

    public function getFormattedDurationAttribute(): string
    {
        if (!$this->duration_minutes) {
            return 'N/A';
        }
        return $this->duration_minutes . ' min';
    }
}