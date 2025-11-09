<?php

namespace Afterburner\Documents\Livewire\Documents;

use Afterburner\Documents\Actions\CreateFolder;
use Afterburner\Documents\Actions\DeleteDocument;
use Afterburner\Documents\Actions\UpdateDocument;
use Afterburner\Documents\Actions\UpdateFolder;
use Afterburner\Documents\Actions\UploadDocument;
use Afterburner\Documents\Models\Document;
use Afterburner\Documents\Models\Folder;
use Afterburner\Documents\Notifications\DocumentUploadComplete;
use Afterburner\Documents\Services\StorageService;
use App\Traits\InteractsWithBanner;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithPagination;
use Spatie\LivewireFilepond\WithFilePond;

class Index extends Component
{
    use WithPagination, WithFilePond, InteractsWithBanner;

    public $teamId;
    public $currentFolderId = null;
    public ?string $searchQuery = null;
    public ?string $folderFilter = null;
    public ?string $statusFilter = null;
    public ?string $mimeTypeFilter = null;
    public ?string $dateFrom = null;
    public ?string $dateTo = null;

    // Sorting
    public ?string $folderSortBy = 'name';
    public ?string $folderSortDirection = 'asc';
    public ?string $documentSortBy = 'created_at';
    public ?string $documentSortDirection = 'desc';

    // Upload Modal
    public bool $showingUploadModal = false;
    public $uploadFiles = [];

    // Folder Modal
    public bool $showingFolderModal = false;
    public bool $showingEditFolderModal = false;
    public bool $showingDeleteModal = false;
    public ?Folder $folderToDelete = null;
    public ?Folder $folderToEdit = null;
    public $folderName = '';

    // Document Viewer
    public ?int $viewingDocumentId = null;

    // Document Edit/Delete
    public bool $showingEditDocumentModal = false;
    public bool $showingDeleteDocumentModal = false;
    public ?Document $documentToEdit = null;
    public ?Document $documentToDelete = null;
    public $documentName = '';
    public $newDocumentFile = null;

    // Move Modals
    public bool $showingMoveDocumentModal = false;
    public bool $showingMoveFolderModal = false;
    public ?Document $documentToMove = null;
    public ?Folder $folderToMove = null;
    public ?int $selectedTargetFolderId = null;

    // Filters Panel
    public bool $showFilters = false;

    protected function rules(): array
    {
        $maxFileSize = config('afterburner-documents.upload.max_file_size', 2147483648);
        $allowedMimeTypes = config('afterburner-documents.upload.allowed_mime_types', []);

        $rules = [
            'folderName' => 'required|string|max:255',
            'documentName' => 'required|string|max:255',
            'uploadFiles.*' => [
                'required',
                'file',
                'max:'.$maxFileSize,
            ],
            'newDocumentFile' => [
                'nullable',
                'file',
                'max:'.$maxFileSize,
            ],
        ];

        if (!empty($allowedMimeTypes)) {
            $rules['uploadFiles.*'][] = 'mimetypes:'.implode(',', $allowedMimeTypes);
            $rules['newDocumentFile'][] = 'mimetypes:'.implode(',', $allowedMimeTypes);
        }

        return $rules;
    }

    protected $queryString = [
        'currentFolderId' => ['except' => ''],
        'searchQuery' => ['except' => ''],
        'folderFilter' => ['except' => ''],
        'statusFilter' => ['except' => ''],
        'mimeTypeFilter' => ['except' => ''],
        'dateFrom' => ['except' => ''],
        'dateTo' => ['except' => ''],
        'folderSortBy' => ['except' => 'name'],
        'folderSortDirection' => ['except' => 'asc'],
        'documentSortBy' => ['except' => 'created_at'],
        'documentSortDirection' => ['except' => 'desc'],
    ];

