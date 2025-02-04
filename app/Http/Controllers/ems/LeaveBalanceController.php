<?php

namespace App\Http\Controllers\ems;

use App\User;
use Carbon\Carbon;
use App\Mail\Action;
use App\Models\Leave;
use App\Models\Employee;
use Carbon\CarbonPeriod;
use App\Models\LeaveType;
use App\Models\Department;
use App\Models\LeaveBalance;
use Illuminate\Http\Request;
use App\Exports\LeaveBalanceExport;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\LeaveBalanceComplaint;
use Illuminate\Contracts\Mail\Mailer;
use App\Http\Requests\LeaveBalanceRequest;

class LeaveBalanceController extends Controller
{
    public $dateFrom;
    public $dateTo;
    protected $mailer;

    public function __construct(Mailer $mailer)
    {
        $this->mailer       = $mailer;
        $this->dateFrom     = Carbon::today()->startOfMonth();
        $this->dateTo       = Carbon::today()->endOfMonth();
    }

    public function dashboard(Request $request, $export = false)
    {
        $this->authorize('hrEmployeeList', new Employee());

        if ($request->filled('dateFrom') && $request->filled('dateTo')) {
            $this->dateFrom = $request->dateFrom;
            $this->dateTo   = $request->dateTo;
        }

        $date                   =   empty($request->month) ? Carbon::now()->startOfMonth() : Carbon::createFromFormat('Y-m', $request->month)->startOfMonth();
        // $date = $date->toDateString();

        // $leaveBalances          =   LeaveBalance::with([
        //     'user.employee.department',
        //     'leaveBalanceComplaints' => function ($query) {
        //         $query->orderBy('created_at', 'desc');
        //     }
        // ])->whereHas('user', function ($user) {
        //     $user->where('user_type', 'Employee')->where('is_active', '1')->has('employee');
        // });

        $leaveBalances = LeaveBalance::with([
            'user' => function ($user) {
                $user->withoutGlobalScopes()->with(['employee' => function ($emp) {
                    $emp->withoutGlobalScopes();
                }]);
            }
        ])->whereHas('user', function ($user) {
            $user->withoutGlobalScopes()->where('user_type', 'Employee')->whereHas('employee', function ($emp) {
                $emp->withoutGlobalScopes();
            });
        });

        if (!empty($request->user_id)) {
            $leaveBalances->where('user_id', $request->user_id);
        }
        if (!empty($request->previous_query)) {
            $leaveBalances->whereHas('leaveBalanceComplaints', function ($query) use ($date) {
                $query->whereYear('created_at', $date);
            });
        }
        if (!empty($request->has_complaint)) {
            $leaveBalances->whereHas('leaveBalanceComplaints', function ($query) use ($date) {
                $query->where('is_responded', 0)->whereYear('created_at', $date)->orderBy('created_at')->whereColumn('user_id', 'leave_balances.user_id');
            });
        }
        if ($request->has('date_from') && $request->has('date_to')) {
            $dateFrom = $request->date_from;
            $dateTo = $request->date_to;
            $leaveBalances->whereDate('month', '>=', $dateFrom)->whereDate('month', '<=', $dateTo);
        }
        if (!empty($request->department_id)) {
            $leaveBalances->whereHas('user.employee', function ($user) {
                $user->where('department_id', request()->department_id);
            });
        }

        $employees     =   User::withoutGlobalScopes()->with(['employee' => function ($q) {
            $q->withoutGlobalScopes();
        }])
            // ->where('is_active', 1)
            ->where('user_type', 'Employee')->whereHas('employee', function ($employee) {
                $employee->select('biometric_id', 'name', 'user_id')->withoutGlobalScopes()
                    ->when(request()->filled('department_id'), function ($q) {
                        $q->where('department_id', request()->department_id);
                    });
            });

        $data['employeeDepartments']    =   $employees->get()->groupBy('employee.department.name');;
        $data['departments']            =   Department::pluck('name', 'id')->toArray();
        $leaveBalances                  =   $leaveBalances;
        if ($export) {
            return $leaveBalances->get();
        }
        // $start                  =   Carbon::parse($date)->startOfMonth()->format('Y-m-d');
        // $end                    =   Carbon::parse($date)->endOfMonth()->format('Y-m-d');
        $data['leaveBalances']  =   $leaveBalances->get();
        $data['start']          =   Carbon::parse($this->dateFrom);
        $data['end']            =   Carbon::parse($this->dateTo);
        // $data['date']           =   Carbon::parse($date);
        return view('leave.balanceDashboard', $data);
    }

