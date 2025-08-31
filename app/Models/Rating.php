<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Rating extends Model {
    protected $fillable = [
        'service_request_id', 'user_id', 'rating', 'comment'
    ];

    protected $casts = [
        'rating' => 'integer',
    ];

    public function serviceRequest(): BelongsTo
    {
        return $this->belongsTo(ServiceRequest::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getRatingStarsAttribute(): string
    {
        return str_repeat('⭐', $this->rating);
    }
}