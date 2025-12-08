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
        'id' => 'string',
        'package_id' => 'string',
        'user_id' => 'string',
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


    /**
     * Danh sách ads account Meta liên kết với người dùng dịch vụ - chỉ với dịch vụ Meta Ads
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function metaAccount()
    {
        return $this->hasMany(MetaAccount::class, 'service_user_id');
    }
     /**
     * Danh sách ads campaign Meta liên kết với người dùng dịch vụ - chỉ với dịch vụ Meta Ads
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function metaAdsCampaigns()
    {
        return $this->hasMany(MetaAdsCampaign::class, 'service_user_id');
    }

    /**
     * Danh sách tài khoản Google Ads liên kết với service user
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function googleAccounts()
    {
        return $this->hasMany(GoogleAccount::class, 'service_user_id');
    }
}
