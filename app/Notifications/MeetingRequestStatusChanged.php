<?php

namespace App\Notifications;

use App\Models\MeetingRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MeetingRequestStatusChanged extends Notification implements ShouldQueue
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
        $status = $this->meetingRequest->status;

        $mail = (new MailMessage)
            ->subject("Meeting Request Status: " . ucfirst($status))
            ->line("Your meeting request for property {$property->label} has been " . $status . ".");

        // Add specific information based on status
        if ($status === 'approved') {
            $mail->line("Scheduled Date: " . $this->meetingRequest->approved_date->format('F j, Y \a\t g:i A'))
                ->line("Please arrive on time for your scheduled appointment.");
        } elseif ($status === 'rejected') {
            $mail->line("We're sorry we couldn't accommodate your meeting request at this time.");
        }

        // Add admin notes if available
        if (!empty($this->meetingRequest->admin_notes)) {
            $mail->line("Additional information: " . $this->meetingRequest->admin_notes);
        }

        $mail->action('View Request Details', url("/meeting-requests/{$this->meetingRequest->id}"))
            ->line('Thank you for using our service.');

        return $mail;
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $property = $this->meetingRequest->property;

        return [
            'meeting_request_id' => $this->meetingRequest->id,
            'property_id' => $property->id,
            'property_label' => $property->label,
            'status' => $this->meetingRequest->status,
            'type' => 'meeting_request_status_changed',
            'message' => "Your meeting request for property {$property->label} has been {$this->meetingRequest->status}",
        ];
    }
}
