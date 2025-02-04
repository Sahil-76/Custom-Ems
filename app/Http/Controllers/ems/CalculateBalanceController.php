<?php

namespace App\Http\Controllers\ems;

use App\User;
use Carbon\Carbon;
use Illuminate\Support\Str;
use App\Models\LeaveBalance;
use App\Http\Controllers\Controller;

class CalculateBalanceController extends Controller
{

    public $defaultEveryMonthBalance;
    public $currentMonthDate;
    public $lastMonthDate;

    public function __construct()
    {
        ini_set('max_execution_time', '-1');
        $this->defaultEveryMonthBalance = 1.25;
        $this->currentMonthDate = Carbon::now()->startOfMonth();
        $this->lastMonthDate = Carbon::now()->startOfMonth()->subMonth();
        if ($this->currentMonthDate->format("m") == "01") {
            $this->lastMonthDate = Carbon::now()->subYear()->addMonths(11)->startOfMonth();
        }
        //  $this->currentMonthDate = Carbon::create(2024, 02, 1)->startOfMonth(); // December of the current year
        // $this->lastMonthDate = Carbon::create(2024, 01, 1)->startOfMonth(); // November of the current year
    }

    public function calculateBalance($usrId = null)
    {
        $users = User::whereHas('employee', function ($query) {
            $query->whereNotNull('contract_date');
        })->with('employee');

        if (!empty($usrId)) {
            $users = $users->where('id', $usrId);
        }

        $users = $users->get();

        foreach ($users as $user) {
            if ($this->newJoinerBalanceEligibility($user->employee->contract_date) === false) {
                continue;
            }

            $leaveBalance = $this->getLeaveBalanceByMonth($user->id, $this->currentMonthDate);
            $lastMonthLeaveBalance = $this->getLeaveBalanceByMonth($user->id, $this->lastMonthDate);

            if (empty($leaveBalance)) {
                $this->createNewBalance($user->id, $lastMonthLeaveBalance);
            } else {
                $this->updateExistingBalance($leaveBalance, $lastMonthLeaveBalance);
            }
        }
    }


    private function createNewBalance($userId, $lastMonthLeaveBalance)
    {
        $leaveBalance = new LeaveBalance();
        $balance = $this->defaultEveryMonthBalance;
        $leaveBalance->month = $this->currentMonthDate->format('Y-m-d');
        $leaveBalance->user_id = $userId;
        $leaveBalance->allowance = $this->defaultEveryMonthBalance;
        $leaveBalance->previous_balance = ($this->currentMonthDate->format('m') != "1" && !empty($lastMonthLeaveBalance)) 
                                          ? $lastMonthLeaveBalance->final_balance 
                                          : 0;
        if ($this->currentMonthDate->format('m') != "1" && !empty($lastMonthLeaveBalance)) {
            $balance += $lastMonthLeaveBalance->balance;
        }
        $leaveBalance->paid_leaves = 0;
        $leaveBalance->lwp_leaves = 0;
        $leaveBalance->final_balance = $leaveBalance->allowance + $leaveBalance->previous_balance - $leaveBalance->paid_leaves;
        $leaveBalance->utilizable_balance = $this->calculateUtilizableBalance($leaveBalance->final_balance);
        $leaveBalance->balance = $balance;
        $leaveBalance->save();
    }

    private function updateExistingBalance($leaveBalance, $lastMonthLeaveBalance)
    {
        $leaveBalance->allowance = $this->defaultEveryMonthBalance;
    
        if ($this->currentMonthDate->format('m') != "1" && !empty($lastMonthLeaveBalance)) {
            $leaveBalance->previous_balance = $lastMonthLeaveBalance->final_balance;
        } else {
            $leaveBalance->previous_balance = 0;
        }
    
        
        $leaveBalance->paid_leaves += 0; 
        $leaveBalance->lwp_leaves += 0;  
    
        $leaveBalance->final_balance = $leaveBalance->allowance + $leaveBalance->previous_balance - $leaveBalance->paid_leaves;
        $leaveBalance->utilizable_balance = $this->calculateUtilizableBalance($leaveBalance->final_balance);
    
        $leaveBalance->save();
    }
    
    private function calculateUtilizableBalance($finalBalance)
    {

        return floor($finalBalance * 2) / 2;
    }
    

    /** if person has joined in current month and joined after 15 then no balance will be credited  */

    public function newJoinerBalanceEligibility($contractDate)
    {
        if (empty($contractDate)) {
            return false;
        }

        $joinMonth = Carbon::createFromFormat('Y-m-d', $contractDate);
        if ($joinMonth->format('d') > 15 && $joinMonth->format('m') == $this->lastMonthDate->format('m') && $joinMonth->format('Y') == $this->lastMonthDate->format('Y')) {
            return false;
        } else {
            return true;
        }
    }

    private function getLeaveBalanceByMonth($userId, $date)
    {
        return LeaveBalance::where('user_id', $userId)->whereMonth('month', $date)->whereYear('month', $date)->first();
    }

    private function isDecimalBalanceExists($deductibleBalance)
    {
        $decimal = Str::after($deductibleBalance, ".");
        if ($decimal == '25' || $decimal == '75') {
            return true;
        } else {
            return false;
        }
    }
}
