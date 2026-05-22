<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        RateLimiter::for('ai-internal', function (Request $request) {
            $providedKey = (string) $request->header('X-AI-Orchestration-Key', '');
            $keyFingerprint = $providedKey !== '' ? hash('sha256', $providedKey) : 'missing';

            return Limit::perMinute(60)->by($keyFingerprint.'|'.$request->ip());
        });

        VerifyEmail::toMailUsing(function (object $notifiable, string $url) {
            $appName = config('app.name');

            return (new MailMessage)
                ->mailer('auth')
                ->subject("Verify your {$appName} email address")
                ->greeting("Hi {$notifiable->name},")
                ->line("Welcome to {$appName}! Please confirm your email address to activate your account.")
                ->action('Verify email address', $url)
                ->line('If you did not create an account, no further action is required.')
                ->salutation("Thanks,\n{$appName}");
        });

        ResetPassword::toMailUsing(function (object $notifiable, string $token) {
            $appName = config('app.name');
            $url = url(route('password.reset', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ], false));

            return (new MailMessage)
                ->mailer('auth')
                ->subject("Reset your {$appName} password")
                ->greeting("Hi {$notifiable->name},")
                ->line('You are receiving this email because we received a password reset request for your account.')
                ->action('Reset password', $url)
                ->line('This password reset link will expire in '.config('auth.passwords.'.config('auth.defaults.passwords').'.expire').' minutes.')
                ->line('If you did not request a password reset, no further action is required.')
                ->salutation("Thanks,\n{$appName}");
        });
    }
}