    public function mount(\App\Models\Team $team, $folder_slug = null)
    {
        $this->teamId = $team->id;

        // Ensure user belongs to team
        if (!Auth::user()->belongsToTeam($team)) {
            abort(403, 'Access denied.');
        }

        // If folder slug provided, find folder and set currentFolderId
        if ($folder_slug) {
            $folder = \Afterburner\Documents\Models\Folder::where('team_id', $this->teamId)
                ->where('slug', $folder_slug)
                ->first();
            
            if ($folder) {
                $this->currentFolderId = $folder->id;
            }
        }

        // Cast query string values
        if ($this->currentFolderId !== null && $this->currentFolderId !== '') {
            $this->currentFolderId = (int) $this->currentFolderId;
        } else {
            $this->currentFolderId = null;
        }
    }

    public function updatingSearchQuery()
    {
        $this->resetPage();
    }

    public function updatingFolderFilter()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function updatingMimeTypeFilter()
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

    public function sortFolders($column)
    {
        if ($this->folderSortBy === $column) {
            $this->folderSortDirection = $this->folderSortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->folderSortBy = $column;
            $this->folderSortDirection = 'asc';
        }
    }

    public function sortDocuments($column)
    {
        if ($this->documentSortBy === $column) {
            $this->documentSortDirection = $this->documentSortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->documentSortBy = $column;
            $this->documentSortDirection = 'asc';
        }
        $this->resetPage();
    }

    public function sortByName()
    {
        $isCurrentlySorted = ($this->folderSortBy === 'name' || $this->documentSortBy === 'name');
        
        if ($isCurrentlySorted) {
            // Toggle direction for both
            $newDirection = ($this->folderSortBy === 'name' && $this->folderSortDirection === 'asc') || 
                           ($this->documentSortBy === 'name' && $this->documentSortDirection === 'asc') 
                           ? 'desc' : 'asc';
            
            $this->folderSortBy = 'name';
            $this->folderSortDirection = $newDirection;
            $this->documentSortBy = 'name';
            $this->documentSortDirection = $newDirection;
        } else {
            // Set both to ascending
            $this->folderSortBy = 'name';
            $this->folderSortDirection = 'asc';
            $this->documentSortBy = 'name';
            $this->documentSortDirection = 'asc';
        }
        
        $this->resetPage();
    }

    public function sortByOwner()
    {
        $isCurrentlySorted = ($this->folderSortBy === 'created_by' || $this->documentSortBy === 'uploaded_by');
        
        if ($isCurrentlySorted) {
            // Toggle direction for both
            $newDirection = ($this->folderSortBy === 'created_by' && $this->folderSortDirection === 'asc') || 
                           ($this->documentSortBy === 'uploaded_by' && $this->documentSortDirection === 'asc') 
                           ? 'desc' : 'asc';
            
            $this->folderSortBy = 'created_by';
            $this->folderSortDirection = $newDirection;
            $this->documentSortBy = 'uploaded_by';
            $this->documentSortDirection = $newDirection;
        } else {
            // Set both to ascending
            $this->folderSortBy = 'created_by';
            $this->folderSortDirection = 'asc';
            $this->documentSortBy = 'uploaded_by';
            $this->documentSortDirection = 'asc';
        }
        
        $this->resetPage();
    }

    public function sortByModified()
    {
        $isCurrentlySorted = ($this->folderSortBy === 'updated_at' || $this->documentSortBy === 'created_at');
        
        if ($isCurrentlySorted) {
            // Toggle direction for both
            $newDirection = ($this->folderSortBy === 'updated_at' && $this->folderSortDirection === 'asc') || 
                           ($this->documentSortBy === 'created_at' && $this->documentSortDirection === 'asc') 
                           ? 'desc' : 'asc';
            
            $this->folderSortBy = 'updated_at';
            $this->folderSortDirection = $newDirection;
            $this->documentSortBy = 'created_at';
            $this->documentSortDirection = $newDirection;
        } else {
            // Set both to ascending
            $this->folderSortBy = 'updated_at';
            $this->folderSortDirection = 'asc';
            $this->documentSortBy = 'created_at';
            $this->documentSortDirection = 'asc';
        }
        
        $this->resetPage();
    }

