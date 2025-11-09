@props(['folder'])

@php
    $maxVisibleMobile = 3; // Number of folders to show on mobile before truncation (shows last 2)
    $maxVisibleDesktop = 7; // Number of folders to show on desktop before truncation (shows last 6)
    $path = $folder ? $folder->getPath() : [];
    $pathCount = count($path);
    $shouldTruncateMobile = $pathCount > $maxVisibleMobile;
    $shouldTruncateDesktop = $pathCount > $maxVisibleDesktop;
    
    if ($shouldTruncateDesktop) {
        // Desktop: Show root + ellipsis + last 6 folders
        $visibleFoldersDesktop = array_slice($path, -($maxVisibleDesktop - 1));
        $hiddenFoldersDesktop = array_slice($path, 0, -($maxVisibleDesktop - 1));
    } else {
        $visibleFoldersDesktop = $path;
        $hiddenFoldersDesktop = [];
    }
    
    if ($shouldTruncateMobile) {
        // Mobile: Show root + ellipsis + last 2 folders
        $visibleFoldersMobile = array_slice($path, -($maxVisibleMobile - 1));
        $hiddenFoldersMobile = array_slice($path, 0, -($maxVisibleMobile - 1));
    } else {
        $visibleFoldersMobile = $path;
        $hiddenFoldersMobile = [];
    }
    
    // Use desktop values as default, mobile will be hidden/shown with CSS
    $visibleFolders = $visibleFoldersDesktop;
    $hiddenFolders = $hiddenFoldersDesktop;
    $shouldTruncate = $shouldTruncateDesktop;
@endphp

<nav class="flex overflow-x-auto overflow-y-visible" aria-label="Breadcrumb" x-data="{ 
    showHiddenFolders: false,
    updateDropdownPosition() {
        if (this.showHiddenFolders) {
            // Update desktop dropdown position
            if (this.$refs.ellipsisButton && this.$refs.dropdownMenu) {
                const rect = this.$refs.ellipsisButton.getBoundingClientRect();
                this.$refs.dropdownMenu.style.left = rect.left + 'px';
                this.$refs.dropdownMenu.style.top = (rect.bottom + 8) + 'px';
            }
            // Update mobile dropdown position
            if (this.$refs.ellipsisButtonMobile && this.$refs.dropdownMenuMobile) {
                const rect = this.$refs.ellipsisButtonMobile.getBoundingClientRect();
                this.$refs.dropdownMenuMobile.style.left = rect.left + 'px';
                this.$refs.dropdownMenuMobile.style.top = (rect.bottom + 8) + 'px';
            }
        }
    }
}" x-init="
    $watch('showHiddenFolders', () => {
        if (showHiddenFolders) {
            setTimeout(() => updateDropdownPosition(), 10);
            window.addEventListener('scroll', updateDropdownPosition, true);
            window.addEventListener('resize', updateDropdownPosition);
        } else {
            window.removeEventListener('scroll', updateDropdownPosition, true);
            window.removeEventListener('resize', updateDropdownPosition);
        }
    })
