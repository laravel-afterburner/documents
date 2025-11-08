<x-app-layout title="Documents">
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                Documents
            </h2>
            <div class="flex gap-2" x-data>
                @can('create', [\Afterburner\Documents\Models\Document::class, $team])
                    <x-button
                        @click="$dispatch('open-upload-modal')"
                    >
                        Upload
                    </x-button>
                @endcan
                @can('create', [\Afterburner\Documents\Models\Folder::class, $team])
                    <x-button
                        @click="$dispatch('open-folder-modal')"
                    >
                        New Folder
                    </x-button>
                @endcan
            </div>
        </div>
    </x-slot>

    <div>
        <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
            @livewire('documents.index', ['team' => $team, 'folder_slug' => $folder_slug ?? null])
        </div>
    </div>
</x-app-layout>

