<?php

namespace App\Models;

use App\Core\GenerateId\GenerateIdSnowflake;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MetaAdsCampaign extends Model
{
    use GenerateIdSnowflake, HasFactory, SoftDeletes;

    protected $table = 'meta_ads_campaigns';

    protected $fillable = [
        'service_user_id',
        'meta_account_id',
        'campaign_id',
        'name',
        'status',
        'effective_status',
        'objective',
        'daily_budget',
        'budget_remaining',
        'created_time',
        'start_time',
        'stop_time',
        'last_synced_at',
    ];

    protected $casts = [
        'id' => 'string',
        'service_user_id' => 'string',
        'meta_account_id' => 'string',
    ];

    // Relationships
    public function serviceUser()
    {
        return $this->belongsTo(ServiceUser::class, 'service_user_id');
    }

    public function metaAccount()
    {
        return $this->belongsTo(MetaAccount::class, 'meta_account_id');
    }
}
