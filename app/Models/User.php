<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'receives_notification_mail',
        'is_admin',
        'is_seed_admin',
        'google_id',
        'google_email',
        'google_avatar',
        'google_connected_at',
        'deletion_requested_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'receives_notification_mail' => 'boolean',
        'is_admin' => 'boolean',
        'is_seed_admin' => 'boolean',
        'google_connected_at' => 'datetime',
        'deletion_requested_at' => 'datetime',
    ];

    public function tweets()
    {
        return $this->hasMany(Tweet::class);
    }

    public function likes()
    {
        return $this->hasMany(Like::class);
    }

    public function scopeNotPendingDeletion($query)
    {
        return $query->whereNull('deletion_requested_at');
    }

    public function isDeletionRequested(): bool
    {
        return $this->deletion_requested_at !== null;
    }

    public function isPendingInitialEmailVerification(): bool
    {
        return ! $this->hasVerifiedEmail()
            && $this->created_at !== null
            && $this->updated_at !== null
            && $this->created_at->equalTo($this->updated_at);
    }
}
