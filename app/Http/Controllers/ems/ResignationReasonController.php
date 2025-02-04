<?php

namespace App\Http\Controllers\ems;

use App\Models\Employee;
use Illuminate\Http\Request;
use App\Models\ResignationReason;
use App\Http\Controllers\Controller;

class ResignationReasonController extends Controller
{
      
    public function index()
    {
        $this->authorize('hrEmployeeList', new Employee());
        $reason = ResignationReason::withTrashed()->get();
        $data['reason'] = $reason;
        return view('ResignationReason.index')->with(($data));
    }


    public function create()
    {
        $this->authorize('hrUpdateEmployee', new Employee());
        $data['title'] = "Add Reason";
        $data['button'] = "Add";
        $data['submitRoute']    =   'resignation-reason.store';

        $data['method'] = "post";
        return view('ResignationReason.create')->with($data);
    }


    public function store(Request $request)
    {

        $request->validate([
            'name' => 'required',
        ]);
        $reason = new ResignationReason();

        $reason->name = $request['name'];
        $reason->save();

        return redirect(route('resignation-reason.index'));
    }


    public function show($id)
    {
        //
    }


    public function edit($id)
    {
        $this->authorize('hrUpdateEmployee', new Employee());
        $reason = ResignationReason::find($id);

        if (is_null($reason)) {
            // not found
            return redirect(route('resignation-reason.index'));
        } else {
            $data['title'] = "Update Reason";
            $data['reason'] = $reason;
            $data['button'] = "Update";
            $data['method'] = "put";
            $data['submitRoute'] = ['resignation-reason.update',$id];

            return view('ResignationReason.create')->with($data);
        }
    }


    public function update(Request $request, $id)
    {
        $reason = ResignationReason::find($id);
        $reason->name = $request['name'];
        $reason->save();

        return redirect(route('resignation-reason.index'));
    }


    public function destroy($id)
    {
        $this->authorize('hrUpdateEmployee', new Employee());
        $reason = ResignationReason::find($id);
        if (!is_null($reason)) {
            $reason->delete();
        }
        return redirect(route('resignation-reason.index'));
    }

    public function permanentdel($id)
    {
        $reason = ResignationReason::withTrashed()->find($id);
        if (!is_null($reason)) {
            $reason->forceDelete();
        }
        return redirect(route('resignation-reason.index'));
    }

    public function restore($id)
    {
        $this->authorize('hrUpdateEmployee', new Employee());
        $reason = ResignationReason::withTrashed()->find($id);
        if (!is_null($reason)) {
            $reason->restore();
        }
        return redirect(route('resignation-reason.index'));
    }
}
