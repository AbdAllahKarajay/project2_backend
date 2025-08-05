<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRatingRequest;
use App\Models\Rating;

class RatingController extends Controller
{
    public function store(StoreRatingRequest $request)
    {
        $rating = Rating::create([
            'service_request_id' => $request->service_request_id,
            'user_id' => auth()->id(),
            'rating' => $request->rating,
            'comment' => $request->comment,
        ]);

        return response()->json([
            'message' => 'Thank you for your feedback.',
            'rating' => $rating
        ]);
    }
}