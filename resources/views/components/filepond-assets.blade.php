@assets
    @if (file_exists(public_path('vendor/livewire-filepond/filepond.js')))
        <link rel="stylesheet" type="text/css" href="{{ asset('vendor/livewire-filepond/filepond.css') }}">
        <script type="module" src="{{ asset('vendor/livewire-filepond/filepond.js') }}" data-navigate-once data-navigate-track></script>
    @else
        @php
            $filepondVersion = \Composer\InstalledVersions::getPrettyVersion('spatie/livewire-filepond');
        @endphp
        <link rel="stylesheet" type="text/css" href="{{ route('livewire-filepond.styles') }}?v={{ $filepondVersion }}">
        <script type="module" src="{{ route('livewire-filepond.scripts') }}?v={{ $filepondVersion }}" data-navigate-once data-navigate-track></script>
    @endif
@endassets
