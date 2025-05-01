<?php

namespace App\Notifications;

use App\Models\ServiceRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ServiceRequestStatusChanged extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public ServiceRequest $serviceRequest)
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
        $service = $this->serviceRequest->service;
        $property = $this->serviceRequest->property;
        $status = $this->serviceRequest->status;

        $mail = (new MailMessage)
            ->subject("Service Request Status: " . ucfirst($status))
            ->line("Your service request for {$service->name} at property {$property->label} has been updated to: " . ucfirst($status));

        // Add specific information based on status
        if ($status === 'approved') {
            $mail->line("Your request has been approved and will be scheduled soon.");
        } elseif ($status === 'scheduled') {
            $mail->line("Your service has been scheduled for: " . $this->serviceRequest->scheduled_date->format('F j, Y'));
            $mail->line("Please ensure someone is available at the property during this time.");
        } elseif ($status === 'in_progress') {
            $mail->line("Work on your service request has begun.");
        } elseif ($status === 'completed') {
            $mail->line("Your service request has been completed.");

            // If there's a bill, mention it
            if ($this->serviceRequest->bill_id) {
                $mail->line("A bill has been generated for this service. Please check your billing section for details.");
            }
        } elseif ($status === 'cancelled') {
            $mail->line("Your service request has been cancelled.");
        }

        // Add notes if available
        if (!empty($this->serviceRequest->notes)) {
            $mail->line("Additional information: " . $this->serviceRequest->notes);
        }

        $mail->action('View Request Details', url("/resident/service-requests/{$this->serviceRequest->id}"))
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
        $service = $this->serviceRequest->service;
        $property = $this->serviceRequest->property;

        return [
            'service_request_id' => $this->serviceRequest->id,
            'service_id' => $service->id,
            'service_name' => $service->name,
            'property_id' => $property->id,
            'property_label' => $property->label,
            'status' => $this->serviceRequest->status,
            'scheduled_date' => $this->serviceRequest->scheduled_date ?
                $this->serviceRequest->scheduled_date->format('Y-m-d') : null,
            'completion_date' => $this->serviceRequest->completion_date ?
                $this->serviceRequest->completion_date->format('Y-m-d') : null,
            'type' => 'service_request_status_changed',
            'message' => "Your service request for {$service->name} at property {$property->label} has been updated to: " .
                ucfirst($this->serviceRequest->status),
            'bill_id' => $this->serviceRequest->bill_id,
            'notes' => $this->serviceRequest->notes,
        ];
    }
}
