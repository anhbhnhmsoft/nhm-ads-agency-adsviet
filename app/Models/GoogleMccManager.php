<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GoogleMccManager extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'google_mcc_managers';

    protected $fillable = [
        'mcc_id',
        'parent_mcc_id',
        'name',
        'time_zone',
        'currency',
        'is_primary',
        'last_synced_at',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'last_synced_at' => 'datetime',
    ];

    public function children()
    {
        return $this->hasMany(GoogleMccManager::class, 'parent_mcc_id', 'mcc_id');
    }

    public function parent()
    {
        return $this->belongsTo(GoogleMccManager::class, 'parent_mcc_id', 'mcc_id');
    }
}




