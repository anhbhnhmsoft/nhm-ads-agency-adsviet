<?php

namespace App\Models;

use App\Core\GenerateId\GenerateIdSnowflake;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use HasFactory, SoftDeletes, GenerateIdSnowflake;

    protected $fillable = [
        'id',
        'name',
        'open_fee',
        'postpay_fee',
        'supplier_fee_percent',
        'monthly_spending_fee_structure',
        'disabled',
    ];

    protected $casts = [
        'id' => 'string',
        'open_fee' => 'decimal:8',
        'postpay_fee' => 'decimal:8',
        'supplier_fee_percent' => 'decimal:2',
        'monthly_spending_fee_structure' => 'array',
        'disabled' => 'boolean',
    ];

    public function servicePackages()
    {
        return $this->hasMany(ServicePackage::class, 'supplier_id');
    }
}

