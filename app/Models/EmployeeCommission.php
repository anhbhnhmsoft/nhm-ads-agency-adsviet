<?php

namespace App\Models;

use App\Core\GenerateId\GenerateIdSnowflake;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeCommission extends Model
{
    use HasFactory, SoftDeletes, GenerateIdSnowflake;

    protected $fillable = [
        'employee_id',
        'service_package_id',
        'type',
        'rate',
        'min_amount',
        'max_amount',
        'is_active',
        'description',
    ];

    protected $casts = [
        'id' => 'string',
        'employee_id' => 'string',
        'service_package_id' => 'string',
        'rate' => 'decimal:4',
        'min_amount' => 'decimal:2',
        'max_amount' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function servicePackage()
    {
        return $this->belongsTo(ServicePackage::class, 'service_package_id');
    }

    // Commission types
    public const TYPE_SERVICE = 'service';
    public const TYPE_SPENDING = 'spending';
    public const TYPE_ACCOUNT = 'account';

    public static function getTypes(): array
    {
        return [
            self::TYPE_SERVICE => 'Hoa hồng dịch vụ',
            self::TYPE_SPENDING => 'Hoa hồng theo spending',
            self::TYPE_ACCOUNT => 'Hoa hồng theo bán account',
        ];
    }
}

