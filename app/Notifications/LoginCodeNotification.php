<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LoginCodeNotification extends Notification
{
    public function __construct(public readonly string $code) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $minutes = config()->integer('auth.login_code.ttl_minutes');

        return (new MailMessage)
            ->subject(__('messages.login_code.subject', ['code' => $this->code]))
            ->greeting(__('messages.login_code.greeting'))
            ->line(__('messages.login_code.intro'))
            ->line('**'.$this->code.'**')
            ->line(__('messages.login_code.expires', ['minutes' => $minutes]))
            ->line(__('messages.login_code.ignore'));
    }
}
