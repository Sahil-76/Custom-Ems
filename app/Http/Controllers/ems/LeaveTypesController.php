<?php

namespace App\Http\Controllers\ems;

use App\Models\LeaveTypes;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Config;

class LeaveTypesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $data['leaveTypes']=LeaveTypes::all();
        return view('Leaves.LeaveType.index',$data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $data['allowance_type']=Config::get('employee.allowance_type');
        return view('Leaves.LeaveType.create', $data);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
// dd($request);

        $leaveType                                      =   LeaveTypes::updateOrCreate(['name' => $request->name]);
        $leaveType->allowance                           =   $request->allowance;
        $leaveType->short_name                          =   $request->short_name;
        $leaveType->allowance_type                      =   $request->allowance_type;
        $leaveType->carry_forward_type                  =   $request->carry_forward_type;
        $leaveType->notice_period                       =   $request->notice_period;
        $leaveType->apply_after                         =   $request->apply_after;
        $leaveType->balance                             =   ($request->balance == 'on') ? 1 : 0;
        $leaveType->leave_permission                    =   ($request->leave_permission == 'on') ? 1 : 0;
        $leaveType->attachment_required                 =   ($request->attachment_required == 'on') ? 1 : 0;
        $leaveType->half_day_allowed                    =   ($request->half_day_allowed == 'on') ? 1 : 0;
        $leaveType->can_apply_probation                 =   ($request->can_apply_probation == 'on') ? 1 : 0;
        $leaveType->can_negative                        =   ($request->can_negative == 'on') ? 1 : 0;
        $leaveType->description                         =   $request->description;
        $leaveType->carry_forward                       =   ($request->carry_forward == 'on') ? 1 : 0;
        $leaveType->can_apply                           =   ($request->can_apply == 'on') ? 1 : 0;
        $leaveType->is_active                           =   ($request->is_active == 'on') ? 1 : 0;
        $leaveType->yearly_expiry                        =  ($request->yearly_expiry == 'on')? 0 : 1;
        $leaveType->color_code                          =  $request->color_code;
        $leaveType->save();
        return redirect(route('leave-types.index'))->with('success','Leave Type Added');
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
        $data['leaveType']  =   LeaveTypes::findOrFail($id);
        $data['allowance_type']=Config::get('employee.allowance_type');
        return view('Leaves.LeaveType.edit',$data);
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
       $leaveType                                       =   LeaveTypes::findOrFail($id);
       $leaveType->name                                 =   $request->name;
       $leaveType->short_name                           =   $request->short_name;
       $leaveType->allowance_type                       =   $request->allowance_type;
       $leaveType->carry_forward_type                   =   $request->carry_forward_type;
       $leaveType->allowance                            =   $request->allowance;
       $leaveType->notice_period                        =   $request->notice_period;
       $leaveType->apply_after                          =   $request->apply_after;
       $leaveType->balance                              =   $request->balance;
       $leaveType->leave_permission                     =   $request->leave_permission;
       $leaveType->description                          =   $request->description;
       $leaveType->carry_forward                        =   empty($request->carry_forward)? 0 : 1;
       $leaveType->half_day_allowed                     =   empty($request->half_day_allowed)? 0 : 1;
       $leaveType->can_negative                         =   empty($request->can_negative)? 0 : 1;
       $leaveType->attachment_required                  =   empty($request->attachment_required)? 0 : 1;
       $leaveType->can_apply_probation                  =   empty($request->can_apply_probation)? 0 : 1;
       $leaveType->attachment_required                  =   empty($request->attachment_required)? 0 : 1;
       $leaveType->can_apply                            =   empty($request->can_apply)? 0 : 1;
       $leaveType->color_code                           =   $request->color_code;
       $leaveType->is_active                            =   empty($request->is_active)? 0 : 1;
       $leaveType->yearly_expiry                        =   empty($request->yearly_expiry)? 0 : 1;
       $leaveType->save();
       return redirect(route('leave-types.index'))->with('success','Leave Type Updated');
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
