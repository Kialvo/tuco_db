<?php

namespace App\Providers;

use App\Models\User;
use App\Services\Payments\FakeGateway;
use App\Services\Payments\PaymentGateway;
use App\Services\Payments\StripeGateway;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // The gateway is chosen by config and defaults to the fake driver: a
        // missing PAYMENTS_DRIVER must never leave a half-configured live
        // gateway in front of customers.
        $this->app->bind(PaymentGateway::class, function () {
            $driver = config('tokens.driver', 'fake');

            return match ($driver) {
                'fake' => new FakeGateway,
                'stripe' => new StripeGateway(
                    (string) config('services.stripe.secret_key', ''),
                    (string) config('services.stripe.webhook_secret', ''),
                ),
                default => throw new \RuntimeException(
                    "Unsupported payments driver [{$driver}]. Valid drivers are [fake, stripe]."
                ),
            };
        });
    }

    public function boot(): void
    {
        // "Bulk Add to Campaign" on /websites — a CRM write exposed on a page
        // guests and editors can reach, so the endpoint is gated, not just the
        // button. Admin role AND an allowlisted email are both required.
        Gate::define('bulk-add-to-campaign', function (User $user): bool {
            if (! $user->isAdmin()) {
                return false;
            }

            $allowed = array_map(
                'mb_strtolower',
                (array) config('linkbuilding.bulk_campaign_managers', [])
            );

            return in_array(mb_strtolower((string) $user->email), $allowed, true);
        });

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
