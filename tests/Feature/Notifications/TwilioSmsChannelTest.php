<?php

namespace Tests\Feature\Notifications;

use App\Models\User;
use App\Notifications\LoginVerificationCode;
use App\Notifications\TwilioSmsChannel;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class TwilioSmsChannelTest extends TestCase
{
    public function test_sends_login_code_to_peruvian_mobile_number(): void
    {
        config()->set([
            'services.twilio.account_sid' => 'AC123456789',
            'services.twilio.auth_token' => 'secret-token',
            'services.twilio.messaging_service_sid' => 'MG123456789',
            'services.twilio.from' => null,
        ]);
        Http::preventStrayRequests();
        Http::fake([
            'api.twilio.com/*' => Http::response(['sid' => 'SM123456789'], 201),
        ]);
        $user = new User(['phone' => '+51906239450']);
        $notification = new LoginVerificationCode('482913', 5);

        (new TwilioSmsChannel)->send($user, $notification);

        Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
            && $request->url() === 'https://api.twilio.com/2010-04-01/Accounts/AC123456789/Messages.json'
            && $request['To'] === '+51906239450'
            && $request['MessagingServiceSid'] === 'MG123456789'
            && $request['Body'] === 'Morales Activos: su código de acceso es 482913. Vence en 5 minutos. No lo comparta.'
        );
    }

    public function test_missing_twilio_credentials_stops_login_code_delivery(): void
    {
        config()->set([
            'services.twilio.account_sid' => null,
            'services.twilio.auth_token' => null,
        ]);
        Http::preventStrayRequests();
        $user = new User(['phone' => '+51906239450']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('El servicio SMS todavía no tiene configuradas sus credenciales.');

        (new TwilioSmsChannel)->send($user, new LoginVerificationCode('482913', 5));
    }
}
