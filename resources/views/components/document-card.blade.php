@props(['document'])

<div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4 hover:shadow-md transition-shadow">
    <div class="flex items-start justify-between">
        <div class="flex-1 min-w-0">
            <div class="flex items-center space-x-2">
                <svg class="w-8 h-8 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">
                        {{ $document->name }}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                        {{ $document->filename }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-2 flex items-center justify-between">
        <div class="flex items-center space-x-2">
            <span class="text-xs text-gray-500 dark:text-gray-400">
                {{ number_format($document->size / 1024, 1) }} KB
            </span>
            <span class="px-2 py-1 text-xs font-medium rounded
                @if($document->upload_status === 'completed') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                @elseif($document->upload_status === 'failed') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                @else bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                @endif">
                {{ ucfirst($document->upload_status) }}
            </span>
        </div>
    </div>

    @if($document->upload_status === 'uploading' || $document->upload_status === 'processing')
        <div class="mt-2">
            @include('afterburner-documents::components.progress-bar', ['progress' => $document->upload_progress])
        </div>
    @endif

    <div class="mt-3 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
        <span>{{ $document->created_at->format('M d, Y') }}</span>
        <span>{{ $document->uploader->name }}</span>
    </div>
</div>

