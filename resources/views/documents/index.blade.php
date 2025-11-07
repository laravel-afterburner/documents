<x-app-layout title="{{ Str::title(config('afterburner.entity_label')) }} Documents">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ Str::title(config('afterburner.entity_label')) }} Documents
        </h2>
    </x-slot>

    <div>
        <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
            @livewire('documents.document-manager')
        </div>
    </div>
</x-app-layout>