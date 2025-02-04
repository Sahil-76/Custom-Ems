<?php

namespace App\Models;

use App\User;
use App\Models\ResignationReason;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EmployeeResignation extends Model
{
    use HasFactory;

    protected $table   =   'employee_resignation';
    protected $guarded = ['id'];

    function user()
    {
        return $this->belongsTo(User::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'user_id','user_id');
    }


    public function actionByUser()
    {
        return $this->belongsTo(User::class, 'action_by');
    }

    public function reason()
    {
        return $this->belongsTo(ResignationReason::class, 'resignation_reason_id');
    }
    public function department()
    {
        return $this->belongsTo('App\Models\Department', 'department_id');
    }
}
