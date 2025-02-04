<?php

namespace App\Models;

use App\Models\NewLeaveBalance;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class LeaveTypes extends Model
{
    use HasFactory;

    protected $table="leave_types";
    // protected $primaryKey="id";
    protected $guarded=['id'];

    public function scopeActive($query)
    {
        $query->where('is_active', 1);
    }

    public function scopeCanApply($query)
    {
        $query->where('can_apply', 1);
    }

    public function leaveBalances()
    {
        return $this->hasMany(NewLeaveBalance::class,'leave_type_id','id');
    }
}
