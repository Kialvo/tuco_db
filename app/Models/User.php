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
