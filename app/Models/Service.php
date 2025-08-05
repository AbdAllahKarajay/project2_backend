<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model {
    protected $fillable = [
        'name', 'description', 'category', 'base_price', 'duration_minutes', 'average_rating'
    ];
}