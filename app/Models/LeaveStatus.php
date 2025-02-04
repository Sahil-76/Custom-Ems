<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class LeaveStatus extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table="leave_status";
    protected $primaryKey="id";
    protected $guareded="id";
}
