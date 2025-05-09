<?php

namespace App\Notifications;

use App\Models\MaintenanceFeedback;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewMaintenanceFeedback extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public MaintenanceFeedback $feedback)
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
        $maintenanceRequest = $this->feedback->maintenanceRequest;
        $resident = $this->feedback->resident;
        $property = $maintenanceRequest->property;
        $rating = $this->feedback->rating;
        $starRating = str_repeat('★', $rating) . str_repeat('☆', 5 - $rating);

        $mailSubject = "New Maintenance Feedback";
        if ($rating >= 4) {
            $mailSubject .= " - Positive";
        } elseif ($rating <= 2) {
            $mailSubject .= " - Needs Attention";
        }

        $mail = (new MailMessage)
            ->subject($mailSubject)
            ->line("New feedback received for maintenance request at {$property->label}.")
            ->line("From: {$resident->name}")
            ->line("Rating: {$starRating} ({$rating}/5)")
            ->line("Resolved Satisfactorily: " . ($this->feedback->resolved_satisfactorily ? 'Yes' : 'No'))
            ->line("Would Recommend: " . ($this->feedback->would_recommend ? 'Yes' : 'No'));

        if ($this->feedback->comments) {
            $mail->line("Comments: {$this->feedback->comments}");
        }

        if ($this->feedback->improvement_suggestions) {
            $mail->line("Improvement Suggestions: {$this->feedback->improvement_suggestions}");
        }

        $mail->action('View Feedback', url("/admin/maintenance-requests/{$maintenanceRequest->id}"))
            ->line('Thank you for your attention to resident feedback.');

        return $mail;
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $maintenanceRequest = $this->feedback->maintenanceRequest;
        $resident = $this->feedback->resident;
        $property = $maintenanceRequest->property;

        return [
            'feedback_id' => $this->feedback->id,
            'maintenance_request_id' => $maintenanceRequest->id,
            'property_id' => $property->id,
            'property_label' => $property->label,
            'resident_id' => $resident->id,
            'resident_name' => $resident->name,
            'rating' => $this->feedback->rating,
            'resolved_satisfactorily' => $this->feedback->resolved_satisfactorily,
            'would_recommend' => $this->feedback->would_recommend,
            'type' => 'new_maintenance_feedback',
            'message' => "New feedback received for maintenance at {$property->label}: {$this->feedback->rating}/5 stars",
        ];
    }
}
