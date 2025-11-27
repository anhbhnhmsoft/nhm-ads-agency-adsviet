<?php

namespace App\Models;

use App\Core\GenerateId\GenerateIdSnowflake;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserWallet extends Model
{
    use HasFactory, SoftDeletes, GenerateIdSnowflake;

    protected $fillable = [
        'user_id',
        'balance',
        'password',
        'status',
    ];
    protected $hidden = [
        'password',
    ];
    protected $casts = [
        'id' => 'string',
        'user_id' => 'string',
        'balance' => 'decimal:8',
        'status' => 'integer',
    ];
    protected $appends = [
        'has_password',
    ];

    /**
     * 2. Định nghĩa Accessor cho has_password
     * Laravel 9+ Syntax
     */
    protected function hasPassword(): Attribute
    {
        return Attribute::make(
            get: fn () => !is_null($this->password) && $this->password !== ''
        );
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transactions()
    {
        return $this->hasMany(UserWalletTransaction::class, 'wallet_id');
    }
}
