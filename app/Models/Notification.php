<?php

namespace App\Models;

use App\Core\GenerateId\GenerateIdSnowflake;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Notification extends Model
{
    use HasFactory, SoftDeletes, GenerateIdSnowflake;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'data',
        'type',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
