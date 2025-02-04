<?php

namespace App\Http\Controllers\ems;

use App\User;
use DateTime;
use Carbon\Carbon;
use App\Mail\Action;
use App\Models\Role;
use App\Models\Leave;
use App\Models\Employee;
use App\Mail\LeaveAction;
use App\Models\LeaveType;
use App\Models\Department;
use App\Mail\LeaveApproved;
use Illuminate\Support\Arr;
use App\Exports\LeaveExport;
use App\Mail\LeaveCancelled;
use App\Mail\LeaveRejection;
use App\Models\LeaveBalance;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Collection;
use App\Http\Requests\LeaveRequest;
use App\Http\Controllers\Controller;
use App\Models\LeaveDeduction;
use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Contracts\Mail\Mailer;
use App\Models\NewLeaveApprovalStatus;

class LeaveController extends Controller
{
    public function __construct(Mailer $mailer)
    {
        $this->mailer       =       $mailer;
    }

    public function managerLeaveList(Request $request)
    {
        
        $this->authorize('managerLeaveList', new Leave());

        // abort_if(auth()->user()->employee->managerDepartments->isEmpty() && !auth()->user()->hasRole('admin'), 403);
        if (auth()->user()->hasRole('Line Manager')) {
            
            $departmentIds = Department::where('line_manager_id', auth()->user()->id)->pluck('id', 'id')->toArray();
        }else{

            $departmentIds  =   auth()->user()->employee->managerDepartments->pluck('id', 'id')->toArray();
        }


        $leaves         =   Leave::with('user.employee.department', 'leaveType')->where(function ($query) {
                            $query->where(function ($subQuery) {
                                $subQuery->where('is_approved', '1')->where(function ($subQuery2) {
                                    $subQuery2->where(function ($subQuery3) {
                                        $subQuery3->whereDate('to_date', '>=', today())->whereDate('from_date', '<=', today());
                                    })->orWhere(function ($subQuery4) {
                                        $subQuery4->whereDate('to_date', '>=', today())->whereDate('from_date', '>=', today());
                                    });
                                });
                                })->orWhereIn('status', ['Pending', 'Auto Forwarded', 'Forwarded']);
                                })->whereNotIn('status', ['Cancelled', 'Rejected'])
                                ->where('user_id', '<>', auth()->user()->id);
        $leaves         =   $this->leaveSearch($request, $leaves);
        if (empty($departmentIds))
        {
            $leaves              =   $leaves->whereHas('user.employee', function ($query) {
                                    $query->where('department_id', auth()->user()->employee->department_id);
                                    });
        } else
        {
            $leaves             =   $leaves->whereHas('user.employee', function ($query) use ($departmentIds) {
                                    $query->whereIn('department_id', $departmentIds);
                                    });
        }
        $data['leaves']         =   $leaves->orderBy('from_date', 'desc')->get();
        $data['submitRoute']    =   'leaveAlter';
        $data['departmentIds']  =   $departmentIds;
        $data['today']          =   today()->format('Y-m-d');
        return view('manager.leaves', $data);
    }

    public function hrLeaveList(Request $request)
    {
        $this->authorize('hrEmployeeList', new Employee());
        $data['department_id']              =   Department::pluck('name', 'id')->toArray();
        $data['leaveTypes']                 =   LeaveType::pluck('name', 'id')->toArray();
        $data['employeeDepartments']        =   User::with('employee.department')->where('is_active', 1)->where('user_type', 'Employee')->whereHas('employee', function ($employee) {
                                                $employee->select('biometric_id', 'name');
                                                })->get()->groupBy('employee.department.name');
        $leaves                             =   Leave::has('user.employee.department')->with('user.employee.department')
                                                // ->whereDate('to_date', '>=', Carbon::today())
                                                ->where('forwarded', '1')->whereNotIn('status', ['Cancelled', 'Rejected']);
        $leaves                             =   $this->leaveSearch($request, $leaves);
        if (request()->has('leave_id'))
        {
            $leaves      = $leaves->where('id', $request->leave_id);
        }
        if (request()->has('leave_session'))
        {
            $leaves      = $leaves->where('leave_session', $request->leave_session);
        }
        if (request()->has('department_id'))
        {
            $leaves = $leaves->whereHas('user.employee', function ($query) {
                $query->where('department_id', request()->department_id);
            });
        }
        if (request()->has('leave_type_id'))
        {
            $leaves = $leaves->where('leave_type_id', request()->leave_type_id);
        }
        if (request()->has('user_id'))
        {
            $leaves = $leaves->where('user_id', request()->user_id);
        }
        if (request()->has('is_pending'))
        {
            $leaves = $leaves->whereNull('is_approved');
        }
        $data['leaves']         = $leaves->orderByRaw("Field(status,'Forwarded','Auto Forwarded','Approved')")->paginate(10);
        $data['submitRoute']    = 'leaveAlter';
        $data['today']          = today()->format('Y-m-d');
        $data['leaveSessions']  = Leave::pluck('leave_session', 'leave_session')->toArray();
        return view('hr.forwardedLeaves', $data);
    }

    public function  managerLeave(Request $request)
    {
        $this->authorize('hrEmployeeList', new Employee());
        $manager_ids            = User::whereHas('employee.managerDepartments')->pluck('id', 'id')->toArray();
        $leaves                 = Leave::whereDate('to_date', '>=', today())->whereIn('user_id', $manager_ids)->whereNotIn('status', ['Cancelled', 'Rejected']);
        $leaves                 = $this->leaveSearch($request, $leaves);
        $data['leaves']         = $leaves->orderBy('from_date', 'desc')->paginate(8);
        $data['submitRoute']    = 'leaveAlter';
        $data['today']          = Carbon::today()->format('Y-m-d');
        $data['leaveSessions']  = Leave::pluck('leave_session', 'leave_session')->toArray();
        return view('hr.leaves', $data);
    }

    public function leaveList(Request $request)
    {
        $leaves                 =    Leave::with('user')->where('user_id', auth()->user()->id);
        $leaves                 =    $this->leaveSearch($request, $leaves)->where('user_id', auth()->user()->id);
        $data['leaves']         =    $leaves->orderBy('created_at', 'desc')->get();
        return view('leave.leaves', $data);
    }

    public function leaveForm()
    {
        $data['model']              =        new Leave();
        $data['submitRoute']        =        'submitLeave';
        $data['leaveTypes']         =        LeaveType::where('name', '<>', 'Manual')->pluck('name', 'id')->toArray();
        $leaveBalance               =        LeaveBalance::whereMonth('month', Carbon::today())->whereYear('month', today())->where('user_id', auth()->user()->id)
            ->first();
        $data['balance']            =        !empty($leaveBalance) ? $leaveBalance->balance : 0;
        $data['today']              =        Carbon::today()->format('Y-m-d');
        $data['max']                =        Carbon::now()->startOfMonth()->addMonth()->endOfMonth()->format('Y-m-d');
        $data['leaveNature']        =        $data['model']->getLeaveSession();
        return view('leave.leaveForm', $data);
    }

