<?php

namespace App\Http\Controllers\ems;

use App\User;
use App\Models\NewLeave;
use Carbon\CarbonPeriod;
use App\Models\LeaveTypes;
use App\Models\NewHoliday;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Models\NewLeaveBalance;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use League\CommonMark\Reference\Reference;

class LeaveDashboardController extends Controller
{
    public $month;
    public $year;
    protected $references;

    public function __construct()
    {
        $this->month    = Carbon::today()->format('m');
        $this->year     = Carbon::today()->format('Y');
        // $this->references = Reference::where('entity_name', 'Attendance')->get();
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // $this->authorize('view',new NewLeave());
        $currentUser    = Auth::user();
        $invalid        = false;

        if ($request->filled('user_id')) {
            $user_id = $request->user_id;
        }elseif ($request->filled('emp_id')) {
            $user = User::whereHas('employee', function($employee) use($request){
                $employee->where('emp_id', $request->emp_id);
            })->first();

            $user_id = $user->id ?? null;
            if(empty($user_id)){
                $invalid = true;
            }
        }

        if (empty($user_id)) {
            $user_id = $currentUser->id;
        }


        $user                        =   User::with('employee')->find($user_id);

        if ($currentUser->can('viewDetailCalendar', auth()->user())) {
        // if ($user->hasRole('HR Admin') || $user->hasRole('Leave Admin')) {
            $data['users']      =   User::whereHas('employee', function ($employee) {
                $employee->whereIn('branch', ['Bengaluru', 'Delhi', 'Chandigarh']);
            })->pluck('name', 'id')->toArray();
        }elseif ($currentUser->hasRole('Employee View - BLR')) {
            $data['users']      =   User::whereHas('employee', function($employee) use($request){

                    $employee->where('branch', 'Bengaluru');
                    $employee->whereNotIn('on_board_status', ['Pending with Recruiter', 'Pending for Salary Negotiation', 'Pending with HR', 'Waiting on employee', 'Rejected' ]);
                })->pluck('name', 'id')->toArray();
        }


        $data['user'] = $user;

        if ($invalid) {
            return redirect()->route('leave.dashboard')->with('failure', 'Invalid Employee ID');
        }
        return view('Leaves.LeaveDashboard.dashboard', $data);

    }

    public function getCalendarHolidays()
    {
        $holidays       = NewHoliday::where('type', '<>', 'Optional Holiday')->where('is_active', 1)->get();
        $holidaysArray  = [];
        foreach ($holidays as $holiday) {
            $holidaysArray[] = [
                'title'         => $holiday->title,
                'start'         => $holiday->date,
                'end'           => Carbon::parse($holiday->date)->addDay()->format('Y-m-d'),
                'color'         => '#4B49AC',
                'url'           => '',
                'description'   => ''
            ];
        }

        return $holidaysArray;
    }

    public function getColumnByName($name, $column)
    {
        $reference = $this->references->where('name', $name)->first();
        if (!empty($reference)) {
            return $reference->{$column};
        } else {
            return null;
        }

    }

    public function getEmployeeLeaves($userId)
    {
        $leaves                         =   NewLeave::with("leaveType")->where(['user_id' => $userId])->whereIn('status', ['Approved', 'Pending'])->get();
        $employeeLeaves                 =   [];
        if ($leaves->isNotEmpty()) {
            foreach ($leaves as $leave) {
                $shortName  =   empty($leave->leaveType->short_name) ? $leave->leaveType->name : $leave->leaveType->short_name;
                $color = $leave->leaveType->color_code;

                $employeeLeaves[] = [
                    'title'         => $leave->leave_nature . ' (' . $shortName . ')',
                    'start'         => $leave->from_date,
                    'end'           => Carbon::parse($leave->to_date)->addDay()->format('Y-m-d'),
                    'color'         => $color,
                    'textColor'     => 'white',
                    'url'           => '',
                    'description'   => $leave->reason,
                    'status'        =>  $leave->status

                ];
            }
        }

        return $employeeLeaves;
    }

