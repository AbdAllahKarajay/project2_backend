<div class="p-6">
    <div class="mb-6">
        <h3 class="text-lg font-medium text-gray-900 mb-2">Notification Preview</h3>
        <p class="text-sm text-gray-600">This is how your notification will appear to recipients</p>
    </div>

    <div class="bg-white rounded-lg border border-gray-200 p-6 shadow-sm">
        <div class="flex items-start space-x-4">
            <div class="flex-shrink-0">
                @switch($notification->type)
                    @case('email')
                        <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center">
                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        @break
                    @case('sms')
                        <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                            </svg>
                        </div>
                        @break
                    @case('push')
                        <div class="w-10 h-10 rounded-full bg-yellow-100 flex items-center justify-center">
                            <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5zM4 19h6a2 2 0 002-2V7a2 2 0 00-2-2H4a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        @break
                    @default
                        <div class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center">
                            <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5zM4 19h6a2 2 0 002-2V7a2 2 0 00-2-2H4a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                @endswitch
            </div>

            <div class="flex-1 min-w-0">
                <div class="flex items-center justify-between mb-2">
                    <h4 class="text-sm font-medium text-gray-900">{{ $notification->title }}</h4>
                    <div class="flex items-center space-x-2">
                        @if($notification->priority)
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                {{ $notification->priority === 'urgent' ? 'bg-red-100 text-red-800' : 
                                   ($notification->priority === 'high' ? 'bg-orange-100 text-orange-800' : 
                                   ($notification->priority === 'normal' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800')) }}">
                                {{ ucfirst($notification->priority) }}
                            </span>
                        @endif
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">
                            {{ ucfirst($notification->type) }}
                        </span>
                    </div>
                </div>

                <p class="text-sm text-gray-600 mb-3">{{ $notification->message }}</p>

                <div class="text-xs text-gray-500">
                    <p><strong>Type:</strong> {{ ucfirst($notification->type) }}</p>
                    @if($notification->priority)
                        <p><strong>Priority:</strong> {{ ucfirst($notification->priority) }}</p>
                    @endif
                    @if($notification->category)
                        <p><strong>Category:</strong> {{ $notification->category }}</p>
                    @endif
                    @if($notification->scheduled_at)
                        <p><strong>Scheduled for:</strong> {{ $notification->scheduled_at->format('M d, Y g:i A') }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if($notification->metadata)
        <div class="mt-6">
            <h4 class="text-sm font-medium text-gray-900 mb-3">Additional Data</h4>
            <div class="bg-gray-50 rounded-lg p-4">
                <div class="space-y-2">
                    @foreach($notification->metadata as $key => $value)
                        <div class="flex justify-between text-sm">
                            <span class="font-medium text-gray-700">{{ $key }}:</span>
                            <span class="text-gray-600">{{ is_string($value) ? $value : json_encode($value) }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <div class="mt-6 bg-blue-50 rounded-lg p-4">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-blue-800">Preview Information</h3>
                <div class="mt-2 text-sm text-blue-700">
                    <p>This preview shows how your notification will appear. The actual delivery may vary depending on the recipient's device and notification settings.</p>
                </div>
            </div>
        </div>
    </div>
</div>
