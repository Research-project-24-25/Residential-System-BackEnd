<?php

namespace App\Notifications;

use App\Models\Bill;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class NewBillNotification extends Notification implements ShouldQueue
{
  use Queueable;

  /**
   * Create a new notification instance.
   */
  public function __construct(public Bill $bill)
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
    return ['database']; // Also add 'mail' if email notification is desired
  }

  /**
   * Get the mail representation of the notification.
   */
  public function toMail(object $notifiable): MailMessage
  {
    // Optional: Implement if you want to send an email for new bills
    $propertyLabel = $this->bill->property->label ?? 'the property';
    $amount = number_format($this->bill->amount, 2) . ' ' . ($this->bill->currency ?? 'USD');
    $dueDate = $this->bill->due_date->format('F j, Y');

    return (new MailMessage)
      ->subject('New Bill Generated')
      ->line("A new bill has been generated for {$propertyLabel} amounting to {$amount}.")
      ->line("It is due on {$dueDate}.")
      ->action('View Bill', url("/bills/{$this->bill->id}")) // Adjust URL for your frontend
      ->line('Please review the bill details and make a payment by the due date.');
  }

  /**
   * Get the array representation of the notification.
   *
   * @return array<string, mixed>
   */
  public function toArray(object $notifiable): array
  {
    // Load relationships if not already loaded
    if (!$this->bill->relationLoaded('property')) {
      $this->bill->load('property');
    }

    $propertyLabel = $this->bill->property ? $this->bill->property->label : 'your property';
    $currency = $this->bill->currency ? $this->bill->currency : 'USD';

    return [
      'bill_id' => $this->bill->id,
      'property_id' => $this->bill->property ? $this->bill->property->id : null,
      'property_label' => $propertyLabel,
      'amount' => $this->bill->amount,
      'currency' => $this->bill->currency,
      'due_date' => $this->bill->due_date->format('Y-m-d'),
      'bill_type' => $this->bill->bill_type,
      'type' => 'new_bill',
      'message' => "New bill for {$propertyLabel} amounting to " .
        number_format($this->bill->amount, 2) . ' ' . $currency .
        " is due on {$this->bill->due_date->format('Y-m-d')}.",
    ];
  }
}
