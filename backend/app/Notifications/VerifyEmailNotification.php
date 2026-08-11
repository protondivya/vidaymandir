<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class VerifyEmailNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Get the notification's delivery channels.
     *
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $signedUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ],
        );

        $frontendUrl = rtrim((string) config('app.frontend_url'), '/');

        $parts = explode('/', (string) parse_url((string) $signedUrl, PHP_URL_PATH));
        $hash = (string) array_pop($parts);
        $id = (string) array_pop($parts);
        $query = (string) parse_url((string) $signedUrl, PHP_URL_QUERY);

        $url = $frontendUrl.'/verify-email?id='.$id.'&hash='.$hash.'&'.$query;

        return (new MailMessage)
            ->subject('Verify your email address')
            ->greeting('Welcome to the Digital Free Library')
            ->line('Please click the button below to verify your email address.')
            ->action('Verify Email Address', $url)
            ->line('This verification link will expire in 60 minutes.');
    }
}
