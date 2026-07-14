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
