@props(['document'])

@if(in_array($document->upload_status, ['uploading', 'processing'], true))
    @php
        $tooltip = $document->upload_status === 'processing'
            ? __('Saving to cloud storage…')
            : __('Uploading to server… :progress%', ['progress' => $document->upload_progress]);

        $label = $document->upload_status === 'processing'
            ? __('Saving to cloud')
            : __('Uploading');
    @endphp

    <span
        class="inline-flex flex-shrink-0 items-center text-indigo-500 dark:text-indigo-400"
        title="{{ $tooltip }}"
        aria-label="{{ $label }}"
    >
        @if($document->upload_status === 'processing')
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
            </svg>
        @else
            <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        @endif
    </span>
@endif