    public function updatedCurrentFolderId($value)
    {
        $this->currentFolderId = $value !== '' && $value !== null ? (int) $value : null;
        $this->resetPage();
    }

    public function updatedSelectedTargetFolderId($value)
    {
        $this->selectedTargetFolderId = $value !== '' && $value !== null ? (int) $value : null;
    }

    public function toggleFilters()
    {
        $this->showFilters = !$this->showFilters;
    }

    public function clearFilters()
    {
        $this->searchQuery = null;
        $this->folderFilter = null;
        $this->statusFilter = null;
        $this->mimeTypeFilter = null;
        $this->dateFrom = null;
        $this->dateTo = null;
        $this->resetPage();
    }

    public function navigateToFolder($folderId)
    {
        $this->currentFolderId = $folderId ? (int) $folderId : null;
        $this->resetPage();
    }

    public function openUploadModal()
    {
        $this->showingUploadModal = true;
    }

    public function closeUploadModal()
    {
        $this->showingUploadModal = false;
        // Don't reset files here - let updatedUploadFiles handle it after processing
    }

    /**
     * Validate uploaded file (called by Spatie FilePond package).
     * This follows the exact pattern from Spatie's README.
     * The $response parameter is passed by the package but we use $this->validate() instead.
     */
    public function validateUploadedFile($response = null): bool
    {
        $this->validate();

        return true;
    }

    /**
     * Process uploaded files after they're uploaded via Livewire.
     * This is called automatically when uploadFiles property is updated (after upload completes).
     * This follows Spatie's intended pattern - process files in updated* method.
     */
    public function updatedUploadFiles()
    {
        if (empty($this->uploadFiles)) {
            return;
        }

        $files = is_array($this->uploadFiles) ? $this->uploadFiles : [$this->uploadFiles];
        $uploadedCount = 0;
        $errors = [];

        foreach ($files as $file) {
            if (!$file instanceof TemporaryUploadedFile) {
                continue;
            }

            try {
                DB::transaction(function () use ($file, &$uploadedCount) {
                    // Create document record
                    $document = app(UploadDocument::class)->execute(
                        $this->teamId,
                        $this->currentFolderId,
                        $file->getClientOriginalName(),
                        $file->getMimeType(),
                        $file->getSize(),
                        Auth::user()
                    );

                    // Generate storage path
                    $storageService = app(StorageService::class);
                    $storagePath = $storageService->generateStoragePath($document);
                    $document->update(['storage_path' => $storagePath]);

                    // Move file to permanent storage
                    $fileContent = file_get_contents($file->getRealPath());
                    $success = $storageService->storeDocument($fileContent, $storagePath);

                    if (!$success) {
                        throw new \Exception('Failed to store document in storage.');
                    }

                    // Update document status
                    $document->update([
                        'upload_status' => 'completed',
                        'upload_progress' => 100,
                    ]);

                    // Create initial version
                    $document->createVersion($storagePath, $file->getSize(), Auth::user());

                    // Send notification
                    Auth::user()->notify(new DocumentUploadComplete($document));

                    $uploadedCount++;
                });
            } catch (\Exception $e) {
                $errors[] = $file->getClientOriginalName().': '.$e->getMessage();
            }
        }

        // Reset files after processing
        $this->reset(['uploadFiles']);

        // Close modal after processing
        $this->closeUploadModal();

        // Show success/error messages
        if ($uploadedCount > 0) {
            $this->banner(__(':count document(s) uploaded successfully.', ['count' => $uploadedCount]));
        }

        if (!empty($errors)) {
            foreach ($errors as $error) {
                $this->dangerBanner($error);
            }
        }
    }

    public function openFolderModal()
    {
        $this->showingFolderModal = true;
        $this->reset(['folderName']);
    }

    public function closeFolderModal()
    {
        $this->showingFolderModal = false;
        $this->reset(['folderName']);
    }

