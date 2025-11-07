@props(['document'])

<div class="group relative overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm transition-shadow hover:shadow-md">
    <div class="p-4">
        <div class="flex items-start justify-between">
            <div class="flex-1">
                <input type="checkbox" 
                       onclick="window.Livewire.find(document.querySelector('[wire\\:id]').getAttribute('wire:id')).dispatch('selectDocument', { id: {{ $document->id }} })"
                       class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
            </div>
            <div class="ml-3 flex-1">
                <h3 class="text-sm font-medium text-gray-900 line-clamp-2">
                    <a href="{{ route('documents.show', ['team' => $document->team_id, 'document' => $document->id]) }}" 
                       class="hover:text-indigo-600">
                        {{ $document->name }}
                    </a>
                </h3>
                <p class="mt-1 text-xs text-gray-500">{{ $document->original_filename }}</p>
            </div>
        </div>
        
        <div class="mt-3 flex items-center justify-between">
            <div class="flex items-center space-x-2 text-xs text-gray-500">
                @if($document->isImage())
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                @elseif($document->isPdf())
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                    </svg>
                @else
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                @endif
                <span>{{ $document->getFileSizeHuman() }}</span>
            </div>
            <div class="text-xs text-gray-500">
                {{ $document->created_at->diffForHumans() }}
            </div>
        </div>
    </div>
    
    <!-- Hover Actions -->
    <div class="absolute inset-x-0 bottom-0 flex items-center justify-center space-x-2 bg-gray-50 p-2 opacity-0 transition-opacity group-hover:opacity-100">
        @can('view', $document)
            <a href="{{ route('documents.show', ['team' => $document->team_id, 'document' => $document->id]) }}" 
               class="rounded-md bg-indigo-600 px-3 py-1 text-xs font-medium text-white hover:bg-indigo-700">
                View
            </a>
        @endcan
        @can('download', $document)
            <a href="{{ route('documents.download', ['team' => $document->team_id, 'document' => $document->id]) }}" 
               class="rounded-md bg-gray-600 px-3 py-1 text-xs font-medium text-white hover:bg-gray-700">
                Download
            </a>
        @endcan
    </div>
</div>

