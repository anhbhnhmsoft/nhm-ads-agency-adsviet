<?php

namespace App\Models;

use App\Core\GenerateId\GenerateIdSnowflake;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CampaignCreative extends Model
{
    use HasFactory, SoftDeletes, GenerateIdSnowflake;

    protected $fillable = [
        'campaign_id',
        'type',
        'title',
        'content',
        'status',
    ];

    protected $casts = [
        'campaign_id' => 'integer',
        'type' => 'integer',
        'status' => 'integer',
    ];
    public function campaign()
    {
        return $this->belongsTo(Campaign::class, 'campaign_id');
    }

    public function files()
    {
        return $this->hasMany(CampaignCreativeFile::class, 'creative_id');
    }
}