    public function createFolder()
    {
        $this->validateOnly('folderName');

        try {
            app(CreateFolder::class)->execute(
                $this->teamId,
                $this->currentFolderId,
                $this->folderName,
                Auth::user()
            );

            $this->banner(__('Folder created successfully.'));
            $this->closeFolderModal();
            $this->dispatch('folder-created');
        } catch (\Exception $e) {
            $this->dangerBanner($e->getMessage());
        }
    }

    public function openEditFolderModal(Folder $folder)
    {
        if (!Auth::user()->can('update', $folder)) {
            abort(403, 'Access denied.');
        }

        $this->folderToEdit = $folder;
        $this->folderName = $folder->name;
        $this->showingEditFolderModal = true;
    }

    public function closeEditFolderModal()
    {
        $this->showingEditFolderModal = false;
        $this->folderToEdit = null;
        $this->reset(['folderName']);
    }

    public function updateFolder()
    {
        if (!$this->folderToEdit) {
            return;
        }

        if (!Auth::user()->can('update', $this->folderToEdit)) {
            abort(403, 'Access denied.');
        }

        $this->validateOnly('folderName');

        try {
            app(UpdateFolder::class)->execute(
                $this->folderToEdit,
                ['name' => $this->folderName],
                Auth::user()
            );

            $this->banner(__('Folder updated successfully.'));
            $this->closeEditFolderModal();
            $this->dispatch('folder-updated');
        } catch (\Exception $e) {
            $this->dangerBanner($e->getMessage());
        }
    }

    public function confirmDelete(Folder $folder)
    {
        $this->folderToDelete = $folder;
        $this->showingDeleteModal = true;
    }

    public function deleteFolder()
    {
        if (!$this->folderToDelete) {
            return;
        }

        try {
            // Check if folder has children or documents (including nested)
            $descendantIds = $this->folderToDelete->getDescendantIds();
            if (count($descendantIds) > 0) {
                $this->dangerBanner(__('Cannot delete a folder with subfolders inside.'));
                $this->showingDeleteModal = false;
                $this->folderToDelete = null;
                return;
            }

            if ($this->folderToDelete->getTotalDocumentsCount() > 0) {
                $this->dangerBanner(__('Cannot delete a folder with documents inside.'));
                $this->showingDeleteModal = false;
                $this->folderToDelete = null;
                return;
            }

            $this->folderToDelete->delete();
            $this->banner(__('Folder deleted successfully.'));
            $this->showingDeleteModal = false;
            $this->folderToDelete = null;
            $this->dispatch('folder-deleted');
        } catch (\Exception $e) {
            $this->dangerBanner($e->getMessage());
            $this->showingDeleteModal = false;
            $this->folderToDelete = null;
        }
    }

    public function cancelDelete()
    {
        $this->showingDeleteModal = false;
        $this->folderToDelete = null;
    }

    public function openDocumentViewer($documentId)
    {
        $this->viewingDocumentId = $documentId;
    }

    public function closeDocumentViewer()
    {
        $this->viewingDocumentId = null;
    }

    #[On('folder-created')]
    #[On('folder-updated')]
    #[On('folder-deleted')]
    public function refreshFolders()
    {
        // Component will re-render automatically
    }

    #[On('document-viewer-closed')]
    public function handleDocumentViewerClosed()
    {
        $this->viewingDocumentId = null;
    }

    #[On('document-updated')]
    #[On('document-deleted')]
    #[On('document-moved')]
    public function refreshDocuments()
    {
        // Component will re-render automatically
    }

    #[On('folder-moved')]
    public function refreshFoldersAfterMove()
    {
        // Component will re-render automatically
    }

    public function openEditDocumentModal(Document $document)
    {
        if (!Auth::user()->can('update', $document)) {
            abort(403, 'Access denied.');
        }

        $this->documentToEdit = $document;
        $this->documentName = $document->name;
        $this->newDocumentFile = null;
        $this->showingEditDocumentModal = true;
    }

