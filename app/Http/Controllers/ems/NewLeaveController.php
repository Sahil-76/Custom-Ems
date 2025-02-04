<?php

namespace App\Http\Controllers\ems;

use App\User;
use App\Models\Role;
use App\Models\Employee;
use App\Models\NewLeave;
use Carbon\CarbonPeriod;
use App\Models\LeaveTypes;
use App\Models\NewHoliday;
use Illuminate\Support\Arr;
use App\Exports\LeaveExport;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Models\NewLeaveBalance;
use Illuminate\Support\Collection;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\NewLeaveReqest;
use Illuminate\Support\Facades\Crypt;
use App\Models\NewLeaveApprovalStatus;
use Illuminate\Support\Facades\Storage;
use Illuminate\Contracts\Encryption\DecryptException;

class NewLeaveController extends Controller
{
       /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

     public function index()
     {
        //  $this->authorize("view", new NewLeave());
 
         $user                           =   Auth::user();
         $data['status']                 =   config('employee.leave_status');
         $data['leaveTypes']             =   LeaveTypes::pluck('name', 'id')->toArray();
         $leaves                         =   NewLeave::where('user_id', $user->id)->orderByRaw("Field(status,'Pending','Approved','Rejected', 'Cancelled')");
         $object                         =   new NewLeave();
         $data['leaveNature']            =   $object->getLeaveNature();
         $data['today']                  =   Carbon::today()->format('Y-m-d');
         $leaves                         =   $this->filter($leaves);
         $data['leaves']                 =   $leaves->paginate(10);
         $leavesCount                    =   NewLeave::where('user_id', $user->id);
         $leavesCount                    =   $this->filter($leavesCount);
         $leavesCount                    =   $leavesCount->get();
 
         $data['cancelledLeavesCount']   =   $leavesCount->where('status', 'Cancelled')->count();
         $data['pendingLeavesCount']     =   $leavesCount->where('status', 'Pending')->count();
         $data['rejectedLeavesCount']    =   $leavesCount->where('status', 'Rejected')->count();
         $data['approvedLeavesCount']    =   $leavesCount->where('status', 'Approved')->count();
 
         return view('Leaves.show', $data);
     }
 
     public function leaveRequest(Request $request, $export = false) // Request list
     {
         // $this->authorize("approval", new NewLeave());
         $currentUser = auth()->user();
 
        //  if ($currentUser->cannot('viewRequestList', new NewLeave()) && $currentUser->cannot('approval', new NewLeave())) {
        //      abort(403);
        //  }
 
         $data['status']                 =   config('employee.leave_status');
         $data['leaveTypes']             =   LeaveTypes::pluck('name', 'id')->toArray();
 
        //  if ($currentUser->hasRole('Sales Person') || $currentUser->hasRole('Non Sales Person')) {
             $users      = User::whereHas('employee', function ($employees) use ($currentUser) {
                 $employees->where('manager_id', $currentUser->id)->orWhere('team_leader_id', $currentUser->id);
             })->pluck('name', 'id')->toArray();
        //  }else {
        //      $users      = User::whereHas("roles", function ($roles) {
        //          $roles->whereIn("name", ["Sales Person", "Non Sales Person"]);
        //      })->pluck("name", "id")->toArray();
        // //  }
 
         $queryUsers             =   array_flip($users);
         $leaves                 =   NewLeave::with('approvalStatus.leave.leaveResponsible')->whereIn('user_id', $queryUsers)->where('user_id', '<>', auth()->user()->id);
 
 
         $leaves                 =   $this->filter($leaves);
 
         if (request()->filled('waiting')) {
             $ids = $this->getWaitingIds();
 
             $leaves = $leaves->whereIn('id', $ids);
         }
 
         if ($export) return $leaves->get();
 
         $data['leaves']         =   $leaves->latest()->paginate(10);
         $data['pendingLeaves']  =   NewLeave::with('approvalStatus.leave.leaveResponsible')->whereIn('user_id', $queryUsers)->where('user_id', '<>', auth()->user()->id)->where('status', 'Pending')->count('id');
         $data['users']          =   $users;
         $data['managers']       =   User::havingRole(['Sales Manager', 'Manager Non Sales'],'name');
         return view('Leaves.index', $data);
     }
 
