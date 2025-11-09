<div>

    <div>
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Breadcrumbs -->
            @if($currentFolder)
                <div class="mb-4">
                    @include('afterburner-documents::components.breadcrumbs', ['folder' => $currentFolder])
                </div>
            @endif

            <!-- Action Buttons -->
            <div class="mb-6 flex gap-2 justify-end">
                @can('create', [\Afterburner\Documents\Models\Document::class, $team])
                    <x-button
                        wire:click="openUploadModal"
                        no-spinner
                    >
                        Upload Document
                    </x-button>
                @endcan
                @can('create', [\Afterburner\Documents\Models\Folder::class, $team])
                    <x-button
                        wire:click="openFolderModal"
                        no-spinner
                    >
                        New Folder
                    </x-button>
                @endcan
            </div>

            <!-- Filters -->
            <div class="mb-6 bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Search Query -->
                    <div>
                        <label for="searchQuery" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Search
                        </label>
                        <input
                            type="text"
                            wire:model.live.debounce.300ms="searchQuery"
                            id="searchQuery"
                            placeholder="Search documents..."
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
                        />
                    </div>

                    <!-- Folder Filter -->
                    <div>
                        <label for="folderFilter" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Folder
                        </label>
                        <select
                            wire:model.live="folderFilter"
                            id="folderFilter"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
                        >
                            <option value="">All Folders</option>
                            @foreach($allFolders as $folderOption)
                                <option value="{{ $folderOption->id }}">{{ $folderOption->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Status Filter -->
                    <div>
                        <label for="statusFilter" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Status
                        </label>
                        <select
                            wire:model.live="statusFilter"
                            id="statusFilter"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
                        >
                            <option value="">All Statuses</option>
                            @foreach($statuses as $status)
                                <option value="{{ $status }}">{{ ucfirst($status) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- MIME Type Filter -->
                    <div>
                        <label for="mimeTypeFilter" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            File Type
                        </label>
                        <select
                            wire:model.live="mimeTypeFilter"
                            id="mimeTypeFilter"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
                        >
                            <option value="">All Types</option>
                            @foreach($mimeTypes as $mimeType)
                                <option value="{{ $mimeType }}">{{ $mimeType }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Date From -->
                    <div>
                        <label for="dateFrom" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            From Date
                        </label>
                        <input
                            type="date"
                            wire:model.live="dateFrom"
                            id="dateFrom"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
                        />
                    </div>

                    <!-- Date To -->
                    <div>
                        <label for="dateTo" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            To Date
                        </label>
                        <input
                            type="date"
                            wire:model.live="dateTo"
                            id="dateTo"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
                        />
                    </div>

                    <!-- Clear Filters Button -->
                    <div class="flex items-end">
                        <button
                            wire:click="clearFilters"
                            type="button"
                            class="w-full inline-flex justify-center items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-600"
                        >
                            Clear Filters
                        </button>
                    </div>
                </div>
            </div>

            <!-- Documents and Folders Grid -->
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
                <div class="p-6">
                    @if($folders->count() > 0 || $documents->count() > 0)
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                            @foreach($folders as $folder)
                                @include('afterburner-documents::components.folder-card', ['folder' => $folder])
                            @endforeach

                            <div wire:poll.5s style="display: contents;">
                            @foreach($documents as $document)
                                @include('afterburner-documents::components.document-card', ['document' => $document])
                            @endforeach
                            </div>
                        </div>

                        <!-- Pagination -->
                        <div class="mt-6">
                            {{ $documents->links() }}
                        </div>
                    @else
                        <div class="text-center py-12">
                            <p class="text-gray-500 dark:text-gray-400">No documents or folders found.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Upload Modal -->
    <x-dialog-modal wire:model.live="showingUploadModal" maxWidth="2xl">
        <x-slot name="title">
            Upload Document
        </x-slot>

        <x-slot name="content">
            <div>
                <x-filepond::upload 
                    wire:model="uploadFiles"
                    multiple
                    :max-file-size="config('afterburner-documents.upload.max_file_size', 2147483648)"
                    :accepted-file-types="config('afterburner-documents.upload.allowed_mime_types', [])"
                />
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="closeUploadModal">
                Cancel
            </x-secondary-button>
        </x-slot>
    </x-dialog-modal>

    <!-- Folder Creation Modal -->
    <x-dialog-modal wire:model.live="showingFolderModal" maxWidth="md">
        <x-slot name="title">
            Create Folder
        </x-slot>

        <x-slot name="content">
            <div>
                <x-label for="folderName" value="Folder Name" />
                <x-input
                    id="folderName"
                    type="text"
                    class="mt-1 block w-full"
                    wire:model="folderName"
                    autofocus
                />
                <x-input-error for="folderName" class="mt-2" />
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="closeFolderModal">
                Cancel
            </x-secondary-button>
            <x-button wire:click="createFolder" class="ml-3" no-spinner>
                Create
            </x-button>
        </x-slot>
    </x-dialog-modal>

    <!-- Folder Edit Modal -->
    <x-dialog-modal wire:model.live="showingEditFolderModal" maxWidth="md">
        <x-slot name="title">
            Edit Folder
        </x-slot>

        <x-slot name="content">
            <div>
                <x-label for="editFolderName" value="Folder Name" />
                <x-input
                    id="editFolderName"
                    type="text"
                    class="mt-1 block w-full"
                    wire:model="folderName"
                    autofocus
                />
                <x-input-error for="folderName" class="mt-2" />
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="closeEditFolderModal">
                Cancel
            </x-secondary-button>
            <x-button wire:click="updateFolder" class="ml-3" no-spinner>
                Update
            </x-button>
        </x-slot>
    </x-dialog-modal>

    <!-- Folder Delete Confirmation Modal -->
    <x-confirmation-modal wire:model.live="showingDeleteModal">
        <x-slot name="title">
            Delete Folder
        </x-slot>

        <x-slot name="content">
            Are you sure you want to delete this folder? This action cannot be undone.
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="cancelDelete">
                Cancel
            </x-secondary-button>
            <x-danger-button wire:click="deleteFolder" class="ml-3">
                Delete
            </x-danger-button>
        </x-slot>
    </x-confirmation-modal>

    <!-- Document Viewer -->
    @if($viewingDocumentId)
        @php
            $viewingDocument = \Afterburner\Documents\Models\Document::find($viewingDocumentId);
        @endphp
        @if($viewingDocument)
            @livewire('documents.document-viewer', ['document' => $viewingDocument, 'autoOpen' => true], key('document-viewer-'.$viewingDocumentId))
        @endif
    @endif
</div>

