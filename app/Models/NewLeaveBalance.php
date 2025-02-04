<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NewLeaveBalance extends Model
{
    use HasFactory;
    protected $table='new_leave_balances';
    protected $guarded = ['id'];

    public function scopeBalanceBetween($query, $fromDate, $toDate)
    {
        $query->where(function($q1)use($fromDate, $toDate){
            $q1->where(function($q2) use($fromDate){
                $q2->whereMonth('month', getFormattedDate($fromDate, 'm'))->whereYear('month', getFormattedDate($fromDate, 'Y'));
            })->orWhere(function($q3) use($toDate){
                $q3->whereMonth('month', getFormattedDate($toDate, 'm'))->whereYear('month', getFormattedDate($toDate, 'Y'));
            });
        });
    }
}
