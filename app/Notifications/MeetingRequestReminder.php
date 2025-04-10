<?php

namespace App\Notifications;

use App\Models\MeetingRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MeetingRequestReminder extends Notification implements ShouldQueue
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
        $meetingTime = $this->meetingRequest->preferred_time->format('F j, Y, g:i a');

        return (new MailMessage)
            ->subject('Upcoming Meeting Reminder')
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('This is a reminder about your upcoming meeting.')
            ->line('Property Type: ' . ucfirst($this->meetingRequest->property_type))
            ->line('Scheduled Time: ' . $meetingTime)
            ->line('The meeting is scheduled in 24 hours.')
            ->action('View Meeting Details', url(config('app.frontend_url') . '/meeting-requests/' . $this->meetingRequest->id))
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
            'title' => 'Upcoming Meeting Reminder',
            'message' => 'You have a meeting scheduled in 24 hours.',
            'type' => 'meeting_upcoming',
            'property_type' => $this->meetingRequest->property_type,
            'property_id' => $this->meetingRequest->property_id,
            'preferred_time' => $this->meetingRequest->preferred_time,
            'status' => $this->meetingRequest->status,
        ];
    }
}
