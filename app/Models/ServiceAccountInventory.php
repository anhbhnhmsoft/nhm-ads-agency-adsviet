<?php

namespace App\Models;

use App\Core\GenerateId\GenerateIdSnowflake;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServiceAccountInventory extends Model
{
    use HasFactory, SoftDeletes, GenerateIdSnowflake;

    protected $fillable = [
        'service_package_id',
        'platform',
        'account_id',
        'account_name',
        'business_manager_id',
        'customer_manager_id',
        'source_account_type',
        'source_account_id',
        'status',
        'assigned_user_id',
        'assigned_service_user_id',
        'reserved_until',
        'link_target_type',
        'link_target_value',
        'metadata',
        'last_error',
    ];

    protected $casts = [
        'id' => 'string',
        'service_package_id' => 'string',
        'platform' => 'integer',
        'source_account_id' => 'string',
        'assigned_user_id' => 'string',
        'assigned_service_user_id' => 'string',
        'reserved_until' => 'datetime',
        'metadata' => 'array',
    ];

    public function package()
    {
        return $this->belongsTo(ServicePackage::class, 'service_package_id');
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function assignedServiceUser()
    {
        return $this->belongsTo(ServiceUser::class, 'assigned_service_user_id');
    }
}
