<?php

namespace App\Notifications;

use App\Models\Resident;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class NewResidentWelcomeNotification extends Notification implements ShouldQueue
{
  use Queueable;

  /**
   * Create a new notification instance.
   */
  public function __construct(public Resident $resident)
  {
    // Ensure property relationship is loaded
    if (!$this->resident->relationLoaded('properties')) {
      $this->resident->load('properties');
    }
  }

  /**
   * Get the notification's delivery channels.
   *
   * @return array<int, string>
   */
  public function via(object $notifiable): array
  {
    return ['database', 'mail']; // Send as both in-app and email notification
  }

  /**
   * Get the mail representation of the notification.
   */
  public function toMail(object $notifiable): MailMessage
  {
    $mail = (new MailMessage)
      ->subject('Welcome to ' . config('app.name'))
      ->greeting("Hello {$this->resident->first_name},")
      ->line('Welcome to our residential management system!');

    if ($this->resident->properties->count() > 0) {
      $propertyLabels = $this->resident->properties->pluck('label')->join(', ');
      $mail->line("Your account is now linked to the following propert(y/ies): {$propertyLabels}.");
    }

    // Add a call to action, e.g., view profile or dashboard
    // You might need to adjust this URL based on your frontend routes
    $mail->action('Get Started', url('/dashboard')); // Example URL

    return $mail->line('We are excited to have you as part of our community.')
      ->salutation('Best regards, ' . config('app.name'));
  }

  /**
   * Get the array representation of the notification.
   *
   * @return array<string, mixed>
   */
  public function toArray(object $notifiable): array
  {
    // Ensure property relationship is loaded
    if (!$this->resident->relationLoaded('properties')) {
      $this->resident->load('properties');
    }

    $propertyLabels = $this->resident->properties->pluck('label')->join(', ', '');

    $message = "Welcome to our residential management system!";
    if (!empty($propertyLabels)) {
      $message .= " Your account is now linked to {$propertyLabels}.";
    }

    return [
      'resident_id' => $this->resident->id,
      'resident_name' => $this->resident->name,
      'property_labels' => $propertyLabels,
      'type' => 'new_resident_welcome',
      'message' => $message,
      // Optionally add property IDs if needed for linking on frontend
      'property_ids' => $this->resident->properties->pluck('id')->toArray(),
    ];
  }
}
