<?php

namespace App\Notifications;

use App\Models\MeetingRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewMeetingRequest extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public MeetingRequest $meetingRequest)
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $property = $this->meetingRequest->property;
        $user = $this->meetingRequest->user;

        return (new MailMessage)
            ->subject('New Meeting Request')
            ->line("A new meeting request has been submitted by {$user->name} for property {$property->label}.")
            ->line("Requested Date: " . $this->meetingRequest->requested_date->format('F j, Y \a\t g:i A'))
            ->line("Purpose: " . $this->meetingRequest->purpose)
            ->when($this->meetingRequest->notes, function ($mail) {
                return $mail->line("Notes: " . $this->meetingRequest->notes);
            })
            ->line("ID Document: " . ($this->meetingRequest->id_document ? "Uploaded" : "Not Provided"))
            ->action('View Request', url("/admin/meeting-requests/{$this->meetingRequest->id}"))
            ->line('Please review this request at your earliest convenience.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $property = $this->meetingRequest->property;
        $user = $this->meetingRequest->user;

        return [
            'meeting_request_id' => $this->meetingRequest->id,
            'property_id' => $property->id,
            'property_label' => $property->label,
            'user_id' => $user->id,
            'user_name' => $user->name,
            'requested_date' => $this->meetingRequest->requested_date->format('Y-m-d H:i:s'),
            'purpose' => $this->meetingRequest->purpose,
            'type' => 'new_meeting_request',
            'message' => "New meeting request from {$user->name} for property {$property->label}",
        ];
    }
}
