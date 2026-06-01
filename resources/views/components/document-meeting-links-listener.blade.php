@props(['team'])

@if (class_exists(\Afterburner\Meetings\Livewire\Documents\DocumentMeetingLinks::class))
    @livewire('meetings.document-meeting-links', ['teamId' => $team->id], key('document-meeting-links-'.$team->id))
@endif
