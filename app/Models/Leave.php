<?php

namespace App\Models;

use Str;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use App\Models\LeaveType;
use App\Models\LeaveResponsible;
use Illuminate\Support\Facades\Cache;
use App\Models\NewLeaveApprovalStatus;
use Illuminate\Database\Eloquent\Model;

class Leave extends Model
{
    protected $table                    = 'leaves';
    protected $guarded                  = ['id'];
    private static $status              = ['Pending', 'Cancel', 'Rejected', 'Approved'];
    private static $leaveSession        = ['Full Day' => ['Full Day'], 'Half Day' => ['First Half', 'Second Half']];
    protected $appends                  = ['duration','sundays'];
    protected $additional_attributes    = ['duration'];
    public function user()
    {
        return $this->belongsTo('App\User', 'user_id')->withoutGlobalScopes();
    }

    public static function getLeaveSession()
    {
        return self :: $leaveSession;
    }

    public function activity()
    {
        return  $this->morphMany('App\Models\ActivityLog','module');
    }

    public function getTimingAttribute()
    {
        if(empty($this->user))
        {
            return '';
        }

        switch ($this->leave_session) {
            case 'Second half':
                return $this->user->shiftType->mid_time.'-'.$this->user->shiftType->end_time;
                break;
            case 'First half':
                return $this->user->shiftType->start_time.'-'.$this->user->shiftType->mid_time;
                break;
            case 'Full day':
                return $this->user->shiftType->start_time.'-'.$this->user->shiftType->end_time;
                break;

            default:
                # code...
                break;
        }
    }

    public function leaveCancellation()
    {
        $date   =   $this->from_date;
        $time   =   Str::before($this->timing,'-');
        $today  =   Carbon::now();

        if($this->leave_session!='Second half')
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

    public function setDurationAttribute()
    {
        if($this->duration > 1)
        {
            return $this->duration . " Days";
        }
        return $this->duration . " Day";
    }

    public function getDurationAttribute()
    {
        $from_date      =   Carbon::parse($this->from_date);
        $to_date        =   Carbon::parse($this->to_date);
        $days           =   $from_date->diffInDays($to_date);
        $offDay         =   $this->user->off_day ?? 'Sunday';
        $day_of_week    =   date('N', strtotime($offDay));
        $offDays        =   intval($days / 7) + ($from_date->format('N') + $days % 7 >= $day_of_week);

        if($this->leave_session!='Full day')
        {
            $days       =   $days+1-$offDays;
            return $days/2;
        }

        return $days+1-$offDays;
    }
    public function approvalStatus()
    {
        return $this->hasMany(NewLeaveApprovalStatus::class,'leave_id','id')->orderBy('id');
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

        if($user->hasRole('Team Leader'))
        {
            $role=Role::where('name','Team Leader')->first();
            $role_id=$role->leaveResponsible->$approval;
            $leaveResponsible= $role->leaveResponsible->id;

        }
        elseif($user->hasRole('Manager'))
        {
           $role=Role::where('name','Manager')->first();
           $role_id=$role->leaveResponsible->$approval;
           $leaveResponsible= $role->leaveResponsible->id;

        }
        elseif($user->hasRole('Line Manager'))
        {
           $role=Role::where('name','Line Manager')->first();
           $role_id=$role->leaveResponsible->$approval;
           $leaveResponsible= $role->leaveResponsible->id;

        }
        elseif($user->hasRole('Employee'))
        {
           $role=Role::where('name','Employee')->first();
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


        if($approval_role == 'Team Leader')
        {
           $approval_user = $employee->teamLeader;
        }
        if($approval_role == 'Manager')
        {
           $approval_user = $employee->manager;
        }
        if($approval_role == 'Line Manager')
        {
           $approval_user = $employee->lineManager;
        }

        $data['approval_role']=$approval_role;
        $data['approval_user']=$approval_user;
        $data['leaveResponsible']=$leaveResponsible;
        return $data;

    }
    public function leaveResponsible()
    {
        return $this->belongsTo(LeaveResponsible::class,'leave_responsible_id');
    }
    public function  getPendingApprovalRoleAttribute()
    { 

       $approvalStatus= $this->approvalStatus->where('action','Pending')->first();

       if(empty($approvalStatus)) return null;

       return $approvalStatus->role($approvalStatus->stage) ?? null;
    }

    public function getSundaysAttribute()
    {
        $from_date      =   Carbon::parse($this->from_date);
        $to_date        =   Carbon::parse($this->to_date);
        $days           =   $from_date->diffInDays($to_date);
        $offDay         =   $this->user->off_day ?? 'Sunday';
        $day_of_week    =   date('N', strtotime($offDay));
        $offDays        =   intval($days / 7) + ($from_date->format('N') + $days % 7 >= $day_of_week);

        return $offDays;
    }

    public function forwardedLeave()
    {
        $leaveTypeIds   =   LeaveType::whereIn('name',['Marriage','Exam','Medical'])->pluck('id','id')->toArray();

        if((in_array($this->leave_nature,$leaveTypeIds) && $this->duration >2 ) ||  ($this->duration>2) )
        {
            return false;
        }
        return true;
    }

    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class,'leave_type_id');
    }

    public function getShiftAttribute($value)
    {
        $start_time = Carbon::createFromFormat('H:i:s',$this->user->shiftType->start_time)->format('g:i A');
        $end_time   = Carbon::createFromFormat('H:i:s',$this->user->shiftType->end_time)->format('g:i A');

        return  $start_time."-".$end_time;
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


    public function leaveDeduction()
    {
        return $this->hasMany(LeaveDeduction::class, 'leave_id','id');
    }
    
}
