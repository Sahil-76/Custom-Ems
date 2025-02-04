<?php

namespace App\Http\Controllers\ems;

use App\Models\LeaveNature;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class LeaveNatureController extends Controller
{
    public function index()
    {
        $leaves = LeaveNature::withTrashed()->get();
        $data['leaves'] = $leaves;
        return view('leaveNature.manageLeaveNature')->with(($data));
    }


    public function create()
    {
        $data['title'] = "Add Leave Nature";
        $data['button'] = "Add";
        $data['submitRoute']    =   'leave-nature.store';

        $data['method'] = "post";
        return view('leaveNature.leaveNature')->with($data);
    }


    public function store(Request $request)
    {

        $request->validate([
            'name' => 'required',
            'deduction' => 'required',

        ]);
        $leaves = new LeaveNature();

        $leaves->name = $request['name'];
        $leaves->deduction = $request['deduction'];
        $leaves->save();

        return redirect(route('leave-nature.index'));
    }


    public function show($id)
    {
        //
    }


    public function edit($id)
    {
        $leaves = LeaveNature::find($id);

        if (is_null($leaves)) {
            // not found
            return redirect(route('leave-nature.index'));
        } else {
            $data['title'] = "Update Leave Nature";
            $data['leaves'] = $leaves;
            $data['button'] = "Update";
            $data['method'] = "put";
            $data['submitRoute'] = ['leave-nature.update',$id];

            // $data = compact('url','leaves', 'title','button','method');

            return view('leaveNature.leaveNature')->with($data);
        }
    }


    public function update(Request $request, $id)
    {
        $leaves = LeaveNature::find($id);
        $leaves->name = $request['name'];
        $leaves->deduction = $request['deduction'];
        $leaves->save();

        return redirect(route('leave-nature.index'));
    }


    public function destroy($id)
    {
        $leaves = LeaveNature::find($id);
        if (!is_null($leaves)) {
            $leaves->delete();
        }
        return redirect(route('leave-nature.index'));
    }

    public function permanentdel($id)
    {
        $leaves = LeaveNature::withTrashed()->find($id);
        if (!is_null($leaves)) {
            $leaves->forceDelete();
        }
        return redirect(route('leave-nature.index'));
    }

    // public function onlytrash()
    // {
    //     $leaves = LeaveNature::onlyTrashed()->get();
    //     $data = compact('leaves');
    //     return view('leaveNature')->with(($data));
    // }

    public function restore($id)
    {
        $leaves = LeaveNature::withTrashed()->find($id);
        if (!is_null($leaves)) {
            $leaves->restore();
        }
        return redirect(route('leave-nature.index'));
    }
}
