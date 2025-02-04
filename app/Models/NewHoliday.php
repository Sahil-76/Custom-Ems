<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NewHoliday extends Model
{
    use HasFactory;
    protected $table    =   'general_holidays';
    protected $guarded  =   ['id'];
}
