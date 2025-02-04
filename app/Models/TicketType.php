<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TicketType extends Model
{
    use HasFactory;
    protected $guarded = ['id'];

    public function scopeActive(Builder $query)
    {
        return $query->where('is_active', 1);
    }


    function responsibleUsers()
    {
        return $this->belongsToMany(User::class, 'ticket_responsible_user', 'ticket_type_id','user_id')->withTimestamps();
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class, 'type_id');
    }

}