    public function insert(LeaveRequest $request)
    {
        $shiftType          =   auth()->user()->shiftType;
        if (empty($shiftType))
        {
            return back()->with('failure', 'Please contact to administrator to add your shift first.');
        }
        $leaveSession       =   'Full day';
        $timings            = [$shiftType->start_time . '-' . $shiftType->end_time => $shiftType->start_time . '-' . $shiftType->end_time];
        if ($request->leave_session == 'Half day')
        {
            $leaveSession   =   $request->halfDayType; // first half or second half
            if ($request->halfDayType == 'First half')
            {
                $timings += [$shiftType->start_time . '-' . $shiftType->mid_time => $shiftType->start_time . '-' . $shiftType->mid_time];
            } else
            {
                $timings += [$shiftType->mid_time . '-' . $shiftType->end_time => $shiftType->mid_time . '-' . $shiftType->end_time];
            }
        }
        $leaveExists = $this->leaveExists($request, $leaveSession, auth()->user()->id);
        if ($leaveExists->exists())
        {
            return back()->with('failure', 'Leave already Exists');
        }
        $end    = Carbon::createFromTimeString('14:00', 'Asia/Kolkata')->format('H:i:s');
        $now    = Carbon::now()->format('H:i:s');
        if ($request->from_date == Carbon::today()->format('Y-m-d') && $now > $end)
        {
            if ($request->leave_session == 'Full day' || $request->halfDayType == 'First half') {
                return back()->with('failure', 'You can not apply ' . $request->leave_session . ' leave now.');
            }
        }

        
        $leave                  = new Leave();
        $leave->user_id         = auth()->user()->id;
        $leave->leave_session   = $leaveSession;
        $leave->leave_type_id   = $request->leave_type;
        $leave->from_date       = $request->from_date;
        $leave->to_date         = $request->to_date;
        if ($request->has('attachment'))
        {
            $file               = 'leaveFile' . Carbon::now()->timestamp . '.' . $request->file('attachment')->getClientOriginalExtension();
            $request->file('attachment')->move(storage_path('app/documents/leave_documents'), $file);
            $leave->attachment  = $file;
        }
        $leave->reason          = $request->reason;
        $leave->save();
        $carbonDate             =   Carbon::createFromFormat('Y-m-d', $leave->from_date);
        $duration               =   $leave->duration;
        $appliedAt              =   Carbon::createFromFormat('Y-m-d H:i:s', $leave->created_at)->format('Y-m-d');
        $cutOffDate             =   Carbon::now()->endOfMonth();
        if ($leave->from_date == Carbon::createFromFormat('Y-m-d H:i:s', $leave->created_at)->format('Y-m-d')  && $leave->duration > 1)
        {
            $from_date                =   Carbon::parse($leave->from_date)->addDays(1)->format('Y-m-d');
            $newLeave                 =   new Leave();
            $newLeave->leave_session  =   $leave->leave_session;
            $newLeave->leave_type_id  =   $leave->leave_type_id;
            $newLeave->user_id        =   $leave->user_id;
            $newLeave->action_by      =   $leave->action_by;
            $newLeave->is_approved    =   $leave->is_approved;
            $newLeave->forwarded      =   $leave->forwarded;
            $newLeave->remarks        =   $leave->remarks;
            $newLeave->attachment     =   $leave->attachment;
            $newLeave->reason         =   $leave->reason;
            $newLeave->from_date      =   $from_date;
            $newLeave->to_date        =   $leave->to_date;
            $newLeave->save();
            $leave->to_date          =  $leave->from_date;
            $leave->save();
            

            $preDuration = $newLeave->duration;
            $appDuration = $leave->duration;

            // dd($from_date,$leave->to_date,$leaveSession, $appDuration, $preDuration);
            $this->approvalDeduction($leave, $carbonDate, $appDuration, $appliedAt, $cutOffDate);
            $this->preApprovalBalance($newLeave, $carbonDate, $preDuration, $appliedAt, $cutOffDate);
        } elseif ($leave->from_date == Carbon::createFromFormat('Y-m-d H:i:s', $leave->created_at)->format('Y-m-d'))
        {
            $this->approvalDeduction($leave, $carbonDate, $duration, $appliedAt, $cutOffDate);
        } else
        {
            $this->preApprovalBalance($leave, $carbonDate, $duration, $appliedAt, $cutOffDate);
        }

        $user = auth()->user();
        $approvalCount = $this->approvalCount($user);

        $arr = ['first_approval', 'second_approval', 'third_approval', 'fourth_approval'];

        $approvalStages = $this->approvalStages(auth()->user());

        for ($i = 0; $i < $approvalCount; $i++) {

            $stage                      =  $arr[$i];

            $stageRoleName  = $this->getRoleNameByApprovalStage($approvalStages, $stage);
            $relation       = $this->getRelationNameByRole($stageRoleName);

            $allowStage = false;

            if ($stageRoleName == 'Leave Admin') {
                $allowStage = true;
            } elseif (!empty($user->employee->$relation)) {
                $allowStage = true;
            }

            if ($allowStage) {
                $approvalStatus             =   new NewLeaveApprovalStatus();
                $approvalStatus->leave_id   =   $leave->id;
                $approvalStatus->action     =   "Pending";
                $approvalStatus->stage      =   $arr[$i];
                $approvalStatus->save();
            }
        }
        $responsibleUsers               =   $leave->responsiblePerson();
        $leave->leave_responsible_id    =    $responsibleUsers['leaveResponsible'];


        $leave->save();
        $leave                          =   $leave->load('user');
        if ($responsibleUsers['approval_role'] == 'Leave Admin') {
            $responsibleUsers           =       User::whereHas('roles', function ($role) {
                $role->where('name', 'Leave Admin');
            })->get();
        } else {
            $responsibleUsers          =      $responsibleUsers['approval_user'];
        }


        if ($responsibleUsers  instanceof Collection) {


            $responsibleUsers = $responsibleUsers->pluck('email')->toArray();
        } else {
            $responsibleUsers = [$responsibleUsers->email ?? null];
        }

        if (!empty($responsibleUsers)) {
            $data['to']  = $responsibleUsers;
            $data['object'] = $leave;

            foreach ($responsibleUsers as $userEmail) {
                $message = (new LeaveAction($leave, $user))->onQueue('emails');
                Mail::to($userEmail)->later(now()->addSeconds(1), $message);
            }
        }
        return redirect(route('leaveList'))->with('success', 'Leave applied');
    }

