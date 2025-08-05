<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Payment;
use App\Models\ServiceRequest;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'service_request_id' => 'required|exists:service_requests,id',
            'method' => 'required|in:cash,wallet,third_party',
        ]);

        $serviceRequest = ServiceRequest::where('user_id', auth()->id())->findOrFail($request->service_request_id);

        if ($serviceRequest->status !== 'pending') {
            return response()->json(['message' => 'This booking is already paid or in progress.'], 400);
        }

        $user = $request->user();
        $amount = $serviceRequest->total_price;

        if ($request->method === 'wallet') {
            if ($user->wallet_balance < $amount) {
                return response()->json(['message' => 'Insufficient wallet balance.'], 400);
            }

            $user->wallet_balance -= $amount;
            $user->save();
        }

        $invoiceNumber = strtoupper(Str::random(10));

        $payment = Payment::create([
            'service_request_id' => $serviceRequest->id,
            'method' => $request->method,
            'amount' => $amount,
            'status' => 'paid',
            'invoice_number' => $invoiceNumber
        ]);

        $serviceRequest->status = 'in_progress';
        $serviceRequest->save();

        return response()->json([
            'message' => 'Payment successful.',
            'invoice_number' => $invoiceNumber
        ]);
    }
}