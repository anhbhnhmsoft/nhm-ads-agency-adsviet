<?php

namespace App\Models;

use App\Core\GenerateId\GenerateIdSnowflake;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GoogleAdsCampaign extends Model
{
    use GenerateIdSnowflake, HasFactory, SoftDeletes;

    protected $table = 'google_ads_campaigns';

    protected $fillable = [
        'service_user_id',
        'google_account_id',
        'campaign_id',
        'name',
        'status',
        'effective_status',
        'objective',
        'daily_budget',
        'budget_remaining',
        'start_time',
        'stop_time',
        'last_synced_at',
    ];

    protected $casts = [
        'id' => 'string',
        'service_user_id' => 'string',
        'google_account_id' => 'string',
        'start_time' => 'datetime',
        'stop_time' => 'datetime',
        'last_synced_at' => 'datetime',
    ];

    // Relationships
    public function serviceUser()
    {
        return $this->belongsTo(ServiceUser::class, 'service_user_id');
    }

    public function googleAccount()
    {
        return $this->belongsTo(GoogleAccount::class, 'google_account_id');
    }
}