    public function closeEditDocumentModal()
    {
        $this->showingEditDocumentModal = false;
        $this->documentToEdit = null;
        $this->reset(['documentName', 'newDocumentFile']);
    }

    public function updateDocument()
    {
        if (!$this->documentToEdit) {
            return;
        }

        if (!Auth::user()->can('update', $this->documentToEdit)) {
            abort(403, 'Access denied.');
        }

        $this->validateOnly(['documentName', 'newDocumentFile']);

        try {
            $attributes = ['name' => $this->documentName];
            $fileContent = null;

            // If new file uploaded, get its content
            if ($this->newDocumentFile instanceof TemporaryUploadedFile) {
                $fileContent = file_get_contents($this->newDocumentFile->getRealPath());
                $attributes['filename'] = $this->newDocumentFile->getClientOriginalName();
                $attributes['mime_type'] = $this->newDocumentFile->getMimeType();
                $attributes['size'] = $this->newDocumentFile->getSize();
            }

            app(UpdateDocument::class)->execute(
                $this->documentToEdit,
                $attributes,
                $fileContent,
                Auth::user()
            );

            $this->banner(__('Document updated successfully.'));
            $this->closeEditDocumentModal();
            $this->dispatch('document-updated');
        } catch (\Exception $e) {
            $this->dangerBanner($e->getMessage());
        }
    }

    public function confirmDeleteDocument(Document $document)
    {
        if (!Auth::user()->can('delete', $document)) {
            abort(403, 'Access denied.');
        }

        $this->documentToDelete = $document;
        $this->showingDeleteDocumentModal = true;
    }

    public function cancelDeleteDocument()
    {
        $this->showingDeleteDocumentModal = false;
        $this->documentToDelete = null;
    }

    public function deleteDocument()
    {
        if (!$this->documentToDelete) {
            return;
        }

        if (!Auth::user()->can('delete', $this->documentToDelete)) {
            abort(403, 'Access denied.');
        }

        try {
            app(DeleteDocument::class)->execute(
                $this->documentToDelete,
                Auth::user(),
                false // Soft delete
            );

            $this->banner(__('Document deleted successfully.'));
            $this->showingDeleteDocumentModal = false;
            $this->documentToDelete = null;
            $this->dispatch('document-deleted');
        } catch (\Exception $e) {
            $this->dangerBanner($e->getMessage());
        }
    }

    // Move Document Methods
    public function openMoveDocumentModal(Document $document)
    {
        if (!Auth::user()->can('update', $document)) {
            abort(403, 'Access denied.');
        }

        $this->documentToMove = $document;
        $this->selectedTargetFolderId = $document->folder_id;
        $this->showingMoveDocumentModal = true;
    }

    public function closeMoveDocumentModal()
    {
        $this->showingMoveDocumentModal = false;
        $this->documentToMove = null;
        $this->selectedTargetFolderId = null;
    }

    public function moveDocument()
    {
        if (!$this->documentToMove) {
            return;
        }

        if (!Auth::user()->can('update', $this->documentToMove)) {
            abort(403, 'Access denied.');
        }

        // Validate target folder (empty string means root/null)
        $targetFolderId = ($this->selectedTargetFolderId === '' || $this->selectedTargetFolderId === null) 
            ? null 
            : (int) $this->selectedTargetFolderId;

        // Check if moving to same location
        if ($this->documentToMove->folder_id === $targetFolderId) {
            $this->dangerBanner(__('Document is already in this location.'));
            return;
        }

        // Validate target folder exists and belongs to same team (if not root)
        if ($targetFolderId !== null) {
            $targetFolder = Folder::forTeam($this->teamId)->find($targetFolderId);
            if (!$targetFolder) {
                $this->dangerBanner(__('Target folder not found.'));
                return;
            }
        }

        try {
            app(UpdateDocument::class)->execute(
                $this->documentToMove,
                ['folder_id' => $targetFolderId],
                null,
                Auth::user()
            );

            $this->banner(__('Document moved successfully.'));
            $this->closeMoveDocumentModal();
            $this->dispatch('document-moved');
        } catch (\Exception $e) {
            $this->dangerBanner($e->getMessage());
        }
    }

