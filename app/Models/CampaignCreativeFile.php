<?php

namespace App\Models;

use App\Core\GenerateId\GenerateIdSnowflake;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CampaignCreativeFile extends Model
{
    use HasFactory, SoftDeletes, GenerateIdSnowflake;

    protected $fillable = [
        'creative_id',
        'file_path',
        'file_type',
        'file_size',
        'file_name',
    ];

    protected $casts = [
        'creative_id' => 'integer',
        'file_size' => 'integer',
    ];

    public function creative()
    {
        return $this->belongsTo(CampaignCreative::class, 'creative_id');
    }

}
