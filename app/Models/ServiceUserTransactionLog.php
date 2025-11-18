<?php

namespace App\Models;

use App\Core\GenerateId\GenerateIdSnowflake;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServiceUserTransactionLog extends Model
{
    use HasFactory, SoftDeletes, GenerateIdSnowflake;

    protected $fillable = [
        'service_user_id',
        'amount',
        'type',
        'status',
        'reference_id',
        'description',
    ];

    protected $casts = [
        'id' => 'string',
        'service_user_id' => 'string',
        'amount' => 'decimal:8',
        'type' => 'integer',
        'status' => 'integer',
    ];

    public function serviceUser()
    {
        return $this->belongsTo(ServiceUser::class, 'service_user_id');
    }
}