    public function leaveRequest(Request $request, $export = false) // Request list
    {
        // $this->authorize("approval", new Leave());
        $currentUser = auth()->user();

        // if ($currentUser->cannot('viewRequestList', new Leave()) && $currentUser->cannot('approval', new Leave())) {
        //     abort(403);
        // }

        $data['status']                 =   config('employee.leave_status');
        $data['leaveTypes']             =   LeaveType::pluck('name', 'id')->toArray();

      
       // if($currentUser->hasRole('Leave Admin')) {
        if ($currentUser->hasRole('HR')   || $currentUser->hasRole(' Leave Admin')|| $currentUser->hasRole('Admin')) {
            $users      = User::whereHas("roles", function ($roles) {
                $roles->whereIn("name", ["Employee"]);
            })->pluck("name", "id")->toArray();
            // dd($users);
        }elseif ($currentUser->hasRole('Employee')) {
            $users      = User::whereHas('employee', function ($employees) use ($currentUser) {
                $employees->where('manager_id', $currentUser->id)->orWhere('team_leader_id', $currentUser->id);
            })->pluck('name', 'id')->toArray();
        }

        $queryUsers             =   array_flip($users);
        $leaves                 =   Leave::with('approvalStatus.leave.leaveResponsible')->whereIn('user_id', $queryUsers)->where('user_id', '<>', auth()->user()->id);


        $leaves                 =   $this->filter($request, $leaves);

        if (request()->filled('waiting')) {
            $ids = $this->getWaitingIds();

            $leaves = $leaves->whereIn('id', $ids);
        }

        if ($export) return $leaves->get();

        $data['leaves']         =   $leaves->latest()->paginate(10);
        $data['pendingLeaves']  =   Leave::with('approvalStatus.leave.leaveResponsible')->whereIn('user_id', $queryUsers)->where('user_id', '<>', auth()->user()->id)->where('status', 'Pending')->count('id');
        $data['users']          =   $users;
        $data['managers']       =   User::havingRole(['Manager'],'name');

        return view('leave.leave-requst', $data);
    }

     public function getRoleNameByApprovalStage($approvalStages, $stage)
    {
        switch ($stage) {
            case 'first_approval':
                $roleName = $approvalStages->firstApproval->name;
                break;
            case 'second_approval':
                $roleName = $approvalStages->secondApproval->name;
                break;
            case 'third_approval':
                $roleName = $approvalStages->thirdApproval->name;
                break;
            case 'fourth_approval':
                $roleName = $approvalStages->fourthApproval->name;
                break;
        }

        return $roleName ?? null;
    }


    public function getRelationNameByRole($roleName)
    {
        switch ($roleName) {
            case 'Team Leader':
                $relation = 'teamLeader';
                break;
            case 'Manager':
                $relation = 'manager';
                break;
            case 'Line Manager':
                $relation = 'lineManager';
                break;
            case 'Employee':
                $relation = 'employee';
                break;
        }

        return $relation ?? null;
    }

    public function approvalStages($user)
    {
        $roles = $user->roles;

        if ($roles->whereIn('name', ['Team Leader', 'team leader'])->count()) {
            $roleName      = 'Team Leader';
        } elseif ($roles->whereIn('name', ['Manager', 'manager'])->count()) {
            $roleName      = 'Manager';
        }elseif ($roles->whereIn('name', ['Line Manager', 'line manager'])->count()) {
            $roleName      = 'Manager';
        } 
        elseif ($roles->whereIn('name', ['Employee', 'employee'])->count()) {
            $roleName      = 'Employee';
        } 

        return Role::whereName($roleName)->first()->leaveResponsible ?? null;
    }
    public function approvalCount($user)
    {

        $approval_count = 0;
        if ($user->hasRole('Team Leader')) {
            $role = Role::where('name', 'Team Leader')->first();
            $approval_count = $role->leaveResponsible->approval_count;
        }elseif ($user->hasRole('Manager')) {
            $role = Role::where('name', 'Manager')->first();
            $approval_count = $role->leaveResponsible->approval_count;
        }elseif ($user->hasRole('Line Manager')) {
            $role = Role::where('name', 'Line Manager')->first();
            $approval_count = $role->leaveResponsible->approval_count;
        }  
        elseif ($user->hasRole('Employee')) {
            $role = Role::where('name', 'Employee')->first();
            $approval_count = $role->leaveResponsible->approval_count;
        }
        return $approval_count;
    }

    function autoForwardedLeaveNotification($manager,$hr,$leave)
    {
        $remarks        = "I'm on leave today";
        $leave->update(['forwarded' => '1', 'action_by' => $manager->user_id, 'remarks' => $remarks, 'status' => 'Auto Forwarded']);
        $notificationReceivers  =    $hr->pluck('id', 'id')->toArray();
        $email                  =    $hr->pluck('email')->toArray();
        $link                   = route('hrLeaveList');
        send_notification($notificationReceivers, 'Leave forwarded by ' .  $manager->name, $link, 'leave');
        $data['leave']          = $leave;
        $data['link']           = $link;
        $message                = "Leave Forwarded of " . $leave->user->name;
        $subject                = 'Leave Forwarded by ' . $manager->name;
        send_email("email.leave", $data, $subject, $message, $email, null);
    }

