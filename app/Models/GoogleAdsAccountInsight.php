<?php

namespace App\Models;

use App\Core\GenerateId\GenerateIdSnowflake;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GoogleAdsAccountInsight extends Model
{
    use GenerateIdSnowflake, HasFactory, SoftDeletes;

    protected $table = 'google_ads_account_insights';

    protected $fillable = [
        'service_user_id',
        'google_account_id',
        'date',
        'spend',
        'impressions',
        'clicks',
        'conversions',
        'ctr',
        'cpc',
        'cpm',
        'conversion_actions',
        'roas',
        'last_synced_at',
    ];

    protected $casts = [
        'id' => 'string',
        'service_user_id' => 'string',
        'google_account_id' => 'string',
        'date' => 'date',
        'conversion_actions' => 'array',
        'last_synced_at' => 'datetime',
    ];

    public function googleAccount()
    {
        return $this->belongsTo(GoogleAccount::class, 'google_account_id');
    }

    public function serviceUser()
    {
        return $this->belongsTo(ServiceUser::class, 'service_user_id');
    }
}

