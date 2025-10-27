<?php

namespace App\Models;

use App\Core\GenerateId\GenerateIdSnowflake;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServicePackage extends Model
{
    use HasFactory, SoftDeletes, GenerateIdSnowflake;

    protected $fillable = [
        'name',
        'platform',
        'platform_setting_id',
        'features',
        'open_fee',
        'top_up_fee',
        'set_up_time',
        'disabled',
    ];

    protected $casts = [
        'platform' => 'integer',
        'features' => 'array',
        'open_fee' => 'decimal:8',
        'top_up_fee' => 'decimal:8',
        'set_up_time' => 'integer',
        'disabled' => 'boolean',
    ];

    public function platformSetting()
    {
        return $this->belongsTo(PlatformSetting::class);
    }

    public function feeTiers()
    {
        return $this->hasMany(ServicePackageFeeTier::class, 'package_id');
    }

    public function serviceUsers()
    {
        return $this->hasMany(ServiceUser::class, 'package_id');
    }
}
