<?php

namespace App\Http\Controllers\ems;

use App\Models\LeaveStatus;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class LeaveStatusController extends Controller
{
    public function index()
    {
        $leaves = LeaveStatus::withTrashed()->get();
        $data['leaves'] = $leaves;
        return view('leaveStatus.manageLeaveStatus')->with(($data));
    }


    public function create()
    {
        $data['title'] = "Add Leave Status";
        $data['button'] = "Add";
        $data['submitRoute']    =   'leave-status.store';

        $data['method'] = "post";
        return view('leaveStatus.leaveStatus')->with($data);
    }


    public function store(Request $request)
    {

        $request->validate([
            'name' => 'required',

        ]);
        $leaves = new LeaveStatus();

        $leaves->name = $request['name'];
        $leaves->save();

        return redirect(route('leave-status.index'));
    }


    public function show($id)
    {
        //
    }


    public function edit($id)
    {
        $leaves = LeaveStatus::find($id);

        if (is_null($leaves)) {
            // not found
            return redirect(route('leave-status.index'));
        } else {
            $data['title'] = "Update Leave status";
            $data['leaves'] = $leaves;
            $data['button'] = "Update";
            $data['method'] = "put";
            $data['submitRoute'] = ['leave-status.update',$id];

            // $data = compact('url','leaves', 'title','button','method');

            return view('leaveStatus.leaveStatus')->with($data);
        }
    }


    public function update(Request $request, $id)
    {
        $leaves = LeaveStatus::find($id);
        $leaves->name = $request['name'];
        $leaves->save();

        return redirect(route('leave-status.index'));
    }


    public function destroy($id)
    {
        $leaves = LeaveStatus::find($id);
        if (!is_null($leaves)) {
            $leaves->delete();
        }
        return redirect(route('leave-status.index'));
    }

    public function permanentdel($id)
    {
        $leaves = LeaveStatus::withTrashed()->find($id);
        if (!is_null($leaves)) {
            $leaves->forceDelete();
        }
        return redirect(route('leave-status.index'));
    }

    // public function onlytrash()
    // {
    //     $leaves = LeaveStatus::onlyTrashed()->get();
    //     $data = compact('leaves');
    //     return view('LeaveStatus')->with(($data));
    // }

    public function restore($id)
    {
        $leaves = LeaveStatus::withTrashed()->find($id);
        if (!is_null($leaves)) {
            $leaves->restore();
        }
        return redirect(route('leave-status.index'));
    }
}