" @click.away="showHiddenFolders = false">
    <ol class="inline-flex items-center space-x-1 md:space-x-3 min-w-0 flex-1">
        <li class="inline-flex items-center flex-shrink-0">
            @if($folder)
                <button
                    type="button"
                    wire:click="navigateToFolder(null)"
                    class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-indigo-600 dark:text-gray-400 dark:hover:text-white whitespace-nowrap"
                >
                    <svg class="w-4 h-4 mx-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"></path>
                    </svg>
                    Documents
                </button>
            @else
                <span class="inline-flex items-center text-sm font-medium text-gray-500 dark:text-gray-400 whitespace-nowrap">
                    <svg class="w-4 h-4 mx-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"></path>
                    </svg>
                    Documents
                </span>
            @endif
        </li>
        
        @if($folder)
            {{-- Mobile ellipsis (shown only on mobile when needed) --}}
            @if($shouldTruncateMobile && count($hiddenFoldersMobile) > 0)
                <li class="flex-shrink-0 relative md:hidden">
                    <div class="flex items-center">
                        <svg class="w-6 h-6 text-gray-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        <button
                            type="button"
                            @click="showHiddenFolders = !showHiddenFolders"
                            x-ref="ellipsisButtonMobile"
                            class="ml-1 text-sm font-medium text-gray-700 hover:text-indigo-600 md:ml-2 dark:text-gray-400 dark:hover:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 rounded px-1"
                            aria-label="Show hidden folders"
                        >
                            ...
                        </button>
                    </div>
                    
                    {{-- Dropdown menu for hidden folders (mobile) --}}
                    <div
                        x-show="showHiddenFolders"
                        x-ref="dropdownMenuMobile"
                        x-transition:enter="transition ease-out duration-100"
                        x-transition:enter-start="opacity-0 scale-95"
                        x-transition:enter-end="opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-75"
                        x-transition:leave-start="opacity-100 scale-100"
                        x-transition:leave-end="opacity-0 scale-95"
                        class="fixed w-56 rounded-md shadow-lg bg-white dark:bg-gray-800 ring-1 ring-black ring-opacity-5 z-[9999]"
                        style="display: none;"
                    >
                        <div class="py-1" role="menu" aria-orientation="vertical">
                            @foreach($hiddenFoldersMobile as $hiddenFolder)
                                <button
                                    type="button"
                                    wire:click="navigateToFolder({{ $hiddenFolder->id }})"
                                    @click="showHiddenFolders = false"
                                    class="block w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:bg-gray-100 dark:focus:bg-gray-700"
                                    role="menuitem"
                                >
                                    <div class="flex items-center">
                                        <svg class="w-4 h-4 text-gray-400 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"></path>
                                        </svg>
                                        <span class="truncate">{{ $hiddenFolder->name }}</span>
                                    </div>
                                </button>
                            @endforeach
                        </div>
                    </div>
                </li>
            @endif
            
            {{-- Desktop ellipsis (shown only on desktop when needed) --}}
            @if($shouldTruncateDesktop && count($hiddenFoldersDesktop) > 0)
                <li class="flex-shrink-0 relative hidden md:block">
                    <div class="flex items-center">
                        <svg class="w-6 h-6 text-gray-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        <button
                            type="button"
                            @click="showHiddenFolders = !showHiddenFolders"
                            x-ref="ellipsisButton"
                            class="ml-1 text-sm font-medium text-gray-700 hover:text-indigo-600 md:ml-2 dark:text-gray-400 dark:hover:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 rounded px-1"
                            aria-label="Show hidden folders"
                        >
                            ...
                        </button>
                    </div>
                    
                    {{-- Dropdown menu for hidden folders (desktop) --}}
                    <div
                        x-show="showHiddenFolders"
                        x-ref="dropdownMenu"
                        x-transition:enter="transition ease-out duration-100"
                        x-transition:enter-start="opacity-0 scale-95"
                        x-transition:enter-end="opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-75"
                        x-transition:leave-start="opacity-100 scale-100"
                        x-transition:leave-end="opacity-0 scale-95"
                        class="fixed w-56 rounded-md shadow-lg bg-white dark:bg-gray-800 ring-1 ring-black ring-opacity-5 z-[9999]"
                        style="display: none;"
                    >
                        <div class="py-1" role="menu" aria-orientation="vertical">
                            @foreach($hiddenFoldersDesktop as $hiddenFolder)
                                <button
                                    type="button"
                                    wire:click="navigateToFolder({{ $hiddenFolder->id }})"
                                    @click="showHiddenFolders = false"
                                    class="block w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:bg-gray-100 dark:focus:bg-gray-700"
                                    role="menuitem"
                                >
                                    <div class="flex items-center">
                                        <svg class="w-4 h-4 text-gray-400 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"></path>
                                        </svg>
                                        <span class="truncate">{{ $hiddenFolder->name }}</span>
                                    </div>
                                </button>
                            @endforeach
                        </div>
                    </div>
                </li>
            @endif
        @endif
        
        @if($folder)
            @php
                // Determine which folders to show on mobile vs desktop
                $mobileStartIndex = $shouldTruncateMobile ? count($hiddenFoldersMobile) : 0;
                $desktopStartIndex = $shouldTruncateDesktop ? count($hiddenFoldersDesktop) : 0;
            @endphp
            @foreach($path as $index => $pathFolder)
                @php
                    $isMobileVisible = $index >= $mobileStartIndex;
                    $isDesktopVisible = $index >= $desktopStartIndex;
                    // Build visibility classes
                    $visibilityClasses = '';
                    if ($isMobileVisible && $isDesktopVisible) {
                        // Visible on all screens
                        $visibilityClasses = '';
                    } elseif ($isMobileVisible && !$isDesktopVisible) {
                        // Visible on mobile only
                        $visibilityClasses = 'md:hidden';
                    } elseif (!$isMobileVisible && $isDesktopVisible) {
                        // Visible on desktop only
                        $visibilityClasses = 'hidden md:block';
                    } else {
                        // Hidden on all screens (shouldn't happen, but just in case)
                        $visibilityClasses = 'hidden';
                    }
                @endphp
                <li class="flex-shrink-0 {{ $visibilityClasses }}">
                    <div class="flex items-center">
                        <svg class="w-6 h-6 text-gray-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        @if($pathFolder->id === $folder->id)
                            <span class="ml-1 text-sm font-medium text-gray-500 md:ml-2 dark:text-gray-400 truncate max-w-[150px] sm:max-w-none">{{ $pathFolder->name }}</span>
                        @else
                            <button
                                type="button"
                                wire:click="navigateToFolder({{ $pathFolder->id }})"
                                class="ml-1 text-sm font-medium text-gray-700 hover:text-indigo-600 md:ml-2 dark:text-gray-400 dark:hover:text-white truncate max-w-[150px] sm:max-w-none"
                            >
                                {{ $pathFolder->name }}
                            </button>
                        @endif
                    </div>
                </li>
            @endforeach
        @endif
    </ol>
</nav>

