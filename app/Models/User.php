<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, HasApiTokens;

    protected $fillable = [
        'name',
        'email',
        'password_hash',
        'avatar',
        'bio',
        'role',
        'email_verified_at',
        'last_login',
        'privacy_accepted_at',
        'privacy_policy_version',
    ];

    protected $hidden = [
        'password_hash',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login' => 'datetime',
            'privacy_accepted_at' => 'datetime',
            'suspended_at' => 'datetime',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin' && ! $this->isSuspended();
    }

    public function isSuspended(): bool
    {
        return $this->suspended_at !== null;
    }

    public function getAuthPassword(): string
    {
        return $this->password_hash;
    }

    public function tests(): HasMany
    {
        return $this->hasMany(Test::class);
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(TestAttempt::class);
    }

    public function points(): HasOne
    {
        return $this->hasOne(UserPoint::class);
    }

    public function stats(): HasOne
    {
        return $this->hasOne(UserStat::class);
    }
}
