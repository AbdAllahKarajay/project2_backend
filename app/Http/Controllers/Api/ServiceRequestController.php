<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreServiceRequest;
use App\Http\Resources\ServiceRequestResource;
use App\Models\git ;
use App\Models\Service;
use App\Models\Location;
use Illuminate\Support\Str;

class ServiceRequestController extends Controller
{
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

        return response()->json([
            'message' => 'Service booked successfully.',
            'booking' => new ServiceRequestResource($booking)
        ], 201);
    }
}