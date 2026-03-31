<?php

namespace App\Models;

use App\Core\GenerateId\GenerateIdSnowflake;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MetaBusinessAssetGroup extends Model
{
    use GenerateIdSnowflake, HasFactory;

    protected $table = 'meta_business_asset_groups';

    protected $fillable = [
        'group_id',
        'name',
        'business_manager_id',
        'status',
    ];

    protected $casts = [
        'id' => 'string',
    ];

    /**
     * Relationship with MetaAccount (Many-to-Many via pivot table)
     */
    public function accounts()
    {
        return $this->belongsToMany(MetaAccount::class, 'meta_account_asset_group', 'meta_business_asset_group_id', 'meta_account_id');
    }

    /**
     * Relationship with MetaBusinessManager (BelongsTo via Meta ID)
     */
    public function businessManager()
    {
        return $this->belongsTo(MetaBusinessManager::class, 'business_manager_id', 'bm_id');
    }
}
