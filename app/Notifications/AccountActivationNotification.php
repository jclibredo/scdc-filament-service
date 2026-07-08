<?php

namespace App\Notifications;

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;

class AccountActivationNotification extends Notification implements ShouldQueue
{
    use Queueable;


    public User $user;
    /**
     * Create a new notification instance.
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    // public function toMail(object $notifiable): MailMessage
    // {
    //     // 1. Generate a secure Laravel password reset token for this user
    //     $token = Password::getRepository()->create($this->user);

    //     // 2. Generate the direct URL to Filament's native password reset screen
    //     // Filament 4 routes follow the 'filament.{panel_id}.auth.password-reset.reset' naming convention
    //     $activationUrl = route('filament.admin.auth.password-reset.reset', [
    //         'token' => $token,
    //         'email' => $this->user->email,
    //     ]);

    //     return (new MailMessage)
    //         ->subject('Setup Your Admin Account Password')
    //         ->greeting("Hello {$this->user->name},")
    //         ->line('An System account has been created for you.')
    //         ->line('To complete your registration, please click the button below to set up your account password.')
    //         ->action('Set Account Password', $activationUrl)
    //         ->line('This security link will expire shortly.');
    // }
    public function toMail(object $notifiable): MailMessage
    {
        // 1. Generate the standard secure Laravel password token
        $token = Password::getRepository()->create($this->user);

        // 2. Use Filament's helper to build the absolute, cleanly signed URL
        $activationUrl = Filament::getResetPasswordUrl($token, $this->user);

        return (new MailMessage)
            ->subject('Setup Your Admin Account Password')
            ->greeting("Hello {$this->user->name},")
            ->line('An user account has been created for you.')
            ->line('To complete your registration, please click the button below to set up your account password.')
            ->action('Set Account Password', $activationUrl)
            ->line('This security link is valid for a limited time.');
    }
    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'user_id' => $this->user->id,
            'email' => $this->user->email,
        ];
    }
}