    public function edit($id, Request $request)
    {
        $this->authorize('hrEmployeeList', new Employee());
        if (!empty($request->user_id)) {
            $month                                  =   Carbon::createFromFormat('Y-m-d', $request->month);
            $leaveBalance                           =   LeaveBalance::with('leaveBalanceComplaints')
                ->where('user_id', $request->user_id)->whereMonth('month', $month)->first();
            $beforeCutOffDate1                          =   Carbon::createFromFormat('Y-m-d', $request->month)->startOfMonth();
            $beforeCutOffDate2                          =   Carbon::createFromFormat('Y-m-d', $request->month)->startOfMonth()->addDays(20);
            $afterCutOffDate1                           =   Carbon::createFromFormat('Y-m-d', $request->month)->startOfMonth()->addDays(21);
            $afterCutOffDate2                           =   Carbon::createFromFormat('Y-m-d', $request->month)->endOfMonth();
        } else {
            $leaveBalance                               =   LeaveBalance::with('leaveBalanceComplaints')->findOrFail($id);
            $beforeCutOffDate1                          =   Carbon::createFromFormat('Y-m-d', $leaveBalance->month)->startOfMonth();
            $beforeCutOffDate2                          =   Carbon::createFromFormat('Y-m-d', $leaveBalance->month)->startOfMonth()->addDays(20);
            $afterCutOffDate1                           =   Carbon::createFromFormat('Y-m-d', $leaveBalance->month)->startOfMonth()->addDays(21);
            $afterCutOffDate2                           =   Carbon::createFromFormat('Y-m-d', $leaveBalance->month)->endOfMonth();
        }
        $beforeCutOffLeaves                         =   Leave::where("user_id", $leaveBalance->user_id)->whereNotIn('status', ['Rejected', 'Cancelled'])->where(function ($subQuery) use ($beforeCutOffDate1, $beforeCutOffDate2) {
            $subQuery->where(function ($query1) use ($beforeCutOffDate1, $beforeCutOffDate2) {
                $query1->where('from_Date', '<=', $beforeCutOffDate1)->where('to_Date', '>=', $beforeCutOffDate1);
            })->orWhere(function ($query2) use ($beforeCutOffDate1, $beforeCutOffDate2) {
                $query2->whereBetween('from_Date', [$beforeCutOffDate1, $beforeCutOffDate2]);
            });
        })->select("from_date", "to_date", "user_id", "leave_session")->get();
        $afterCutOffLeaves                         =   Leave::where("user_id", $leaveBalance->user_id)->whereNotIn('status', ['Rejected', 'Cancelled'])->where(function ($subQuery) use ($afterCutOffDate1, $afterCutOffDate2) {
            $subQuery->where(function ($query1) use ($afterCutOffDate1, $afterCutOffDate2) {
                $query1->where('from_Date', '<=', $afterCutOffDate1)->where('to_Date', '>=', $afterCutOffDate1);
            })->orWhere(function ($query2) use ($afterCutOffDate1, $afterCutOffDate2) {
                $query2->whereBetween('from_Date', [$afterCutOffDate1, $afterCutOffDate2]);
            });
        })->select("from_date", "to_date", "user_id", "leave_session")->get();
        $data['beforeCutOffLeaves']                 =   $beforeCutOffLeaves->isEmpty() ? 0 : array_sum($beforeCutOffLeaves->pluck("duration")->toArray());
        $data['afterCutOffLeaves']                  =   $afterCutOffLeaves->isEmpty() ? 0 : array_sum($afterCutOffLeaves->pluck("duration")->toArray());
        $data['submitRoute']                        =   ['leaveBalanceUpdate', $id];
        $data['method']                             =   'POST';
        $data['leaveBalance']                       =   $leaveBalance;
        $data['userDepartments']                    =   User::with('employee.department')->where('is_active', '1')->where('user_type', 'Employee')
            ->select('id', 'name')->get()->groupBy('employee.department.name');
        $data['now']                                =   now();
        return view('leave.updateLeaveBalance', $data);
    }

    public function update(LeaveBalanceRequest $request, $id)
    {
        $leaveBalance = LeaveBalance::findOrFail($id);
        $this->updateLeaveBalance($leaveBalance, $request);
        $this->handleLeaveBalanceRaise($leaveBalance, "Sorted");
        return redirect(route('leaveBalanceDashboard'))->with('success', 'Success ');
    }

