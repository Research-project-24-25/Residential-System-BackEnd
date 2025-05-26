<?php

namespace App\Notifications;

use App\Models\Bill;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class BillOverdueNotification extends Notification implements ShouldQueue
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
    return ['database', 'mail']; // Send as both in-app and email notification
  }

  /**
   * Get the mail representation of the notification.
   */
  public function toMail(object $notifiable): MailMessage
  {
    $propertyLabel = $this->bill->property ? $this->bill->property->label : 'the property';
    $amount = number_format($this->bill->amount, 2) . ' ' . ($this->bill->currency ? $this->bill->currency : 'USD');
    $dueDate = $this->bill->due_date->format('F j, Y');

    return (new MailMessage)
      ->subject('Bill Overdue')
      ->greeting('Hello,')
      ->line("Your bill for {$propertyLabel} amounting to {$amount} was due on {$dueDate} and is now overdue.")
      ->action('View Bill', url("/bills/{$this->bill->id}")) // Adjust URL for your frontend
      ->line('Please make a payment as soon as possible to avoid any late fees.')
      ->salutation('Best regards, ' . config('app.name'));
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
      'type' => 'bill_overdue',
      'message' => "Your bill for {$propertyLabel} amounting to " .
        number_format($this->bill->amount, 2) . ' ' . $currency .
        " was due on {$this->bill->due_date->format('Y-m-d')} and is now overdue.",
    ];
  }
}
