<?php

namespace Afterburner\Documents\Livewire\Documents;

use Afterburner\Documents\Actions\CreateFolder;
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

    protected function rules(): array
    {
        $maxFileSize = config('afterburner-documents.upload.max_file_size', 2147483648);
        $allowedMimeTypes = config('afterburner-documents.upload.allowed_mime_types', []);

        $rules = [
            'folderName' => 'required|string|max:255',
            'uploadFiles.*' => [
                'required',
                'file',
                'max:'.$maxFileSize,
            ],
        ];

        if (!empty($allowedMimeTypes)) {
            $rules['uploadFiles.*'][] = 'mimetypes:'.implode(',', $allowedMimeTypes);
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

    public function updatedCurrentFolderId($value)
    {
        $this->currentFolderId = $value !== '' && $value !== null ? (int) $value : null;
        $this->resetPage();
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
        $this->validate();

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

        $this->validate();

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
            // Check if folder has children or documents
            if ($this->folderToDelete->children()->count() > 0) {
                $this->dangerBanner(__('Cannot delete a folder with subfolders inside.'));
                $this->showingDeleteModal = false;
                $this->folderToDelete = null;
                return;
            }

            if ($this->folderToDelete->documents()->count() > 0) {
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
    public function refreshDocuments()
    {
        // Component will re-render automatically
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

        $folders = $foldersQuery->orderBy('name')->get();
        $documents = $documentsQuery->with('team')->orderBy('created_at', 'desc')->paginate(25);

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
        ]);
    }
}

