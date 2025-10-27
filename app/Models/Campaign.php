<?php

namespace App\Models;

use App\Core\GenerateId\GenerateIdSnowflake;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Campaign extends Model
{
    use HasFactory, SoftDeletes, GenerateIdSnowflake;


    protected $fillable = [
        'service_user_id',
        'name',
        'platform',
        'config',
        'status',
        'budget',
        'target_audience',
        'start_date',
        'end_date',
        'description',
    ];

    protected $casts = [
        'service_user_id' => 'integer',
        'platform' => 'integer',
        'config' => 'array',
        'status' => 'integer',
        'budget' => 'decimal:8',
        'target_audience' => 'array',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function serviceUser()
    {
        return $this->belongsTo(ServiceUser::class, 'service_user_id');
    }

    public function creatives()
    {
        return $this->hasMany(CampaignCreative::class, 'campaign_id');
    }

    public function performanceLogs()
    {
        return $this->hasMany(CampaignPerformanceLog::class, 'campaign_id');
    }
}
