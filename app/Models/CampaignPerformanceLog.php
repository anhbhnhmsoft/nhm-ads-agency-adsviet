<?php

namespace App\Models;

use App\Core\GenerateId\GenerateIdSnowflake;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CampaignPerformanceLog extends Model
{
    use HasFactory, SoftDeletes, GenerateIdSnowflake;

    protected $fillable = [
        'campaign_id',
        'date',
        'impressions',
        'clicks',
        'conversions',
        'cost',
    ];

    protected $casts = [
        'campaign_id' => 'integer',
        'date' => 'date',
        'impressions' => 'integer',
        'clicks' => 'integer',
        'conversions' => 'integer',
        'cost' => 'decimal:8',
    ];

    public function campaign()
    {
        return $this->belongsTo(Campaign::class, 'campaign_id');
    }

}
