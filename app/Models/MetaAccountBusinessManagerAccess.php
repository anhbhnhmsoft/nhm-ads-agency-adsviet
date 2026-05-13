<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MetaAccountBusinessManagerAccess extends Model
{
    protected $table = 'meta_account_business_manager_accesses';

    protected $fillable = [
        'source_bm_id',
        'owner_bm_id',
        'account_id',
        'last_synced_at',
    ];

    protected $casts = [
        'id' => 'string',
        'last_synced_at' => 'datetime',
    ];
}