    // Move Folder Methods
    public function openMoveFolderModal(Folder $folder)
    {
        if (!Auth::user()->can('update', $folder)) {
            abort(403, 'Access denied.');
        }

        $this->folderToMove = $folder;
        $this->selectedTargetFolderId = $folder->parent_id;
        $this->showingMoveFolderModal = true;
    }

    public function closeMoveFolderModal()
    {
        $this->showingMoveFolderModal = false;
        $this->folderToMove = null;
        $this->selectedTargetFolderId = null;
    }

    public function moveFolder()
    {
        if (!$this->folderToMove) {
            return;
        }

        if (!Auth::user()->can('update', $this->folderToMove)) {
            abort(403, 'Access denied.');
        }

        // Validate target folder (empty string means root/null)
        $targetParentId = ($this->selectedTargetFolderId === '' || $this->selectedTargetFolderId === null) 
            ? null 
            : (int) $this->selectedTargetFolderId;

        // Check if moving to same location
        if ($this->folderToMove->parent_id === $targetParentId) {
            $this->dangerBanner(__('Folder is already in this location.'));
            return;
        }

        // Prevent circular moves
        if ($targetParentId !== null) {
            $descendants = $this->getFolderDescendants($this->folderToMove->id);
            if (in_array($targetParentId, $descendants)) {
                $this->dangerBanner(__('Cannot move folder into its own subfolder.'));
                return;
            }

            // Validate target folder exists and belongs to same team
            $targetFolder = Folder::forTeam($this->teamId)->find($targetParentId);
            if (!$targetFolder) {
                $this->dangerBanner(__('Target folder not found.'));
                return;
            }
        }

        try {
            app(UpdateFolder::class)->execute(
                $this->folderToMove,
                ['parent_id' => $targetParentId],
                Auth::user()
            );

            $this->banner(__('Folder moved successfully.'));
            $this->closeMoveFolderModal();
            $this->dispatch('folder-moved');
        } catch (\Exception $e) {
            $this->dangerBanner($e->getMessage());
        }
    }

    /**
     * Get all descendant folder IDs recursively.
     */
    protected function getFolderDescendants($folderId): array
    {
        $descendants = [];
        $children = Folder::where('parent_id', $folderId)->get();

        foreach ($children as $child) {
            $descendants[] = $child->id;
            $descendants = array_merge($descendants, $this->getFolderDescendants($child->id));
        }

        return $descendants;
    }

    /**
     * Get folder tree for move modal, excluding current folder and its descendants.
     */
    public function getFolderTreeProperty()
    {
        // Load all folders for the team
        $allFolders = Folder::forTeam($this->teamId)
            ->orderBy('name')
            ->get();

        // Build tree structure
        $foldersByParent = $allFolders->groupBy('parent_id');
        
        // Recursively build tree starting from root folders
        $buildTree = function ($parentId = null) use (&$buildTree, $foldersByParent) {
            if (!$foldersByParent->has($parentId)) {
                return collect();
            }

            return $foldersByParent->get($parentId)->map(function ($folder) use (&$buildTree, $foldersByParent) {
                $folder->setRelation('children', $buildTree($folder->id));
                return $folder;
            });
        };

        $folders = $buildTree();

        // If moving a folder, exclude it and its descendants
        if ($this->folderToMove) {
            $excludeIds = $this->getFolderDescendants($this->folderToMove->id);
            $excludeIds[] = $this->folderToMove->id;

            $folders = $folders->reject(function ($folder) use ($excludeIds) {
                return in_array($folder->id, $excludeIds);
            })->map(function ($folder) use ($excludeIds) {
                return $this->filterFolderTree($folder, $excludeIds);
            })->filter();
        }

        return $folders;
    }

