<?php

namespace App\Models;

use App\Core\GenerateId\GenerateIdSnowflake;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MetaAccount extends Model
{
    use GenerateIdSnowflake, HasFactory, SoftDeletes;

    protected $table = 'meta_accounts';

    protected $fillable = [
        'service_user_id',
        'account_id',
        'account_name',
        'account_status',
        'disable_reason',
        'spend_cap',
        'amount_spent',
        'balance',
        'currency',
        'created_time',
        'is_prepay_account',
        'timezone_id',
        'timezone_name',
        'last_synced_at',
    ];

    protected $casts = [
        'id' => 'string',
        'service_user_id' => 'string',
        'account_status' => 'integer',
    ];

    // Relationships
    public function serviceUser()
    {
        return $this->belongsTo(ServiceUser::class, 'service_user_id');
    }
    public function metaAdsCampaigns()
    {
        return $this->hasMany(MetaAdsCampaign::class, 'campaign_id');
    }
}
