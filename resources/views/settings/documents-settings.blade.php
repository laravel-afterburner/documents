<div>
    <x-form-section submit="save">
        <x-slot name="title">
            Documents
        </x-slot>

        <x-slot name="description">
            Configure document management options for this {{ entity_label() }}.
        </x-slot>

        <x-slot name="form">
            <div class="col-span-6 space-y-6">
                <label class="flex items-start gap-3">
                    <input
                        type="checkbox"
                        wire:model="retentionTagsEnabled"
                        class="mt-1 rounded border-gray-300 dark:border-gray-700 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-900"
                    />
                    <span>
                        <span class="block text-sm font-medium text-gray-900 dark:text-gray-100">
                            Enable retention tags
                        </span>
                        <span class="block mt-1 text-sm text-gray-500 dark:text-gray-400">
                            When enabled, users can manage retention tags and assign them to documents. Documents with active retention cannot be deleted until the retention period expires.
                        </span>
                    </span>
                </label>

                <label class="flex items-start gap-3">
                    <input
                        type="checkbox"
                        wire:model="versioningEnabled"
                        class="mt-1 rounded border-gray-300 dark:border-gray-700 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-900"
                    />
                    <span>
                        <span class="block text-sm font-medium text-gray-900 dark:text-gray-100">
                            Enable version control
                        </span>
                        <span class="block mt-1 text-sm text-gray-500 dark:text-gray-400">
                            When enabled, clicking a document opens the details modal with version history and restore options. When disabled, document names are not clickable.
                        </span>
                    </span>
                </label>
            </div>
        </x-slot>

        <x-slot name="actions">
            <x-action-message class="me-3" on="saved">
                {{ __('Saved.') }}
            </x-action-message>

            <x-button wire:loading.attr="disabled">
                {{ __('Save') }}
            </x-button>
        </x-slot>
    </x-form-section>
</div>
