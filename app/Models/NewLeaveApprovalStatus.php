<?php

namespace App\Models;

use App\User;
use App\Models\Leave;
use App\Models\NewLeave;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class NewLeaveApprovalStatus extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table='new_leave_approval_status';
    protected $guarded=['id'];

    public function leave()
    {
        return $this->belongsTo(Leave::class,'leave_id');
    }
    public function actionBy()
    {
        return $this->belongsTo(User::class,'action_by','id');
    }

    public function role($stage)
    {

        $role_id    =  $this->leave->leaveResponsible->$stage ?? null;

        if (empty($role_id)) {
            return null;
        }

        $roles = Cache::remember(auth()->user()->id.'-roles', 120, function () {
            return Role::get();
        });


        $role       =   $roles->where('id', $role_id)->first();
        return $role->name;

    }
}
