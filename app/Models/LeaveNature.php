<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class LeaveNature extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table="leave_natures";
    protected $primaryKey="id";
    protected $guareded="id";
}
