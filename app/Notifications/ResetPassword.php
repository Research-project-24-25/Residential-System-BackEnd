<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword as BaseResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPassword extends BaseResetPassword
{
    /**
     * Build the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        $frontendUrl = config('app.frontend_url');
        $resetUrl = "{$frontendUrl}/reset-password?token={$this->token}&email=" . urlencode($notifiable->getEmailForPasswordReset());

        // Determine user type
        $userType = match ($notifiable->getTable()) {
            'admins' => 'Administrator',
            'residents' => 'Resident',
            default => 'User'
        };

        return (new MailMessage)
            ->subject('Reset Your Password')
            ->greeting("Hello {$userType},")
            ->line('You are receiving this email because we received a password reset request for your account.')
            ->action('Reset Password', $resetUrl)
            ->line('This password reset link will expire in ' . config('auth.passwords.users.expire') . ' minutes.')
            ->line('If you did not request a password reset, no further action is required.')
            ->salutation('Best regards, ' . config('app.name'));
    }
}
