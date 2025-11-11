@props(['document'])

<button
    type="button"
    wire:click="openDocumentViewer({{ $document->id }})"
    class="w-full text-left bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4 hover:shadow-md transition-shadow cursor-pointer focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
>
    <div class="flex items-start justify-between">
        <div class="flex-1 min-w-0">
            <div class="flex items-center space-x-2">
                {!! $document->getIconSvg() !!}
                <div class="flex-1 min-w-0">
                    <div class="flex items-center space-x-2">
                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">
                            {{ $document->name }}
                        </p>
                        @if($document->getCurrentVersionNumber())
                            <span class="px-1.5 py-0.5 text-xs font-medium rounded bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                v{{ $document->getCurrentVersionNumber() }}
                            </span>
                        @endif
                        @if($document->retentionTag)
                            <span 
                                class="px-1.5 py-0.5 text-xs font-medium rounded"
                                style="background-color: {{ $document->retentionTag->color }}20; color: {{ $document->retentionTag->color }};"
                                title="Retention: {{ $document->retentionTag->name }} (expires {{ $document->retention_expires_at->format('Y-m-d') }})"
                            >
                                {{ $document->retentionTag->name }}
                            </span>
                        @endif
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                        {{ $document->filename }}
                    </p>
                    @if($document->notes)
                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-1 truncate" title="{{ $document->notes }}">
                            {{ Str::limit($document->notes, 50) }}
                        </p>
                    @endif
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
        <span>{{ $document->getFormattedCreatedAt('M d, Y') }}</span>
        <span>{{ $document->uploader->name }}</span>
    </div>
</button>

