<?php

namespace App\Models;

use App\Core\GenerateId\GenerateIdSnowflake;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserWalletTransaction extends Model
{
    use HasFactory, SoftDeletes, GenerateIdSnowflake;

    protected $fillable = [
        'wallet_id',
        'amount',
        'type',
        'status',
        'reference_id',
        'description',
        'network',
        'tx_hash',
        'customer_name',
        'customer_email',
        'deposit_address',
        'payment_id',
        'pay_address',
        'expires_at',
        'withdraw_info',
    ];

    protected $casts = [
        'id' => 'string',
        'wallet_id' => 'string',
        'amount' => 'decimal:8',
        'type' => 'integer',
        'status' => 'integer',
        'expires_at' => 'datetime',
        'withdraw_info' => 'array',
    ];

    public function wallet()
    {
        return $this->belongsTo(UserWallet::class, 'wallet_id');
    }

    public function logs()
    {
        return $this->hasMany(UserWalletTransactionLog::class, 'transaction_id');
    }
}