    public function dashboard(Request $request)
    {
        $currentUser    = Auth::user();
        $invalid        = false;

        if ($request->filled('user_id')) {
            $user_id = $request->user_id;
        }elseif ($request->filled('emp_id')) {
            $user = User::whereHas('employee', function($employee) use($request){
                $employee->where('emp_id', $request->emp_id);
            })->first();

            $user_id = $user->id ?? null;
            if(empty($user_id)){
                $invalid = true;
            }
        }

        if (empty($user_id)) {
            $user_id = $currentUser->id;
        }

        $data                        =   [];
        $FilterDate                  =   (empty($request->date) ? now() : now()->createFromFormat('Y-m-d', $request->date));
        $user                        =   User::with('attendance')->find($user_id);
        $presentUser                 =   User::with(['attendance' => function ($attendance) {
                                            $attendance->where('status', 'Present');
                                        }])->find($user_id);

        if ($request->ajax()) {

            $employeeAttendances            =   [];
            $punchInEmployeeAttendances     =   [];
            $punchOutEmployeeAttendances    =   [];
            $holidaysArray                  =   $this->getCalendarHolidays();

            foreach ($user->attendance as $attendance) {

                if($attendance->status =="Present" && $attendance->entry_type != 'Manual'){continue;}

                $title = $attendance->status;
                if ($attendance->nature == 'Half Day') {
                    $title = $title.' - HD';
                }

                $bgColor = $this->getColumnByName($attendance->status, 'color_code');

                $employeeAttendances[$attendance->date] = [
                    'title'         => $title,
                    'start'         => $attendance->date,
                    'end'           => Carbon::parse($attendance->date)->addDay()->format('Y-m-d'),
                    'color'         => $bgColor,
                    'textColor'     => 'white',
                    'url'           => '',
                    'description'   => $attendance->remarks
                ];
            }

            foreach ($presentUser->attendance  as $punchInAttendance) {

                if ($punchInAttendance->status == "Present" && !empty($punchInAttendance->punch_in)) {

                    $title    = getFormattedTime($punchInAttendance->punch_in, 'h:iA');

                    $punchInEmployeeAttendances[$punchInAttendance->date] = [
                        'title'         => 'In: '.$title,
                        'start'         => $punchInAttendance->date,
                        'end'           => Carbon::parse($punchInAttendance->date)->addDay()->format('Y-m-d'),
                        'color'         => 'white',
                        'textColor'     => 'green',
                        'url'           => '',
                        'description'   => ''
                    ];
                }
            }

            foreach ($presentUser->attendance  as $punchOutAttendance) {

                if ($punchOutAttendance->status == "Present" && !empty($punchOutAttendance->punch_out)) {
                    $title    = getFormattedTime($punchOutAttendance->punch_out, 'h:iA');

                    $punchOutEmployeeAttendances[$punchOutAttendance->date] = [
                        'title'         => 'Out: '.$title,
                        'start'         => $punchOutAttendance->date,
                        'end'           => Carbon::parse($punchOutAttendance->date)->addDay()->format('Y-m-d'),
                        'color'         => 'white',
                        'textColor'     => 'red',
                        'url'           => '',
                        'description'   => ''
                    ];
                }
            }

            $employeeLeaves                 =   $this->getEmployeeLeaves($user->id);


            $startDate  = Carbon::parse('2021-08-01');
            $endDate    = Carbon::today()->endOfMonth();

            $dateRange      = CarbonPeriod::create($startDate, $endDate)->toArray();
            $offDays        =   [];

            /**
             * New Code On the basis of employee working day
             */
            if (!empty($user->employee)) {


                $empOffDays = $user->employee->getOffDays();

                if (empty($empOffDays)) {
                    $empOffDays = array_map('trim', explode('-', $user->employee->region->off_day));
                }
            }else{
                $empOffDays =  [];
            }

            /**
             * End Code of employee working day
             */

            // $regionOffDays  =   ['Sat', 'Sun'];
            // if (!empty($user->employee->region->off_day)) {
            //     $regionOffDays  =   array_map('trim', explode('-', $user->employee->region->off_day));
            // }



            foreach ($dateRange as $date) {
                // if (!in_array($date->format('D'), $regionOffDays) || array_key_exists($date->format('Y-m-d'), $employeeAttendances)) {
                if (!in_array($date->format('l'), $empOffDays) || array_key_exists($date->format('Y-m-d'), $employeeAttendances)) {
                    continue;
                }

                $offDays[$date->format('Y-m-d')] = [
                    'title' => 'Off Day',
                    'start' => $date->format('Y-m-d'),
                    'end' => $date->addDay()->format('Y-m-d'),
                    'color' => 'Black',
                    'textColor' => 'White',
                    'url' => '',
                    'description' => ''
                ];
            }
            $employeeAttendances             =   array_merge(array_values($employeeAttendances), $holidaysArray);
            $employeeAttendances             =   array_merge($employeeAttendances, array_values($offDays));
            $employeeAttendances             =   array_merge(array_values($employeeAttendances), array_values($employeeLeaves));
            $employeeAttendances             =  array_merge(array_values($employeeAttendances), array_values($punchInEmployeeAttendances));
            $employeeAttendances             =  array_merge(array_values($employeeAttendances), array_values($punchOutEmployeeAttendances));
            $data['employeeLeaves']          =   json_encode($employeeAttendances);

            $data['user']               =   $user;
            $balanceChart               =   $this->balanceChart($user, $FilterDate);
            $totalBalance               =   $this->totalBalanceChart($user, $FilterDate);
            $data['balanceChart']       =   view('Leaves.LeaveDashboard.balanceChart', compact('balanceChart', 'totalBalance'))->render();
            return $data;
        }


    }
    public function totalBalanceChart($user, $date)
    {
        $currentMonth           = today()->endOfMonth()->toDateString();
        $leaveBalance           = NewLeaveBalance::where('user_id', $user->id)->whereYear('month', $date)->whereDate('month', '<=', $currentMonth)->get();
        $allLeaveBalance        = NewLeaveBalance::where('user_id', $user->id)->whereDate('month', '<=', $currentMonth)->get();
        $leaveTypes             = LeaveTypes::where('is_active', 1)->where('balance', 1)->get();
        $totalArray     = [];

        foreach ($leaveTypes as $leaveType) {
            $leaveTypeBalance       =   $leaveBalance->where('leave_type_id', $leaveType->id);
            $allLeaveTypeBalance    =   $allLeaveBalance->where('leave_type_id', $leaveType->id);

            if ($leaveType->yearly_expiry == 1) {

                if($leaveType->allowance_type == 'yearly'){
                    $allowance          =   $leaveType->allowance;
                    $taken              =   $leaveTypeBalance->last()->taken_leaves ?? 0;
                }else{
                    $allowance          =   $leaveTypeBalance->sum('allowance');
                    $taken              =   $leaveTypeBalance->sum('taken_leaves');
                }

                $expiry                 =   'Yearly';

            }else{
                $allowance          =   $allLeaveTypeBalance->sum('allowance');
                $taken              =   $allLeaveTypeBalance->sum('taken_leaves');
                $expiry             =   'All Time';
            }

            $totalArray[$leaveType->name]        = [
                'allowance' => $allowance,
                'taken'     => $taken,
                'balance'   => $allowance - $taken,
                'expiry'    => $expiry,
                'user_id'   => $user->id,
                'date'      => $date->format('Y-m-d'),
            ];
        }

        return $totalArray;
    }

    public function balanceChart($user, $date)
    {


        $leaveBalance = NewLeaveBalance::where('user_id', $user->id)->whereMonth('month', $date)->whereYear('month', $date)->get();

        $leaveTypes = LeaveTypes::where('is_active', 1)->where('balance', 1)->get();

        $typesArray = [];
        foreach ($leaveTypes as $type) {
            $leaveTypeBalance               = $leaveBalance->where('leave_type_id', $type->id)->first();
            $dataArray['id']                = $type->id;
            $dataArray['allowance']         = $leaveTypeBalance->allowance ?? 0;
            $dataArray['previous_balance']  = $leaveTypeBalance->previous_balance ?? 0;
            $dataArray['taken_leaves']      = $leaveTypeBalance->taken_leaves ?? 0;
            $dataArray['final_balance']     = $leaveTypeBalance->final_balance ?? 0;
            $dataArray['waiting']           = $leaveTypeBalance->waiting ?? 0;
            $dataArray['date']              = $date;
            $dataArray['user_id']           = $user->id;

            $typesArray[$type->name]        = $dataArray;
        }



        return $typesArray;
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
