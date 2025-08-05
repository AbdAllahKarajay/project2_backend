<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rating extends Model {
    protected $fillable = [
        'service_request_id', 'user_id', 'rating', 'comment'
    ];
}