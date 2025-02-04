<?php

namespace App\Http\Controllers\ems;

use App\Models\Role;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\LeaveResponsible;

class LeaveResponsibleController extends Controller
{
    //
    public function index()
    {

        $data['leaveResponsibles'] = LeaveResponsible::with('employeeRole','firstApproval','secondApproval','thirdApproval','fourthApproval')->get();

        return view('Leaves.LeaveResponsible.index', $data);
    }

    public function create()
    {
        $data['leaveResponsible']   =   new LeaveResponsible();
        $data['leaveRoles']         =   Role::whereIn('name',['Employee','Team Leader','Manager','Line Manager','Leave Admin'])->pluck('name','id')->toArray();
        //dd($data['leaveRoles'] );
        return view('Leaves.LeaveResponsible.create', $data);
    }

    public function store(Request $request)
    {

         $inputs=$request->except(['_token','employee_role_id']);
         $approval_count = array_filter($inputs, function($e)  {
            return ($e !== null);
        });

        $approval_count=count($approval_count);
        $leaveResponsible                       =   LeaveResponsible::updateOrCreate(['employee_role_id'=>$request->employee_role_id]);
        $leaveResponsible->first_approval       =   $request->first_approval;
        $leaveResponsible->second_approval      =   $request->second_approval;
        $leaveResponsible->third_approval       =   $request->third_approval;
        $leaveResponsible->fourth_approval      =   $request->fourth_approval;
        $leaveResponsible->approval_count       =   $approval_count;
        $leaveResponsible->save();

        // $action     = "Leave Responsible Added: $leaveResponsible->id";
        // storeLog($action, $leaveResponsible);

        // return back()->with('success', 'Leave Responsible Added');
		return redirect()->route('leave-responsible.index')->with('success', 'Leave Responsible Added');

        
    }

    public function show($id)
    {
        //
    }

    public function edit($id)
    {
        $data['leaveResponsible']   = LeaveResponsible::findOrFail($id);
        $data['leaveRoles']         =   Role::whereIn('name',['Employee','Team Leader','Manager','Line Manager','Leave Admin'])->pluck('name','id')->toArray();
        return view('Leaves.LeaveResponsible.edit',$data);

    }


    public function update(Request $request, $id)
    {
        $leaveResponsible                           = LeaveResponsible::findOrFail($id);

        $inputs=$request->except(['_token','employee_role_id','_method']);

        $approval_count = array_filter($inputs, function($e)  {
           return ($e !== null);
       });

        $approval_count=count($approval_count);
        $leaveResponsible->employee_role_id         =   $request->employee_role_id;
        $leaveResponsible->first_approval           =   $request->first_approval;
        $leaveResponsible->second_approval          =   $request->second_approval;
        $leaveResponsible->third_approval           =   $request->third_approval;
        $leaveResponsible->fourth_approval          =   $request->fourth_approval;
        $leaveResponsible->approval_count           =   $approval_count;
        $leaveResponsible->save();

        $action     = "Leave Responsible Updated: $leaveResponsible->id";
        // storeLog($action, $leaveResponsible);

        // return back()->with('success', 'Leave Responsible Updated');
		return redirect()->route('leave-responsible.index')->with('success', 'Leave Responsible Updated');

    }


    public function destroy($id)
    {
        //
    }

}
