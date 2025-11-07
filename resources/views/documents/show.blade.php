<div>
    <div class="mb-6">
        <a href="{{ route('documents.index', ['team' => $document->team_id]) }}" 
           class="text-sm text-gray-600 hover:text-gray-900">
            ← Back to Documents
        </a>
    </div>

    <div class="bg-white shadow sm:rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">{{ $document->name }}</h1>
                    <p class="mt-1 text-sm text-gray-500">{{ $document->original_filename }} • {{ $document->getFileSizeHuman() }}</p>
                </div>
                <div class="flex space-x-3">
                    @can('download', $document)
                        <button wire:click="download" 
                                class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                            Download
                        </button>
                    @endcan
                    @can('update', $document)
                        <button wire:click="openPermissionsModal" 
                                class="rounded-md bg-gray-600 px-4 py-2 text-sm font-medium text-white hover:bg-gray-700">
                            Permissions
                        </button>
                    @endcan
                    @can('delete', $document)
                        <button wire:click="confirmDelete" 
                                class="rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700">
                            Delete
                        </button>
                    @endcan
                </div>
            </div>

            <!-- Tabs -->
            <div class="border-b border-gray-200">
                <nav class="-mb-px flex space-x-8">
                    <button wire:click="$set('activeTab', 'details')"
                            class="@if($activeTab === 'details') border-indigo-500 text-indigo-600 @else border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 @endif whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium">
                        Details
                    </button>
                    <button wire:click="$set('activeTab', 'versions')"
                            class="@if($activeTab === 'versions') border-indigo-500 text-indigo-600 @else border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 @endif whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium">
                        Versions
                    </button>
                    <button wire:click="$set('activeTab', 'preview')"
                            class="@if($activeTab === 'preview') border-indigo-500 text-indigo-600 @else border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 @endif whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium">
                        Preview
                    </button>
                </nav>
            </div>

            <!-- Tab Content -->
            <div class="mt-6">
                @if($activeTab === 'details')
                    <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Created</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $document->created_at->format('M d, Y H:i') }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Created By</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $document->creator->name }}</dd>
                        </div>
                        @if($document->updated_by)
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Last Updated</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $document->updated_at->format('M d, Y H:i') }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Updated By</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $document->updater->name }}</dd>
                            </div>
                        @endif
                        <div>
                            <dt class="text-sm font-medium text-gray-500">File Type</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $document->mime_type }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Version</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $document->version }}</dd>
                        </div>
                        @if($document->folder)
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Folder</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $document->folder->name }}</dd>
                            </div>
                        @endif
                        @if($document->retentionTag)
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Retention Tag</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $document->retentionTag->name }}</dd>
                            </div>
                        @endif
                    </dl>
                @elseif($activeTab === 'versions')
                    <button wire:click="openVersionsModal" 
                            class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                        View All Versions
                    </button>
                @elseif($activeTab === 'preview')
                    @if($document->isImage() || $document->isPdf())
                        @if($previewUrl)
                            <div class="rounded-lg border border-gray-200 p-4">
                                @if($document->isImage())
                                    <img src="{{ $previewUrl }}" alt="{{ $document->name }}" class="max-w-full">
                                @elseif($document->isPdf())
                                    <iframe src="{{ $previewUrl }}" class="h-screen w-full rounded-lg"></iframe>
                                @endif
                            </div>
                        @else
                            <button wire:click="getPreviewUrl" 
                                    class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                                Generate Preview
                            </button>
                        @endif
                    @else
                        <p class="text-gray-500">Preview not available for this file type.</p>
                    @endif
                @endif
            </div>
        </div>
    </div>

    <!-- Permissions Modal -->
    <div x-data="{ show: @entangle('showPermissionsModal') }" 
         x-show="show" 
         class="fixed inset-0 z-50 overflow-y-auto"
         style="display: none;">
        <div class="flex min-h-screen items-end justify-center px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="show = false"></div>
            <div class="inline-block transform overflow-hidden rounded-lg bg-white text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-2xl sm:align-middle">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">Document Permissions</h3>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Role</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500">View</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500">Edit</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500">Delete</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500">Share</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                @foreach($availableRoles as $roleName => $roleSlug)
                                    <tr>
                                        <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900">{{ $roleName }}</td>
                                        <td class="whitespace-nowrap px-6 py-4 text-center text-sm text-gray-500">
                                            <input type="checkbox" 
                                                   @if(isset($permissions[$roleSlug]) && $permissions[$roleSlug]['can_view']) checked @endif
                                                   wire:change="updatePermission('{{ $roleSlug }}', 'can_view', $event.target.checked)"
                                                   class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-center text-sm text-gray-500">
                                            <input type="checkbox" 
                                                   @if(isset($permissions[$roleSlug]) && $permissions[$roleSlug]['can_edit']) checked @endif
                                                   wire:change="updatePermission('{{ $roleSlug }}', 'can_edit', $event.target.checked)"
                                                   class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-center text-sm text-gray-500">
                                            <input type="checkbox" 
                                                   @if(isset($permissions[$roleSlug]) && $permissions[$roleSlug]['can_delete']) checked @endif
                                                   wire:change="updatePermission('{{ $roleSlug }}', 'can_delete', $event.target.checked)"
                                                   class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-center text-sm text-gray-500">
                                            <input type="checkbox" 
                                                   @if(isset($permissions[$roleSlug]) && $permissions[$roleSlug]['can_share']) checked @endif
                                                   wire:change="updatePermission('{{ $roleSlug }}', 'can_share', $event.target.checked)"
                                                   class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                                            @if(isset($permissions[$roleSlug]))
                                                <button wire:click="removePermission('{{ $roleSlug }}')" 
                                                        class="text-red-600 hover:text-red-900">
                                                    Remove
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                    <button wire:click="closePermissionsModal" 
                            class="inline-flex w-full justify-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-base font-medium text-white shadow-sm hover:bg-indigo-700 sm:ml-3 sm:w-auto">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Versions Modal -->
    <div x-data="{ show: @entangle('showVersionsModal') }" 
         x-show="show" 
         class="fixed inset-0 z-50 overflow-y-auto"
         style="display: none;">
        <div class="flex min-h-screen items-end justify-center px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="show = false"></div>
            <div class="inline-block transform overflow-hidden rounded-lg bg-white text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-3xl sm:align-middle">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">Version History</h3>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Version</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Created</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Created By</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Size</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Summary</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                @foreach($versions as $version)
                                    <tr>
                                        <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900">
                                            v{{ $version['version_number'] }}
                                            @if($version['version_number'] == $document->version)
                                                <span class="ml-2 rounded-full bg-green-100 px-2 py-1 text-xs text-green-800">Current</span>
                                            @endif
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                            {{ \Carbon\Carbon::parse($version['created_at'])->format('M d, Y H:i') }}
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                            {{ $version['creator']['name'] ?? 'Unknown' }}
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                            {{ number_format($version['file_size'] / 1024, 2) }} KB
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-500">
                                            {{ $version['change_summary'] ?? '-' }}
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                                            <div class="flex justify-end space-x-2">
                                                <button wire:click="downloadVersion({{ $version['id'] }})" 
                                                        class="text-indigo-600 hover:text-indigo-900">
                                                    Download
                                                </button>
                                                @if($version['version_number'] != $document->version)
                                                    <button wire:click="confirmRestore({{ $version['id'] }})" 
                                                            class="text-green-600 hover:text-green-900">
                                                        Restore
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                    <button wire:click="closeVersionsModal" 
                            class="inline-flex w-full justify-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-base font-medium text-white shadow-sm hover:bg-indigo-700 sm:ml-3 sm:w-auto">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div x-data="{ show: @entangle('showDeleteConfirm').live }" 
         x-show="show" 
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex min-h-screen items-end justify-center px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" 
                 wire:click="$set('showDeleteConfirm', false)"
                 @click="show = false"></div>
            <div class="inline-block transform overflow-hidden rounded-lg bg-white dark:bg-gray-800 text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:align-middle">
                <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-gray-100">Delete Document</h3>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Are you sure you want to delete this document? This action cannot be undone.</p>
                    <p class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">{{ $document->name }}</p>
                </div>
                <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                    <button wire:click="delete" 
                            class="inline-flex w-full justify-center rounded-md border border-transparent bg-red-600 px-4 py-2 text-base font-medium text-white shadow-sm hover:bg-red-700 sm:ml-3 sm:w-auto">
                        Delete
                    </button>
                    <button wire:click="$set('showDeleteConfirm', false)" 
                            @click="show = false"
                            class="mt-3 inline-flex w-full justify-center rounded-md border border-gray-300 bg-white dark:bg-gray-600 dark:text-white px-4 py-2 text-base font-medium text-gray-700 shadow-sm hover:bg-gray-50 dark:hover:bg-gray-500 sm:mt-0 sm:w-auto">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Restore Confirmation Modal -->
    <div x-data="{ show: @entangle('showRestoreConfirm').live }" 
         x-show="show" 
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex min-h-screen items-end justify-center px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" 
                 wire:click="cancelRestore"
                 @click="show = false"></div>
            <div class="inline-block transform overflow-hidden rounded-lg bg-white dark:bg-gray-800 text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:align-middle">
                <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-gray-100">Restore Version</h3>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        Are you sure you want to restore this document to version {{ $versionToRestore->version_number ?? '' }}? 
                        The current version will be saved as a backup.
                    </p>
                </div>
                <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                    <button wire:click="restore" 
                            class="inline-flex w-full justify-center rounded-md border border-transparent bg-green-600 px-4 py-2 text-base font-medium text-white shadow-sm hover:bg-green-700 sm:ml-3 sm:w-auto">
                        Restore
                    </button>
                    <button wire:click="cancelRestore" 
                            @click="show = false"
                            class="mt-3 inline-flex w-full justify-center rounded-md border border-gray-300 bg-white dark:bg-gray-600 dark:text-white px-4 py-2 text-base font-medium text-gray-700 shadow-sm hover:bg-gray-50 dark:hover:bg-gray-500 sm:mt-0 sm:w-auto">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

