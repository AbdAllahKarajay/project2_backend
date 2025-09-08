<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreServiceRequest;
use App\Http\Requests\UpdateServiceRequest;
use App\Http\Requests\UpdateServiceRequestStatus;
use App\Http\Resources\ServiceRequestResource;
use App\Models\Service;
use App\Models\ServiceRequest;
use App\Models\Location;
use App\Services\FcmService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class ServiceRequestController extends Controller
{
    private FcmService $fcmService;

    public function __construct(FcmService $fcmService)
    {
        $this->fcmService = $fcmService;
    }
    public function index()
    {
        $requests = ServiceRequest::with('service', 'location')->where('user_id', auth()->id())->latest()->get();
        return ServiceRequestResource::collection($requests);
    }

    public function show($id)
    {
        $request = ServiceRequest::with('service', 'location')->where('user_id', auth()->id())->findOrFail($id);
        return new ServiceRequestResource($request);
    }

    public function store(StoreServiceRequest $request)
    {
        $service = Service::findOrFail($request->service_id);

        $totalPrice = $service->base_price; 

        if ($request->location) {
            $location = Location::create([
                'address_text' => $request->location,
                'user_id' => auth()->id(),
            ]);
        } else {
            $location = Location::findOrFail($request->location_id);
        }

        $booking = ServiceRequest::create([
            'user_id' => auth()->id(),
            'service_id' => $service->id,
            'location_id' => $location->id,
            'scheduled_at' => $request->scheduled_at,
            'status' => 'pending',
            'total_price' => $totalPrice,
            'special_instructions' => $request->special_instructions,
        ]);

        // Send notification to user about booking confirmation
        $user = auth()->user();
        if ($user && $user->hasFcmToken()) {
            $this->fcmService->sendServiceRequestUpdate(
                $user,
                'confirmed',
                $service->name
            );
        }

        return response()->json([
            'message' => 'Service booked successfully.',
            'booking' => new ServiceRequestResource($booking)
        ], 201);
    }

    public function update(UpdateServiceRequest $request, $id): JsonResponse
    {
        $serviceRequest = ServiceRequest::where('user_id', auth()->id())->findOrFail($id);

        if (!$serviceRequest->canBeUpdated()) {
            return response()->json([
                'message' => 'This booking cannot be updated in its current status.'
            ], 400);
        }

        $serviceRequest->update($request->validated());

        return response()->json([
            'message' => 'Booking updated successfully.',
            'booking' => new ServiceRequestResource($serviceRequest->load('service', 'location'))
        ]);
    }

    public function destroy($id): JsonResponse
    {
        $serviceRequest = ServiceRequest::where('user_id', auth()->id())->findOrFail($id);

        if (!$serviceRequest->canBeCancelled()) {
            return response()->json([
                'message' => 'This booking cannot be cancelled in its current status.'
            ], 400);
        }

        // Check if payment has been made
        if ($serviceRequest->payments()->where('status', 'paid')->exists()) {
            return response()->json([
                'message' => 'Cannot cancel booking with paid payment. Please contact support for refund.'
            ], 400);
        }

        $serviceRequest->update(['status' => 'cancelled']);

        // Send notification to user about cancellation
        $user = auth()->user();
        if ($user && $user->hasFcmToken()) {
            $this->fcmService->sendServiceRequestUpdate(
                $user,
                'cancelled',
                $serviceRequest->service->name
            );
        }

        return response()->json([
            'message' => 'Booking cancelled successfully.'
        ]);
    }

    public function updateStatus(UpdateServiceRequestStatus $request, $id): JsonResponse
    {
        $serviceRequest = ServiceRequest::where('user_id', auth()->id())->findOrFail($id);
        $newStatus = $request->validated()['status'];

        // Validate status transition
        if (!$this->isValidStatusTransition($serviceRequest->status, $newStatus)) {
            return response()->json([
                'message' => 'Invalid status transition from ' . $serviceRequest->status . ' to ' . $newStatus
            ], 400);
        }

        $serviceRequest->update([
            'status' => $newStatus,
            // You could add a notes field to track status change reasons
        ]);

        // Send notification to user about status update
        $user = auth()->user();
        if ($user && $user->hasFcmToken()) {
            $this->fcmService->sendServiceRequestUpdate(
                $user,
                $newStatus,
                $serviceRequest->service->name
            );
        }

        return response()->json([
            'message' => 'Booking status updated successfully.',
            'booking' => new ServiceRequestResource($serviceRequest->load('service', 'location'))
        ]);
    }

    private function isValidStatusTransition(string $currentStatus, string $newStatus): bool
    {
        $validTransitions = [
            'pending' => ['assigned', 'cancelled'],
            'assigned' => ['in_progress', 'cancelled'],
            'in_progress' => ['completed', 'cancelled'],
            'completed' => [], // No further transitions
            'cancelled' => [], // No further transitions
        ];

        return in_array($newStatus, $validTransitions[$currentStatus] ?? []);
    }
}