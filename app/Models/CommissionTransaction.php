<?php

namespace App\Models;

use App\Core\GenerateId\GenerateIdSnowflake;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CommissionTransaction extends Model
{
    use HasFactory, SoftDeletes, GenerateIdSnowflake;

    protected $fillable = [
        'employee_id',
        'customer_id',
        'type',
        'reference_type',
        'reference_id',
        'base_amount',
        'commission_rate',
        'commission_amount',
        'period',
        'is_paid',
        'paid_at',
        'notes',
    ];

    protected $casts = [
        'id' => 'string',
        'employee_id' => 'string',
        'customer_id' => 'string',
        'base_amount' => 'decimal:2',
        'commission_rate' => 'decimal:4',
        'commission_amount' => 'decimal:2',
        'is_paid' => 'boolean',
        'paid_at' => 'date',
    ];

    // Relationships
    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }
}


