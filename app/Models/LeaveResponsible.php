<?php

namespace App\Models;

use App\Models\Role;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LeaveResponsible extends Model
{
    use HasFactory;

    protected $table='leave_responsibles';
    protected $guarded=['id'];

    public function employeeRole()
    {
        return $this->belongsTo(Role::class,'employee_role_id','id');
    }

    
    public function firstApproval()
    {
        return $this->belongsTo(Role::class,'first_approval','id');
    }
    
    public function secondApproval()
    {
        return $this->belongsTo(Role::class,'second_approval','id');
    }
    
    public function thirdApproval()
    {
        return $this->belongsTo(Role::class,'third_approval','id');
    }
    public function fourthApproval()
    {
        return $this->belongsTo(Role::class,'fourth_approval','id');
    }

}
