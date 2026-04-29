<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServicePackageAllowedUser extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_package_id',
        'user_id',
    ];

    protected $casts = [
        'service_package_id' => 'string',
        'user_id' => 'string',
    ];
}
