<?php

namespace App\Models;

use App\Core\GenerateId\GenerateIdSnowflake;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ticket extends Model
{
    use HasFactory, SoftDeletes, GenerateIdSnowflake;

    protected $fillable = [
        'user_id',
        'subject',
        'description',
        'status',
        'priority',
        'assigned_to',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function conversations()
    {
        return $this->hasMany(TicketConversation::class, 'ticket_id');
    }
}
