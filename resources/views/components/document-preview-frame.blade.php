@props([
    'document',
    'previewUrl',
])

<div {{ $attributes->merge(['class' => 'overflow-hidden rounded-md border border-gray-200 bg-gray-50 dark:border-gray-600 dark:bg-gray-900']) }}>
    @if ($document->isImagePreview())
        <img src="{{ $previewUrl }}"
             alt="{{ $document->name }}"
             class="mx-auto max-h-[70vh] w-full object-contain">
    @else
        <iframe src="{{ $previewUrl }}"
                title="{{ $document->name }}"
                class="h-[70vh] w-full bg-white dark:bg-gray-900"></iframe>
    @endif
</div>
