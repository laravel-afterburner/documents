<div>

    <div>
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Breadcrumbs -->
            <div class="mb-4">
                @include('afterburner-documents::components.breadcrumbs', ['folder' => $currentFolder])
            </div>

            <!-- Filters -->
            <div class="mb-6 bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
                <!-- Filters Header -->
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                        Search & Filters
                    </h3>
                    <button
                        type="button"
                        wire:click="toggleFilters"
                        class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-600"
                    >
                        <span>{{ $showFilters ? 'Hide' : 'Show' }} Filters</span>
                        <svg 
                            class="ml-2 h-4 w-4 transition-transform {{ $showFilters ? 'rotate-180' : '' }}" 
                            fill="none" 
                            stroke="currentColor" 
                            viewBox="0 0 24 24"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                </div>

                <!-- Filters Content -->
                @if($showFilters)
                    <div class="p-6">
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
                @endif
            </div>

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

            <!-- Documents and Folders List -->
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
                @if($folders->count() > 0 || $documents->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-900">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        <button 
                                            type="button"
                                            wire:click="sortByName"
                                            class="flex items-center space-x-1 hover:text-gray-700 dark:hover:text-gray-300 focus:outline-none"
                                        >
                                            <span>Name</span>
                                            @if($folderSortBy === 'name' || $documentSortBy === 'name')
                                                @if(($folderSortBy === 'name' && $folderSortDirection === 'asc') || ($documentSortBy === 'name' && $documentSortDirection === 'asc'))
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                                                    </svg>
                                                @else
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                                    </svg>
                                                @endif
                                            @else
                                                <svg class="w-4 h-4 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                                                </svg>
                                            @endif
                                        </button>
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        <button 
                                            type="button"
                                            wire:click="sortByOwner"
                                            class="flex items-center space-x-1 hover:text-gray-700 dark:hover:text-gray-300 focus:outline-none"
                                        >
                                            <span>Owner</span>
                                            @if($folderSortBy === 'created_by' || $documentSortBy === 'uploaded_by')
                                                @if(($folderSortBy === 'created_by' && $folderSortDirection === 'asc') || ($documentSortBy === 'uploaded_by' && $documentSortDirection === 'asc'))
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                                                    </svg>
                                                @else
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                                    </svg>
                                                @endif
                                            @else
                                                <svg class="w-4 h-4 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                                                </svg>
                                            @endif
                                        </button>
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        <button 
                                            type="button"
                                            wire:click="sortByModified"
                                            class="flex items-center space-x-1 hover:text-gray-700 dark:hover:text-gray-300 focus:outline-none"
                                        >
                                            <span>Modified</span>
                                            @if($folderSortBy === 'updated_at' || $documentSortBy === 'created_at')
                                                @if(($folderSortBy === 'updated_at' && $folderSortDirection === 'asc') || ($documentSortBy === 'created_at' && $documentSortDirection === 'asc'))
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                                                    </svg>
                                                @else
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                                    </svg>
                                                @endif
                                            @else
                                                <svg class="w-4 h-4 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                                                </svg>
                                            @endif
                                        </button>
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        <button 
                                            type="button"
                                            wire:click="sortDocuments('size')"
                                            class="flex items-center space-x-1 hover:text-gray-700 dark:hover:text-gray-300 focus:outline-none"
                                        >
                                            <span>Size</span>
                                            @if($documentSortBy === 'size')
                                                @if($documentSortDirection === 'asc')
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                                                    </svg>
                                                @else
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                                    </svg>
                                                @endif
                                            @else
                                                <svg class="w-4 h-4 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                                                </svg>
                                            @endif
                                        </button>
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($folders as $folder)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors group">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <button
                                                type="button"
                                                wire:click="navigateToFolder({{ $folder->id }})"
                                                class="flex items-center space-x-3 cursor-pointer w-full text-left"
                                            >
                                                <svg class="w-6 h-6 text-yellow-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"></path>
                                                </svg>
                                                <div class="flex-1 min-w-0">
                                                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 truncate">
                                                        {{ $folder->name }}
                                                    </p>
                                                </div>
                                            </button>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            {{ $folder->creator->name ?? 'N/A' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            {{ $folder->updated_at->format('M d, Y') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            {{ $folder->getTotalDocumentsCount() }} items
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <div class="flex items-center justify-end space-x-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                                @can('update', $folder)
                                                    <button
                                                        type="button"
                                                        wire:click="openMoveFolderModal({{ $folder->id }})"
                                                        class="p-1 text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 rounded"
                                                        title="Move folder"
                                                    >
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                                                        </svg>
                                                    </button>
                                                    <button
                                                        type="button"
                                                        wire:click="openEditFolderModal({{ $folder->id }})"
                                                        class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded"
                                                        title="Edit folder"
                                                    >
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                        </svg>
                                                    </button>
                                                @endcan
                                                @can('delete', $folder)
                                                    <button
                                                        type="button"
                                                        wire:click="confirmDelete({{ $folder->id }})"
                                                        class="p-1 text-gray-400 hover:text-red-600 dark:hover:text-red-400 rounded"
                                                        title="Delete folder"
                                                    >
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                        </svg>
                                                    </button>
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach

                                <div wire:poll.5s style="display: contents;">
                                @foreach($documents as $document)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors group">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <button
                                                type="button"
                                                wire:click="openDocumentViewer({{ $document->id }})"
                                                class="flex items-center space-x-3 cursor-pointer w-full text-left"
                                            >
                                                {!! $document->getIconSvg('w-6 h-6') !!}
                                                <div class="flex-1 min-w-0">
                                                    <div class="flex items-center space-x-2">
                                                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 truncate">
                                                            {{ $document->name }}
                                                        </p>
                                                        @if($document->getCurrentVersionNumber())
                                                            <span class="px-1.5 py-0.5 text-xs font-medium rounded bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                                                v{{ $document->getCurrentVersionNumber() }}
                                                            </span>
                                                        @endif
                                                    </div>
                                                    <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                                        {{ $document->filename }}
                                                    </p>
                                                </div>
                                            </button>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            {{ $document->uploader->name ?? 'N/A' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            {{ $document->getFormattedCreatedAt('M d, Y') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            @if($document->size)
                                                @if($document->size < 1024)
                                                    {{ $document->size }} B
                                                @elseif($document->size < 1048576)
                                                    {{ number_format($document->size / 1024, 1) }} KB
                                                @else
                                                    {{ number_format($document->size / 1048576, 2) }} MB
                                                @endif
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <div class="flex items-center justify-end space-x-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                                @can('update', $document)
                                                    <button
                                                        type="button"
                                                        wire:click="openMoveDocumentModal({{ $document->id }})"
                                                        class="p-1 text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 rounded"
                                                        title="Move document"
                                                    >
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                                                        </svg>
                                                    </button>
                                                    <button
                                                        type="button"
                                                        wire:click="openEditDocumentModal({{ $document->id }})"
                                                        class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded"
                                                        title="Edit document"
                                                    >
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                        </svg>
                                                    </button>
                                                @endcan
                                                @can('download', $document)
                                                    <a
                                                        href="{{ route('teams.documents.download', [$document->team, $document]) }}"
                                                        class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded"
                                                        title="Download document"
                                                    >
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                                        </svg>
                                                    </a>
                                                @endcan
                                                @can('delete', $document)
                                                    <button
                                                        type="button"
                                                        wire:click="confirmDeleteDocument({{ $document->id }})"
                                                        class="p-1 text-gray-400 hover:text-red-600 dark:hover:text-red-400 rounded"
                                                        title="Delete document"
                                                    >
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                        </svg>
                                                    </button>
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                                </div>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
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

    <!-- Document Edit Modal -->
    <x-dialog-modal wire:model.live="showingEditDocumentModal" maxWidth="2xl">
        <x-slot name="title">
            Edit Document
        </x-slot>

        <x-slot name="content">
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
            <div class="mt-4">
                <x-label for="newDocumentFile" value="Replace File (Optional)" />
                <x-input
                    id="newDocumentFile"
                    type="file"
                    class="mt-1 block w-full"
                    wire:model="newDocumentFile"
                />
                <x-input-error for="newDocumentFile" class="mt-2" />
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Leave empty to keep the current file.
                </p>
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="closeEditDocumentModal">
                Cancel
            </x-secondary-button>
            <x-button wire:click="updateDocument" class="ml-3" no-spinner>
                Update
            </x-button>
        </x-slot>
    </x-dialog-modal>

    <!-- Document Delete Confirmation Modal -->
    <x-confirmation-modal wire:model.live="showingDeleteDocumentModal">
        <x-slot name="title">
            Delete Document
        </x-slot>

        <x-slot name="content">
            Are you sure you want to delete this document? This action cannot be undone.
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="cancelDeleteDocument">
                Cancel
            </x-secondary-button>
            <x-danger-button wire:click="deleteDocument" class="ml-3">
                Delete
            </x-danger-button>
        </x-slot>
    </x-confirmation-modal>

    <!-- Move Document Modal -->
    <x-dialog-modal wire:model.live="showingMoveDocumentModal" maxWidth="lg">
        <x-slot name="title">
            Move Document
        </x-slot>

        <x-slot name="content">
            @if($documentToMove)
                <div class="space-y-4">
                    <!-- Current Document Info -->
                    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                        <div class="flex items-center space-x-3">
                            {!! $documentToMove->getIconSvg('w-6 h-6') !!}
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                    {{ $documentToMove->name }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    Current location: 
                                    @if($documentToMove->folder)
                                        @foreach($documentToMove->folder->getPath() as $pathFolder)
                                            {{ $pathFolder->name }}@if(!$loop->last) / @endif
                                        @endforeach
                                    @else
                                        Root
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Folder Selection -->
                    <div>
                        <x-label value="Select Destination Folder" />
                        <div class="mt-2 border border-gray-200 dark:border-gray-700 rounded-lg p-4 max-h-96 overflow-y-auto">
                            <!-- Root Option -->
                            <label class="flex items-center cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 rounded px-2 py-1.5 transition-colors mb-2 {{ ($selectedTargetFolderId === null || $selectedTargetFolderId === '') ? 'bg-indigo-50 dark:bg-indigo-900/20' : '' }}">
                                <input 
                                    type="radio" 
                                    wire:model="selectedTargetFolderId" 
                                    value=""
                                    class="mr-3 h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 dark:border-gray-600"
                                >
                                <div class="flex items-center flex-1">
                                    <svg class="w-5 h-5 text-gray-400 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path>
                                    </svg>
                                    <span class="text-sm font-medium text-gray-900 dark:text-gray-100">Root</span>
                                </div>
                            </label>

                            <!-- Folder Tree -->
                            @if($folderTree && $folderTree->count() > 0)
                                @include('afterburner-documents::components.folder-tree', [
                                    'folders' => $folderTree,
                                    'selectedId' => $selectedTargetFolderId
                                ])
                            @else
                                <p class="text-sm text-gray-500 dark:text-gray-400 py-4 text-center">
                                    No folders available. Select Root to move to the top level.
                                </p>
                            @endif
                        </div>
                    </div>
                </div>
            @endif
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="closeMoveDocumentModal" wire:loading.attr="disabled">
                Cancel
            </x-secondary-button>
            <x-button wire:click="moveDocument" class="ml-3" wire:loading.attr="disabled">
                Move Document
            </x-button>
        </x-slot>
    </x-dialog-modal>

    <!-- Move Folder Modal -->
    <x-dialog-modal wire:model.live="showingMoveFolderModal" maxWidth="lg">
        <x-slot name="title">
            Move Folder
        </x-slot>

        <x-slot name="content">
            @if($folderToMove)
                <div class="space-y-4">
                    <!-- Current Folder Info -->
                    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                        <div class="flex items-center space-x-3">
                            <svg class="w-6 h-6 text-yellow-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"></path>
                            </svg>
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                    {{ $folderToMove->name }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    Current location: 
                                    @if($folderToMove->parent)
                                        @foreach($folderToMove->parent->getPath() as $pathFolder)
                                            {{ $pathFolder->name }}@if(!$loop->last) / @endif
                                        @endforeach
                                    @else
                                        Root
                                    @endif
                                </p>
                                @if(count($folderToMove->getDescendantIds()) > 0 || $folderToMove->getTotalDocumentsCount() > 0)
                                    <p class="text-xs text-amber-600 dark:text-amber-400 mt-1">
                                        This folder contains {{ count($folderToMove->getDescendantIds()) }} subfolder(s) and {{ $folderToMove->getTotalDocumentsCount() }} document(s).
                                    </p>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Folder Selection -->
                    <div>
                        <x-label value="Select Destination Folder" />
                        <div class="mt-2 border border-gray-200 dark:border-gray-700 rounded-lg p-4 max-h-96 overflow-y-auto">
                            <!-- Root Option -->
                            <label class="flex items-center cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 rounded px-2 py-1.5 transition-colors mb-2 {{ ($selectedTargetFolderId === null || $selectedTargetFolderId === '') ? 'bg-indigo-50 dark:bg-indigo-900/20' : '' }}">
                                <input 
                                    type="radio" 
                                    wire:model="selectedTargetFolderId" 
                                    value=""
                                    class="mr-3 h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 dark:border-gray-600"
                                >
                                <div class="flex items-center flex-1">
                                    <svg class="w-5 h-5 text-gray-400 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path>
                                    </svg>
                                    <span class="text-sm font-medium text-gray-900 dark:text-gray-100">Root</span>
                                </div>
                            </label>

                            <!-- Folder Tree -->
                            @if($folderTree && $folderTree->count() > 0)
                                @include('afterburner-documents::components.folder-tree', [
                                    'folders' => $folderTree,
                                    'selectedId' => $selectedTargetFolderId
                                ])
                            @else
                                <p class="text-sm text-gray-500 dark:text-gray-400 py-4 text-center">
                                    No other folders available. Select Root to move to the top level.
                                </p>
                            @endif
                        </div>
                    </div>
                </div>
            @endif
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="closeMoveFolderModal" wire:loading.attr="disabled">
                Cancel
            </x-secondary-button>
            <x-button wire:click="moveFolder" class="ml-3" wire:loading.attr="disabled">
                Move Folder
            </x-button>
        </x-slot>
    </x-dialog-modal>

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