    public function leaveAction(Request $request)
    {
        $leave = Leave::find($request->leave_id);
        $action_by                  =   NewLeaveApprovalStatus::where('leave_id', $request->leave_id)->where('action', 'Approved')->pluck('action_by')->toArray();

        // New Code to check approver
        $currentUser = auth()->user();
        $responsibleArray = $leave->responsiblePerson();
        $canApprove = false;
        if ($currentUser->hasRole($responsibleArray['approval_role'])  &&  !empty($responsibleArray['approval_user']->email) && $responsibleArray['approval_user']->email == $currentUser->email && $leave->canApprove()) {
            $canApprove = true;
        } elseif ($currentUser->hasRole('Leave Admin') && $responsibleArray['approval_role'] == "Leave Admin") {
            $canApprove = true;
        } elseif ($currentUser->hasRole('Manager') && $responsibleArray['approval_role'] == "Manager" && $leave->canApprove()) {
            $canApprove = true;
        }
        // end new code to check approver

        if ($canApprove === false || $leave->status == 'Rejected') {
            abort(403, 'Unauthorised Action');
        }


        $approval_status            =   NewLeaveApprovalStatus::where('leave_id', $request->leave_id)->where('action', 'Pending')->orderBy('id')->first();
        $approval_status->action    =   $request->action;
        $approval_status->remarks   =   !empty($request->remarks) ? $request->remarks : null;
        $approval_status->action_by =   auth()->user()->id;
        $approval_status->save();
        // $month                          =   Carbon::parse($leave->to_date)->format('m');
        // $year                           =   Carbon::parse($leave->to_date)->format('Y');
        // $leaveBalance                   =   $leave->leaveType->leaveBalances()->where('user_id', $leave->user_id)->whereYear('month', $year)->whereMonth('month', $month)->first();


        $users          = User::whereIn('id', $action_by)->pluck('email', 'email')->toArray();

        if (auth()->user()->hasRole('Leave Admin')) {
            $actionByName = 'HR Department';
        } else {
            $actionByName = auth()->user()->name;
        }
        // $leaveBalance   = $leave->getBalance();
        if ($approval_status->action == "Rejected") {

            $leave->status = $request->action;
            $leave->remarks = $request->remarks;
            $leave->is_approved = 0;
            // $leave->status      = 'Rejected';
            $this->updateLeaveBalance($leave);
            $leave->save();
            // $month                          =   Carbon::parse($leave->to_date)->format('m');
            // $year                           =   Carbon::parse($leave->to_date)->format('Y');

            // $leaveBalance->waiting          =   ($leaveBalance->waiting - $leave->duration);
            // $leaveBalance->save();


            $stages = NewLeaveApprovalStatus::where('leave_id', $request->leave_id)->where('action', 'Pending')->get();

            foreach ($stages as $stage) {
                $stage->delete();
            }


            $data['subject']        = "Leave Rejected";
            $data['to']             =  $leave->user->email;
            $data['cc']             =  $users;
            $data['user']           =  $leave->user;
            $data['object']         =  $leave;
            $data['message']        = "Leave Rejected by " . $actionByName . "<br> Reason: " . $leave->remarks;
            $data['emailMessage']   = "Leave Rejected by " . $actionByName;
            $data['currentUser']    =   auth()->user();
            $message = (new LeaveRejection($leave, $data['user']))->onQueue('emails');
            Mail::to($data['to'])->cc($data['cc'])->later(now()->addSeconds(1), $message);//responsible name goes to cc

            return "Leave " . $request->action;
        }


        // $approvalStatus = $leave->approvalStatus->where('action', 'Pending');
        if (!empty($users) && $request->action == "Approved" && $leave->approvalStatus()->where('action', 'Pending')->exists()) {
            // if (!empty($users) && $request->action == "Approved" && $approvalStatus->isNotEmpty()) {
            $data['subject']                = "Leave Approved";
            $data['emailMessage']           = "Leave Approved by " . $actionByName;
            $data['to']                     =  $users;
            $data['user']                   =  $leave->user;
            $data['object']                 =  $leave;
            $message = (new LeaveAction($leave, $data['user']))->onQueue('emails');
            Mail::to($data['to'])->later(now()->addSeconds(1), $message);
        }

        // if ($approvalStatus->isEmpty() && $request->action == "Approved") {
        if ($request->action == "Approved" && $leave->approvalStatus()->where('action', 'Pending')->exists() == false) {

            $leave->is_approved = 1;
            if ($leave->from_date == Carbon::createFromFormat('Y-m-d H:i:s', $leave->created_at)->format('Y-m-d')) {
                $leave->status      = 'Approved';
            } else {
                $leave->status      = 'Pre Approved';
            }
            $leave->remarks                 =   $request->remarks;
            $leave->save();

            $data['subject']                = "Leave Approved";
            $data['emailMessage']           = "Leave Approved by " . $actionByName;
            $data['to']                     =  $leave->user->email;
            $data['user']                   =  $leave->user;
            $data['object']                 =  $leave;
            $message = (new LeaveApproved($data['user'],$leave))->onQueue('emails');
            Mail::to($data['user'])->later(now()->addSeconds(1), $message);
            return "Leave " . $request->action;
        }
        $leave->load('approvalStatus');
        $responsibleUsers               =   $leave->responsiblePerson();

        $leave                          =   $leave->load('user');
        if ($responsibleUsers['approval_role'] == 'Leave Admin') {
            $responsibleUsers           =       User::whereHas('roles', function ($role) {
                $role->where('name', 'Leave Admin');
            })->pluck('email', 'email')->toArray();
        } else {
            if (!empty($responsibleUsers['approval_user']->email)) {
                $responsibleUsers          =     [$responsibleUsers['approval_user']->email];
            }
        }

        if (!empty($responsibleUsers)) {
            $data['subject']                = "Leave Applied";
            $data['to']                     = $responsibleUsers;
            $data['object']                 = $leave;
            $data['user']                   =  $leave->user;
            //first approval to send mail
            $message = (new LeaveAction($leave, $data['user']))->onQueue('emails');
            Mail::to($responsibleUsers)->later(now()->addSeconds(1), $message);
        }
        return "Leave " . $request->action;
    }

    public function getWaitingIds()
    {
        $user = auth()->user();
        $leaves = Leave::with("user.employee", 'approvalStatus.leave.leaveResponsible')->where('status', 'Pending')->where('user_id', '<>', $user->id)->get();
        $ids = [];
        foreach ($leaves as $leave) {
            $responsible_role  = $leave->pending_approval_role;

            $authorizedUser = $user->hasRole($responsible_role);
            $employee       = $leave->user->employee;

            if ($authorizedUser == false || empty($employee))   continue; // If not authorized  ||  If leave not applied by employee

            if($responsible_role == 'Leave Admin'){
                $ids[]  = $leave->id;
            }
            elseif($employee->isMyTeam()){
                $ids[] = $leave->id;
            }

        }

        return $ids;
    }


    public function show($id)
    {
        $leave      =   Leave::with(['user', 'approvalStatus'])->findOrFail($id);

       // abort_unless($leave->canView(), 403);

        $user                       =   auth()->user();
        $correspondingLeavesColumn  =   null;

        if ($user->hasRole('Line Manager') || $user->hasRole('Line Manager')) {
            $correspondingLeavesColumn  =   "line_manager_id";
        } elseif ($user->hasRole('Manager') || $user->hasRole('Manager')) {
            $correspondingLeavesColumn  =   "manager_id";
        } elseif ($user->hasRole('Team Leader') || $user->hasRole('Team Leader')) {
            $correspondingLeavesColumn  =   "team_leader_id";
        }
        $data['onLeaveToday']       =    Leave::where("user_id", "<>", $id)->with('user')->whereIn('status', ['Approved', "Pending"])
            ->where(function ($subQuery) use ($leave) {

                $subQuery->where(function ($query1) use ($leave) {
                    $query1->where('from_date', '<=', $leave->from_date)->where('to_date', '>=', $leave->to_date);
                })->orWhere(function ($query2) use ($leave) {

                    $query2->whereBetween('from_date', [$leave->from_date, $leave->to_date]);
                });
            })->when($correspondingLeavesColumn, function ($subQuery2) use ($user, $correspondingLeavesColumn) {
                $subQuery2->whereHas("user.employee", function ($subQuery3) use ($user, $correspondingLeavesColumn) {
                    $subQuery3->where($correspondingLeavesColumn, $user->id);
                });
            })->get();
        // $balanceMonth = Carbon::parse($leave->from_date);
        // $data['leaveBalance']       =   LeaveBalance::where('user_id', $leave->user_id)
        //                                     ->whereMonth('month', $balanceMonth)
        //                                     ->whereYear('month', $balanceMonth)
        //                                     ->where('leave_type_id', $leave->leave_type_id)
        //                                     ->first();

        // $data['leaveBalance']   = $leave->getBalance();
        $data['leave']              =   $leave;
        return view('leave.detail', $data);
    }

    public function viewFile(Request $request)
    {
        $file   = storage_path("app/documents/leave_documents/$request->file");
        return response()->file($file, ['Content-Type' => 'application/pdf']);
    }

