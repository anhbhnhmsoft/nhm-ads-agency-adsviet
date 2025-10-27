<?php

namespace App\Models;

use App\Core\GenerateId\GenerateIdSnowflake;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserOtp extends Model
{
    use HasFactory, GenerateIdSnowflake;

    protected $table = 'user_otp';

    protected $fillable = [
        'user_id',
        'code',
        'type',
        'expires_at',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'type' => 'integer',
        'expires_at' => 'datetime',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
