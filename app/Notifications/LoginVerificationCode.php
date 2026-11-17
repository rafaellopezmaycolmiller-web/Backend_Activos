<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class LoginVerificationCode extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $code,
        private readonly int $expiresMinutes,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return [TwilioSmsChannel::class];
    }

    public function toTwilioSms(object $notifiable): string
    {
        return 'Morales Activos: su código de acceso es '.$this->code
            .'. Vence en '.$this->expiresMinutes.' minutos. No lo comparta.';
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [];
    }
}