    public function leaveBalanceRaise(Request $request)
    {
        $leaveBalance = LeaveBalance::with('user')->findOrFail($request->leave_balance_id);
        $this->handleLeaveBalanceRaise($leaveBalance, $request->description);
        return redirect(route('myBalance'))->with('success', 'Response Submitted');
        // return redirect(route('leaveBalanceDashboard'))->with('success', 'Response Submitted');
    }

    private function updateLeaveBalance(LeaveBalance $leaveBalance, LeaveBalanceRequest $request)
    {
        $leaveBalance->balance = $request->balance;
        $leaveBalance->absent = $request->absent;
        $leaveBalance->deduction = $request->deduction;
        $leaveBalance->prev_month_deduction = $request->prev_month_deduction;
        $leaveBalance->next_month_deduction = $request->next_month_deduction;
        $leaveBalance->pre_approval_deduction = $request->pre_approval_deduction;
        $leaveBalance->update();
    }

    private function handleLeaveBalanceRaise(LeaveBalance $leaveBalance, $description)
    {
        $leaveBalanceComplaint = new LeaveBalanceComplaint();
        $leaveBalanceComplaint->leave_balance_id = $leaveBalance->id;
        $leaveBalanceComplaint->user_id = auth()->user()->id;
        $leaveBalanceComplaint->description = $description;
        $leaveBalanceComplaint->save();

        if ($leaveBalance->user_id != auth()->user()->id) {
            $leaveBalance->leaveBalanceComplaints()->update(['is_responded' => 1]);

            $email = $leaveBalance->user->email;
            $message = auth()->user()->name . " Responded on your leave balance query.";
            $emailData['message'] = $description;
            $emailData['link'] = route('myBalance');
            $message = (new Action($leaveBalance, $emailData, $message, 'email.action'))->onQueue('emails');
            $this->mailer->to($email)->later(Carbon::now()->addSeconds(30), $message);
        }
    }

    public function myBalance(Request $request)
    {
        $employees      =   Employee::withoutGlobalScopes()->with(['user' => function ($user) {
            $user->withoutGlobalScopes();
        }])->select('biometric_id', 'user_id', 'department_id', 'name')->whereHas('user', function ($user) {
            $user->withoutGlobalScopes()->where('user_type', 'Employee');
        })->get()->groupBy('department.name');


        return view('leave.myBalance', compact('employees'));
    }

