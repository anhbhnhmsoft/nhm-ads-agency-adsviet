<?php

namespace App\Models;

use App\Core\GenerateId\GenerateIdSnowflake;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServicePackageFeeTier extends Model
{
    use HasFactory, SoftDeletes, GenerateIdSnowflake;

    protected $fillable = [
        'package_id',
        'range_min',
        'range_max',
        'fee_percent',
    ];

    protected $casts = [
        'package_id' => 'integer',
        'range_min' => 'decimal:8',
        'range_max' => 'decimal:8',
        'fee_percent' => 'decimal:2',
    ];
    public function package()
    {
        return $this->belongsTo(ServicePackage::class, 'package_id');
    }

}
