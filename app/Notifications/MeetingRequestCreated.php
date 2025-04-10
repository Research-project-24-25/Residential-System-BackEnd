<?php

namespace App\Notifications;

use App\Models\MeetingRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MeetingRequestCreated extends Notification implements ShouldQueue
{
    use Queueable;

    protected $meetingRequest;

    /**
     * Create a new notification instance.
     */
    public function __construct(MeetingRequest $meetingRequest)
    {
        $this->meetingRequest = $meetingRequest;
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
        return (new MailMessage)
            ->subject('Meeting Request Confirmation')
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('Your meeting request has been received and is awaiting confirmation.')
            ->line('Property Type: ' . ucfirst($this->meetingRequest->property_type))
            ->line('Preferred Time: ' . $this->meetingRequest->preferred_time->format('F j, Y, g:i a'))
            ->line('Current Status: ' . ucfirst($this->meetingRequest->status))
            ->action('View Meeting Request', url(config('app.frontend_url') . '/meeting-requests/' . $this->meetingRequest->id))
            ->line('Thank you for using our platform!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'meeting_request_id' => $this->meetingRequest->id,
            'title' => 'Meeting Request Created',
            'message' => 'Your meeting request for ' . ucfirst($this->meetingRequest->property_type) . ' has been received.',
            'type' => 'meeting_created',
            'property_type' => $this->meetingRequest->property_type,
            'property_id' => $this->meetingRequest->property_id,
            'preferred_time' => $this->meetingRequest->preferred_time,
            'status' => $this->meetingRequest->status,
        ];
    }
}
