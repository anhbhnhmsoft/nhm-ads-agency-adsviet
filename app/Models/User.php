<?php

namespace App\Models;

use App\Core\GenerateId\GenerateIdSnowflake;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * @method static \Illuminate\Database\Eloquent\Builder|static isActive() //  Get active users
 */
class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes, GenerateIdSnowflake, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'phone',
        'password',
        'role',
        'disabled',
        'telegram_id',
        'whatsapp_id',
        'referral_code',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'id' => 'string',
            'role' => 'integer',
            'disabled' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function scopeIsActive($query)
    {
        return $query->where('disabled', false);
    }

    // Relationships
    public function wallet()
    {
        return $this->hasOne(UserWallet::class);
    }

    public function referrals()
    {
        return $this->hasMany(UserReferral::class, 'referrer_id');
    }

    public function referredBy()
    {
        return $this->hasOne(UserReferral::class, 'referred_id');
    }

    public function otps()
    {
        return $this->hasMany(UserOtp::class);
    }

    public function devices()
    {
        return $this->hasMany(UserDevice::class);
    }

    public function serviceUsers()
    {
        return $this->hasMany(ServiceUser::class);
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    public function assignedTickets()
    {
        return $this->hasMany(Ticket::class, 'assigned_to');
    }

    public function ticketConversations()
    {
        return $this->hasMany(TicketConversation::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }
}
