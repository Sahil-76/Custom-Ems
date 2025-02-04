<?php

namespace App\Http\Controllers\ems;

use App\User;
use App\Models\Employee;
use App\Models\ShiftType;
use App\Models\Department;
use Illuminate\Http\Request;
use App\Models\ResignationReason;
use App\Models\EmployeeResignation;
use App\Http\Controllers\Controller;
use Yajra\DataTables\Facades\DataTables;


class EmployeeResignationController extends Controller
{
    /**
     * Display a listing of the resource.
     */

     public function index(Request $request)
{
    $this->authorize('hrEmployeeList', new Employee());
    $resignations = EmployeeResignation::has('user');

    $resignations = $this->filter($resignations, $request);

    if ($request->ajax()) {
        return Datatables::of($resignations)
            ->addColumn('user_name', function ($resignations) {
                return $resignations->user->name ?? null;
            })
            ->addColumn('department_name', function ($resignations) {
                return $resignations->user->employee->department->name ?? null;
            })
            ->addColumn('resson_name', function ($resignations) {
                return $resignations->reason->name;
            })
            ->addColumn('actionByUser_name', function ($resignations) {
                if ($resignations->action_by) {
                    return $resignations->actionByUser->name;
                } else {
                    return 'Unknown User';
                }
            })
            ->addColumn('leaving_date', function ($resignations) {
                return \Carbon\Carbon::parse($resignations->leaving_date)->format('d-m-y');
            })
            ->addColumn('is_exit', function ($resignations) {
                return $resignations->is_exit ? 'Yes' : 'No';
            })
            ->addColumn('action', function ($resignations) {
                $actionBtn = '<a href="' . route('resignations.edit', $resignations->id) . '"><i class="fas fa-edit"></i></a> ';
                $actionBtn .= '<form id="deleteForm' . $resignations->id . '" action="' . route('resignations.destroy', $resignations->id) . '" method="post" style="display:inline;">';
                $actionBtn .= '<input type="hidden" name="_token" value="' . csrf_token() . '">';
                $actionBtn .= '<input type="hidden" name="_method" value="DELETE">';
                $actionBtn .= '<button class="btn delete-btn" data-id="' . $resignations->id . '"><i class="fas fa-trash-alt" style="cursor: pointer; color:red;"></i></button>';
                $actionBtn .= '</form>';
                return $actionBtn;
            })
            ->rawColumns(['action'])
            ->make(true);
    }


    $data['office_emails']          =   Employee::where('is_active', 1)->pluck('office_email', 'office_email')->toArray();
    $data['department_id']          =   Department::pluck('name', 'id')->toArray();
    // $data['department_id']          =   $employee->department->name;
    $data['gender']                 =   config('employee.gender');
    $data['shift_types']            =   ['Morning' => 'Morning', 'Evening' => 'Evening'];
    // $data['shift_types']             = ShiftType::pluck('name', 'id')->toArray();
    $data['shiftTypes']             =   ShiftType::get();
  
    //  $data['method']         =   'GET';
   
    return view('employeeResignations.index', compact('resignations','data'));
}




public function filter($resignations, $request)
{
  
    // dd($resignations->toSql());
    // dd($request->all());
    if ($request->has('department_id')) {
        $resignations        =       $resignations->whereHas('employee', function ($query) {
            $query->where('department_id', request()->department_id);
        });
    }
    

    // if ($request->has('department_id')) {
    //     $resignations = $resignations->whereHas('user.employee.department', function ($query) {
    //         $query->where('id', request()->department_id);
    //     });
    // }
  
    if ($request->filled('department_id')) {
        $resignations = $resignations->whereHas('employee', function ($query) {
            $query->where('id', request()->department_id);
        });
    }
    
    if (request()->has('office_email')) {
        $resignations = $resignations->where('office_email', request()->office_email);
    }
    if (request()->has('gender')) {
        $resignations  =   $resignations->where('gender', $request->gender);
    }

    return $resignations;
}
     /**
      * Show the form for creating a new resource.
      */
     public function create(Request $request)
     {
        $this->authorize('hrUpdateEmployee', new Employee());
         $user = User::findOrFail($request->employee);
         $reasons         =   ResignationReason::pluck('name', 'id')->toArray();
         return view('employeeResignations.create', compact('user', 'reasons'));
     }
 
     /**
      * Store a newly created resource in storage.
      */
     public function store(Request $request)
     {
        // dd($request);
         $request->validate([
            'user_id' => 'required|exists:users,id|unique:employee_resignation,user_id',
            'leaving_date' => 'required|date',
            'is_exit' => 'boolean'
         ]);
 
         EmployeeResignation::create(
             [
                 'user_id' => $request->user_id,
                 'action_by' => auth()->user()->id,
                 'resignation_reason_id'=>$request->reason_id,
                 'leaving_date' => $request->leaving_date,
                 'is_exit' => isset($request->is_exit) ? 1: 0,
             ]
         );
         
         // EmployeeResignation::create($request->all());
 
         return redirect()->route('resignations.index')
         ->with('success', 'Employee resignation recorded successfully.');
     
     }
 
     public function edit($resignation)
     {
        $this->authorize('hrUpdateEmployee', new Employee());
         $resignation = EmployeeResignation::findOrFail($resignation);
         $user=$resignation->user;
         $reasons = ResignationReason::pluck('name', 'id')->toArray();  
         return view('employeeResignations.edit', compact('user', 'reasons', 'resignation'));
     }
     
     public function update(Request $request, $resignation)
     {
         $request->validate([
            'user_id' => 'unique:employee_resignation,user_id,' . $resignation,
            'leaving_date' => 'required|date',
            'is_exit' => 'boolean'
         ]);
     
         $resignation = EmployeeResignation::findOrFail($resignation);
     
         $resignation->update([
             'resignation_reason_id'=>$request->reason_id,
             'action_by' => auth()->user()->id,
             'leaving_date' => $request->leaving_date,
             'is_exit' => isset($request->is_exit) ? 1: 0,
         ]);
     
         return redirect()->route('resignations.index')
         ->with('success', 'Employee resignation updated successfully.');
     
     }
     
     /**
      * Remove the specified resource from storage.
      */
 
     
     public function destroy($resignation)
     {
        $this->authorize('hrUpdateEmployee', new Employee());
         $resignation = EmployeeResignation::findOrFail($resignation);
         $resignation->delete();
     
         return redirect()->back()
             ->with('success', 'Employee resignation deleted successfully.');
     }
}