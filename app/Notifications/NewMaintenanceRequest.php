<?php

namespace App\Notifications;

use App\Models\MaintenanceRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewMaintenanceRequest extends Notification implements ShouldQueue
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
        $resident = $this->maintenanceRequest->resident;
        $priority = ucfirst($this->maintenanceRequest->priority);

        $mail = (new MailMessage)
            ->subject("New Maintenance Request: {$priority} Priority")
            ->line("A new maintenance request has been submitted by {$resident->name} for property {$property->label}.")
            ->line("Priority: {$priority}")
            ->line("Description: " . $this->maintenanceRequest->description);

        // Add issue details if available
        if ($this->maintenanceRequest->issue_details) {
            $mail->line("Issue Details: " . $this->maintenanceRequest->issue_details);
        }

        // Add information about attached images
        if (!empty($this->maintenanceRequest->images)) {
            $imageCount = count($this->maintenanceRequest->images);
            $mail->line("Images Attached: {$imageCount}");
        }

        $mail->action('View Request', url("/admin/maintenance-requests/{$this->maintenanceRequest->id}"))
            ->line('Please review this request at your earliest convenience.');

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
        $resident = $this->maintenanceRequest->resident;

        return [
            'maintenance_request_id' => $this->maintenanceRequest->id,
            'property_id' => $property->id,
            'property_label' => $property->label,
            'resident_id' => $resident->id,
            'resident_name' => $resident->name,
            'priority' => $this->maintenanceRequest->priority,
            'requested_date' => $this->maintenanceRequest->requested_date->format('Y-m-d'),
            'description' => $this->maintenanceRequest->description,
            'type' => 'new_maintenance_request',
            'message' => "New {$this->maintenanceRequest->priority} priority maintenance request from {$resident->name} for property {$property->label}",
        ];
    }
}
