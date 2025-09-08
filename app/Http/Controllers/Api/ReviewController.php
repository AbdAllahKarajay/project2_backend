<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Review;
use App\Models\Service;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ReviewController extends Controller
{
    public function index($serviceId)
    {
        $service = Service::findOrFail($serviceId);

        $reviews = Review::with(['user:id,name,email'])
            ->where('service_id', $service->id)
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($r) {
                return [
                    'id' => $r->id,
                    'service_id' => $r->service_id,
                    'user_id' => $r->user_id,
                    'user_name' => optional($r->user)->name,
                    'user_avatar' => null,
                    'rating' => (int) $r->rating,
                    'comment' => (string) ($r->comment ?? ''),
                    'created_at' => $r->created_at?->toIso8601String(),
                    'updated_at' => $r->updated_at?->toIso8601String(),
                ];
            });

        return response()->json($reviews);
    }

    public function summary($serviceId)
    {
        $service = Service::findOrFail($serviceId);

        $query = Review::where('service_id', $service->id);
        $total = (int) $query->count();
        $average = $total > 0 ? (float) round($query->avg('rating'), 1) : 0.0;

        $distribution = [
            '5' => 0,
            '4' => 0,
            '3' => 0,
            '2' => 0,
            '1' => 0,
        ];

        Review::select('rating')
            ->where('service_id', $service->id)
            ->get()
            ->groupBy('rating')
            ->each(function ($group, $rating) use (&$distribution) {
                $distribution[(string) $rating] = $group->count();
            });

        return response()->json([
            'average_rating' => $average,
            'total_reviews' => $total,
            'rating_distribution' => $distribution,
        ]);
    }

    public function store(Request $request, $serviceId)
    {
        $service = Service::findOrFail($serviceId);

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $userId = Auth::id();

        $review = Review::updateOrCreate(
            [
                'service_id' => $service->id,
                'user_id' => $userId,
            ],
            [
                'rating' => (int) $validated['rating'],
                'comment' => $validated['comment'] ?? null,
            ]
        );

        return response()->json([
            'id' => $review->id,
            'service_id' => $review->service_id,
            'user_id' => $review->user_id,
            'user_name' => $review->user?->name,
            'user_avatar' => null,
            'rating' => (int) $review->rating,
            'comment' => (string) ($review->comment ?? ''),
            'created_at' => $review->created_at?->toIso8601String(),
            'updated_at' => $review->updated_at?->toIso8601String(),
        ], 201);
    }

    public function update(Request $request, $reviewId)
    {
        $review = Review::findOrFail($reviewId);
        $this->authorizeOwnership($review);

        $validated = $request->validate([
            'rating' => ['nullable', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        if (array_key_exists('rating', $validated) && $validated['rating'] !== null) {
            $review->rating = (int) $validated['rating'];
        }
        if (array_key_exists('comment', $validated)) {
            $review->comment = $validated['comment'];
        }
        $review->save();

        return response()->json([
            'id' => $review->id,
            'service_id' => $review->service_id,
            'user_id' => $review->user_id,
            'user_name' => $review->user?->name,
            'user_avatar' => null,
            'rating' => (int) $review->rating,
            'comment' => (string) ($review->comment ?? ''),
            'created_at' => $review->created_at?->toIso8601String(),
            'updated_at' => $review->updated_at?->toIso8601String(),
        ]);
    }

    public function destroy($reviewId)
    {
        $review = Review::findOrFail($reviewId);
        $this->authorizeOwnership($review);
        $review->delete();
        return response()->json(['success' => true]);
    }

    private function authorizeOwnership(Review $review): void
    {
        if (Auth::id() !== $review->user_id) {
            abort(403, 'Unauthorized');
        }
    }
}


