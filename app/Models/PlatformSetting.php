<?php

namespace App\Models;

use App\Core\GenerateId\GenerateIdSnowflake;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PlatformSetting extends Model
{
    use HasFactory, SoftDeletes, GenerateIdSnowflake;

    protected $fillable = [
        'platform',
        'config',
        'disabled',
    ];
    protected $casts = [
        'platform' => 'integer',
        'config' => 'array',
        'disabled' => 'boolean',
    ];

    public function servicePackages()
    {
        return $this->hasMany(ServicePackage::class);
    }
}
