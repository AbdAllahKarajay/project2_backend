<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ApplyCouponRequest;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\ServiceRequest;
use App\Services\FcmService;

class CouponController extends Controller
{
    private FcmService $fcmService;

    public function __construct(FcmService $fcmService)
    {
        $this->fcmService = $fcmService;
    }
    public function apply(ApplyCouponRequest $request)
    {
        $coupon = Coupon::where('code', $request->code)->firstOrFail();
        $serviceRequest = ServiceRequest::findOrFail($request->service_request_id);

        if ($coupon->expiry_date && $coupon->expiry_date < now()) {
            return response()->json(['message' => 'Coupon has expired.'], 400);
        }

        if ($coupon->min_spend && $serviceRequest->total_price < $coupon->min_spend) {
            return response()->json(['message' => 'Minimum spend not met.'], 400);
        }

        $used = CouponUsage::where('coupon_id', $coupon->id)
            ->where('user_id', auth()->id())
            ->count();

        if ($coupon->usage_limit && $used >= $coupon->usage_limit) {
            return response()->json(['message' => 'Coupon usage limit reached.'], 400);
        }

        CouponUsage::create([
            'coupon_id' => $coupon->id,
            'user_id' => auth()->id(),
            'service_request_id' => $request->service_request_id,
        ]);

        $discount = $coupon->type === 'percentage'
            ? ($coupon->value / 100) * $serviceRequest->total_price
            : $coupon->value;

        // Send coupon applied notification
        $user = auth()->user();
        if ($user && $user->hasFcmToken()) {
            $this->fcmService->sendCouponNotification(
                $user,
                $coupon->code,
                "You saved $" . round($discount, 2) . " with this coupon!"
            );
        }

        return response()->json([
            'message' => 'Coupon applied successfully.',
            'discount_amount' => round($discount, 2)
        ]);
    }
}