<x-app-layout :title="\Afterburner\Documents\Support\PageHeader::make('Documents')">
    @include('afterburner-documents::components.filepond-assets')

    <x-slot name="header">
        <x-afterburner-documents::page-header section="Documents" />
    </x-slot>

    <div>
        <div class="max-w-7xl mx-auto px-4 py-6 sm:py-10 sm:px-6 lg:px-8">
            @livewire('documents.index', ['team' => $team, 'folder_slug' => $folder_slug ?? null])
        </div>
    </div>
</x-app-layout>