     function export(Request $request)
     {
 
        //  $this->authorize('export', new Employee());
        //  $leaves = $this->leaveRequest($request, true);
 
        //  return Excel::download(new LeaveExport($leaves), 'leaves.xlsx');
     }
 
     public function getWaitingIds()
     {
         $user = auth()->user();
         $leaves = NewLeave::with("user.employee", 'approvalStatus.leave.leaveResponsible')->where('status', 'Pending')->where('user_id', '<>', $user->id)->get();
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
 
             // elseif ( $responsible_role == "Sales Senior Manager" && $employee->senior_manager_id  == $user->id) {
             //     $ids[]  = $leave->id;
             // } elseif ( $responsible_role == "Sales Manager" && $employee->manager_id  == $user->id) {
             //     $ids[]  = $leave->id;
             // } elseif ( $responsible_role == "Team Leader" && $employee->team_leader_id  == $user->id) {
             //     $ids[]  = $leave->id;
             // } elseif ( $responsible_role == "Non Sales Senior Manager" && $employee->team_leader_id  == $user->id) {
             //     $ids[]  = $leave->id;
             // } elseif ( $responsible_role == "Manager Non Sales" && $employee->team_leader_id  == $user->id) {
             //     $ids[]  = $leave->id;
             // } elseif ( $responsible_role == "Non Sales Team Leader" && $employee->team_leader_id  == $user->id) {
             //     $ids[]  = $leave->id;
             // }
         }
 
