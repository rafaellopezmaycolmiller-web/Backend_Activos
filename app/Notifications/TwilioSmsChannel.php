<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class TwilioSmsChannel
{
    public function send(object $notifiable, Notification $notification): void
    {
        if (! $notification instanceof LoginVerificationCode) {
            throw new RuntimeException('La notificación no es compatible con el canal SMS configurado.');
        }

        $accountSid = config('services.twilio.account_sid');
        $channel = config('services.twilio.channel', 'sms');
        $authToken = config('services.twilio.auth_token');
        $messagingServiceSid = config('services.twilio.messaging_service_sid');
        $from = config('services.twilio.from');
        $recipient = method_exists($notifiable, 'routeNotificationForTwilioSms')
            ? $notifiable->routeNotificationForTwilioSms()
            : null;

        if ($channel === 'log') {
            Log::notice('Código MFA generado para pruebas locales', [
                'recipient' => $recipient,
                'message' => $notification->toTwilioSms($notifiable),
            ]);

            return;
        }

        if (! is_string($accountSid) || $accountSid === '' || ! is_string($authToken) || $authToken === '') {
            throw new RuntimeException('El servicio SMS todavía no tiene configuradas sus credenciales.');
        }

        if (! is_string($recipient) || $recipient === '') {
            throw new RuntimeException('La cuenta administradora no tiene un teléfono celular válido.');
        }

        $payload = [
            'To' => $channel === 'whatsapp' ? 'whatsapp:'.$recipient : $recipient,
            'Body' => $notification->toTwilioSms($notifiable),
        ];

        if ($channel === 'whatsapp' && is_string(config('services.twilio.whatsapp_from')) && config('services.twilio.whatsapp_from') !== '') {
            $payload['From'] = 'whatsapp:'.config('services.twilio.whatsapp_from');
        } elseif (is_string($messagingServiceSid) && $messagingServiceSid !== '') {
            $payload['MessagingServiceSid'] = $messagingServiceSid;
        } elseif (is_string($from) && $from !== '') {
            $payload['From'] = $from;
        } else {
            throw new RuntimeException('El servicio SMS necesita un remitente o Messaging Service de Twilio.');
        }

        Http::asForm()
            ->withBasicAuth($accountSid, $authToken)
            ->timeout(10)
            ->post("https://api.twilio.com/2010-04-01/Accounts/{$accountSid}/Messages.json", $payload)
            ->throw();
    }
}
