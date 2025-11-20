<?php

namespace App\Models;

use App\Core\GenerateId\GenerateIdSnowflake;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MetaAdsAccountInsight extends Model
{
    use GenerateIdSnowflake, HasFactory, SoftDeletes;

    protected $table = 'meta_ads_account_insights';

     protected $fillable = [
         'service_user_id',
         'meta_account_id',
         'date',
         'spend',
         'impressions',
         'reach',
         'frequency',
         'clicks',
         'inline_link_clicks',
         'ctr',
         'cpc',
         'cpm',
         'actions',
         'purchase_roas',
         'last_synced_at',
    ];

     protected $casts = [
         'id' => 'string',
         'service_user_id' => 'string',
         'meta_account_id' => 'string',
         'date' => 'date',
         'last_synced_at' => 'datetime',
         'actions' => 'array',
     ];

     // Relations
     public function serviceUser()
     {
         return $this->belongsTo(ServiceUser::class);
     }

     public function metaAccount()
     {
         return $this->belongsTo(MetaAccount::class);
     }
}
