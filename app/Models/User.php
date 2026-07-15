<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'google_id',
        'avatar_url',
        'must_change_password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at'    => 'datetime',
        'must_change_password' => 'boolean',
    ];

    protected $attributes = [
        'role' => 'guest',
    ];

    /**
     * Display URL for the avatar: external URLs (Google) pass through,
     * local uploads (avatars/… on the public disk) get the storage URL,
     * null → initials fallback in the views.
     */
    public function getAvatarAttribute(): ?string
    {
        if (! $this->avatar_url) {
            return null;
        }

        return str_starts_with($this->avatar_url, 'http')
            ? $this->avatar_url
            : asset('storage/' . $this->avatar_url);
    }

    /**
     * Verification email goes through the dedicated 'auth' mailer, and a
     * transport failure is logged LOUDLY instead of 500ing the register
     * flow or vanishing silently (2026-07-14: verification mails from the
     * default gmail sender never reached a menford.com inbox).
     */
    public function sendEmailVerificationNotification(): void
    {
        try {
            $this->notify(new \App\Notifications\VerifyEmailViaAuthMailer());
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error(
                '[verify-email] send FAILED for ' . $this->email . ': ' . $e->getMessage()
            );
        }
    }

    /**
     * Password-reset link through the same dedicated 'auth' mailer as the
     * verification email (Resend API), with the same loud failure logging.
     */
    public function sendPasswordResetNotification($token): void
    {
        try {
            $this->notify(new \App\Notifications\ResetPasswordViaAuthMailer($token));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error(
                '[reset-email] send FAILED for ' . $this->email . ': ' . $e->getMessage()
            );
        }
    }

    public function hasRole(string $role): bool
    {
        return strtolower((string) $this->role) === strtolower($role);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    public function isEditor(): bool
    {
        return $this->hasRole('editor');
    }

    public function isGuest(): bool
    {
        return $this->hasRole('guest');
    }

    public function favoriteWebsites()
    {
        return $this->belongsToMany(Website::class, 'user_favorite_domains')
            ->withTimestamps();
    }

    public function orders()
    {
        return $this->hasMany(Order::class)->latest();
    }

    /**
     * Returns this user's open draft order (the cart). Creates one if none exists.
     */
    public function draftOrder(): Order
    {
        return $this->orders()
            ->where('status', Order::STATUS_DRAFT)
            ->firstOrCreate(['status' => Order::STATUS_DRAFT]);
    }
}
