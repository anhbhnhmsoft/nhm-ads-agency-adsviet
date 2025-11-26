<?php

namespace App\Models;

use App\Core\GenerateId\GenerateIdSnowflake;
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
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transactions()
    {
        return $this->hasMany(UserWalletTransaction::class, 'wallet_id');
    }
}
