<?php

namespace App\Http\Controllers\ems;

use App\User;
use App\Models\Leave;
use App\Models\NewLeave;
use App\Models\Attendance;
use App\Models\LeaveTypes;
use App\Models\NewHoliday;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Models\NewLeaveBalance;
use App\Http\Controllers\Controller;

class NewLeaveBalanceController extends Controller
{
    public $staticStartMonth;
    public $currentMonth;
    public $on_board_status;

    public function __construct() {
        // $this->staticStartMonth = '2021-08-01'; //Start date of Banaluru Office 2021
        $this->staticStartMonth = Carbon::today()->subMonth()->startOfMonth()->format('Y-m-d');
        $this->currentMonth     = Carbon::today()->format('Y-m-d');
        $this->on_board_status  = ['Started'];
    }

    public function calculateBalance()
    {
        ini_set('max_execution_time',600);
        $months     = $this->getMonths();

        $leaveTypes = LeaveTypes::where('is_active', 1)->get();

        $users      =   User::with('employee')->whereHas('employee', function($emp){
                            $emp->whereIn('branch', ['Bengaluru', 'Delhi', 'Chandigarh'])
                                ->whereIn('on_board_status', [$this->on_board_status]);
                            })
                            // ->where('id',1140)
                            ->get();

        foreach ($months as $monthArray) {
            $monthDate          = $monthArray['monthDate'];
            $month              = $monthArray['month'];
            $year               = $monthArray['year'];
            $totalDaysInMonth   = Carbon::parse($monthDate)->daysInMonth;
            $middleOfMonth      = (int) ceil($totalDaysInMonth/2);
            // $previousMonth      = Carbon::parse($monthDate)->subDays(3)->subMonth()->endOfMonth();
            $previousMonth      = Carbon::parse($monthDate)->setDay(1)->subMonth()->endOfMonth();
            foreach ($users as $user) {

                $allowance = 0;
                $previous = 0;
                $lastBalance = 0;
                $joinedThisMonth = false;

                $userId = $user->id;

                $joiningDate    = $user->employee->joining_date;
                $joinedDay      = Carbon::parse($joiningDate)->format('d');
                $joiningMonth   = Carbon::parse($joiningDate)->format('m');
                $joiningYear    = Carbon::parse($joiningDate)->format('Y');


                if ($month == $joiningMonth && $year == $joiningYear) {
                    $joinedThisMonth = true;
                } elseif (Carbon::parse($joiningDate)->startOfMonth()->lt(Carbon::parse($monthDate))) {
                    $joinedThisMonth = false;
                }else{
                    continue;
                }

                $attendance    =   Attendance::where('user_id', $userId)
                ->whereMonth('date','=', $month)
                ->whereYear('date', $year)
                ->get();

                $monthlyLeaves  = $this->getUserMonthlyLeaves($userId, $month, $year);

                if ($joinedThisMonth) { //if current month is joining month

                    foreach ($leaveTypes as $leaveType) {

                        if ((int) $joinedDay < $middleOfMonth) { //if date is <= middle of month

                            $allowance      = $leaveType->allowance; // set allowence  = leave type -> allowance

                        }else{ //if date is > middle of month
                            $allowance = 0; // set allowance = 0
                        }

                        $previous = 0; //previous balance = set its value = 0

                        $takenLeaves        =   $this->getTakenManualLeaves($attendance, $leaveType);
                        $takenLeaves        +=  $this->getTakenLeaves($monthlyLeaves, $leaveType);

                        $this->saveBalance($userId, $leaveType, $monthDate, $allowance, $previous, $takenLeaves, $monthArray);
                    }

                }else { // if this is not joinig month

                    foreach ($leaveTypes as $leaveType) {

                        $lastMonthBalance   = $this->getUserLeaveBalance($userId, $leaveType, $previousMonth);
                        $lastBalance        = 0;

                        if (!empty($leaveType->yearly_expiry) && $month == 01 ) {
                            $lastBalance    = 0;
                        }elseif(!empty($lastMonthBalance)){
                            $lastBalance    =   $lastMonthBalance->final_balance;
                        }

                        $previous       = 0; //default carry balance 0


                        if ($leaveType->carry_forward == 1 &&  $lastBalance > 0) {
                            $previous   = $lastBalance;
                        }

                        if ($leaveType->allowance_type == 'yearly') {

                            $lastMonthAttendance    =   Attendance::where('user_id', $userId)
                                                        ->whereMonth('date','<=', $month)
                                                        ->whereYear('date', $year)
                                                        ->get();
                            $takenLeaves         =   $this->getTakenManualLeaves($lastMonthAttendance, $leaveType);

                        }else{
                            $takenLeaves        =   $this->getTakenManualLeaves($attendance, $leaveType);
                        }

                        $takenLeaves        +=  $this->getTakenLeaves($monthlyLeaves, $leaveType);
                        $allowance      =   $leaveType->allowance; // set allowence  = leave type -> allowance

                        $this->saveBalance($userId, $leaveType, $monthDate, $allowance, $previous, $takenLeaves, $monthArray);
                    }
                }
            }
        }

    }

