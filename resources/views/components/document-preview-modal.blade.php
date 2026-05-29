@props([
    'team',
    'document',
    'previewUrl',
    'closeAction' => 'closePreview',
])

<x-dialog-modal wire:model.live="showPreviewModal" maxWidth="5xl">
    <x-slot name="title">
        {{ $document->name }}
    </x-slot>

    <x-slot name="content">
        @include('afterburner-documents::components.document-preview-frame', [
            'document' => $document,
            'previewUrl' => $previewUrl,
        ])
    </x-slot>

    <x-slot name="footer">
        <x-secondary-button wire:click="{{ $closeAction }}">
            Close
        </x-secondary-button>
        @can('download', $document)
            <a href="{{ route('teams.documents.download', ['team' => $team, 'document' => $document]) }}"
               class="ms-3 inline-flex items-center px-4 py-2 bg-gray-800 dark:bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-white dark:text-gray-800 uppercase tracking-widest hover:bg-gray-700 dark:hover:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                Download
            </a>
        @endcan
    </x-slot>
</x-dialog-modal>
