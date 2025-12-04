<?php

namespace App\Models;

use App\Core\GenerateId\GenerateIdSnowflake;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GoogleAccount extends Model
{
    use GenerateIdSnowflake, HasFactory, SoftDeletes;

    protected $table = 'google_accounts';

    protected $fillable = [
        'service_user_id',
        'account_id',
        'account_name',
        'account_status',
        'currency',
        'customer_manager_id',
        'time_zone',
        'primary_email',
        'balance',
        'balance_exhausted',
        'last_synced_at',
    ];

    protected $casts = [
        'id' => 'string',
        'service_user_id' => 'string',
        'balance' => 'decimal:2',
        'balance_exhausted' => 'boolean',
        'last_synced_at' => 'datetime',
    ];

    public function serviceUser()
    {
        return $this->belongsTo(ServiceUser::class, 'service_user_id');
    }
}

