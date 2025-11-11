<div>
    <x-dialog-modal wire:model.live="showing" maxWidth="4xl">
        <x-slot name="title">
            <div class="flex items-center space-x-2">
                <div class="flex-shrink-0">
                    {!! $document->getIconSvg('w-6 h-6') !!}
                </div>
                <div class="flex items-center space-x-2">
                    <span>{{ $document->name }}</span>
                    @if($document->getCurrentVersionNumber())
                        <span class="px-1.5 py-0.5 text-xs font-medium rounded bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                            v{{ $document->getCurrentVersionNumber() }}
                        </span>
                    @endif
                </div>
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

                @if($document->notes)
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Notes</dt>
                        <dd class="text-sm text-gray-900 dark:text-gray-100 whitespace-pre-wrap bg-gray-50 dark:bg-gray-700 rounded-md p-3">
                            {{ $document->notes }}
                        </dd>
                    </div>
                @endif

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
                                        @if($version->version_number === $document->getCurrentVersionNumber())
                                            <span class="ml-2 px-1.5 py-0.5 text-xs font-medium rounded bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                                Current
                                            </span>
                                        @endif
                                    </div>
                                    @if($version->version_number !== $document->getCurrentVersionNumber())
                                        @can('restoreVersion', $document)
                                            <x-button 
                                                wire:click="confirmRestoreVersion({{ $version->id }})" 
                                                size="sm"
                                                class="ml-2"
                                            >
                                                Restore
                                            </x-button>
                                        @endcan
                                    @endif
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
                    <x-label for="documentNotes" value="Notes (Optional)" />
                    <textarea
                        id="documentNotes"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
                        wire:model="documentNotes"
                        rows="3"
                        placeholder="Add notes about this document..."
                    ></textarea>
                    <x-input-error for="documentNotes" class="mt-2" />
                </div>

                <div>
                    <x-label for="newFile" value="Upload New Version (Optional)" />
                    <x-filepond::upload 
                        wire:model="newFile"
                        :max-file-size="config('afterburner-documents.upload.max_file_size', 2147483648)"
                        :accepted-file-types="config('afterburner-documents.upload.allowed_mime_types', [])"
                    />
                    <x-input-error for="newFile" class="mt-2" />
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Leave empty to keep the current file. Uploading a new file will create a new version and automatically update the document.
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

    <!-- Restore Version Confirmation Modal -->
    <x-confirmation-modal wire:model.live="showingRestoreVersionModal">
        <x-slot name="title">
            Restore Version
        </x-slot>

        <x-slot name="content">
            @if($versionToRestore)
                Are you sure you want to restore Version {{ $versionToRestore->version_number }} of "{{ $document->name }}"?
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    The current version will be saved as a new version before restoring. This action cannot be undone.
                </p>
            @endif
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="cancelRestoreVersion">
                Cancel
            </x-secondary-button>
            <x-button wire:click="restoreVersion" class="ml-3">
                Restore
            </x-button>
        </x-slot>
    </x-confirmation-modal>
</div>

