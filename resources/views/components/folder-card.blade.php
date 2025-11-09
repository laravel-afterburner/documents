@props(['folder'])

<div class="group relative bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4 hover:shadow-md transition-shadow">
    <a
        href="{{ route('teams.documents.folder', [$folder->team, $folder->slug]) }}"
        class="block"
    >
        <div class="flex items-center space-x-3">
            <svg class="w-8 h-8 text-yellow-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"></path>
            </svg>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">
                    {{ $folder->name }}
                </p>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    {{ $folder->documents()->count() }} documents
                </p>
            </div>
            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
            </svg>
        </div>
    </a>
    
    @can('update', $folder)
        <div class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity">
            <div class="flex gap-1">
                <button
                    type="button"
                    wire:click="openEditFolderModal({{ $folder->id }})"
                    class="p-1.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded"
                    title="Edit folder"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                    </svg>
                </button>
                @can('delete', $folder)
                    <button
                        type="button"
                        wire:click="confirmDelete({{ $folder->id }})"
                        class="p-1.5 text-gray-400 hover:text-red-600 dark:hover:text-red-400 rounded"
                        title="Delete folder"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                    </button>
                @endcan
            </div>
        </div>
    @endcan
</div>