         return $ids;
     }
 
     /**
      * Show the form for creating a new resource.
      *
      * @return \Illuminate\Http\Response
      */
     public function create()
     {
        //  $this->authorize("create", new NewLeave());
         $currentUser                = auth()->user();
         $today                      = Carbon::today()->format('Y-m-d');
         $diffDays                   = today()->diffInDays($currentUser->employee->joining_date);
 
         $data['leaveTypes']         = LeaveTypes::active()->canApply()->pluck('name', 'id');
        //  $data['leaveTypes']         = LeaveTypes::active()->canApply()->where('apply_after', '<', $diffDays)->pluck('name', 'id');
         $data['region']             = auth()->user()->employee->region ?? null;
         $data['today']              = $today;
 
         $data['holidays']           = NewHoliday::where('type', 'Optional Leave')->whereYear('date', $today)->whereDate('date', '>', $today)->pluck('title', 'id');
//  dd($diffDays);
         return view('Leaves.create', $data);
     }
 
     /**
      * Store a newly created resource in storage.
      *
      * @param  \Illuminate\Http\Request  $request
      * @return \Illuminate\Http\Response
      */
     public function store(NewLeaveReqest $request)
     {
         $leaveType = LeaveTypes::findOrFail($request->leave_type);
 
         if ($leaveType->attachment_required && !$request->has('attachment')) {
             return back()->withErrors('attachment', 'Attachment Required');
         }
 
         $user           =   auth()->user();
         $leaveNature    =   (empty($request->leave_nature) ? 'Full day' : 'Half day');
         if (empty($user)) {
             return back();
         }
         $timings = [auth()->user()->employee->region->work_from . "-" . auth()->user()->employee->region->work_till => auth()->user()->employee->region->work_from . "-" . auth()->user()->employee->region->work_till];
         if ($leaveNature == 'Half day') {
             if ($request->halfDayType == 'First half') {
                 $timings += [auth()->user()->employee->region->work_from . "-" . auth()->user()->employee->region->first_session_till => auth()->user()->employee->region->work_from . "-" . auth()->user()->employee->region->first_session_till];
             } else {
                 $timings += [auth()->user()->employee->region->second_session_from . "-" . auth()->user()->employee->region->work_till => auth()->user()->employee->region->second_session_from . "-" . auth()->user()->employee->region->work_till];
             }
         }
 
         $leaveExists = $this->leaveExists($request, $user->id);
 
         if (count($timings) > 1) {
             $leaveExists->whereIn('timing', $timings);
         }
         if ($leaveExists->exists()) {
             return back()->with('failure', 'Leave already Exists');
         }
 
         $currentMonth           = today()->startOfMonth()->toDateTimeString();
 
         $leave                  = new NewLeave();
         $leave->user_id         = $user->id;
         $leave->leave_type_id   = $request->leave_type;
         $leave->leave_nature    = $leaveNature;
         $leave->from_date       = $request->from_date;
         $leave->to_date         = $request->to_date;
         $leave->timing          = auth()->user()->employee->region->work_from . "-" . auth()->user()->employee->region->work_till ?? null;
         $leave->balance_dt      = $currentMonth;
         if ($leaveNature == 'Half day') {
 
             $leave->leave_nature  = $leaveNature . '(' . $request->halfDayType . ')';
             $leave->timing      = auth()->user()->employee->region->second_session_from . "-" . auth()->user()->employee->region->work_till ?? null;
 
             if ($request->halfDayType == 'First half') {
                 $leave->timing  = auth()->user()->employee->region->work_from . "-" . auth()->user()->employee->region->first_session_till ?? null;
             }
         }
 
         if ($request->has('attachment')) {
             $name   = 'leaveFile' . Carbon::now()->timestamp . '.' . $request->file('attachment')->getClientOriginalExtension();
             Storage::disk('local')->putFileAs('leave_documents/' . $user->id, $request->attachment, $name);
             $this->encrypt('/leave_documents/' . $user->id . '/', $name);
             $leave->attachment  = $name;
         }
 
         $leave->reason  = $request->reason;
         $leave->save();
 
        //  $leaveBalance   = NewLeaveBalance::firstOrNew([
        //      'leave_type_id' => $leave->leave_type_id,
        //      'user_id'       => $leave->user_id,
        //      'month'         => Carbon::parse($leave->from_date)->startOfMonth()->toDateString()
        //  ]);
 
         $leaveBalance = $leave->getBalance();
         $leaveBalance->waiting  = ($leaveBalance->waiting + $leave->duration);
         $leaveBalance->save();
 
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
 
            //  if ($allowStage) {
            //      $approvalStatus             =   new LeaveApprovalStatus();
            //      $approvalStatus->leave_id   =   $leave->id;
            //      $approvalStatus->action     =   "Pending";
            //      $approvalStatus->stage      =   $arr[$i];
            //      $approvalStatus->save();
            //  }
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
 
         if (!empty($responsibleUser)) {
             $data['to']  = $responsibleUsers;
             $data['object'] = $leave;
 
            //  EmailController::request_email('Leave Applied', $data);
         }
 
         return redirect(route('leave.index'))->with('success', 'Leave applied');
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
             case 'Sales Manager':
                 $relation = 'manager';
                 break;
 
             case 'Sales Senior Manager':
                 $relation = 'seniorManager';
                 break;
             case 'Non Sales Team Leader':
                 $relation = 'teamLeader';
                 break;
             case 'Manager Non Sales':
                 $relation = 'manager';
                 break;
             case 'Non Sales Senior Manager':
                 $relation = 'seniorManager';
                 break;
         }
 
         return $relation ?? null;
     }
 
     public function approvalStages($user)
     {
         $roles = $user->roles;
 
         if ($roles->whereIn('name', ['Sales Senior Manager', 'sales senior manager'])->count()) {
             $roleName      = 'Sales Senior Manager';
         } elseif ($roles->whereIn('name', ['Sales Manager', 'sales manager'])->count()) {
             $roleName      = 'Sales Manager';
         } elseif ($roles->whereIn('name', ['Sales Person', 'sales person'])->count()) {
             $roleName      = 'Sales Person';
         } elseif ($roles->whereIn('name', ['Non Sales Senior Manager', 'non sales senior manager'])->count()) {
             $roleName      = 'Non Sales Senior Manager';
         } elseif ($roles->whereIn('name', ['Manager Non Sales', 'manager non sales'])->count()) {
             $roleName      = 'Manager Non Sales';
         } elseif ($roles->whereIn('name', ['Non Sales Team Leader', 'non sales team leader'])->count()) {
             $roleName      = 'Non Sales Team Leader';
         } elseif ($roles->whereIn('name', ['Non Sales Person', 'non sales person'])->count()) {
             $roleName      = 'Non Sales Person';
         }
 
         return Role::whereName($roleName)->first()->leaveResponsible ?? null;
     }
     public function approvalCount($user)
     {
 
         $approval_count = 0;
         if ($user->hasRole('Sales Senior Manager')) {
             $role = Role::where('name', 'Sales Senior Manager')->first();
             $approval_count = $role->leaveResponsible->approval_count;
         } elseif ($user->hasRole('Sales Manager')) {
             $role = Role::where('name', 'Sales Manager')->first();
             $approval_count = $role->leaveResponsible->approval_count;
         } elseif ($user->hasRole('Team Leader')) {
             $role = Role::where('name', 'Team Leader')->first();
             $approval_count = $role->leaveResponsible->approval_count;
         } elseif ($user->hasRole('Non Sales Senior Manager')) {
             $role = Role::where('name', 'Non Sales Senior Manager')->first();
             $approval_count = $role->leaveResponsible->approval_count;
         } elseif ($user->hasRole('Manager Non Sales')) {
             $role = Role::where('name', 'Manager Non Sales')->first();
             $approval_count = $role->leaveResponsible->approval_count;
         } elseif ($user->hasRole('Non Sales Team Leader')) {
             $role = Role::where('name', 'Non Sales Team Leader')->first();
             $approval_count = $role->leaveResponsible->approval_count;
         } elseif ($user->hasRole('Sales Person')) {
             $role = Role::where('name', 'Sales Person')->first();
             $approval_count = $role->leaveResponsible->approval_count;
         } elseif ($user->hasRole('Non Sales Person')) {
             $role = Role::where('name', 'Non Sales Person')->first();
             $approval_count = $role->leaveResponsible->approval_count;
         }
         return $approval_count;
     }
 
     private function leaveExists($request, $user_id)
     {
         $exists =   NewLeave::where('user_id', $user_id)->whereNotIn('status', ['Rejected', 'Cancelled'])
             ->where(function ($subQuery) use ($request) {
                 $subQuery->where(function ($query1) use ($request) {
                     $query1->where('from_Date', '<=', $request->from_date)->where('to_Date', '>=', $request->from_date);
                 })->orWhere(function ($query2) use ($request) {
                     $query2->whereBetween('from_Date', [$request->from_date, $request->to_date]);
                 });
             });
         return $exists;
     }
     /**
      * Display the specified resource.
      *
      * @param  int  $id
      * @return \Illuminate\Http\Response
      */
     public function show($id)
     {
         $leave      =   NewLeave::with(['user', 'approvalStatus'])->findOrFail($id);
 
         abort_unless($leave->canView(), 403);
 
         $user                       =   auth()->user();
         $correspondingLeavesColumn  =   null;
 
         if ($user->hasRole('Sales Senior Manager') || $user->hasRole('Non Sales Senior Manager')) {
             $correspondingLeavesColumn  =   "senior_manager_id";
         } elseif ($user->hasRole('Sales Manager') || $user->hasRole('Manager Non Sales')) {
             $correspondingLeavesColumn  =   "manager_id";
         } elseif ($user->hasRole('Team Leader') || $user->hasRole('Non Sales Team Leader')) {
             $correspondingLeavesColumn  =   "team_leader_id";
         }
         $data['onLeaveToday']       =    NewLeave::where("user_id", "<>", $id)->with('user')->whereIn('status', ['Approved', "Pending"])
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
         // $data['leaveBalance']       =   NewLeaveBalance::where('user_id', $leave->user_id)
         //                                     ->whereMonth('month', $balanceMonth)
         //                                     ->whereYear('month', $balanceMonth)
         //                                     ->where('leave_type_id', $leave->leave_type_id)
         //                                     ->first();
 
         $data['leaveBalance']   = $leave->getBalance();
         $data['leave']              =   $leave;
         return view('Leaves.detail', $data);
     }
 
     public function leaveAction(Request $request)
     {
         $leave = NewLeave::find($request->leave_id);
        //  $action_by                  =   LeaveApprovalStatus::where('leave_id', $request->leave_id)->where('action', 'Approved')->pluck('action_by')->toArray();
 
         // New Code to check approver
         $currentUser = auth()->user();
         $responsibleArray = $leave->responsiblePerson();
         $canApprove = false;
         if ($currentUser->hasRole($responsibleArray['approval_role'])  &&  !empty($responsibleArray['approval_user']->email) && $responsibleArray['approval_user']->email == $currentUser->email && $leave->canApprove()) {
             $canApprove = true;
         } elseif ($currentUser->hasRole('Leave Admin') && $responsibleArray['approval_role'] == "Leave Admin") {
             $canApprove = true;
         } elseif ($currentUser->hasRole('Sales Senior Manager') && $responsibleArray['approval_role'] == "Sales Senior Manager" && $leave->canApprove()) {
             $canApprove = true;
         }
         // end new code to check approver
 
         if ($canApprove === false || $leave->status == 'Rejected') {
             abort(403, 'Unauthorised Action');
         }
 
 
        //  $approval_status            =   LeaveApprovalStatus::where('leave_id', $request->leave_id)->where('action', 'Pending')->first();
        //  $approval_status->action    =   $request->action;
        //  $approval_status->remarks   =   !empty($request->remarks) ? $request->remarks : null;
        //  $approval_status->action_by =   auth()->user()->id;
        //  $approval_status->save();
         // $month                          =   Carbon::parse($leave->to_date)->format('m');
         // $year                           =   Carbon::parse($leave->to_date)->format('Y');
         // $leaveBalance                   =   $leave->leaveType->leaveBalances()->where('user_id', $leave->user_id)->whereYear('month', $year)->whereMonth('month', $month)->first();
 
 
         $users          = User::whereIn('id', $action_by)->pluck('email', 'email')->toArray();
 
         if (auth()->user()->hasRole('Leave Admin')) {
             $actionByName = 'HR Department';
         } else {
             $actionByName = auth()->user()->name;
         }
         $leaveBalance   = $leave->getBalance();
         if ($approval_status->action == "Rejected") {
 
             $leave->status = $request->action;
             $leave->remarks = $request->remarks;
             $leave->save();
             // $month                          =   Carbon::parse($leave->to_date)->format('m');
             // $year                           =   Carbon::parse($leave->to_date)->format('Y');
 
             $leaveBalance->waiting          =   ($leaveBalance->waiting - $leave->duration);
             $leaveBalance->save();
 
 
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
            //  EmailController::request_email('Leave Action', $data);
 
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
            //  EmailController::request_email('Leave Action', $data);
         }
 
         // if ($approvalStatus->isEmpty() && $request->action == "Approved") {
         elseif ($request->action == "Approved" && $leave->approvalStatus()->where('action', 'Pending')->exists() == false) {
 
             $leave->status                  =   $request->action;
             $leave->remarks                 =   $request->remarks;
             $leave->save();
             $leaveBalance->waiting          =   ($leaveBalance->waiting - $leave->duration);
             $leaveBalance->taken_leaves     =   $leaveBalance->taken_leaves + $leave->duration;
             $leaveBalance->final_balance    =   ($leaveBalance->allowance + $leaveBalance->previous_balance) - $leaveBalance->taken_leaves;
             $leaveBalance->save();
             $data['subject']                = "Leave Approved";
             $data['emailMessage']           = "Leave Approved by " . $actionByName;
             $data['to']                     =  $leave->user->email;
             $data['user']                   =  $leave->user;
             $data['object']                 =  $leave;
            //  EmailController::request_email('Leave Action', $data);
             return "Leave " . $request->action;
         }
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
 
            //  EmailController::request_email('Leave Applied', $data);
         }
         return "Leave " . $request->action;
     }
 
     public function cancel($id)
     {
         $leave                          =   NewLeave::findOrFail($id);
 
         abort_unless($leave->canCancel(), 403);
 
         // $month                          =   Carbon::parse($leave->to_date)->format('m');
         // $year                           =   Carbon::parse($leave->to_date)->format('Y');
         // $leaveBalance                   =   $leave->leaveType->leaveBalances()->where('user_id', $leave->user_id)->whereYear('month', $year)->whereMonth('month', $month)->first();
 
         $leaveBalance = $leave->getBalance();
 
         if ($leave->status == 'Pending') {
             $leaveBalance->waiting      =   $leaveBalance->waiting - $leave->duration;
         } elseif ($leave->status == 'Approved') {
             $leaveBalance->taken_leaves     =   $leaveBalance->taken_leaves - $leave->duration;
             $leaveBalance->final_balance    =   $leaveBalance->final_balance + $leave->duration;
         }
 
         $leaveBalance->save();
         $leave->update(['status' => 'Cancelled']);
 
         $user_ids = $leave->approvalStatus->pluck('action_by')->toArray();
         $users = User::whereIn('id', $user_ids)->get();
 
         $pendingStages = $leave->approvalStatus->where('action', 'Pending');
         foreach ($pendingStages as $stage) {
             $stage->delete();
         }
         $responsibleUser = $leave->responsiblePerson();
         $approval_role = $responsibleUser['approval_role'];
 
         if ($approval_role == 'Leave Admin') {
             $approval_user           =       User::whereHas('roles', function ($role) {
                 $role->where('name', 'Leave Admin');
             })->get();
         } else {
             $approval_user = $responsibleUser['approval_user'];
         }
 
 
 
         if ($approval_user  instanceof Collection) {
 
             $users =  $users->merge($approval_user);
         } else {
 
             $users = $users->push($approval_user);
         }
 
         $to = $users->pluck('email')->toArray();
         $data['subject']        = "Leave Cancelled";
         $data['emailMessage']   = "Leave has been cancelled";
         $data['to']       =  $to;
         $data['user']     =  $leave->user;
         $data['object']   =  $leave;
 
        //  EmailController::request_email('Leave Action', $data);
         return ['success' => "Leave Cancelled."];
     }
 
     public function leaveType(Request $request)
     {
         $user               = auth()->user();
         $data               = [];
         $leaveType          = LeaveTypes::find($request->id);
         $today              = Carbon::today()->toDateTimeString();
 
         if (empty($leaveType)) {
             return [];
         }
 
         $from_date          = Carbon::today()->format('Y-m-d');
         $to_date            = $from_date;
 
         $empOffDays = $user->employee->getOffDays();
 
         if (empty($empOffDays)) {
             $empOffDays = array_map('trim', explode('-', $user->employee->region->off_day));
         }
 
         $employeeProbationPeriod              =   $user->employee->probation_date ?? null;
 
         if (!empty($employeeProbationPeriod) && ($from_date > $employeeProbationPeriod || $leaveType->can_apply_probation == 1)) {
             $canApplyInProbation =   1;
         }
 
         $data['canApplyProbation'] = $canApplyInProbation ?? 0;
 
         if (!empty($request->from_date) && !empty($request->to_date)) {
             $from_date                  =   Carbon::parse($request->from_date);
             $to_date                    =   Carbon::parse($request->to_date);
             $days                       =   $from_date->diffInDays($to_date);
             // $data['days']               =   $days + 1;
 
             $nature = empty($request->is_half_day) ? 'Full Day' : 'Half Day';
 
             $data['days']               = (new NewLeave())->calculateDuration($user->id, $from_date, $to_date, $nature);
             $period                     = CarbonPeriod::create($from_date, $to_date);
 
             $dateArray = [];
             foreach ($period as $date) {
 
                 $dateArray[] = $date->format('Y-m-d');
             }
             $data['dateArray'] = $dateArray;
 
             $check_off_day = null;
             foreach ($dateArray as $date) {
                 $timestamp = strtotime($date);
                 $dayName = date('l', $timestamp);
                 $check_off_day = in_array($dayName, $empOffDays);
                 if ($check_off_day) {
                     break;
                 }
             }
             $data['check_off_day'] = $check_off_day;
         }
 
         if ($leaveType->name == "Optional Leave") {
 
             $data['leaveCount']         =  NewLeave::where('user_id', auth()->user()->id)->where('status', 'Approved')->where('leave_type_id', $leaveType->id)->whereYear('from_date',$from_date)->count('id');
             $data['leavePendingCount']  =  NewLeave::where('user_id', auth()->user()->id)->where('status', 'Pending')->where('leave_type_id', $leaveType->id)->whereYear('from_date',$from_date)->count('id');
 
         }
         if (!empty($request->holiday_id)) {
             $holiday = NewHoliday::find($request->holiday_id);
 
             $data['from_date'] = $holiday->date;
             $data['to_date'] = $holiday->date;
         }
 
 
         $notice_period                        =   $leaveType->notice_period;
         $data['notice_period_date_before']    =   Carbon::today()->addDays($notice_period)->format('Y-m-d');
         $data['leaveType']                    =   $leaveType;
 
 
 
         // $month                                =   Carbon::parse($to_date)->format('m');
         // $year                                 =   Carbon::parse($to_date)->format('Y');
         // $data['final_balance']                =   $leaveType->leaveBalances()->where('user_id', auth()->user()->id)->whereYear('month', $year)->whereMonth('month', $month)->first()->final_balance ?? null;
         // $data['waiting']                      =   $leaveType->leaveBalances()->where('user_id', auth()->user()->id)->whereYear('month', $year)->whereMonth('month', $month)->first()->waiting ?? null;
 
         if ($leaveType->name == "Optional Leave") {
             $data['final_balance'] = number_format($leaveType->allowance - $data['leaveCount']);
             // dd($data['final_balance']);
         } else {
          $leaveBalances      = NewLeaveBalance::where('leave_type_id', $leaveType->id)->where('user_id', $user->id)->balanceBetween($today, $to_date)->get();
         $finalBalance       = $leaveBalances->sum('final_balance');
         $waitingBalanace    = $leaveBalances->sum('waiting');
         $data['final_balance'] = number_format($finalBalance - $waitingBalanace, 2);
         }
         // $data['waiting']                    = $leaveBalance->waiting ?? null;
 
         // $employeeProbationPeriod              =   auth()->user()->employee->probation_date ?? null;
         // $data['canApplyProbation']            =   0;
         // if (!empty($employeeProbationPeriod) && ($from_date > $employeeProbationPeriod || $leaveType->can_apply_probation == 1)) {
         //     $data['canApplyProbation']      =   1;
         // }
         return $data;
     }
 
     private function getLeaveTypeBalances($fromDate, $toDate, $leaveTypeId, $userId)
     {
         return NewLeaveBalance::where('leave_type_id', $leaveTypeId)->where('user_id', $userId)->balanceBetween($fromDate, $toDate)->get();
     }
 
     public function downloadFile()
     {
         $userId = request()->segment(3);
         $document = request()->segment(4);
         ini_set('max_execution_time', "-1");
         ini_set("memory_limit", "-1");
         $fileFullPath = storage_path('app/leave_documents/' . $userId . '/' . $document);
         // check for file exist before get content
         if (!file_exists($fileFullPath)) {
             echo "<script>alert('File does not exist')</script>";
             abort(404, 'file not found');
         }
         $fileData =  file_get_contents($fileFullPath);
         try {
             $image1 = Crypt::decrypt($fileData);
         } catch (DecryptException $err) {
             dd($err->getMessage());
         }
 
         $headers = array(
             'Content-Type: application/pdf',
         );
         return response()->streamDownload(function () use ($image1) {
             echo $image1;
         }, 'attachment.pdf', $headers);
     }
 
     private function filter($leaves)
     {
         if (!empty(request()->leave_type)) {
             $leaves->where('leave_type_id', request()->leave_type);
         }
 
         if (!empty(request()->status)) {
             $leaves->whereIn('status', Arr::wrap(request()->status));
         } else {
             $leaves->where('status', "Pending");
         }
         if (!empty(request()->user)) {
             $leaves->where('user_id', request()->user);
         }
 
         if (request()->filled('emp_id')) {
             $leaves->whereHas('user', function ($user) {
                 $user->whereHas('employee', function ($emp) {
                     $emp->where('emp_id', 'like', '%' . request()->emp_id . '%');
                 });
             });
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
 
         if (!empty(request()->manager_id)) {
             $leaves->whereHas('employee', function ($manager) {
                 $manager->where('manager_id', request()->manager_id);
             });
         }
         return $leaves;
     }
 
     private function encrypt($pathToSave, $fileName)
     {
         $encryptedCV = Crypt::encrypt(file_get_contents(storage_path('app/' . $pathToSave . $fileName)));
         unlink(storage_path('app/' . $pathToSave . $fileName));
         Storage::put($pathToSave . $fileName, $encryptedCV);
     }
}
