<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        ResetPassword::createUrlUsing(function (User $user, string $token): string {
            $query = http_build_query([
                'token' => $token,
                'email' => $user->getEmailForPasswordReset(),
            ]);

            return config('app.frontend_url').'/restablecer-contrasena?'.$query;
        });

        Gate::define(
            'view-dashboard',
            fn (User $user): bool => $user->hasPermission('dashboard.view'),
        );

        RateLimiter::for('login', function (Request $request): Limit {
            $email = Str::lower($request->string('email')->trim()->toString());

            return Limit::perMinute(5)
                ->by($email.'|'.$request->ip())
                ->response(fn () => response()->json([
                    'message' => 'Demasiados intentos. Intente nuevamente en un minuto.',
                ], Response::HTTP_TOO_MANY_REQUESTS));
        });

        RateLimiter::for('mfa', function (Request $request): Limit {
            return Limit::perMinute(5)
                ->by($request->string('challenge_id')->toString().'|'.$request->ip())
                ->response(fn () => response()->json([
                    'message' => 'Demasiados intentos de verificación.',
                ], Response::HTTP_TOO_MANY_REQUESTS));
        });

        RateLimiter::for('password-reset', function (Request $request): Limit {
            $email = Str::lower($request->string('email')->trim()->toString());

            return Limit::perHour(3)
                ->by($email.'|'.$request->ip())
                ->response(fn () => response()->json([
                    'message' => 'Se alcanzó el límite temporal de solicitudes.',
                ], Response::HTTP_TOO_MANY_REQUESTS));
        });
    }
}
