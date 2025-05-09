<?php

namespace App\Notifications;

use App\Models\MaintenanceRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MaintenanceRequestStatusChanged extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public MaintenanceRequest $maintenanceRequest)
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
        $property = $this->maintenanceRequest->property;
        $status = ucfirst($this->maintenanceRequest->status);
        $priority = ucfirst($this->maintenanceRequest->priority);

        $mail = (new MailMessage)
            ->subject("Maintenance Request Status: {$status}")
            ->line("Your {$priority} priority maintenance request for property {$property->label} has been updated to: {$status}");

        // Add specific information based on status
        if ($this->maintenanceRequest->status === 'approved') {
            $mail->line("Your request has been approved and will be scheduled soon.");
        } elseif ($this->maintenanceRequest->status === 'scheduled') {
            $mail->line("Your maintenance has been scheduled for: " . $this->maintenanceRequest->scheduled_date->format('F j, Y'));
            $mail->line("Please ensure someone is available at the property during this day.");
        } elseif ($this->maintenanceRequest->status === 'in_progress') {
            $mail->line("Work on your maintenance request has begun.");
        } elseif ($this->maintenanceRequest->status === 'completed') {
            $mail->line("Your maintenance request has been completed.");
            $mail->line("We would appreciate your feedback on the service provided.");
            $mail->action('Provide Feedback', url("/resident/maintenance-requests/{$this->maintenanceRequest->id}/feedback"));

            // If there's a bill, mention it
            if ($this->maintenanceRequest->bill_id) {
                $mail->line("A bill has been generated for this maintenance. Please check your billing section for details.");
            }
        } elseif ($this->maintenanceRequest->status === 'cancelled') {
            $mail->line("Your maintenance request has been cancelled.");
        }

        // Add notes if available
        if (!empty($this->maintenanceRequest->notes)) {
            $mail->line("Additional information: " . $this->maintenanceRequest->notes);
        }

        $mail->action('View Request Details', url("/resident/maintenance-requests/{$this->maintenanceRequest->id}"))
            ->line('Thank you for using our maintenance service.');

        return $mail;
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $property = $this->maintenanceRequest->property;

        return [
            'maintenance_request_id' => $this->maintenanceRequest->id,
            'property_id' => $property->id,
            'property_label' => $property->label,
            'priority' => $this->maintenanceRequest->priority,
            'status' => $this->maintenanceRequest->status,
            'scheduled_date' => $this->maintenanceRequest->scheduled_date ?
                $this->maintenanceRequest->scheduled_date->format('Y-m-d') : null,
            'completion_date' => $this->maintenanceRequest->completion_date ?
                $this->maintenanceRequest->completion_date->format('Y-m-d') : null,
            'type' => 'maintenance_request_status_changed',
            'message' => "Your maintenance request for property {$property->label} has been updated to: " .
                ucfirst($this->maintenanceRequest->status),
            'bill_id' => $this->maintenanceRequest->bill_id,
            'notes' => $this->maintenanceRequest->notes,
        ];
    }
}