    public function hrLeaveHistoryList(Request $request)
    {
        $this->authorize('leaveHistory', new Employee());
        // $this->authorize(['hrEmployeeList', 'leaveHistory'], new Employee());

        $data['leave_types']        =   LeaveType::pluck('name', 'id')->toArray();
        $data['leave_session']      =   Leave::pluck('leave_session', 'leave_session')->toArray();
        $data['department']         =   Department::pluck('name', 'id')->toArray();
        $leaves                     =   Leave::select('leaves.*', 'leave_types.name as leaveType', 'users.name as user_name', 'departments.name as department_name',)
                                        ->leftJoin('users', 'users.id', '=', 'leaves.user_id')
                                        ->leftJoin('employee', 'employee.user_id', '=', 'users.id')
                                        ->leftJoin('departments', 'departments.id', '=', 'employee.department_id')
                                        ->leftJoin('leave_types', 'leave_types.id', '=', 'leaves.leave_type_id');
        if (request()->ajax()) {
            $leaves                 =   $this->leaveSearch($request, $leaves);
            $leaves                 =   $this->filter($request, $leaves);
            return DataTables::of($leaves)
                ->addColumn('attachment', function ($leaves) {
                    if ($leaves->attachment)
                        $btn = '<a target="_blank" href="' . route("viewFile", ["file" => $leaves->attachment]) . '">
                                <i class="fa fa-eye text-primary"></i>
                                </a>';
                    else {
                        $btn    =    'N/A';
                    }
                    return $btn;
                })
                ->editColumn('duration', function ($leaves) {
                    return $leaves->setDurationAttribute();
                })
                ->editColumn('created_at', function ($leaves) {
                    return getFormatedDateTime($leaves->created_at);
                })
                ->editColumn('from_date', function ($leaves) {
                    return getFormatedDate($leaves->from_date);
                })
                ->editColumn('to_date', function ($leaves) {
                    return getFormatedDate($leaves->to_date);
                })
                ->editColumn('status', function ($leaves) {
                    return ucfirst($leaves->status);
                })
                ->addColumn('reason', function ($leaves) {
                    return '<textarea name="" id="" cols="15" rows="3" disabled>' . $leaves->reason . '</textarea>';
                })
                ->addColumn('remarks', function ($leaves) {
                    if(!empty($leaves->remarks))
                    {
                        return '<textarea name="" id="" cols="13" rows="3" disabled>' . $leaves->remarks . '</textarea>';
                    }
                    else
                    {
                        return '';
                    }
                })
                ->addColumn('action', function ($leaves) {
                    if (Carbon::parse($leaves->from_date)->format('M') == Carbon::now()->format('M')) {
                        $btn = '<button onClick="leaveCancel(' . $leaves->id . ')" class="btn btn-danger btn-xl p-2 leave-cancel"' . (($leaves->status == "Cancelled") ? "disabled" : "") . '>Cancel</button>';
                    } else {
                        $btn = 'N/A';
                    }
                    return $btn;
                })
                ->filterColumn('leaveType', function ($query, $keyword) {
                    $query->where('leave_types.name', 'like', "%$keyword%");
                })
                ->filterColumn('department_name', function ($query, $keyword) {
                    $query->where('departments.name', 'like', "%$keyword%");
                })
                ->filterColumn('user_name', function ($query, $keyword) {
                    $query->where('users.name', 'like', "%$keyword%");
                })
                ->rawColumns(['attachment', 'action', 'reason','remarks'])
                ->make(true);
        }
        $data['submitRoute']              =     'hrLeaveHistoryCancel';
        $data['employeeDepartments']      =     User::with('employee.department')->where('is_active', 1)->where('user_type', 'Employee')->whereHas('employee', function ($employee) {
                                                $employee->select('biometric_id', 'name');
                                                })->get()->groupBy('employee.department.name');

        $leavesCount                    =   Leave::with('user.employee.department');
        $leavesCount                    =   $this->filter($request,$leavesCount);
        $leavesCount                    =   $leavesCount->get();

        $data['cancelledLeavesCount']   =   $leavesCount->where('status', 'Cancelled')->count();
        $data['pendingLeavesCount']     =   $leavesCount->where('status', 'Pending')->count();
        $data['rejectedLeavesCount']    =   $leavesCount->where('status', 'Rejected')->count();
        $data['approvedLeavesCount']    =   $leavesCount->where('status', 'Approved')->count();
        return view('hr.leaveHistory', $data);
    }

public function filter($request, $leaves)
    {
        if (request()->has('leave_type_id')) {
            $leaves         = $leaves->where('leave_type_id', $request->leave_type_id);
        }
        if (request()->has('leave_session')) {
            if ($request->leave_session != 'Half day') {
                $leaves      = $leaves->where('leave_session', $request->leave_session);
            } else {
                $leaves      = $leaves->whereNotIn('leave_session', ['Full day', 'Absent']);
            }
        }
        if (request()->has('user_id')) {
            $leaves         = $leaves->where('leaves.user_id', $request->user_id);
        }
        if (request()->has('department_id')) {
            $leaves = $leaves->whereHas('user.employee', function ($query) {
                $query->where('department_id', request()->department_id);
            });
        }
        if (request()->has('pre_approved')) {

            $leaves = $leaves->where('status', 'Pre Approved');
        }

        if (!empty(request()->status)) {
            $leaves->whereIn('status', Arr::wrap(request()->status));
        } else {
            $leaves->where('status', "Pending");
        }

        if (!empty(request()->user)) {
            $leaves->where('user_id', request()->user);
        }

        if (!empty(request()->leave_type)) {
            $leaves->where('leave_type_id', request()->leave_type);
        }

        if (!empty(request()->dateFrom) && !empty(request()->dateTo)) {
            $leaves->where(function ($subQuery) {

                $subQuery->where(function ($query1) {

                    $query1->where('from_Date', '<=', request()->dateFrom)->where('to_Date', '>=', request()->dateFrom);
                })->orWhere(function ($query2) {

                    $query2->whereBetween('from_Date', [request()->dateFrom, request()->dateTo]);
                });
            });
        }

        if (request()->filled('biometric_id')) {
            $leaves->whereHas('user', function ($user) {
                $user->whereHas('employee', function ($emp) {
                    $emp->where('biometric_id', 'like', '%' . request()->biometric_id . '%');
                });
            });
        }
        // if (request()->has('status')) {

        //     $leaves = $leaves->whereIn('status', ['Approved', 'Pre Approved']);
        // }
        return $leaves;
    }

