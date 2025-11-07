<div>
    <div class="mb-6 flex items-center justify-between">
        <div>
            @if($currentFolder)
                <nav class="mt-2 flex items-center space-x-2 text-sm text-gray-600">
                    <a wire:click="navigateToRoot" class="cursor-pointer hover:text-gray-900">Home</a>
                    @foreach($breadcrumbs as $breadcrumb)
                        <span>/</span>
                        @if($loop->last)
                            <span class="text-gray-900">{{ $breadcrumb->name }}</span>
                        @else
                            <a wire:click="navigateToFolder({{ $breadcrumb->id }})" class="cursor-pointer hover:text-gray-900">{{ $breadcrumb->name }}</a>
                        @endif
                    @endforeach
                </nav>
            @endif
        </div>
        <div class="flex items-center space-x-3">
            @if(count($selectedDocuments) > 0)
                <x-danger-button wire:click="confirmBulkDelete" 
                                 type="button">
                    Delete Selected ({{ count($selectedDocuments) }})
                </x-danger-button>
            @endif
            @if($currentFolder)
                @can('update', $currentFolder)
                    <x-button wire:click="openEditFolderModal({{ $currentFolder->id }})" 
                              type="button">
                        Edit Folder
                    </x-button>
                @endcan
            @endif
            <x-button wire:click="$dispatch('openCreateFolderModal', { parentId: {{ $currentFolder?->id ?? 'null' }} })"
                      type="button">
                New Folder
            </x-button>

            <x-button wire:click="openModal" type="button">
                Upload Document
            </x-button>
        </div>
    </div>

    <!-- Filters -->
    <div class="mb-6 bg-white dark:bg-gray-800 shadow rounded-lg p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Search Query -->
            <div>
                <label for="search" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Search
                </label>
                <input
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    id="search"
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
                    <option value="null">Root (No Folder)</option>
                    @foreach($allFolders as $folder)
                        <option value="{{ $folder->id }}">{{ $folder->name }}</option>
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
                    <option value="application/pdf">PDF</option>
                    <option value="image/">Images</option>
                    <option value="application/vnd.openxmlformats-officedocument.wordprocessingml.document">Word</option>
                    <option value="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet">Excel</option>
                    <option value="application/vnd.openxmlformats-officedocument.presentationml.presentation">PowerPoint</option>
                    <option value="text/plain">Text</option>
                    <option value="application/zip">ZIP Archive</option>
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

            <!-- Sort By -->
            <div>
                <label for="sortBy" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Sort By
                </label>
                <select
                    wire:model.live="sortBy"
                    id="sortBy"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
                >
                    <option value="created_at">Date</option>
                    <option value="name">Name</option>
                    <option value="file_size">Size</option>
                </select>
            </div>

            <!-- Sort Order -->
            <div>
                <label for="sortOrder" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Order
                </label>
                <select
                    wire:model.live="sortOrder"
                    id="sortOrder"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
                >
                    <option value="desc">Descending</option>
                    <option value="asc">Ascending</option>
                </select>
            </div>

            <!-- Clear Filters Button -->
            <div class="flex items-end">
                <x-secondary-button
                    wire:click="clearFilters"
                    type="button"
                    class="w-full"
                >
                    Clear Filters
                </x-secondary-button>
            </div>
        </div>
    </div>

    <!-- Folders -->
    @if($folders->count() > 0)
        <div class="mb-6">
            <h3 class="mb-3 text-sm font-medium text-gray-700">Folders</h3>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach($folders as $folder)
                    <div class="group relative overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm transition-shadow hover:shadow-md cursor-pointer"
                         wire:click="navigateToFolder({{ $folder->id }})">
                        <div class="p-4">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="h-8 w-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path>
                                    </svg>
                                </div>
                                <div class="ml-4 flex-1">
                                    <h3 class="text-sm font-medium text-gray-900">
                                        {{ $folder->name }}
                                    </h3>
                                    @if($folder->description)
                                        <p class="mt-1 text-xs text-gray-500 line-clamp-2">{{ $folder->description }}</p>
                                    @endif
                                    <p class="mt-1 text-xs text-gray-500">
                                        {{ $folder->documents()->count() }} document(s)
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Documents -->
    <div>
        <h3 class="mb-3 text-sm font-medium text-gray-700">Documents</h3>
        @if($documents->count() > 0)
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach($documents as $document)
                    <div class="group relative overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm transition-shadow hover:shadow-md">
                        <div class="p-4">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <input type="checkbox" 
                                           wire:click="selectDocument({{ $document->id }})"
                                           class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                </div>
                                <div class="ml-3 flex-1">
                                    <h3 class="text-sm font-medium text-gray-900 line-clamp-2">
                                        <a href="{{ route('documents.show', ['team' => $document->team_id, 'document' => $document->id]) }}" 
                                           class="hover:text-indigo-600">
                                            {{ $document->name }}
                                        </a>
                                    </h3>
                                    <p class="mt-1 text-xs text-gray-500">{{ $document->original_filename }}</p>
                                </div>
                            </div>
                            
                            <div class="mt-3 flex items-center justify-between">
                                <div class="flex items-center space-x-2 text-xs text-gray-500">
                                    @if($document->isImage())
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                        </svg>
                                    @elseif($document->isPdf())
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                        </svg>
                                    @else
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                    @endif
                                    <span>{{ $document->getFileSizeHuman() }}</span>
                                </div>
                                <div class="text-xs text-gray-500">
                                    {{ $document->created_at->diffForHumans() }}
                                </div>
                            </div>
                        </div>
                        
                        <!-- Hover Actions -->
                        <div class="absolute inset-x-0 bottom-0 flex items-center justify-center space-x-2 bg-gray-50 p-2 opacity-0 transition-opacity group-hover:opacity-100">
                            @can('view', $document)
                                <a href="{{ route('documents.show', ['team' => $document->team_id, 'document' => $document->id]) }}" 
                                   class="rounded-md bg-indigo-600 px-3 py-1 text-xs font-medium text-white hover:bg-indigo-700">
                                    View
                                </a>
                            @endcan
                            @can('download', $document)
                                <a href="{{ route('documents.download', ['team' => $document->team_id, 'document' => $document->id]) }}" 
                                   class="rounded-md bg-gray-600 px-3 py-1 text-xs font-medium text-white hover:bg-gray-700">
                                    Download
                                </a>
                            @endcan
                            @can('delete', $document)
                                <button wire:click.stop="confirmDelete({{ $document->id }})" 
                                        class="rounded-md bg-red-600 px-3 py-1 text-xs font-medium text-white hover:bg-red-700">
                                    Delete
                                </button>
                            @endcan
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="mt-6">
                {{ $documents->links() }}
            </div>
        @else
            <div class="rounded-lg border-2 border-dashed border-gray-300 p-12 text-center">
                <p class="text-gray-500">No documents found.</p>
            </div>
        @endif
    </div>

    <!-- Upload Modal -->
    @if($showUploadModal || $isUploading)
        <div x-data="{
            chunkSize: {{ $chunkSize }},
            uploadId: null,
            isUploading: @entangle('isUploading'),
            uploadProgress: @entangle('uploadProgress'),
            cancelUpload: false,
            
            init() {
                // Watch for chunked upload trigger
                this.$watch('isUploading', (value) => {
                    if (value && !this.uploadId) {
                        // Chunked upload was triggered, start the upload process
                        const fileInput = this.$refs.fileInput;
                        const nameInput = this.$refs.nameInput;
                        if (fileInput && fileInput.files[0]) {
                            this.uploadFile(
                                fileInput.files[0],
                                nameInput.value,
                                @js($folderId),
                                @js($team->id)
                            );
                        }
                    }
                });
            },
            
            async handleUpload() {
                // Check if file is available via Livewire
                if (!$wire.file) {
                    $wire.addError('file', 'Please select a file to upload.');
                    return;
                }
                
                // Dispatch a custom event to trigger upload without syncing file inputs
                $wire.dispatch('processUpload');
            },
            
            async uploadFile(file, name, folderId, teamId) {
                if (!file) return;
                
                const fileSize = file.size;
                
                // Chunked upload - already triggered by Livewire setting isUploading = true
                this.uploadProgress = 0;
                this.cancelUpload = false;
                
                try {
                    const totalChunks = Math.ceil(fileSize / this.chunkSize);
                    
                    // Initiate upload session
                    const initiateResponse = await fetch('{{ route('upload.initiate') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            filename: file.name,
                            total_chunks: totalChunks,
                            total_size: fileSize,
                            team_id: teamId
                        })
                    });
                    
                    if (!initiateResponse.ok) {
                        const error = await initiateResponse.json();
                        throw new Error(error.message || 'Failed to initiate upload');
                    }
                    
                    const session = await initiateResponse.json();
                    this.uploadId = session.upload_id;
                    @this.set('uploadId', session.upload_id);
                    
                    // Upload chunks
                    for (let chunkNumber = 0; chunkNumber < totalChunks; chunkNumber++) {
                        if (this.cancelUpload) {
                            throw new Error('Upload cancelled');
                        }
                        
                        const start = chunkNumber * this.chunkSize;
                        const end = Math.min(start + this.chunkSize, fileSize);
                        const chunk = file.slice(start, end);
                        
                        const formData = new FormData();
                        formData.append('upload_id', session.upload_id);
                        formData.append('chunk_number', chunkNumber);
                        formData.append('chunk', chunk, file.name);
                        
                        const chunkResponse = await fetch('{{ route('upload.chunk') }}', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '',
                                'Accept': 'application/json'
                            },
                            body: formData
                        });
                        
                        if (!chunkResponse.ok) {
                            const error = await chunkResponse.json();
                            throw new Error(error.error || 'Failed to upload chunk ' + chunkNumber);
                        }
                        
                        const result = await chunkResponse.json();
                        this.uploadProgress = Math.round((result.uploaded_chunks / totalChunks) * 100);
                        @this.set('uploadProgress', this.uploadProgress);
                    }
                    
                    // Get final path from Livewire
                    const finalPath = await $wire.generateFinalPath(file.name);
                    
                    // Complete upload
                    const completeResponse = await fetch('{{ route('upload.complete') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            upload_id: session.upload_id,
                            final_path: finalPath
                        })
                    });
                    
                    if (!completeResponse.ok) {
                        const error = await completeResponse.json();
                        throw new Error(error.error || 'Failed to complete upload');
                    }
                    
                    const completeResult = await completeResponse.json();
                    
                    // Create document via Livewire
                    @this.createDocumentFromChunkedUpload({
                        name: name,
                        folderId: folderId,
                        filename: completeResult.filename,
                        originalFilename: completeResult.filename,
                        mimeType: file.type,
                        fileSize: completeResult.size,
                        storagePath: completeResult.path
                    });
                    
                } catch (error) {
                    console.error('Upload error:', error);
                    if (this.uploadId && !this.cancelUpload) {
                        // Cancel upload session
                        try {
                            await fetch('{{ route('upload.cancel') }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '',
                                    'Accept': 'application/json'
                                },
                                body: JSON.stringify({
                                    upload_id: this.uploadId
                                })
                            });
                        } catch (e) {
                            console.error('Failed to cancel upload:', e);
                        }
                    }
                    @this.addError('upload', error.message || 'Failed to upload document');
                    this.isUploading = false;
                    @this.set('isUploading', false);
                }
            },
            
            cancel() {
                this.cancelUpload = true;
                @this.cancel();
            }
        }" 
             x-show="true"
             class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex min-h-screen items-end justify-center px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" 
                     wire:click="closeModal"
                     @if(!$isUploading)
                     @click="$wire.closeModal()"
                     @endif></div>
                <div class="inline-block transform overflow-hidden rounded-lg bg-white dark:bg-gray-800 text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:align-middle">
                    <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-gray-100 mb-4">Upload Document</h3>
                        
                        <div>
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Document Name</label>
                                <input type="text" 
                                       x-ref="nameInput"
                                       wire:model="name"
                                       class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                       required>
                                @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>

                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">File</label>
                                <input type="file" 
                                       x-ref="fileInput"
                                       wire:model="file"
                                       wire:loading.attr="disabled"
                                       wire:target="file"
                                       accept="*"
                                       class="mt-1 block w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 dark:file:bg-indigo-900 dark:file:text-indigo-200">
                                @error('file') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                <div wire:loading wire:target="file" class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                    Processing file...
                                </div>
                                @if($file)
                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $file->getClientOriginalName() }} ({{ number_format($file->getSize() / 1024, 2) }} KB)</p>
                                @endif
                            </div>

                            @error('upload')
                                <div class="mb-4 rounded-md bg-red-50 dark:bg-red-900/20 p-4">
                                    <p class="text-sm text-red-800 dark:text-red-200">{{ $message }}</p>
                                </div>
                            @enderror

                            <!-- Regular upload loading state -->
                            <div wire:loading wire:target="upload" class="mb-4">
                                <div class="mb-2 flex items-center justify-between text-sm text-gray-700 dark:text-gray-300">
                                    <span class="flex items-center">
                                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Uploading...
                                    </span>
                                </div>
                                <div class="h-2 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                                    <div class="h-full bg-indigo-600 animate-pulse" style="width: 50%"></div>
                                </div>
                            </div>

                            <!-- Chunked upload progress -->
                            @if($isUploading && !$isUploadingRegular)
                                <div class="mb-4">
                                    <div class="mb-2 flex items-center justify-between text-sm text-gray-700 dark:text-gray-300">
                                        <span>Uploading...</span>
                                        <span x-text="uploadProgress + '%'"></span>
                                    </div>
                                    <div class="h-2 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                                        <div class="h-full bg-indigo-600 transition-all" x-bind:style="'width: ' + uploadProgress + '%'"></div>
                                    </div>
                                </div>
                            @endif

                            <div class="flex justify-end space-x-3">
                                @if($isUploading && !$isUploadingRegular)
                                    <x-secondary-button @click="cancel()" type="button">
                                        Cancel
                                    </x-secondary-button>
                                @else
                                    <x-secondary-button wire:click="closeModal" wire:loading.attr="disabled" wire:target="upload" type="button">
                                        Cancel
                                    </x-secondary-button>
                                    <x-button @click="handleUpload()" 
                                              wire:loading.attr="disabled" 
                                              wire:target="upload" 
                                              type="button"
                                              x-bind:disabled="!$wire.file">
                                        <span wire:loading.remove wire:target="upload">Upload</span>
                                        <span wire:loading wire:target="upload" class="flex items-center">
                                            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                            Uploading...
                                        </span>
                                    </x-button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Delete Confirmation Modal -->
    <div x-data="{ show: @entangle('showDeleteModal').live }" 
         x-show="show" 
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex min-h-screen items-end justify-center px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" 
                 wire:click="$set('showDeleteModal', false)"
                 @click="show = false"></div>
            <div class="inline-block transform overflow-hidden rounded-lg bg-white dark:bg-gray-800 text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:align-middle">
                <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-gray-100">Delete Document</h3>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Are you sure you want to delete this document? This action cannot be undone.</p>
                    @if($documentToDelete)
                        <p class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">{{ $documentToDelete->name }}</p>
                    @endif
                </div>
                <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                    <x-danger-button wire:click="deleteDocument" 
                                     type="button"
                                     class="sm:ml-3">
                        Delete
                    </x-danger-button>
                    <x-secondary-button wire:click="$set('showDeleteModal', false)" 
                                        @click="show = false"
                                        type="button"
                                        class="mt-3 sm:mt-0">
                        Cancel
                    </x-secondary-button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bulk Delete Confirmation Modal -->
    <div x-data="{ show: @entangle('showBulkDeleteModal').live }" 
         x-show="show" 
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex min-h-screen items-end justify-center px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" 
                 wire:click="$set('showBulkDeleteModal', false)"
                 @click="show = false"></div>
            <div class="inline-block transform overflow-hidden rounded-lg bg-white dark:bg-gray-800 text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:align-middle">
                <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-gray-100">Delete Selected Documents</h3>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Are you sure you want to delete {{ count($selectedDocuments) }} document(s)? This action cannot be undone.</p>
                </div>
                <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                    <x-danger-button wire:click="bulkDelete" 
                                     type="button"
                                     class="sm:ml-3">
                        Delete
                    </x-danger-button>
                    <x-secondary-button wire:click="$set('showBulkDeleteModal', false)" 
                                        @click="show = false"
                                        type="button"
                                        class="mt-3 sm:mt-0">
                        Cancel
                    </x-secondary-button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Folder Confirmation Modal -->
    <div x-data="{ show: @entangle('showDeleteFolderModal').live }" 
         x-show="show" 
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex min-h-screen items-end justify-center px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" 
                 wire:click="$set('showDeleteFolderModal', false)"
                 @click="show = false"></div>
            <div class="inline-block transform overflow-hidden rounded-lg bg-white dark:bg-gray-800 text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:align-middle">
                <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-gray-100">Delete Folder</h3>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Are you sure you want to delete this folder? This action cannot be undone.</p>
                </div>
                <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                    <x-danger-button wire:click="deleteFolder" 
                                     type="button"
                                     class="sm:ml-3">
                        Delete
                    </x-danger-button>
                    <x-secondary-button wire:click="$set('showDeleteFolderModal', false)" 
                                        @click="show = false"
                                        type="button"
                                        class="mt-3 sm:mt-0">
                        Cancel
                    </x-secondary-button>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Folder Modal -->
    <div x-data="{ show: @entangle('showCreateFolderModal').live }" 
         x-show="show" 
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex min-h-screen items-end justify-center px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" 
                 wire:click="closeCreateFolderModal"
                 @click="show = false">
            </div>
            <div class="inline-block transform overflow-hidden rounded-lg bg-white dark:bg-gray-800 text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:align-middle">
                <form wire:submit.prevent="createFolder">
                    <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-gray-100 mb-4">Create Folder</h3>
                        
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Folder Name</label>
                            <input type="text" 
                                   wire:model="folderName"
                                   class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                   required>
                            @error('folderName') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
                            <textarea wire:model="folderDescription"
                                      rows="3"
                                      class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"></textarea>
                            @error('folderDescription') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                        <x-button type="submit" class="sm:ml-3">
                            Create
                        </x-button>
                        <x-secondary-button wire:click="closeCreateFolderModal" 
                                            @click="show = false"
                                            type="button"
                                            class="mt-3 sm:mt-0">
                            Cancel
                        </x-secondary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Folder Modal -->
    <div x-data="{ show: @entangle('showEditFolderModal').live }" 
         x-show="show" 
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex min-h-screen items-end justify-center px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" 
                 wire:click="closeEditFolderModal"
                 @click="show = false">
            </div>
            <div class="inline-block transform overflow-hidden rounded-lg bg-white dark:bg-gray-800 text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:align-middle">
                <form wire:submit.prevent="updateFolder">
                    <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-gray-100 mb-4">Edit Folder</h3>
                        
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Folder Name</label>
                            <input type="text" 
                                   wire:model="editFolderName"
                                   class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                   required>
                            @error('editFolderName') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
                            <textarea wire:model="editFolderDescription"
                                      rows="3"
                                      class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"></textarea>
                            @error('editFolderDescription') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        @error('delete') 
                            <div class="mb-4 rounded-md bg-red-50 dark:bg-red-900/20 p-4">
                                <p class="text-sm text-red-800 dark:text-red-200">{{ $message }}</p>
                            </div>
                        @enderror
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                        <x-button type="submit" class="sm:ml-3">
                            Update
                        </x-button>
                        <x-danger-button wire:click="confirmDeleteFolder" 
                                         type="button"
                                         class="sm:ml-3">
                            Delete
                        </x-danger-button>
                        <x-secondary-button wire:click="closeEditFolderModal" 
                                            @click="show = false"
                                            type="button"
                                            class="mt-3 sm:mt-0">
                            Cancel
                        </x-secondary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>