    /**
     * Recursively filter folder tree to exclude certain IDs.
     */
    protected function filterFolderTree($folder, array $excludeIds)
    {
        if (in_array($folder->id, $excludeIds)) {
            return null;
        }

        $filteredChildren = $folder->children->map(function ($child) use ($excludeIds) {
            return $this->filterFolderTree($child, $excludeIds);
        })->filter();

        $folder->setRelation('children', $filteredChildren);

        return $folder;
    }

    public function render()
    {
        $team = \App\Models\Team::findOrFail($this->teamId);

        // Build folders query
        $foldersQuery = Folder::forTeam($this->teamId)
            ->where('parent_id', $this->currentFolderId);

        // Build documents query
        $documentsQuery = Document::forTeam($this->teamId)
            ->where('folder_id', $this->currentFolderId);


        // Apply search
        if ($this->searchQuery) {
            $documentsQuery->where(function ($q) {
                $q->where('name', 'like', "%{$this->searchQuery}%")
                  ->orWhere('filename', 'like', "%{$this->searchQuery}%");
            });
        }

        // Apply filters
        if ($this->folderFilter) {
            $documentsQuery->where('folder_id', $this->folderFilter);
        }

        if ($this->statusFilter) {
            $documentsQuery->byStatus($this->statusFilter);
        }

        if ($this->mimeTypeFilter) {
            $documentsQuery->where('mime_type', $this->mimeTypeFilter);
        }

        if ($this->dateFrom) {
            $documentsQuery->where('created_at', '>=', $this->dateFrom);
        }

        if ($this->dateTo) {
            $documentsQuery->where('created_at', '<=', $this->dateTo);
        }

        // Apply folder sorting
        $validFolderSortColumns = ['name', 'created_by', 'updated_at'];
        $folderSortColumn = in_array($this->folderSortBy, $validFolderSortColumns) ? $this->folderSortBy : 'name';
        $folderSortDirection = in_array($this->folderSortDirection, ['asc', 'desc']) ? $this->folderSortDirection : 'asc';
        
        if ($folderSortColumn === 'created_by') {
            $foldersQuery->join('users', 'folders.created_by', '=', 'users.id')
                ->select('folders.*')
                ->orderBy('users.name', $folderSortDirection);
        } else {
            $foldersQuery->orderBy($folderSortColumn, $folderSortDirection);
        }
        
        $folders = $foldersQuery->with('creator')->get();

        // Apply document sorting
        $validDocumentSortColumns = ['name', 'uploaded_by', 'created_at', 'size'];
        $documentSortColumn = in_array($this->documentSortBy, $validDocumentSortColumns) ? $this->documentSortBy : 'created_at';
        $documentSortDirection = in_array($this->documentSortDirection, ['asc', 'desc']) ? $this->documentSortDirection : 'desc';
        
        if ($documentSortColumn === 'uploaded_by') {
            $documentsQuery->join('users', 'documents.uploaded_by', '=', 'users.id')
                ->select('documents.*')
                ->orderBy('users.name', $documentSortDirection);
        } else {
            $documentsQuery->orderBy($documentSortColumn, $documentSortDirection);
        }
        
        $documents = $documentsQuery->with(['team', 'uploader'])->paginate(25);

        // Get current folder for breadcrumbs
        $currentFolder = $this->currentFolderId
            ? Folder::find($this->currentFolderId)
            : null;

        // Get filter options
        $statuses = Document::forTeam($this->teamId)
            ->distinct()
            ->pluck('upload_status')
            ->sort()
            ->values();

        $mimeTypes = Document::forTeam($this->teamId)
            ->distinct()
            ->pluck('mime_type')
            ->sort()
            ->values();

        $allFolders = Folder::forTeam($this->teamId)->orderBy('name')->get();

        return view('afterburner-documents::documents.index', [
            'team' => $team,
            'folders' => $folders,
            'documents' => $documents,
            'currentFolder' => $currentFolder,
            'statuses' => $statuses,
            'mimeTypes' => $mimeTypes,
            'allFolders' => $allFolders,
            'folderTree' => $this->folderTree,
        ]);
    }
}