    public function managerLeaveHistory(Request $request)
    {
        $this->authorize('managerLeaveList', new Leave());
        if (auth()->user()->hasRole('Line Manager')) {
            
            $departmentIds = Department::where('line_manager_id', auth()->user()->id)->pluck('id', 'id')->toArray();
        }else{

            $departmentIds  =   auth()->user()->employee->managerDepartments->pluck('id', 'id')->toArray();
        }
        
        $data['departmentCount']    = (count($departmentIds) > 1) ? true : false;
        $leaves                     = Leave::select('leaves.*','users.name as user_name','departments.name as department_name','leave_types.name as leaveType')
                                        ->leftJoin('users','leaves.user_id','=','users.id')
                                        ->leftJoin('employee','employee.user_id','=','users.id')
                                        ->leftJoin('departments','employee.department_id','=','departments.id')
                                        ->leftJoin('leave_types','leaves.leave_type_id','=','leave_types.id')
                                        ->where('users.is_active', '=','1')
                                        ->where('employee.is_active', '=','1');
        if (empty($departmentIds))
        {
            $leaves         =   $leaves->where('departments.id', auth()->user()->employee->department_id);
        } else
        {
            $leaves         =   $leaves->whereIn('departments.id', $departmentIds);
        }
        $leaves             =   $this->leaveSearch($request, $leaves);
        if(request()->ajax())
        {
            return DataTables::of($leaves)
            ->addColumn('attachment', function ($leaves) {
                if ($leaves->attachment)
                    $btn = '<a target="_blank" href="' . route("viewFile", ["file" => $leaves->attachment]) . '">
                    <i class="fa fa-eye text-primary"></i>
                </a>';
                else {
                    $btn    =    'N/A';
                }
                return $btn;
            })
            ->editColumn('duration', function ($leaves) {
                return $leaves->setDurationAttribute();
            })
            ->editColumn('created_at', function ($leaves) {
                return getFormatedDate($leaves->created_at);
            })
            ->editColumn('timing', function ($leaves) {
                return $leaves->getShiftAttribute('timing');
            })
            ->editColumn('created_at', function ($leaves) {
                return getFormatedDate($leaves->created_at);
            })
            ->editColumn('from_date', function ($leaves) {
                return getFormatedDate($leaves->from_date);
            })
            ->editColumn('to_date', function ($leaves) {
                return getFormatedDate($leaves->to_date);
            })
            ->editColumn('status', function ($leaves) {
                return ucfirst($leaves->status);
            })
            ->addColumn('reason', function ($leaves) {
                return '<textarea  cols="20" rows="3" disabled>' . $leaves->reason . '</textarea>';
            })
            ->addColumn('remarks', function ($leaves) {
                if (!empty($leaves->remarks))
                {
                    return '<textarea  cols="15" rows="3" disabled>' . $leaves->remarks . '</textarea>';
                }
                else
                {
                    return '';
                }
            })
            ->filterColumn('leaveType', function ($query, $keyword) {
                $query->where('leave_types.name', 'like', "%$keyword%");
            })
            ->filterColumn('department_name', function ($query, $keyword) {
                $query->where('departments.name', 'like', "%$keyword%");
            })
            ->filterColumn('user_name', function ($query, $keyword) {
                $query->where('users.name', 'like', "%$keyword%");
            })
            ->rawColumns(['attachment', 'reason','remarks'])
            ->make(true);
        }
        return view('manager.leaveHistory', $data);
    }

    public function export(Request $request)
    {
        $this->authorize('hrUpdateEmployee', new Employee());
        ini_set('max_execution_time', -1);
        return Excel::download(new LeaveExport($request), 'leave.xlsx');
    }

    function hrLeaveHistoryCancel(Request $request)
    {
        $leave = Leave::find($request->id);
        $leave->update(['status' => 'Cancelled', 'is_approved' => 0]);
        $message    = 'Leave Cancelled';
        $this->leaveProcess($leave, $message);
        $this->updateLeaveBalance($leave);
        return back()->with('success', 'Leave cancelled');
    }

    private function leaveProcess($leave, $message)
    {
        $email                  = $leave->user->employee->office_email;
        $notificationReceivers  = $leave->user->employee->user_id;
        $link                   = route('leaveList');
        send_notification($notificationReceivers, $message . " by " . auth()->user()->employee->name, $link, 'leave');
        $data['leave']  = $leave;
        $data['link']   = $link;
        $subject        = $message . " by " . auth()->user()->name;
        send_email("email.action", $data, $subject, $message, $email, null);
    }


    public function cancelLeave(Request $request)
    {

        $leave  = Leave::find($request->leave_id);

        $this->updateLeaveBalance($leave);
        $leave->status          =   'Cancelled';
        $leave->is_approved     =   0;
        $leave->save();
        $leave->update(['status' => 'Cancelled']);

        $userIds = $leave->approvalStatus->pluck('action_by')->toArray();
        $users = User::whereIn('id', $userIds)->get();

        $pendingStages = $leave->approvalStatus->where('action', 'Pending');
        foreach ($pendingStages as $stage) {
            $stage->delete();
        }
        $to = $users->pluck('email')->toArray();
        $data = [
            'subject' => "Leave Cancelled",
            'emailMessage' => "Leave has been cancelled",
            'to' => $to,
            'cc' => $users,
            'user' => $leave->user,
            'object' => $leave,
        ];
        // dd($data);
        $message = "Leave Cancelled";
        $subject = "Leave Cancelled by " . auth()->user()->name;
        foreach ($to as $recipient) {
            $message = (new LeaveCancelled($data['user'], $data['object']))
                ->onQueue('emails')
                ->subject($subject);

            Mail::to($recipient)->later(now()->addSeconds(1), $message);
        }


        return ['success' => "Leave Cancelled."];
    }

    public function bulkLeaveAction(Request $request)
    {
        $leaveIds =   $request->leaves;
        ini_set('max_execution_time', -1);
        foreach ($leaveIds as $leaveId) {
            $request->request->add(['id' => $leaveId]);
            $this->leaveAction($request);
        }
    }

    public function hrLeaveStatusList(Request $request)
    {
        $this->authorize('hrEmployeeList', new Employee());
        $startDate              =   now()->startOfMonth()->format('Y-m-d');
        $endDate                =   now()->endOfMonth()->format('Y-m-d');
        $data['leave_types']    =   LeaveType::pluck('name', 'id')->toArray();
        $data['leave_session']  =   Leave::pluck('leave_session', 'leave_session')->toArray();
        $data['department']     =   Department::pluck('name', 'id')->toArray();
        $leaves                 =   Leave::with('user.employee.department')
            ->where('status', 'Approved')->where(function ($subQuery) use ($startDate, $endDate) {
                $subQuery->where(function ($query1) use ($startDate, $endDate) {
                    $query1->where('from_Date', '<=', $startDate)->where('to_Date', '>=', $endDate);
                })->orWhere(function ($query2) use ($startDate, $endDate) {

                    $query2->whereBetween('from_Date', [$startDate, $endDate]);
                });
            });
        if (request()->has('leave_type_id')) {
            $leaves      = $leaves->where('leave_type_id', $request->leave_type_id);
        }
        if (request()->has('leave_session')) {
            $leaves      = $leaves->where('leave_session', $request->leave_session);
        }
        if (request()->has('user_id')) {
            $leaves      = $leaves->where('user_id', $request->user_id);
        }

        if (request()->has('department_id')) {
            $leaves     = $leaves->whereHas('user.employee', function ($query) {
                $query->where('department_id', request()->department_id);
            });
        }
        $data['employeeDepartments']      =     User::with('employee.department')->where('is_active', 1)->where('user_type', 'Employee')->whereHas('employee', function ($employee) {
                                                $employee->select('biometric_id', 'name');
                                                })->get()->groupBy('employee.department.name');
        $data['leaves']                   =   $leaves->paginate(12);

        return view('hr.leaveStatusUpdate', $data);
    }

