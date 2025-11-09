<div>
    <x-dialog-modal wire:model.live="showing" maxWidth="4xl">
        <x-slot name="title">
            <div class="flex items-center space-x-2">
                <div class="flex-shrink-0">
                    {!! $document->getIconSvg('w-6 h-6') !!}
                </div>
                <span>{{ $document->name }}</span>
            </div>
        </x-slot>

        <x-slot name="content">
            <div class="space-y-4">
                <div>
                    <dl class="grid grid-cols-1 gap-x-4 gap-y-2 sm:grid-cols-2 text-sm">
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">Filename</dt>
                            <dd class="mt-1 text-gray-900 dark:text-gray-100">{{ $document->filename }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">Size</dt>
                            <dd class="mt-1 text-gray-900 dark:text-gray-100">{{ number_format($document->size / 1024, 2) }} KB</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">Type</dt>
                            <dd class="mt-1 text-gray-900 dark:text-gray-100">{{ $document->mime_type }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">Status</dt>
                            <dd class="mt-1">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                    @if($document->upload_status === 'completed') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                    @elseif($document->upload_status === 'failed') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                                    @else bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                                    @endif">
                                    {{ ucfirst($document->upload_status) }}
                                </span>
                            </dd>
                        </div>
                    </dl>
                </div>

                @if($document->upload_status === 'uploading' || $document->upload_status === 'processing')
                    <div>
                        @include('afterburner-documents::components.progress-bar', ['progress' => $document->upload_progress])
                    </div>
                @endif

                @if($versions->count() > 0)
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-2">Version History</h3>
                        <div class="space-y-2">
                            @foreach($versions as $version)
                                <div class="flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-700 rounded">
                                    <div>
                                        <span class="text-sm font-medium">Version {{ $version->version_number }}</span>
                                        <span class="text-xs text-gray-500 dark:text-gray-400 ml-2">
                                            {{ $version->getFormattedCreatedAt('Y-m-d H:i') }} by {{ $version->creator->name }}
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="close">
                Close
            </x-secondary-button>
            @can('update', $document)
                <x-button wire:click="openEditModal" class="ml-3" no-spinner>
                    Edit
                </x-button>
            @endcan
            @can('delete', $document)
                <x-danger-button wire:click="confirmDelete" class="ml-3">
                    Delete
                </x-danger-button>
            @endcan
            @can('download', $document)
                <x-button wire:click="download" class="ml-3">
                    Download
                </x-button>
            @endcan
        </x-slot>
    </x-dialog-modal>

    <!-- Document Edit Modal -->
    <x-dialog-modal wire:model.live="showingEditModal" maxWidth="2xl">
        <x-slot name="title">
            Edit Document
        </x-slot>

        <x-slot name="content">
            <div class="space-y-4">
                <div>
                    <x-label for="documentName" value="Document Name" />
                    <x-input
                        id="documentName"
                        type="text"
                        class="mt-1 block w-full"
                        wire:model="documentName"
                        autofocus
                    />
                    <x-input-error for="documentName" class="mt-2" />
                </div>

                <div>
                    <x-label for="newFile" value="Upload New Version (Optional)" />
                    <x-input
                        id="newFile"
                        type="file"
                        class="mt-1 block w-full"
                        wire:model="newFile"
                    />
                    <x-input-error for="newFile" class="mt-2" />
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Leave empty to keep the current file. Uploading a new file will create a new version.
                    </p>
                </div>
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="closeEditModal">
                Cancel
            </x-secondary-button>
            <x-button wire:click="updateDocument" class="ml-3" no-spinner>
                Update
            </x-button>
        </x-slot>
    </x-dialog-modal>

    <!-- Document Delete Confirmation Modal -->
    <x-confirmation-modal wire:model.live="showingDeleteModal">
        <x-slot name="title">
            Delete Document
        </x-slot>

        <x-slot name="content">
            Are you sure you want to delete "{{ $document->name }}"? This action cannot be undone.
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="cancelDelete">
                Cancel
            </x-secondary-button>
            <x-danger-button wire:click="deleteDocument" class="ml-3">
                Delete
            </x-danger-button>
        </x-slot>
    </x-confirmation-modal>
</div>

