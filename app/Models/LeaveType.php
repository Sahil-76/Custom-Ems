<?php

namespace App\Models;

use App\Models\Leave;
use Illuminate\Database\Eloquent\Model;

class LeaveType extends Model
{
    protected $table    = 'leave_types';
    protected $guarded  = ['id'];

    public function leaves()
{
    return $this->hasMany(Leave::class, 'leave_type_id');
}
}
