@props(['folder'])

<a
    href="{{ route('teams.documents.folder', [$folder->team, $folder->slug]) }}"
    class="block bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4 hover:shadow-md transition-shadow"
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

