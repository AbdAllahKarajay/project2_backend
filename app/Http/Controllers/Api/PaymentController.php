<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Payment;
use App\Models\ServiceRequest;
use App\Models\WalletTransaction;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'service_request_id' => 'required|exists:service_requests,id',
            'method' => 'required|in:cash,wallet,third_party',
        ]);
    
        $idempotencyKey = $request->header('Idempotency-Key');
    
        if (!$idempotencyKey) {
            return response()->json(['message' => 'Idempotency-Key header is required.'], 400);
        }
    
        // Check if a payment with this idempotency key already exists
        if ($existing = Payment::where('idempotency_key', $idempotencyKey)->first()) {
            return response()->json([
                'message' => 'Payment already processed.',
                'data' => $existing
            ]);
        }
    
        $serviceRequest = ServiceRequest::where('user_id', auth()->id())->findOrFail($request->service_request_id);
    
        if ($serviceRequest->status !== 'pending') {
            return response()->json(['message' => 'This booking is already paid or in progress.'], 400);
        }
    
        $user = $request->user();
        $amount = $serviceRequest->total_price;
    
        try {
            DB::beginTransaction();
            DB::statement('SET TRANSACTION ISOLATION LEVEL SERIALIZABLE');
    
            if ($request->method === 'wallet') {
                if (!$user->hasSufficientBalance($amount)) {
                    return response()->json(['message' => 'Insufficient wallet balance.'], 400);
                }
    
                $balanceBefore = $user->wallet_balance;
                $user->deductFromWallet($amount);
    
                WalletTransaction::create([
                    'user_id' => $user->id,
                    'type' => 'payment',
                    'amount' => $amount,
                    'balance_before' => $balanceBefore,
                    'balance_after' => $user->wallet_balance,
                    'reference' => 'SERVICE_PAYMENT',
                    'description' => "Payment for service: {$serviceRequest->service->name}",
                    'metadata' => [
                        'service_request_id' => $serviceRequest->id,
                        'service_name' => $serviceRequest->service->name,
                        'payment_method' => 'wallet',
                    ],
                    'status' => 'completed',
                ]);
            }
    
            $invoiceNumber = strtoupper(Str::random(10));
    
            $payment = Payment::create([
                'service_request_id' => $serviceRequest->id,
                'method' => $request->method,
                'amount' => $amount,
                'status' => 'paid',
                'invoice_number' => $invoiceNumber,
                'idempotency_key' => $idempotencyKey, // must add this column in payments table
            ]);
    
            $serviceRequest->status = 'in_progress';
            $serviceRequest->save();
    
            DB::commit();
    
            return response()->json([
                'message' => 'Payment successful.',
                'data' => [
                    'invoice_number' => $invoiceNumber,
                    'amount' => $amount,
                    'method' => $request->method,
                    'wallet_balance' => $request->method === 'wallet' ? $user->wallet_balance : null,
                ]
            ]);
    
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Payment failed. Please try again.'], 500);
        }
    }
    
    public function refund(Request $request, $paymentId)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
            'refund_amount' => 'nullable|numeric|min:0.01',
        ]);
    
        $idempotencyKey = $request->header('Idempotency-Key');
        if (!$idempotencyKey) {
            return response()->json(['message' => 'Idempotency-Key header is required.'], 400);
        }
    
        // Check if refund with this idempotency key already exists
        if ($existing = WalletTransaction::where('reference', 'SERVICE_REFUND')
            ->where('metadata->idempotency_key', $idempotencyKey)
            ->first()) {
            return response()->json([
                'message' => 'Refund already processed.',
                'data' => $existing
            ]);
        }
    
        $payment = Payment::with('serviceRequest.user')
            ->whereHas('serviceRequest', function ($query) {
                $query->where('user_id', auth()->id());
            })
            ->findOrFail($paymentId);
    
        if ($payment->status !== 'paid') {
            return response()->json(['message' => 'This payment cannot be refunded.'], 400);
        }
    
        $refundAmount = $request->refund_amount ?? $payment->amount;
        if ($refundAmount > $payment->amount) {
            return response()->json(['message' => 'Refund amount cannot exceed payment amount.'], 400);
        }
    
        try {
            DB::beginTransaction();
            DB::statement('SET TRANSACTION ISOLATION LEVEL SERIALIZABLE');
    
            // Update payment status
            $payment->update([
                'status' => 'refunded',
                'idempotency_key' => $idempotencyKey, // save key on payment too
            ]);
    
            if ($payment->method === 'wallet') {
                $user = $payment->serviceRequest->user;
                $balanceBefore = $user->wallet_balance;
    
                $user->addToWallet($refundAmount);
    
                WalletTransaction::create([
                    'user_id' => $user->id,
                    'type' => 'refund',
                    'amount' => $refundAmount,
                    'balance_before' => $balanceBefore,
                    'balance_after' => $user->wallet_balance,
                    'reference' => 'SERVICE_REFUND',
                    'description' => "Refund for service: {$payment->serviceRequest->service->name}",
                    'metadata' => [
                        'payment_id' => $payment->id,
                        'service_request_id' => $payment->serviceRequest->id,
                        'refund_reason' => $request->reason,
                        'original_amount' => $payment->amount,
                        'idempotency_key' => $idempotencyKey, // ✅ stored for safety
                    ],
                    'status' => 'completed',
                ]);
            }
    
            DB::commit();
    
            return response()->json([
                'message' => 'Refund processed successfully.',
                'data' => [
                    'refund_amount' => $refundAmount,
                    'refund_reason' => $request->reason,
                    'wallet_balance' => $payment->method === 'wallet'
                        ? $payment->serviceRequest->user->wallet_balance
                        : null,
                ]
            ]);
    
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Refund failed. Please try again.',
            ], 500);
        }
    }
    

    /**
     * Get payment history for the authenticated user.
     */
    public function index(Request $request)
    {
        $query = Payment::with(['serviceRequest.service'])
            ->whereHas('serviceRequest', function ($query) {
                $query->where('user_id', auth()->id());
            });

        // Apply filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('method')) {
            $query->where('method', $request->method);
        }

        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $payments = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => [
                'payments' => $payments->items(),
                'pagination' => [
                    'current_page' => $payments->currentPage(),
                    'last_page' => $payments->lastPage(),
                    'per_page' => $payments->perPage(),
                    'total' => $payments->total(),
                ]
            ]
        ]);
    }

    /**
     * Get payment details.
     */
    public function show($paymentId)
    {
        $payment = Payment::with(['serviceRequest.service', 'serviceRequest.location'])
            ->whereHas('serviceRequest', function ($query) {
                $query->where('user_id', auth()->id());
            })
            ->findOrFail($paymentId);

        return response()->json([
            'success' => true,
            'data' => $payment
        ]);
    }
}