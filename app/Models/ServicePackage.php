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
        'id',
        'name',
        'platform',
        'features',
        'monthly_spending_fee_structure',
        'open_fee',
        'top_up_fee',
        'supplier_fee_percent',
        'set_up_time',
        'disabled',
        'description',
        'range_min_top_up'
    ];

    protected $casts = [
        'id' => 'string',
        'platform' => 'integer',
        'features' => 'array',
        'monthly_spending_fee_structure' => 'array',
        'open_fee' => 'decimal:8',
        'top_up_fee' => 'integer',
        'supplier_fee_percent' => 'decimal:2',
        'set_up_time' => 'integer',
        'disabled' => 'boolean',
        'range_min_top_up' => 'decimal:8',
    ];

    public function serviceUsers()
    {
        return $this->hasMany(ServiceUser::class, 'package_id');
    }

    public function postpayUsers()
    {
        return $this->belongsToMany(User::class, 'service_package_postpay_users', 'service_package_id', 'user_id')
            ->withTimestamps();
    }
}
