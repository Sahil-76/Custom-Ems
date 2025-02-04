<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveDeduction extends Model
{
    use HasFactory;
     protected $table='leave_deductions';
     protected $guarded= ['id']; 

     public function leaveDeduction()
     {
         return $this->belongsTo(Leave::class, 'id','leave_id');
     }
}