    public function getUserMonthlyAttendance($userId, $month, $year)
    {
        return Attendance::where('user_id', $userId)
        ->whereMonth('date','=', $month)
        ->whereYear('date', $year)
        ->get();
    }

    public function getUserMonthlyLeaves($userId, $month, $year)
    {
        return NewLeave::where('user_id', $userId)
        ->whereMonth('balance_dt', $month)
        ->whereYear('balance_dt', $year)
        ->where('status', 'Approved')
        ->get();
        // return Leave::where('user_id', $userId)
        // ->whereMonth('from_date', $month)
        // ->whereYear('from_date', $year)
        // ->where('status', 'Approved')
        // ->get();
    }

    public function getUserLeaveBalance($userId, $leaveType,  $date)
    {
        return NewLeaveBalance::where('user_id', $userId)
                    ->whereMonth('month', $date)
                    ->whereYear('month', $date)
                    ->where('leave_type_id', $leaveType->id)->first();
    }

    public function getTakenLeaves($leaves, $leaveType)
    {
        return $leaves->where('leave_type_id', $leaveType->id)->sum('duration');
        // $takenHalfDays   = $leaves->where('type_id', $leaveType->id)->sum('duration');

        // dd($takenFullDays, $takenHalfDays);
        // return $takenFullDays + ($takenHalfDays/2);
    }

    public function getTakenManualLeaves($attendance, $leaveType)
    {
        $takenFullDays   = $attendance->where('status', $leaveType->name)->where('nature', 'Full Day')->count();
        $takenHalfDays   = $attendance->where('status', $leaveType->name)->where('nature', 'Half Day')->count();
        return $takenFullDays + ($takenHalfDays/2);
    }

    public function saveBalance($userId, $leaveType, $monthDate, $allowance, $previous, $takenLeaves, $monthArray)
    {
        $monthDate      = $monthArray['monthDate'];
        $month          = $monthArray['month'];
        $year           = $monthArray['year'];

        $final = ($allowance + $previous) - $takenLeaves;

        $object = NewLeaveBalance::where('user_id', $userId)->whereMonth('month', $month)->whereYear('month', $year)->where('leave_type_id', $leaveType->id)->first();
        if (empty($object)) {
            $object = new NewLeaveBalance();
            $object->user_id        = $userId;
            $object->leave_type_id  = $leaveType->id;
            $object->month          = $monthDate;
        }

        $object->allowance          = $allowance;
        $object->previous_balance   = $previous;
        $object->taken_leaves       = $takenLeaves;
        $object->final_balance      = $final;

        $object->save();
    }

    public function getMonths()
    {
        $start_time         =   strtotime($this->staticStartMonth);
        // $end_time           =   strtotime($this->currentMonth);
        $end_time           =   strtotime(Carbon::parse($this->currentMonth)->addMonth());

        for($i=$start_time; $i<=$end_time; $i+=86400)
        {
            $months[date('Y-m', $i)] =  [
                                            'monthDate' =>  date('Y-m-d', $i),
                                            'month'     =>  date('m', $i),
                                            'year'      =>  date('Y', $i),
                                        ];
        }

        return $months;
    }

    public function calculateNationalHolidayBalance($leaveType,$user_id,$monthArray)
    {


        $monthDate      =   $monthArray['monthDate'];
        $month          =   $monthArray['month'];
        $year           =   $monthArray['year'];
        $holidays       =   NewHoliday::where('type',$leaveType->name)
                            ->whereMonth('date',$month)
                            ->whereYear('date',$year)->count();


        $object         =   NewLeaveBalance::where(['user_id'=>$user_id,'leave_type_id'=>$leaveType->id])
        ->whereMonth("month",$month)->whereYear("month",$year)->first();
        if(empty($object))
        {
            $object                 = new NewLeaveBalance();
            $object->user_id        = $user_id;
            $object->leave_type_id  = $leaveType->id;
        }

        $object->month          = $monthDate;
        $object->allowance      = $holidays;
        $object->final_balance  = $holidays;
        $object->save();

    }
}
