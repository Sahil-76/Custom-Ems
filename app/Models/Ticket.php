<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Ticket extends Model
{
    use HasFactory;
    
    protected $guarded  =   ['id'];
    protected $appends  =   ['priority_color'];
    protected $fillable = [
        'priority',
        'description',
        'type_id', 
        'created_by',
        'files',
        'status',
    
       
    ];

    function getFilesAttribute($value)
    {
        return json_decode($value);
    }

    public function type()
    {
        return $this->belongsTo(TicketType::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function logs()
    {
        return $this->hasMany(TicketLog::class);
    }

    // public function comments()
    // {
    //     return $this->morphMany(Comment::class, 'entity');
    // }

    public function tasks()
    {
        return $this->morphMany(Task::class, 'entity');
    }

    public function getPriorityColorAttribute()
    {
        $color='';
        switch ($this->priority) {
            case 'Low':
                $color='green';
                break;
            case 'Medium':
                $color='orange';
                break;
            case 'High':
                $color='red';
                break;
        }
        return $color;
    }
}
