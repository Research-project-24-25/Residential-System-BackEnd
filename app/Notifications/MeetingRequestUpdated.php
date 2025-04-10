<?php

namespace App\Notifications;

use App\Models\MeetingRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MeetingRequestUpdated extends Notification implements ShouldQueue
{
    use Queueable;

    protected $meetingRequest;
    protected $previousStatus;

    /**
     * Create a new notification instance.
     */
    public function __construct(MeetingRequest $meetingRequest, string $previousStatus)
    {
        $this->meetingRequest = $meetingRequest;
        $this->previousStatus = $previousStatus;
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
        $message = (new MailMessage)
            ->subject('Meeting Request Update')
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('Your meeting request status has been updated from ' . ucfirst($this->previousStatus) . ' to ' . ucfirst($this->meetingRequest->status) . '.')
            ->line('Property Type: ' . ucfirst($this->meetingRequest->property_type))
            ->action('View Meeting Request', url(config('app.frontend_url') . '/meeting-requests/' . $this->meetingRequest->id));

        if ($this->meetingRequest->status === 'scheduled') {
            $message->line('Scheduled Time: ' . $this->meetingRequest->preferred_time->format('F j, Y, g:i a'));
        }

        return $message->line('Thank you for using our platform!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $statusMessage = '';

        switch ($this->meetingRequest->status) {
            case 'scheduled':
                $statusMessage = 'Your meeting has been scheduled';
                break;
            case 'cancelled':
                $statusMessage = 'Your meeting has been cancelled';
                break;
            case 'completed':
                $statusMessage = 'Your meeting has been marked as completed';
                break;
            default:
                $statusMessage = 'Your meeting status has been updated to ' . $this->meetingRequest->status;
        }

        return [
            'meeting_request_id' => $this->meetingRequest->id,
            'title' => 'Meeting Request Updated',
            'message' => $statusMessage,
            'type' => 'meeting_updated',
            'property_type' => $this->meetingRequest->property_type,
            'property_id' => $this->meetingRequest->property_id,
            'preferred_time' => $this->meetingRequest->preferred_time,
            'status' => $this->meetingRequest->status,
            'previous_status' => $this->previousStatus
        ];
    }
}
