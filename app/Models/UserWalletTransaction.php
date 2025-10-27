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
    ];

    protected $casts = [
        'wallet_id' => 'integer',
        'amount' => 'decimal:8',
        'type' => 'integer',
        'status' => 'integer',
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
