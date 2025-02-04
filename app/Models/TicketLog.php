<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TicketLog extends Model
{
    use HasFactory;
    protected $guarded = ['id'];
    
    function actionBy()
    {
        return $this->belongsTo(User::class, 'action_by', 'id');
    }
}
