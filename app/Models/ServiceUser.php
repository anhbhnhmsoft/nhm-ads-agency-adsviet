<?php

namespace App\Models;

use App\Core\GenerateId\GenerateIdSnowflake;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServiceUser extends Model
{
    use HasFactory, SoftDeletes, GenerateIdSnowflake;

    protected $fillable = [
        'package_id',
        'user_id',
        'config_account',
        'status',
        'budget',
        'description',
    ];

    protected $casts = [
        'package_id' => 'integer',
        'user_id' => 'integer',
        'config_account' => 'array',
        'status' => 'integer',
        'budget' => 'decimal:8',
    ];

    // Relationships
    public function package()
    {
        return $this->belongsTo(ServicePackage::class, 'package_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transactionLogs()
    {
        return $this->hasMany(ServiceUserTransactionLog::class, 'service_user_id');
    }

    public function campaigns()
    {
        return $this->hasMany(Campaign::class, 'service_user_id');
    }
}
