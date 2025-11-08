<?php

namespace Afterburner\Documents\Livewire\Documents;

use Afterburner\Documents\Actions\CreateDocument;
use Afterburner\Documents\Actions\HandleChunkedUpload;
use Afterburner\Documents\Models\Document;
use Afterburner\Documents\Models\Folder;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class DocumentManager extends Component
{
    use WithPagination;
    use WithFileUploads;

    public $team;
    public $currentFolder = null;
    public $search = '';
    public $mimeTypeFilter = '';
    public $folderFilter = null;
    public $dateFrom = null;
    public $dateTo = null;
    public $sortBy = 'created_at';
    public $sortOrder = 'desc';
    public $selectedDocuments = [];
    public $showDeleteModal = false;
    public $documentToDelete = null;
    public $showBulkDeleteModal = false;
    public $showDeleteFolderModal = false;

    // Upload properties
    public $showUploadModal = false;
    public $file = null;
    public $name = '';
    public $folderId = null;
    public $uploadId = null;
    public $uploadProgress = 0;
    public $isUploading = false;
    public $isUploadingRegular = false;
    public $useChunkedUpload = false;
    public $chunkSize = 5242880; // 5MB

    // Folder creation properties
    public $showCreateFolderModal = false;
    public $folderName = '';
    public $folderDescription = '';
    public $folderParentId = null;

    // Folder edit properties
    public $showEditFolderModal = false;
    public $editFolderId = null;
    public $editFolderName = '';
    public $editFolderDescription = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'mimeTypeFilter' => ['except' => ''],
        'folderFilter' => ['except' => ''],
        'dateFrom' => ['except' => ''],
        'dateTo' => ['except' => ''],
        'sortBy' => ['except' => 'created_at'],
        'sortOrder' => ['except' => 'desc'],
    ];

    protected $listeners = [
        'documentUploaded' => '$refresh',
        'folderCreated' => '$refresh',
        'folderUpdated' => '$refresh',
        'folderDeleted' => '$refresh',
        'openCreateFolderModal' => 'openCreateFolderModal',
        'openEditFolderModal' => 'openEditFolderModal',
        'processUpload' => 'upload',
    ];

    public function mount($team = null, $folderId = null)
    {
        // Use provided team or fall back to user's current team
        $this->team = $team ?? auth()->user()->currentTeam;

        if (!$this->team) {
            abort(404, 'Team not found');
        }

        $this->chunkSize = config('afterburner-documents.upload.chunk_size', 5242880);

        if ($folderId) {
            $this->currentFolder = Folder::with('parent')->findOrFail($folderId);
            
            // Check access
            if (!$this->currentFolder->canView(auth()->user())) {
                abort(403);
            }
        }
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingMimeTypeFilter()
    {
        $this->resetPage();
    }

    public function updatingFolderFilter()
    {
        $this->resetPage();
    }

    public function updatingDateFrom()
    {
        $this->resetPage();
    }

    public function updatingDateTo()
    {
        $this->resetPage();
    }

    public function updatingSortBy()
    {
        $this->resetPage();
    }

    public function updatingSortOrder()
    {
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->search = '';
        $this->mimeTypeFilter = '';
        $this->folderFilter = null;
        $this->dateFrom = null;
        $this->dateTo = null;
        $this->sortBy = 'created_at';
        $this->sortOrder = 'desc';
        $this->resetPage();
    }

    public function navigateToFolder($folderId)
    {
        $this->currentFolder = Folder::with('parent')->findOrFail($folderId);
        $this->resetPage();
    }

    public function navigateUp()
    {
        if ($this->currentFolder && $this->currentFolder->parent) {
            $this->currentFolder = Folder::with('parent')->findOrFail($this->currentFolder->parent->id);
        } else {
            $this->currentFolder = null;
        }
        $this->resetPage();
    }

    public function navigateToRoot()
    {
        $this->currentFolder = null;
        $this->resetPage();
    }

    public function selectDocument($documentId)
    {
        if (in_array($documentId, $this->selectedDocuments)) {
            $this->selectedDocuments = array_diff($this->selectedDocuments, [$documentId]);
        } else {
            $this->selectedDocuments[] = $documentId;
        }
    }

    public function selectAll()
    {
        $this->selectedDocuments = $this->getDocumentsQuery()->pluck('id')->toArray();
    }

    public function deselectAll()
    {
        $this->selectedDocuments = [];
    }

    public function confirmDelete($documentId)
    {
        $this->documentToDelete = Document::findOrFail($documentId);
        $this->showDeleteModal = true;
    }

    public function deleteDocument()
    {
        if ($this->documentToDelete) {
            $this->authorize('delete', $this->documentToDelete);
            $this->documentToDelete->delete();
            $this->showDeleteModal = false;
            $this->documentToDelete = null;
            $this->selectedDocuments = [];
            session()->flash('message', 'Document deleted successfully.');
        }
    }

    public function confirmBulkDelete()
    {
        if (empty($this->selectedDocuments)) {
            return;
        }
        $this->showBulkDeleteModal = true;
    }

    public function bulkDelete()
    {
        if (empty($this->selectedDocuments)) {
            return;
        }

        $documents = Document::whereIn('id', $this->selectedDocuments)
            ->get();

        foreach ($documents as $document) {
            $this->authorize('delete', $document);
            $document->delete();
        }

        $this->selectedDocuments = [];
        $this->showBulkDeleteModal = false;
        session()->flash('message', count($documents).' document(s) deleted successfully.');
    }

    // Upload methods
    public function openModal()
    {
        $this->folderId = $this->currentFolder?->id;
        $this->showUploadModal = true;
    }

    public function closeModal()
    {
        if (!$this->isUploading) {
            $this->reset(['file', 'name', 'folderId']);
            $this->showUploadModal = false;
        }
    }

    public function updatedFile()
    {
        // Note: Livewire respects PHP's upload_max_filesize setting
        // If you get "file must not be greater than X kilobytes" errors,
        // you need to increase PHP's upload_max_filesize and post_max_size settings
        // For files larger than PHP's limit, chunked uploads will be used automatically
        
        if (!$this->file) {
            return;
        }
        
        $phpMaxSizeStr = ini_get('upload_max_filesize') ?: '512M';
        $phpMaxSizeBytes = $this->parseSize($phpMaxSizeStr);
        $configMaxSize = config('afterburner-documents.upload.max_file_size', 2147483648);
        
        // Check if file will use chunked upload BEFORE validation
        // Files larger than chunk size OR larger than PHP's upload_max_filesize should use chunked uploads
        $willUseChunked = $this->file->getSize() > $this->chunkSize || $this->file->getSize() > $phpMaxSizeBytes;
        
        // Only validate if NOT using chunked uploads
        // Chunked uploads bypass Livewire's file upload mechanism entirely,
        // so we don't need to validate those files through Livewire validation
        if (!$willUseChunked) {
            // Use the smaller of PHP limit or config limit for validation
            // Convert to kilobytes for Laravel validation
            $maxSizeKB = (int)(min($phpMaxSizeBytes, $configMaxSize) / 1024);
            
            // Debug: Log what we're using (remove after testing)
            \Log::debug('File upload validation', [
                'php_max_size_str' => $phpMaxSizeStr,
                'php_max_size_bytes' => $phpMaxSizeBytes,
                'config_max_size' => $configMaxSize,
                'max_size_kb' => $maxSizeKB,
                'file_size' => $this->file->getSize(),
                'will_use_chunked' => $willUseChunked,
            ]);
            
            $this->validate([
                'file' => 'file|max:'.$maxSizeKB,
            ]);
        } else {
            // For chunked uploads, just log that we're skipping validation
            \Log::debug('File upload validation skipped (chunked upload)', [
                'file_size' => $this->file->getSize(),
                'chunk_size' => $this->chunkSize,
                'php_max_size_bytes' => $phpMaxSizeBytes,
                'config_max_size' => $configMaxSize,
            ]);
        }
        
        // Set chunked upload flag
        $this->useChunkedUpload = $willUseChunked;

        // Set name from filename if not provided
        if (!$this->name) {
            $this->name = pathinfo($this->file->getClientOriginalName(), PATHINFO_FILENAME);
        }
    }
    
    /**
     * Parse PHP size string (e.g., "12M", "2G") to bytes.
     */
    protected function parseSize(string $size): int
    {
        $size = trim($size);
        if (empty($size)) {
            return 512 * 1024 * 1024; // Default to 512MB if empty
        }
        
        $last = strtolower($size[strlen($size) - 1]);
        $numericSize = (float) $size; // Use float to handle decimal values
        
        switch ($last) {
            case 'g':
                $numericSize *= 1024;
                // no break
            case 'm':
                $numericSize *= 1024;
                // no break
            case 'k':
                $numericSize *= 1024;
        }
        
        return (int) $numericSize;
    }

    public function upload()
    {
        // Ensure file exists first
        if (!$this->file) {
            $this->addError('file', 'Please select a file to upload.');
            return;
        }

        // Validate name and folderId first (don't re-validate file to avoid triggering Livewire upload)
        try {
            $this->validate([
                'name' => 'required|string|max:255',
                'folderId' => 'nullable|exists:folders,id',
            ], [], [
                'name' => 'document name',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->addError('name', 'Please provide a document name.');
            return;
        }

        // Validate file exists and is valid
        if (!is_object($this->file) || !method_exists($this->file, 'getSize')) {
            $this->addError('file', 'Please select a valid file to upload.');
            return;
        }

        // Check file size
        $maxSize = config('afterburner-documents.upload.max_file_size', 2147483648);
        if ($this->file->getSize() > $maxSize) {
            $this->addError('file', 'File size exceeds the maximum allowed size.');
            return;
        }

        // Check folder access if folder is selected
        if ($this->folderId) {
            $folder = Folder::findOrFail($this->folderId);
            if (!$folder->canCreate(auth()->user())) {
                $this->addError('folderId', 'You do not have permission to upload to this folder.');
                return;
            }
        }

        // For chunked uploads, JavaScript will handle it
        if ($this->useChunkedUpload) {
            $this->isUploading = true;
            $this->uploadProgress = 0;
            return;
        }

        // Regular upload for small files
        $this->isUploadingRegular = true;
        $this->uploadRegular();
    }

    protected function uploadRegular()
    {
        try {
            $this->isUploadingRegular = true;
            
            $createDocument = app(CreateDocument::class);

            $document = $createDocument->execute([
                'team_id' => $this->team->id,
                'folder_id' => $this->folderId,
                'name' => $this->name,
            ], $this->file, auth()->user());

            $this->reset(['file', 'name', 'folderId', 'isUploadingRegular']);
            $this->showUploadModal = false;
            $this->dispatch('documentUploaded', $document->id);
            session()->flash('message', 'Document uploaded successfully.');
        } catch (\Exception $e) {
            $this->isUploadingRegular = false;
            $this->addError('upload', 'Failed to upload document: '.$e->getMessage());
        }
    }

    /**
     * Generate the final storage path for a chunked upload.
     * Called by JavaScript before completing the upload.
     */
    public function generateFinalPath(string $filename): string
    {
        $pathTemplate = config('afterburner-documents.storage_path', 'documents/{team_id}/{year}/{month}/{document_id}');
        
        // Generate temporary UUID for path generation
        $tempDocumentId = \Illuminate\Support\Str::uuid()->toString();
        
        $replacements = [
            '{team_id}' => $this->team->id,
            '{year}' => now()->year,
            '{month}' => str_pad(now()->month, 2, '0', STR_PAD_LEFT),
            '{document_id}' => $tempDocumentId,
        ];
        $finalPath = str_replace(array_keys($replacements), array_values($replacements), $pathTemplate);
        $finalPath = rtrim($finalPath, '/').'/'.$filename;

        return $finalPath;
    }

    /**
     * Create a document from a completed chunked upload.
     * Called by JavaScript after chunked upload completes.
     */
    public function createDocumentFromChunkedUpload(array $data)
    {
        try {
            $finalPath = $data['storagePath'];

            // Create document record
            $createDocument = app(CreateDocument::class);
            $document = $createDocument->execute([
                'team_id' => $this->team->id,
                'folder_id' => $data['folderId'] ?? null,
                'name' => $data['name'],
                'filename' => $data['filename'],
                'original_filename' => $data['originalFilename'] ?? $data['filename'],
                'mime_type' => $data['mimeType'],
                'file_size' => $data['fileSize'],
                'storage_path' => $finalPath,
            ], null, auth()->user());

            $this->reset(['file', 'name', 'folderId', 'uploadId', 'uploadProgress', 'isUploading', 'useChunkedUpload']);
            $this->showUploadModal = false;
            $this->dispatch('documentUploaded', $document->id);
            session()->flash('message', 'Document uploaded successfully.');
        } catch (\Exception $e) {
            $this->addError('upload', 'Failed to create document: '.$e->getMessage());
            $this->isUploading = false;
        }
    }

    public function cancel()
    {
        if ($this->uploadId) {
            app(HandleChunkedUpload::class)->cancel($this->uploadId);
        }
        $this->reset(['file', 'name', 'uploadId', 'uploadProgress', 'isUploading', 'useChunkedUpload']);
        $this->showUploadModal = false;
    }

    // Folder creation methods
    public function openCreateFolderModal($parentId = null)
    {
        $this->folderParentId = $parentId;
        $this->reset(['folderName', 'folderDescription']);
        $this->showCreateFolderModal = true;
    }

    public function createFolder()
    {
        $this->validate([
            'folderName' => 'required|string|max:255',
            'folderDescription' => 'nullable|string|max:1000',
            'folderParentId' => 'nullable|exists:folders,id',
        ]);

        // Check parent folder access if provided
        if ($this->folderParentId) {
            $parentFolder = Folder::findOrFail($this->folderParentId);
            $this->authorize('view', $parentFolder);
        }

        $slug = \Illuminate\Support\Str::slug($this->folderName);

        // Ensure unique slug within parent folder
        $existingFolder = Folder::where('team_id', $this->team->id)
            ->where('parent_id', $this->folderParentId)
            ->where('slug', $slug)
            ->first();

        if ($existingFolder) {
            $slug = $slug.'-'.time();
        }

        $folder = Folder::create([
            'team_id' => $this->team->id,
            'parent_id' => $this->folderParentId,
            'name' => $this->folderName,
            'slug' => $slug,
            'description' => $this->folderDescription,
            'created_by' => auth()->id(),
        ]);

        $this->reset(['folderName', 'folderDescription', 'folderParentId']);
        $this->showCreateFolderModal = false;
        $this->dispatch('folderCreated', $folder->id);
        session()->flash('message', 'Folder created successfully.');
    }

    public function closeCreateFolderModal()
    {
        $this->reset(['folderName', 'folderDescription', 'folderParentId']);
        $this->showCreateFolderModal = false;
    }

    // Folder edit methods
    public function openEditFolderModal($folderId)
    {
        $folder = Folder::findOrFail($folderId);
        $this->authorize('update', $folder);
        
        $this->editFolderId = $folder->id;
        $this->editFolderName = $folder->name;
        $this->editFolderDescription = $folder->description ?? '';
        $this->showEditFolderModal = true;
    }

    public function closeEditFolderModal()
    {
        $this->reset(['editFolderId', 'editFolderName', 'editFolderDescription']);
        $this->showEditFolderModal = false;
    }

    public function updateFolder()
    {
        $folder = Folder::findOrFail($this->editFolderId);
        $this->authorize('update', $folder);

        $this->validate([
            'editFolderName' => 'required|string|max:255',
            'editFolderDescription' => 'nullable|string|max:1000',
        ]);

        $data = [
            'name' => $this->editFolderName,
            'description' => $this->editFolderDescription,
        ];

        // Handle slug update if name changed
        if ($this->editFolderName !== $folder->name) {
            $slug = \Illuminate\Support\Str::slug($this->editFolderName);

            // Ensure unique slug within parent folder
            $parentId = $folder->parent_id;
            $existingFolder = Folder::where('team_id', $folder->team_id)
                ->where('parent_id', $parentId)
                ->where('slug', $slug)
                ->where('id', '!=', $folder->id)
                ->first();

            if ($existingFolder) {
                $slug = $slug.'-'.time();
            }

            $data['slug'] = $slug;
        }

        $folder->update($data);

        $this->reset(['editFolderId', 'editFolderName', 'editFolderDescription']);
        $this->showEditFolderModal = false;
        $this->dispatch('folderUpdated', $folder->id);
        session()->flash('message', 'Folder updated successfully.');
    }

    public function confirmDeleteFolder()
    {
        $folder = Folder::findOrFail($this->editFolderId);
        $this->authorize('delete', $folder);
        $this->showDeleteFolderModal = true;
    }

    public function deleteFolder()
    {
        $folder = Folder::findOrFail($this->editFolderId);
        $this->authorize('delete', $folder);

        if (!$folder->canBeDeleted()) {
            $this->addError('delete', 'Folder contains documents or subfolders and cannot be deleted.');
            $this->showDeleteFolderModal = false;
            return;
        }

        $folder->delete();

        $this->reset(['editFolderId', 'editFolderName', 'editFolderDescription']);
        $this->showEditFolderModal = false;
        $this->showDeleteFolderModal = false;
        $this->dispatch('folderDeleted', $folder->id);
        session()->flash('message', 'Folder deleted successfully.');
    }

    protected function getDocumentsQuery()
    {
        $query = Document::forTeam($this->team->id)
            ->with(['folder', 'creator', 'retentionTag']);

        // Folder filtering - if folderFilter is set, use it; otherwise use currentFolder navigation
        if ($this->folderFilter) {
            if ($this->folderFilter === 'null' || $this->folderFilter === '') {
                $query->whereNull('folder_id');
            } else {
                $query->inFolder($this->folderFilter);
            }
        } else {
            // Current folder navigation (breadcrumb navigation)
            if ($this->currentFolder) {
                $query->inFolder($this->currentFolder->id);
            } else {
                $query->whereNull('folder_id');
            }
        }

        // Search
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                    ->orWhere('filename', 'like', "%{$this->search}%")
                    ->orWhere('original_filename', 'like', "%{$this->search}%");
            });
        }

        // MIME type filter
        if ($this->mimeTypeFilter) {
            if (str_ends_with($this->mimeTypeFilter, '/')) {
                // Handle prefix filters like "image/"
                $query->where('mime_type', 'like', $this->mimeTypeFilter . '%');
            } else {
                $query->byMimeType($this->mimeTypeFilter);
            }
        }

        // Date filters
        if ($this->dateFrom) {
            $query->whereDate('created_at', '>=', $this->dateFrom);
        }

        if ($this->dateTo) {
            $query->whereDate('created_at', '<=', $this->dateTo);
        }

        return $query->orderBy($this->sortBy, $this->sortOrder);
    }

    public function getFoldersProperty()
    {
        $query = Folder::forTeam($this->team->id)
            ->with(['creator']);

        if ($this->currentFolder) {
            $query->inFolder($this->currentFolder->id);
        } else {
            $query->root();
        }

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                    ->orWhere('description', 'like', "%{$this->search}%");
            });
        }

        return $query->orderBy('name', 'asc')->get();
    }

    /**
     * Get the breadcrumb trail from root to current folder.
     */
    public function getBreadcrumbsProperty()
    {
        if (!$this->currentFolder) {
            return [];
        }

        // Load all ancestors efficiently
        $ancestors = $this->loadAncestors($this->currentFolder);
        
        return $ancestors;
    }

    /**
     * Load all ancestor folders for a given folder.
     */
    protected function loadAncestors($folder)
    {
        // Collect all folder IDs in the path by traversing up
        $folderIds = [$folder->id];
        $currentId = $folder->parent_id;
        $maxDepth = 20; // Safety limit to prevent infinite loops
        $depth = 0;
        
        // Traverse up the parent chain
        while ($currentId && $depth < $maxDepth) {
            $folderIds[] = $currentId;
            // Query parent_id for the current folder
            $currentId = Folder::where('id', $currentId)->value('parent_id');
            $depth++;
        }
        
        // Load all folders in the path at once
        $allFolders = Folder::whereIn('id', $folderIds)->get()->keyBy('id');
        
        // Build breadcrumb trail from root to current
        $ancestors = [];
        $currentId = $folder->id;
        while ($currentId && isset($allFolders[$currentId])) {
            $currentFolder = $allFolders[$currentId];
            array_unshift($ancestors, $currentFolder);
            $currentId = $currentFolder->parent_id;
        }
        
        return $ancestors;
    }

    public function render()
    {
        $documents = $this->getDocumentsQuery()->paginate(20);

        // Get all folders for the team (for filter dropdown)
        $allFolders = Folder::forTeam($this->team->id)
            ->orderBy('name', 'asc')
            ->get();

        // Get unique MIME types for filter dropdown
        $mimeTypes = Document::forTeam($this->team->id)
            ->distinct()
            ->pluck('mime_type')
            ->filter()
            ->sort()
            ->values();

        return view('afterburner-documents::documents.document-manager', [
            'documents' => $documents,
            'folders' => $this->folders,
            'allFolders' => $allFolders,
            'mimeTypes' => $mimeTypes,
            'breadcrumbs' => $this->breadcrumbs,
        ]);
    }
}

