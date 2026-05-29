@props(['document', 'allFolders'])

@if($document->folder_id)
    @php
        $folderPath = \Afterburner\Documents\Models\Folder::pathFromCollection($allFolders, $document->folder_id);
    @endphp

    <div class="mt-1 flex items-center gap-2 min-w-0">
        <svg class="w-3.5 h-3.5 text-yellow-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
            <path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"></path>
        </svg>
        <span class="text-xs text-gray-400 dark:text-gray-500 truncate" title="{{ collect($folderPath)->pluck('name')->implode(' / ') }}">
            @foreach($folderPath as $pathFolder)
                {{ $pathFolder->name }}@if(!$loop->last)<span class="text-gray-300 dark:text-gray-600"> / </span>@endif
            @endforeach
        </span>
        <button
            type="button"
            wire:click="viewDocumentInFolder({{ $document->folder_id }})"
            class="flex-shrink-0 text-xs font-medium text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300"
        >
            View folder
        </button>
    </div>
@endif
