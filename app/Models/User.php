<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;

    // Optionally, define which fields are mass assignable
    protected $fillable = [
        'name', 'email', 'password', 'role',
    ];

    protected $attributes = [
        'role' => 'editor',
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
}
