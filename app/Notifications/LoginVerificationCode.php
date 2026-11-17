<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
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
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Código de acceso — CCM Activos Morales')
            ->greeting('Verificación de identidad')
            ->line('Se solicitó el ingreso al Sistema de Gestión y Control de Activos.')
            ->line('Código de un solo uso: '.$this->code)
            ->line('El código vence en '.$this->expiresMinutes.' minutos.')
            ->line('Si no reconoce esta solicitud, comuníquese con el administrador técnico.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [];
    }
}