    public function getBalance(Request $request)
    {
        if ($request->ajax()) {
            if (request()->filled('user_id') && auth()->user()->can('hrEmployeeList', new Employee())) {
                $user_id =  $request->user_id;
            } else {
                $user_id =  auth()->user()->id;
            }

            $currentMonth = now()->startOfMonth();
            $nextMonth = now()->addMonth()->startOfMonth();

            $user =  User::withoutGlobalScopes()->find($user_id);
            $getOffDays = explode(',', $user->off_day);
            $leaves =  Leave::where('user_id', $user_id)->whereIn('status', ['Pending', 'Approved', 'Pre Approved', 'Forwarded', 'Rejected', 'Absent'])->get();
            $leaveData =  Leave::where('user_id', $user_id)->whereIn('status', ['Pending', 'Approved', 'Pre Approved', 'Forwarded', 'Rejected', 'Absent'])->whereBetween('leaves.from_date', [$currentMonth, $nextMonth])->get();

            $approvedLeaveTypesData = [];
            $pendingLeaveTypesData = [];

            $employeeLeaves = [];

            foreach ($leaveData as $leave) {
                $leaveTypeName = optional($leave->leaveType)->name ?? 'Unknown';
                $period = CarbonPeriod::create($leave->from_date, $leave->to_date);
                $leaveCount = 0;
    
                foreach ($period as $date) {
                    if (!in_array($date->format('l'), $getOffDays)) {
                        $leaveCount += $leave->leave_session == 'Full day' ? 1 : 0.5;
                    }
                }
    
                if ($leave->status == 'Approved' || $leave->status == 'Pre Approved') {
                    if (!isset($approvedLeaveTypesData[$leaveTypeName])) {
                        $approvedLeaveTypesData[$leaveTypeName] = 0;
                    }
                    $approvedLeaveTypesData[$leaveTypeName] += $leaveCount;
                } elseif ($leave->status == 'Pending') {
                    if (!isset($pendingLeaveTypesData[$leaveTypeName])) {
                        $pendingLeaveTypesData[$leaveTypeName] = 0;
                    }
                    $pendingLeaveTypesData[$leaveTypeName] += $leaveCount;
                }

                $employeeLeaves  =   [];
                if ($leaves->isNotEmpty()) {
                    foreach ($leaves as $leave) {
                        if ($leave->status == 'Pre Approved') {
                            $color = "#a8d9a8";
                        } elseif ($leave->status == 'Approved') {
                            $color = "#ffcbcb";
                        } elseif ($leave->status == 'Forwarded') {
                            $color = "rgba(109,358,331,0.27)";
                        } elseif ($leave->status == 'Pending') {
                            $color = "#fced6deb";
                        } elseif ($leave->status == 'Absent') {
                            $color = "#43464936";
                        } elseif ($leave->status == 'Rejected') {
                            $color = "#f50823";
                        }
                        $employeeLeaves[] = [
                            'title'         => ($leave->leave_session == 'Full day') ? 'Full day' : 'Half day',
                            'start'         =>   $leave->from_date,
                            'end'           =>   Carbon::parse($leave->to_date)->addDay()->format('Y-m-d'),
                            'color'         =>   $color,
                            'url'           =>   route('leaveList'),
                            'description'   =>   '',
                            'status'        =>    $leave->status,
                            'type'          =>    $leave->leave_session,
                        ];
                    }
                }
            }

            // dd($approvedLeaveTypesData);

            $data['user'] = $user;
            $data['employeeLeaves'] = $employeeLeaves;
            $data['route'] = route('createLeave');
            $month = empty($request->month) ? now() : Carbon::createFromFormat('Y-m', $request->month);
            $myBalance = LeaveBalance::with(['user' => function ($u) {
                $u->withoutGlobalScopes();
            }])->where('user_id', $user_id)->whereYear('month', $month)->whereMonth('month', $month)->first();

            $leaveTypes = LeaveType::where('name', '<>', 'Manual')->pluck('name', 'id')->toArray();

            if (!empty($myBalance)) {
                $data['approvedLeaveTypesData'] = $approvedLeaveTypesData;
                $data['pendingLeaveTypesData'] = $pendingLeaveTypesData;

                $data['balanceChart'] = view('leave.balance', compact('myBalance', 'leaveTypes', 'approvedLeaveTypesData', 'pendingLeaveTypesData'))->render();
            }

            $model = new Leave();
            $submitRoute = 'submitLeave';

            $leaveBalance = LeaveBalance::whereMonth('month', Carbon::today())->whereYear('month', today())->where('user_id', $user_id)
                ->first();
            $balance = !empty($leaveBalance) ? $leaveBalance->balance : 0;
            $today = Carbon::today()->format('Y-m-d');
            $max = Carbon::now()->startOfMonth()->addMonth()->endOfMonth()->format('Y-m-d');
            $leaveNature = $model->getLeaveSession();
            $data['leaveForm'] = view('leave.calendarLeaveForm', compact('model', 'submitRoute', 'leaveTypes', 'balance', 'today', 'max', 'leaveNature'))->render();
            return $data;
        }
    }


    public function export(Request $request)
    {
        $this->authorize('hrUpdateEmployee', new Employee());
        $leaveBalances   =   $this->dashboard($request, true);
        return Excel::download(new LeaveBalanceExport($leaveBalances), 'leaveBalance.xlsx');
    }

    public function getLeaveBalance(Request $request)
    {
        $user_id = auth()->user()->id; 
        $month = date('m'); 
        // Retrieve leave balance for the current month
        $leaveBalance = LeaveBalance::where('user_id', $user_id)
            ->whereYear('month', now()->year)
            ->whereMonth('month', now()->month)
            ->first();

        if (!$leaveBalance) {
            // If leave balance for the current month is not found, log the error
            logger()->error('Leave balance not found for user ' . $user_id . ' in ' . now()->format('F Y'));

            // Return a response with an error message
            return response()->json(['error' => 'Leave balance not found for the current month.'], 404);
        }

        // Dump and die to check the utilizable_balance
        //dd($leaveBalance->utilizable_balance);

        // Respond with the leave balance and utilizable_balance
        return response()->json([
            'maxLeave' => $leaveBalance->balance,
            'utilizableBalance' => $leaveBalance->utilizable_balance,
        ]);
    }
}
