<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use App\Models\NewLeaveApprovalStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class NewLeave extends Model
{
    use HasFactory;
    protected $table='new_leaves';
    protected $guarded=['id'];

    private static $status = ['Pending', 'Cancel', 'Rejected', 'Approved'];
    // private static $leaveNature = ['Casual'=>'Casual', 'Sick'=>'Sick', 'Emergency'=>'Emergency',
    //  'Marriage'=>'Marriage','Medical'=>'Medical','Exam'=>'Exam'];
    private static $leaveNature = ['Full Day' => ['Full Day'], 'Half Day' => ['First Half', 'Second Half']];
    // public $appends = ['duration'];
 


    public static function getLeaveNature()
    {
        return self :: $leaveNature;
    }

    public static function getStatus()
    {
        return self :: $status;
    }

    public function getBalanceDtAttribute($value)
    {
        return $value ?? $this->from_date;
    }

    public function user()
    {
        return $this->belongsTo('App\User','user_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'user_id', 'user_id');
    }

    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class,'leave_type_id');
    }

    public function leaveResponsible()
    {
        return $this->belongsTo(LeaveResponsible::class,'leave_responsible_id');
    }
    public function getDurationAttribute()
    {
        if(str_contains($this->leave_nature,'Half day'))
        {
            return 0.5;
        }
        $from_date  = Carbon::parse($this->from_date);
        $to_date    = Carbon::parse($this->to_date);
        $days       = $from_date->diffInDays($to_date);
        return $days+1;
    }

    // public function leaveLogs()
    // {

    //     return $this->hasMany(LeaveLogs::class,'leave_id','id');


    // }
    public function approvalStatus()
    {
        return $this->hasMany(NewLeaveApprovalStatus::class,'leave_id','id');
    }


    public function  getPendingApprovalRoleAttribute()
    {

       $approvalStatus= $this->approvalStatus->where('action','Pending')->first();

       if(empty($approvalStatus)) return null;

       return $approvalStatus->role($approvalStatus->stage) ?? null;
    }

    public function leaveCancellation()
    {
        $date   =   $this->from_date;
        $time   =   Str::before($this->timing,'-');

        $today  =   Carbon::now();

        if($this->leave_nature!='Half day(Second half)')
        {
            $time   =   Carbon::parse($time)->addHour()->format('H:i');
        }
        if($today->format('Y-m-d')<=$date)
        {
            if($today->format('Y-m-d')==$date)
            {
                return strtotime($today->format('H:i:s'))>strtotime($time);
            }
            return false;
        }
        else
        {
            return true;
        }
    }

    public function responsiblePerson()
    {

        $approval  = $this->approvalStatus->where('action','Pending')->first();

        if(empty($approval))
        {
            $data['approval_role']=null;
            $data['approval_user']=null;
            $data['leaveResponsible']=null;
            return $data;
        }
        $approval=$approval->stage;

        $user      = $this->user;
        $employee  = $user->employee;

        if($user->hasRole('Sales Senior Manager'))
        {
            $role=Role::where('name','Sales Senior Manager')->first();
            $role_id=$role->leaveResponsible->$approval;
            $leaveResponsible= $role->leaveResponsible->id;

        }
        elseif($user->hasRole('Sales Manager'))
        {
           $role=Role::where('name','Sales Manager')->first();

           $role_id=$role->leaveResponsible->$approval;
           $leaveResponsible= $role->leaveResponsible->id;


        }
        elseif($user->hasRole('Team Leader'))
        {
           $role=Role::where('name','Team Leader')->first();
           $role_id=$role->leaveResponsible->$approval;
           $leaveResponsible= $role->leaveResponsible->id;

        }
        elseif($user->hasRole('Non Sales Senior Manager'))
        {
           $role=Role::where('name','Non Sales Senior Manager')->first();
           $role_id=$role->leaveResponsible->$approval;
           $leaveResponsible= $role->leaveResponsible->id;

        }
        elseif($user->hasRole('Manager Non Sales'))
        {
           $role=Role::where('name','Manager Non Sales')->first();
           $role_id=$role->leaveResponsible->$approval;
           $leaveResponsible= $role->leaveResponsible->id;

        }
        elseif($user->hasRole('Non Sales Team Leader'))
        {
           $role=Role::where('name','Non Sales Team Leader')->first();
           $role_id=$role->leaveResponsible->$approval;
           $leaveResponsible= $role->leaveResponsible->id;

        }
        elseif($user->hasRole('Sales Person'))
        {
           $role=Role::where('name','Sales Person')->first();
           $role_id=$role->leaveResponsible->$approval;
           $leaveResponsible= $role->leaveResponsible->id;

        }

        elseif($user->hasRole('Non Sales Person'))
        {
           $role=Role::where('name','Non Sales Person')->first();
           $role_id=$role->leaveResponsible->$approval;
           $leaveResponsible= $role->leaveResponsible->id;

        }

        $data   = [];
        if(empty($role_id))
        {

            $data['approval_role']=null;
            $data['approval_user']=null;
            $data['leaveResponsible']=null;
            return $data;
        }


        $roles = Cache::remember(auth()->user()->id.'-roles', 120, function () {
            return Role::get();
        });

        $approval_role  = $roles->where('id', $role_id)->first()->name;
        // dd($approval_role);
        $approval_user=null;


        if($approval_role == 'Team Leader' || $approval_role == 'Non Sales Team Leader')
        {
           $approval_user = $employee->teamLeader;
        }
        if($approval_role == 'Sales Manager' || $approval_role == 'Manager Non Sales')
        {
           $approval_user = $employee->manager;
        }
        if($approval_role == 'Sales Senior Manager' || $approval_role == 'Non Sales Senior Manager')
        {
           $approval_user = $employee->seniorManager;
        }

        $data['approval_role']=$approval_role;
        $data['approval_user']=$approval_user;
        $data['leaveResponsible']=$leaveResponsible;
        return $data;

    }

    public function canCancel() : bool
    {
        if (!in_array($this->status, ['Cancelled','Rejected'])){

            if ($this->from_date > today()->format('Y-m-d') && auth()->user()->id == $this->user_id){
                return true;
            }elseif (auth()->user()->can('manageLeaveType', $this)) {
                return true;
            }
        }

        return false;
    }

    public function canView() : bool
    {
        $currentUser = auth()->user();
        if ($this->user_id == $currentUser->id) {
                return true;
        }elseif ($this->user->isTeamOf($currentUser->id)) {
                return true;
        }elseif ($currentUser->can('manageLeaveType', $this) || $currentUser->can('viewRequestList', $this)) {
            return true;
        }
        return false;
    }

    public function canApprove() : bool
    {
        // if ($this->from_date < today()->toDateString()) {
        //     return false;
        // }else{
            return true;
        // }
    }

    public function calculateDuration($userId, $fromDate, $toDate, $leaveNature = 'Full Day')
    {
        $employee = Employee::firstWhere('user_id', $userId);

        // Get the employee's off days
        $offDays = $employee->getOffDays();

        $period = CarbonPeriod::create($fromDate, $toDate);
        $duration = 0;

        foreach ($period as $date) {
            if (in_array($date->format('l'), $offDays)) {
                continue;
            }

            if (str_contains($leaveNature, 'Half day')) {
                $duration += 0.5;
            } else {
                $duration++;
            }
        }

        return $duration;
    }

    public function getBalance()
    {
        return LeaveBalance::where('leave_type_id', $this->leave_type_id)->where('user_id', $this->user_id)->balanceBetween($this->balance_dt, $this->balance_dt)->first();
    }
}
