<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ResignationReason extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $table   =   'resignation_reasons';
    protected $guarded = ['id'];
}
