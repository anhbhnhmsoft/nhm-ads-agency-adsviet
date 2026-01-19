<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MetaBusinessManager extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'meta_business_managers';

    protected $fillable = [
        'bm_id',
        'parent_bm_id',
        'name',
        'primary_page_id',
        'primary_page_name',
        'verification_status',
        'timezone_id',
        'currency',
        'is_primary',
        'last_synced_at',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'last_synced_at' => 'datetime',
    ];

    /**
     * Lấy danh sách BM con của BM này
     */
    public function children()
    {
        return $this->hasMany(MetaBusinessManager::class, 'parent_bm_id', 'bm_id');
    }

    /**
     * Lấy BM cha của BM này
     */
    public function parent()
    {
        return $this->belongsTo(MetaBusinessManager::class, 'parent_bm_id', 'bm_id');
    }
}
