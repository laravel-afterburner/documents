@php
    $level = $level ?? 0;
    $selectedId = $selectedId ?? null;
    $indentSize = 24; // pixels per level
@endphp

@foreach($folders as $folder)
    <div class="folder-tree-item py-1">
        <label class="flex items-center cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 rounded px-2 py-1.5 transition-colors {{ ($selectedId && $selectedId == $folder->id) ? 'bg-indigo-50 dark:bg-indigo-900/20' : '' }}" style="padding-left: {{ ($level * $indentSize) + 8 }}px;">
            <input 
                type="radio" 
                wire:model="selectedTargetFolderId" 
                value="{{ $folder->id }}"
                class="mr-3 h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 dark:border-gray-600 flex-shrink-0"
            >
            <div class="flex items-center flex-1 min-w-0">
                @if($level > 0)
                    <svg class="w-4 h-4 text-gray-400 mr-1.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                @endif
                <svg class="w-5 h-5 text-yellow-500 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"></path>
                </svg>
                <span class="text-sm text-gray-900 dark:text-gray-100 truncate">{{ $folder->name }}</span>
            </div>
        </label>
        
        @if($folder->children && $folder->children->count() > 0)
            <div class="mt-1">
                @include('afterburner-documents::components.folder-tree', [
                    'folders' => $folder->children,
                    'level' => $level + 1,
                    'selectedId' => $selectedId
                ])
            </div>
        @endif
    </div>
@endforeach

