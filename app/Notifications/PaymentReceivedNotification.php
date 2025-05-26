<?php

namespace App\Notifications;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class PaymentReceivedNotification extends Notification implements ShouldQueue
{
  use Queueable;

  /**
   * Create a new notification instance.
   */
  public function __construct(public Payment $payment)
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
    $bill = $this->payment->bill;
    $propertyLabel = $bill->property ? $bill->property->label : 'the property';
    $paymentAmount = number_format($this->payment->amount, 2) . ' ' . ($this->payment->currency ? $this->payment->currency : 'USD');

    $mail = (new MailMessage)
      ->subject('Payment Received')
      ->greeting('Hello,')
      ->line("We have received a payment of {$paymentAmount} for your bill for {$propertyLabel}.");

    if ($bill->is_fully_paid) {
      $mail->line('Your bill has now been fully paid. Thank you!');
    } else {
      $remainingBalance = number_format($bill->remaining_balance, 2) . ' ' . ($bill->currency ? $bill->currency : 'USD');
      $mail->line("Your remaining balance for this bill is {$remainingBalance}.");
      $mail->action('View Bill', url("/bills/{$bill->id}")); // Adjust URL for your frontend
    }

    return $mail->salutation('Best regards, ' . config('app.name'));
  }

  /**
   * Get the array representation of the notification.
   *
   * @return array<string, mixed>
   */
  public function toArray(object $notifiable): array
  {
    // Load relationships if not already loaded
    if (!$this->payment->relationLoaded('bill')) {
      $this->payment->load('bill');
    }
    if (!$this->payment->bill->relationLoaded('property')) {
      $this->payment->bill->load('property');
    }

    $bill = $this->payment->bill;
    $propertyLabel = $bill->property ? $bill->property->label : 'your property';
    $paymentAmount = number_format($this->payment->amount, 2) . ' ' . ($this->payment->currency ? $this->payment->currency : 'USD');
    $message = "Payment of {$paymentAmount} received for your bill for {$propertyLabel}.";

    if ($bill->is_fully_paid) {
      $message .= " Your bill is now fully paid. Thank you!";
    } else {
      $remainingBalance = number_format($bill->remaining_balance, 2) . ' ' . ($bill->currency ? $bill->currency : 'USD');
      $message .= " Remaining balance: {$remainingBalance}.";
    }

    return [
      'payment_id' => $this->payment->id,
      'bill_id' => $this->payment->bill_id,
      'property_id' => $bill->property ? $bill->property->id : null,
      'property_label' => $propertyLabel,
      'amount' => $this->payment->amount,
      'currency' => $this->payment->currency,
      'type' => 'payment_received',
      'message' => $message,
    ];
  }
}
