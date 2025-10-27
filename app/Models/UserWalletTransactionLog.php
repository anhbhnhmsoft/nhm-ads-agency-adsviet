<?php

namespace App\Models;

use App\Core\GenerateId\GenerateIdSnowflake;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserWalletTransactionLog extends Model
{
    use HasFactory, SoftDeletes, GenerateIdSnowflake;

    protected $fillable = [
        'transaction_id',
        'previous_status',
        'new_status',
        'changed_at',
        'description',
    ];

    protected $casts = [
        'transaction_id' => 'integer',
        'previous_status' => 'integer',
        'new_status' => 'integer',
        'changed_at' => 'datetime',
    ];

    public function transaction()
    {
        return $this->belongsTo(UserWalletTransaction::class, 'transaction_id');
    }
}
