<?php

namespace App\Http\Controllers\ems;

use App\User;
use App\Models\Employee;
use App\Models\Department;
use App\Models\AssetSubType;
use App\Http\Controllers\Controller;

class EmployeeDashboardController extends Controller
{
    public function index()
    {
        $this->authorize('hrEmployeeList', new Employee());
        $data['employeeTotal']      = User::where('is_active', '1')->whereIn('user_type', ['Employee', 'Office Junior'])->count();
        $data['officeJuniorCount']  = User::where('user_type', 'Office Junior')->count();
        $data['employeeCount']      = User::whereIn('user_type', ['Employee'])->whereHas('employee', function ($employee) {
            $employee->whereIn('onboard_status', ['Onboard', 'Training']);
        })->where('is_active', 1)->count();
        // $departments                = Department::with(['employees'=>function($employee){
        //                                         $employee->whereIn('onboard_status',['Onboard','Training']);
        //                                     },
        //                                     'employees.user'=>function($user){
        //                                         $user->where('is_active','1')->where('user_type','Employee');
        //                                     }, 'employees.user.shiftType', 'lineManager:id,name','resignations'])
        //                                     ->withCount([
        //                                         'employees as employees_count' => function ($employee) {
        //                                             $employee->whereHas('user', function ($user) {
        //                                                 $user->where('is_active', '1')->where('user_type', 'Employee');
        //                                             });
        //                                         },
        //                                     ])
        //                                     ->get();
        // $shiftData                  = [];
        // foreach($departments as $department)
        // {
        //     $dept['CurrentEmp']     =   $department->employees_count;
        //     $dept['HeadCount']     =   $department->required_emp;
        //     $dept['regCount']      =   $department->resignations->where('is_exit', 1)->count();
        //     $dept['regId']         =   $department->resignations->pluck('user_id')->toArray();
        //     $dept['id']            =   $department->id;
        //     $dept['Name']          =   $department->name;
        //     // $dept['HeadCount']     =   $department->employees_count;
        //     $dept['Manager']       =   $department->deptManager->name ??'N/A';
        //     $dept['lineManager']   =   $department->lineManager->name ??'N/A';
        //     $morning               =   User::where('user_type','Employee')->whereHas('employee',function($employee) use ($department){
        //                                     $employee->whereIn('onboard_status',['Onboard','Training'])->where('department_id',$department->id);
        //                                 })
        //                                 ->withCount(['shiftType'=>function($shiftType) {
        //                                                     $shiftType->where('name','Morning');
        //                                 }])->get()->sum('shift_type_count');
        //     $evening               =   User::where('user_type','Employee')->whereHas('employee',function($employee) use ($department){
        //                                     $employee->where('department_id',$department->id);
        //                                 })
        //                                 ->withCount(['shiftType'=>function($shiftType) {
        //                                     $shiftType->where('name','Evening');
        //                                 }])->get()->sum('shift_type_count');
        //     $dept['Morning Shift']  =   $morning;
        //     $dept['Evening Shift']  =   $evening;
        //     $shiftData[]            =   $dept;
        // }
        $departments = Department::with([
            'employees' => function ($employee) {
                $employee->whereIn('onboard_status', ['Onboard', 'Training'])
                    ->whereHas('user', function ($user) {
                        $user->where('is_active', '1')->where('user_type', 'Employee');
                    })
                    ->with('user.shiftType');
            },
            'lineManager:id,name',
            'resignations'
        ])
            ->withCount([
                'employees as employees_count' => function ($employee) {
                    $employee->whereHas('user', function ($user) {
                        $user->where('is_active', '1')->where('user_type', 'Employee');
                    });
                },
            ])
            ->get();

        $shiftData = [];

        foreach ($departments as $department) {
            $dept['CurrentEmp'] = $department->employees_count;
            $dept['HeadCount'] = $department->required_emp;
            $dept['regCount'] = $department->resignations->where('is_exit', 1)->count();
            $dept['regId'] = $department->resignations->pluck('user_id')->toArray();
            $dept['id'] = $department->id;
            $dept['Name'] = $department->name;
            $dept['Manager'] = $department->deptManager->name ?? 'N/A';
            $dept['lineManager'] = $department->lineManager->name ?? 'N/A';
            $dept['wifi_availability'] = $department->employees->where('wifi_availability', 1)->count();
            $dept['room_availability'] = $department->employees->where('room_available', 1)->count();
            $dept['single'] = $department->employees->where('marital_status', 'Single')->count();
            $dept['married'] = $department->employees->where('marital_status', 'Married')->count();
            $dept['passport'] = $department->employees()->whereHas('documents', function ($query) {
                $query->whereNotNull('passport');
            })->count();
            $dept['training'] = $department->employees()
                ->whereHas('user', function ($query) {
                    $query->whereHas('training', function ($trainingQuery) {
                        $trainingQuery->whereNull('result');
                    });
                })
                ->count();
                // dd($dept['training']);
            $dept['single_emp'] = $department->employees->where('marital_status', 'Single')->pluck('id')->toArray();
            $dept['married_emp'] = $department->employees->where('marital_status', 'Married')->pluck('id')->toArray();
            $dept['both'] = $department->employees
                ->where('room_available', 1)
                ->where('wifi_availability', 1)
                ->count();
            $dept['emp_wifi'] = $department->employees->where('wifi_availability', 1)->pluck('id')->toArray();
            $dept['emp_room'] = $department->employees->where('room_available', 1)->pluck('id')->toArray();

            $dept['wfh_count']  = $department->employees->where('is_wfh', 1)->count();

            $morning = $department->employees->where('user.shiftType.name', 'Morning')->count();
            $evening = $department->employees->where('user.shiftType.name', 'evening')->count();

            $dept['Morning Shift'] = $morning;
            $dept['Evening Shift'] = $evening;

            $shiftData[] = $dept;
        }

        //  dd($shiftData);
        $data['departments']           =   $shiftData;
        // $data['departments']                    =   $depData;
        $data['departmentCount']                =   $departments->count();
        $data['departmentUnassignedAssets']     =   $this->assetData();
        $data['byGenderTypes']                  =   $this->ByGenderType();
        $data['subTypes']                       =   AssetSubType::whereIn('name', ["Laptop", 'Charger', 'Mouse', 'Headphone'])->pluck('id', 'name');
        return view('employee.dashboard', $data);
    }

