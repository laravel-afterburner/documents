<?php

namespace Afterburner\Documents\Notifications;

use Afterburner\Documents\Models\Document;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DocumentUploadComplete extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Document $document
    ) {
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Document Upload Complete')
            ->line("Your document '{$this->document->name}' has been uploaded successfully.")
            ->action('View Document', route('teams.documents.show', [
                'team' => $this->document->team_id,
                'document' => $this->document->id,
            ]));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'document_upload_complete',
            'document_id' => $this->document->id,
            'document_name' => $this->document->name,
            'team_id' => $this->document->team_id,
            'message' => "Document '{$this->document->name}' has been uploaded successfully.",
        ];
    }
}

