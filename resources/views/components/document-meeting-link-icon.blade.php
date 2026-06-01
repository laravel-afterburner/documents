@props(['document', 'team'])

@if (view()->exists('afterburner-meetings::components.document-meeting-link-icon'))
    @include('afterburner-meetings::components.document-meeting-link-icon', [
        'document' => $document,
        'team' => $team,
    ])
@endif