    public function updateLeaveStatus(Request $request)
    {
        $leave          = Leave::find($request->id);
        $this->updateLeaveBalance($leave);
        $leave->update(['status' => 'Pre Approved']);
        $carbonDate     =   Carbon::createFromFormat('Y-m-d', $leave->from_date);
        $duration       =   $leave->duration;
        $appliedAt      =   Carbon::createFromFormat('Y-m-d H:i:s', $leave->created_at)->format('Y-m-d');
        $cutOffDate     =   Carbon::now()->startOfMonth()->addDays(19);
        $this->preApprovalBalance($leave, $carbonDate, $duration, $appliedAt, $cutOffDate);
        return back()->with('success', 'Leave Status Updated');
    }


    private function notifyHR($hr,$leave)
    {
        $link   = route('hrLeaveList');
        $email  = $hr->pluck('email')->toArray();
        $notificationReceivers  = $hr->pluck('id', 'id')->toArray();
        send_notification($notificationReceivers, 'Leave applied by ' .  $leave->user->name, $link, 'leave');
        $data['leave']  = $leave;
        $data['link']   = $link;
        $message        = "Leave Applied";
        $subject        = 'Leave Applied by ' . $leave->user->name;
        send_email("email.leave", $data, $subject, $message, $email, null);
    }
    private function updateLeaveBalance($leave)
    {
        foreach ($leave->leaveDeduction as $deduction) {
            // Fetch the leave balance for the specific month and user
            $deductionMonth = Carbon::createFromFormat('Y-m-d', $deduction->month);; // Ensuring proper month format
    
            $leaveBalance = LeaveBalance::whereYear('month', $deductionMonth->year)
                                        ->whereMonth('month', $deductionMonth->month)
                                        ->where('user_id', $leave->user_id)
                                        ->first();
    
            if ($leaveBalance) {
                // Adjust the taken leaves, paid leaves, and lwp leaves based on the deduction
                $leaveBalance->taken_leaves -= $deduction->paid_leaves + $deduction->lwp_leaves;
    
                $leaveBalance->paid_leaves -= $deduction->paid_leaves;
                $leaveBalance->lwp_leaves -= $deduction->lwp_leaves;
    
                // Update utilizable balance and final balance
                $leaveBalance->utilizable_balance += $deduction->paid_leaves;
                $leaveBalance->final_balance = $leaveBalance->allowance + $leaveBalance->previous_balance - $leaveBalance->paid_leaves;
    
                // Save the updated leave balance
                $leaveBalance->save();
            }
        }
    }
    

//     private function updateLeaveBalance($leave)
// {
//     $carbonDate = Carbon::createFromFormat('Y-m-d', $leave->from_date);
//     $duration = $leave->duration;

//     $leaveBalance = LeaveBalance::whereMonth('month', $carbonDate)
//                                 ->whereYear('month', $carbonDate)
//                                 ->where('user_id', $leave->user_id)
//                                 ->first();

//     $leaveBalance->taken_leaves -= $duration;

//     $paidLeaveToCancel = min($leave->paid_leave_count, $duration);
//     $lwpLeaveToCancel = $duration - $paidLeaveToCancel;

//     $leaveBalance->paid_leaves -= $paidLeaveToCancel;
//     $leaveBalance->lwp_leaves -= $lwpLeaveToCancel;

//     $leaveBalance->utilizable_balance += $paidLeaveToCancel;
//     $leaveBalance->final_balance = $leaveBalance->allowance + $leaveBalance->previous_balance - $leaveBalance->paid_leaves;

//     $leaveBalance->save();
// }


    private function leaveExists($request, $leaveSession, $user_id)
    {
        $sessions = ['Full day' => 'Full day', 'First half' => 'First half', 'Second half' => 'Second half'];
        if ($leaveSession == 'First half') {
            $sessions = ['Full day' => 'Full day', 'First half' => 'First half'];
        } elseif ($leaveSession == 'Second half') {
            $sessions = ['Full day' => 'Full day', 'Second half' => 'Second half'];
        }
        return Leave::where('user_id', $user_id)->whereNotIn('status', ['Rejected', 'Cancelled'])->whereIn('leave_session', $sessions)
            ->where(function ($subQuery) use ($request) {

                $subQuery->where(function ($query1) use ($request) {

                    $query1->where('from_Date', '<=', $request->from_date)->where('to_Date', '>=', $request->from_date);
                })->orWhere(function ($query2) use ($request) {

                    $query2->whereBetween('from_Date', [$request->from_date, $request->to_date]);
                });
            });
    }
    private function managerLeaveCheck($request, $user_id)
    {
        return Leave::where('user_id', $user_id)->whereNotIn('status', ['Rejected', 'Cancelled'])
            ->where(function ($subQuery) use ($request) {
                $subQuery->where(function ($query1) use ($request) {
                    $query1->where('from_Date', '<=', $request->from_date)->where('to_Date', '>=', $request->from_date);
                })->orWhere(function ($query2) use ($request) {
                    $query2->whereBetween('from_Date', [$request->from_date, $request->to_date]);
                });
            });
    }

    private function leaveSearch(Request $request, $leaves)
    {
        if (!empty(request()->dateFrom) || !empty(request()->dateTo)) {
            $leaves->where(function ($subQuery) use ($request) {
                $subQuery->where(function ($query1) use ($request) {
                    $query1->where('from_Date', '<=', $request->dateFrom)->where('to_Date', '>=', $request->dateFrom);
                })->orWhere(function ($query2) use ($request) {
                    $query2->whereBetween('from_Date', [$request->dateFrom, $request->dateTo]);
                });
            });
        } else {
            $leaves->whereYear('from_date', '>=', Carbon::now()->year);
        }
        return $leaves;
    }

    // private function preApprovalBalance($leave, $carbonDate, $duration, $appliedAt, $cutOffDate) {
    //     // Set cutoff date to the end of the current month
    //     $cutOffDate = Carbon::now()->endOfMonth();
    
    //     $getBalance = $this->getBalance($leave, $carbonDate);
    //     $leaveBalance = $getBalance['leaveBalance'];
    
    //     if (empty($leaveBalance)) {
    //         $leaveBalance = new LeaveBalance();
    //         $leaveBalance->month = $leave->from_date;
    //         $leaveBalance->utilizable_balance = 0;
    //         $leaveBalance->taken_leaves = $duration;
    //         $leaveBalance->user_id = auth()->user()->id;
    //         $leaveBalance->deduction = 0; // No initial deduction
    //     } else {
    //         $leaveBalance->taken_leaves += $duration;
    //     }
    
    //     // Subtract duration from utilizable_balance
    //     $finalBalance = $leaveBalance->utilizable_balance - $duration;
    
    //     if ($finalBalance < 0) {
    //         // If the final balance is negative
    //         $leaveBalance->lwp_leaves +=   abs($finalBalance);
    //         $leave->lwp_leave_count +=  abs($finalBalance);
    //         $leaveBalance->paid_leaves += $leaveBalance->utilizable_balance;
    //         $leave->paid_leave_count += $leaveBalance->utilizable_balance;
    //         $leaveBalance->utilizable_balance = 0;
    //         $leaveBalance->final_balance = $leaveBalance->allowance + $leaveBalance->previous_balance - $leaveBalance->paid_leaves;
    //     } else {
    //         // If the final balance is positive
    //         $leaveBalance->utilizable_balance = $finalBalance;
    //         $leaveBalance->paid_leaves += $duration;
    //         $leave->paid_leave_count += $leave->duration;
    //         $leaveBalance->final_balance = $leaveBalance->allowance + $leaveBalance->previous_balance - $leaveBalance->paid_leaves;
    //     }
    //     $leave->save();
    //     $leaveBalance->save();
    // }
    

