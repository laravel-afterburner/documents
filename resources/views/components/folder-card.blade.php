@props(['folder'])

<div class="group relative overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm transition-shadow hover:shadow-md">
    <div class="p-4">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <svg class="h-8 w-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path>
                </svg>
            </div>
            <div class="ml-4 flex-1">
                <h3 class="text-sm font-medium text-gray-900">
                    <a wire:click="navigateToFolder({{ $folder->id }})" 
                       class="cursor-pointer hover:text-indigo-600">
                        {{ $folder->name }}
                    </a>
                </h3>
                @if($folder->description)
                    <p class="mt-1 text-xs text-gray-500 line-clamp-2">{{ $folder->description }}</p>
                @endif
                <p class="mt-1 text-xs text-gray-500">
                    {{ $folder->documents()->count() }} document(s)
                </p>
            </div>
        </div>
    </div>
    
    <!-- Hover Actions -->
    <div class="absolute inset-x-0 bottom-0 flex items-center justify-center space-x-2 bg-gray-50 p-2 opacity-0 transition-opacity group-hover:opacity-100">
        <button wire:click="navigateToFolder({{ $folder->id }})" 
                class="rounded-md bg-indigo-600 px-3 py-1 text-xs font-medium text-white hover:bg-indigo-700">
            Open
        </button>
        @can('update', $folder)
            <button wire:click="$dispatch('openEditFolderModal', { folderId: {{ $folder->id }} })" 
                    class="rounded-md bg-gray-600 px-3 py-1 text-xs font-medium text-white hover:bg-gray-700">
                Edit
            </button>
        @endcan
    </div>
</div>