    public function  assetData()
    {
        $laptopId       = AssetSubType::where('name', "Laptop")->first()->id;
        $NewlaptopId       = AssetSubType::where('name', "New Laptop")->first()->id;
        $ChargerId      = AssetSubType::where('name', "Charger")->first()->id;
        $NewChargerId      = AssetSubType::where('name', "New Charger")->first()->id;
        $MouseId        = AssetSubType::where('name', "Mouse")->first()->id;
        $HeadphoneId    = AssetSubType::where('name', "Headphone")->first()->id;
        $upsId          = AssetSubType::where('name', "UPS")->first()->id;
        $departments    = Department::with(['employees.user' => function ($user) {
            $user->where('user_type', 'Employee');
        }])->withCount('employees')->withCount(['employees as unassignedLaptops' => function ($employee) use ($laptopId, $NewlaptopId) {
            $employee->whereHas('user', function ($user) {
                $user->where('user_type', 'Employee');
            })->whereDoesNtHave('user.assetAssignments', function ($assets) use ($laptopId, $NewlaptopId) {
                $assets->whereIn('sub_type_id', [$laptopId, $NewlaptopId]);
            });
        }, 'employees as unassignedCharger' => function ($employee) use ($ChargerId, $upsId, $NewChargerId)  // as person having ups charger is not provided.
        {

            $employee->whereHas('user', function ($user) {
                $user->where('user_type', 'Employee');
            })->whereDoesNtHave('user.assetAssignments', function ($assets) use ($ChargerId, $upsId, $NewChargerId) {
                $assets->whereIn('sub_type_id', [$ChargerId, $NewChargerId, $upsId]);
            });
        }, 'employees as unassignedMouse' => function ($employee) use ($MouseId) {

            $employee->whereHas('user', function ($user) {
                $user->where('user_type', 'Employee');
            })->whereDoesNtHave('user.assetAssignments', function ($assets) use ($MouseId) {
                $assets->where('sub_type_id', $MouseId);
            });
        }, 'employees as unassignedHeadphn' => function ($employee) use ($HeadphoneId) {

            $employee->whereHas('user', function ($user) {
                $user->where('user_type', 'Employee');
            })->whereDoesNtHave('user.assetAssignments', function ($assets) use ($HeadphoneId) {
                $assets->where('sub_type_id', $HeadphoneId);
            });
        }])->get();

        return $departments;
    }

    public function ByGenderType()
    {
        $departments    =   Department::withCount([
            'employees' => function ($employee) {
                $employee->whereHas('user', function ($user) {
                    $user->where('is_active', '1')->where('user_type', 'Employee');
                });
            },
            'employees as maleCount' => function ($employee) {
                $employee->whereHas('user', function ($user) {
                    $user->where('is_active', '1')->where('user_type', 'Employee');
                })->whereIn('onboard_status', ['Onboard', 'Training'])->where('gender', 'Male');
            },
            'employees as femaleCount' => function ($employee) {
                $employee->whereHas('user', function ($user) {
                    $user->where('is_active', '1')->where('user_type', 'Employee');
                })->whereIn('onboard_status', ['Onboard', 'Training'])->where('gender', 'Female');
            }
        ])->get();
        return $departments;
    }

    public function managerEmployeeDashboard()
    {
        $this->authorize('managerDashboard', auth()->user());
        if (auth()->user()->hasRole('Line Manager')) {

            $departmentIds = Department::where('line_manager_id', auth()->user()->id)->pluck('id', 'id')->toArray();
        } else {

            $departmentIds  =   auth()->user()->employee->managerDepartments->pluck('id', 'id')->toArray();
        }

        $employees          =   Employee::with('user.shiftType', 'user.assetAssignments')->select('id', 'user_id', 'name', 'gender')
            ->whereIn('department_id', $departmentIds)->get();
        $data['employees']  = $employees;
        $assets             =   ['Laptop' => "Laptop", "Headphone" => "Headphone", "Mouse" => "Mouse", "Charger" => "Charger"];
        $assetAssignments   =   [];
        foreach ($employees as $employee) {
            if ($employee->user->assetAssignments->isNotEmpty()) {
                $assignedAssets     =   $employee->user->assetAssignments->pluck('assetSubType.name', 'assetSubType.name')->toArray();
                $unassignedAssets   =   array_diff($assets, $assignedAssets);
                $assetAssignments[$employee->name]['assigned']      =  !empty($assignedAssets) ?  implode(",", $assignedAssets) : "";
                $assetAssignments[$employee->name]['unAssigned']    =  !empty($unassignedAssets) ? ',' . implode(",", $unassignedAssets) : "";
            }
        }
        $data['assetAssignments'] = $assetAssignments;
        return view('manager.managerDashboard', $data);
    }
}