    private function preApprovalBalance($leave, $carbonDate, $duration) {
        // Split the duration into current month and next month
        list($currentMonthDuration, $nextMonthDuration) = $this->splitDurationByMonth($leave->from_date, $leave->to_date, $duration);
    
        // Handle current month leave balance
        $this->updatePreLeave($leave, $carbonDate, $currentMonthDuration);
    
        // If there is leave in the next month, handle that separately
        if ($nextMonthDuration > 0) {
            $nextMonthDate = $carbonDate->copy()->addMonthNoOverflow();
            $this->updatePreLeave($leave, $nextMonthDate, $nextMonthDuration);
        }
    }
    
    private function updatePreLeave($leave, $date, $duration) {
        $getBalance = $this->getBalance($leave, $date);
        $leaveBalance = $getBalance['leaveBalance'];
        $leaveDeduction = new LeaveDeduction();
        $leaveDeduction->leave_id = $leave->id;
        if (empty($leaveBalance)) {
            $leaveBalance = new LeaveBalance();
            $leaveBalance->month = $date->format('Y-m-d');
            $leaveDeduction->month = $date->format('Y-m-d');
            $leaveBalance->utilizable_balance = 0;
            $leaveBalance->taken_leaves = $duration;
            $leaveBalance->user_id = auth()->user()->id;
            $leaveBalance->deduction = 0;
        } else {
            $leaveBalance->taken_leaves += $duration;
        }
    
        // Subtract duration from utilizable_balance
        $finalBalance = $leaveBalance->utilizable_balance - $duration;
    
        if ($finalBalance < 0) {
            // If the final balance is negative
            $leaveDeduction->month = $date->format('Y-m-d');
            $leaveBalance->lwp_leaves += abs($finalBalance);
            $leaveDeduction->lwp_leaves += abs($finalBalance);
            // $leave->lwp_leave_count += abs($finalBalance);
            $leaveDeduction->paid_leaves += $leaveBalance->utilizable_balance;
            $leaveBalance->paid_leaves += $leaveBalance->utilizable_balance;
            // $leave->paid_leave_count += $leaveBalance->utilizable_balance;
            $leaveBalance->utilizable_balance = 0;
        } else {
            // If the final balance is positive
            $leaveDeduction->month = $date->format('Y-m-d');
            $leaveBalance->utilizable_balance = $finalBalance;
            $leaveDeduction->paid_leaves += $duration;
            $leaveBalance->paid_leaves += $duration;
            // $leave->paid_leave_count += $duration;
        }
    
        $leaveBalance->final_balance = $leaveBalance->allowance + $leaveBalance->previous_balance - $leaveBalance->paid_leaves;
    
        $leave->save();
        $leaveDeduction->save();
        $leaveBalance->save();
    }
    
    private function splitDurationByMonth($fromDate, $toDate, $totalDuration) {
        $fromDateTime = new DateTime($fromDate);
        $toDateTime = new DateTime($toDate);
    
        $endOfMonth = clone $fromDateTime;
        $endOfMonth->modify('last day of this month');
    
        if ($toDateTime <= $endOfMonth) {
            // All leave within the same month
            return [$totalDuration, 0];
        } else {
            $currentMonthDuration = $fromDateTime->diff($endOfMonth)->days + 1;
            $nextMonthDuration = $totalDuration - $currentMonthDuration;
            return [$currentMonthDuration, $nextMonthDuration];
        }
    }


    private function approvalDeduction($leave, $carbonDate, $duration) {
        // Split the duration into current month and next month
        list($currentMonthDuration, $nextMonthDuration) = $this->splitDurationByMonth($leave->from_date, $leave->to_date, $duration);
    
        // Handle current month leave balance
        $this->updateApprpveLeave($leave, $carbonDate, $currentMonthDuration);
    
        // If there is leave in the next month, handle that separately
        if ($nextMonthDuration > 0) {
            $nextMonthDate = $carbonDate->copy()->addMonthNoOverflow();
            $this->updateApprpveLeave($leave, $nextMonthDate, $nextMonthDuration);
        }
    }
    
    private function updateApprpveLeave($leave, $date, $duration) {
        // Obtain the balance for the leave's month
        $getBalance = $this->getBalance($leave, $date);
        $leaveBalance = $getBalance['leaveBalance'];
        $leaveDeduction = new LeaveDeduction();
        $leaveDeduction->leave_id = $leave->id;
        // Create a new balance record if none exists
        if (empty($leaveBalance)) {
            $leaveBalance = new LeaveBalance();
            $leaveBalance->month = $leave->from_date;
            $leaveDeduction->month = $date->format('Y-m-d');
            $leaveBalance->utilizable_balance = 0;
            $leaveBalance->taken_leaves = $duration;
            $leaveBalance->user_id = auth()->user()->id;
            $leaveBalance->deduction = 0; // No initial deduction
            $leaveBalance->lwp_leaves = $duration; // Assign duration to lwp_leaves
            // $leave->lwp_leave_count = $leave->duration;
            $leaveDeduction->lwp_leaves = $duration;

        } else {
            $leaveDeduction->month = $date->format('Y-m-d');
            $leaveBalance->taken_leaves += $duration;
            $leaveBalance->lwp_leaves += $duration; // Add the duration to existing lwp_leaves
            // $leave->lwp_leave_count += $leave->duration;
            $leaveDeduction->lwp_leaves += $duration;
        }
    
        $leave->save();
        $leaveDeduction->save();
        $leaveBalance->save();
    }

    private function getBalance($leave, $carbonDate)
{
    $leaveBalance = LeaveBalance::whereMonth('month', $carbonDate)
                                ->whereYear('month', $carbonDate)
                                ->where('user_id', $leave->user_id)
                                ->first();

    $deductibleBalance = 0;
    $leftBalance = 0;

    // if (!empty($leaveBalance) && $leaveBalance->balance > 0) {
    //     $balance = $leaveBalance->balance;
    //     $whole = intval($balance);
    //     $decimal1 = $balance - $whole;
    //     $decimal2 = round($decimal1, 2);
    //     $decimal = substr($decimal2, 2);

    //     if ($decimal != '25' && $decimal != '75') {
    //         $deductibleBalance = $leaveBalance->balance;
    //         $leftBalance = 0;
    //     } else {
    //         $deductibleBalance = $leaveBalance->balance - 0.25;
            $leftBalance = $leave->final_balance - $leave->utilizable_balance;
    //     }
    // }

    $data['deductibleBalance'] = $leave->utilizable_balance;
    $data['leftBalance'] = $leftBalance;
    $data['leaveBalance'] = $leaveBalance;

    return $data;
}

}
