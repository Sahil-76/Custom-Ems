<?php

namespace App\Http\Controllers\ems;

use App\User;
use App\Models\Role;
use App\Models\Employee;
use App\Models\Department;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\DepartmentRequest;
use Yajra\DataTables\Facades\DataTables;

class DepartmentController extends Controller
{
	// function index(Request $request) {
		
	// 	$this->authorize('view', new Department());

	// 	$departments = Department::query()
	// 		->select(
	// 			'departments.id',
	// 			'departments.name',
	// 			'departments.short_name',
	// 			'departments.required_emp',
	// 			'managers.name as manager_name',
	// 			'team_leaders.name as team_leader_name',
	// 			'line_managers.name as line_manager_name',
	// 		)
	// 		->leftJoin('users as line_managers', 'line_managers.id', 'departments.line_manager_id')
	// 		->leftJoin('employee as team_leaders', 'team_leaders.id', 'departments.team_leader_id')
	// 		->leftJoin('employee as managers', 'managers.id', 'departments.manager_id')
	// 		->withCount('employees')
	// 		->get();

	// 	$data['departments'] = $departments;

	// 	return view('department.index', $data);
	// }

	function index(Request $request) {
        
        $this->authorize('view', new Department());

        $departments = Department::query()
            ->select(
                'departments.id',
                'departments.name',
                'departments.short_name',
                'departments.required_emp',
                'managers.name as manager_name',
                'team_leaders.name as team_leader_name',
                'line_managers.name as line_manager_name',
            )
            ->leftJoin('users as line_managers', 'line_managers.id', 'departments.line_manager_id')
            ->leftJoin('employee as team_leaders', 'team_leaders.id', 'departments.team_leader_id')
            ->leftJoin('employee as managers', 'managers.id', 'departments.manager_id')
            ->leftJoin('employee', 'employee.department_id', '=', 'departments.id') // Join with employee table
            ->leftJoin('employee_resignation', 'employee_resignation.user_id', '=', 'employee.user_id') // Join with employee_resignation table
			->selectRaw('COUNT(DISTINCT CASE WHEN employee_resignation.is_exit = 1 THEN employee_resignation.id ELSE NULL END) as total_resignations')
            ->groupBy('departments.id', 'departments.name', 'departments.short_name', 'departments.required_emp', 'managers.name', 'team_leaders.name', 'line_managers.name')
            ->withCount('employees')
            ->get();
			if ($request->ajax()) {
				return DataTables::of($departments)
				->addColumn('total_employees', function ($department) {
					return $department->employees_count;
				})
				->addColumn('total_resignations', function ($department) {
					return ($department->total_resignations !== 0) ? $department->total_resignations : 'N/A';
				})
				->addColumn('required_hc', function ($department) {
					return ($department->required_emp !== null) ? $department->required_emp : 'N/A';
				})
				->addColumn('vacancies', function ($department) {
					return ($department->required_emp !== null) ? ($department->required_emp - $department->employees_count) + $department->total_resignations : 'N/A';
				})
				->addColumn('action', function ($department) {
					return '<a class="btn btn-danger" style="height:40px;width: 70px; text-align: center; line-height: 35px;" href="'.route('departments.edit', $department).'">Edit</a>';
				})
				->rawColumns(['action'])
				->make(true);
			}
        $data['departments'] = $departments;
        return view('department.index', $data);
    }

	public function create() { 
		
		$this->authorize('insert', new Department());

		$managers  				= User::havingRole('Manager', 'id');
		$data['managers']  		= Employee::whereIn('user_id', $managers)->pluck('name', 'id')->toArray();
		
		$teamLeaders 			= User::havingRole('Team Leader', 'id');
		$data['teamLeaders']  	= Employee::whereIn('user_id', $teamLeaders)->pluck('name', 'id')->toArray();

		$data['lineManagers']  	= User::havingRole('Line Manager', 'name');

		return view('department.create', $data);

	}

	public function store(Request $request) {
		
		$this->authorize('insert', new Department());

		$department 					= new Department();
		$department->name 				= $request->name;
		$department->description 		= $request->description;
		$department->short_name     	= $request->short_name;
		$department->manager_id     	= $request->manager_id;
		$department->team_leader_id     = $request->team_leader_id;
		$department->line_manager_id 	= $request->line_manager_id;
		$department->required_emp 		= $request->required_emp;
		$department->save();

		return redirect()->route('departments.index')->with('success', 'Department Added');

	}

	public function edit($id) {
		$department 			= Department::find($id);
		$this->authorize('update', $department);

		$data['department'] 	= $department;
		$managers  				= User::havingRole('Manager', 'id');
		$data['managers']  		= Employee::whereIn('user_id', $managers)->pluck('name', 'id')->toArray();
		
		$teamLeaders 			= User::havingRole('Team Leader', 'id');
		$data['teamLeaders']  	= Employee::whereIn('user_id', $teamLeaders)->pluck('name', 'id')->toArray();
		$data['lineManagers']  	= User::havingRole('Line Manager', 'name');

		return view('department.edit', $data);
	}

	public function view(Request $request)
	{
		$this->authorize('view', new Department());

		$departments = Department::query()
			->select(
				'departments.id',
				'departments.name',
				'departments.short_name',
				'managers.name as manager_name',
				'team_leaders.name as team_leader_name',
				'line_managers.name as line_manager_name',
			)
			->leftJoin('users as line_managers', 'line_managers.id', 'departments.line_manager_id')
			->leftJoin('users as team_leaders', 'team_leaders.id', 'departments.team_leader_id')
			->leftJoin('users as managers', 'managers.id', 'departments.manager_id')
			->get();

		$data['departments'] = $departments;
		return view('department.department', $data);
	}
	public function list(Request $request)
	{
		$pageIndex 		=  $request->pageIndex;
		$pageSize 		= $request->pageSize;
		$departments 	= Department::query();
		if (!empty($request->get('name')))
		{
			$departments 	= $departments->where('name', 'like', '%' . $request->get('name') . '%');
		}
		$data['itemsCount'] = $departments->count();
		$data['data'] 		= $departments->limit($pageSize)->offset(($pageIndex - 1) * $pageSize)->get();
		return json_encode($data);
	}

	public function insert(DepartmentRequest $request)
	{
		$this->authorize('insert', new Department());
		$department 				= new Department();
		$department->name 			= $request->name;
		$department->description 	= $request->description;
		$department->short_name     = $request->short_name;
		$department->save();
		return json_encode($department);
	}

	public function update(DepartmentRequest $request, $id)
	{
		$department 				= 	Department::findOrFail($id);
		$this->authorize('update', $department);

		$department->name 				= 	$request->name;
		$department->description 		= 	$request->description;
		$department->short_name     	= 	$request->short_name;
		$department->manager_id     	= 	$request->manager_id;
		$department->team_leader_id     = 	$request->team_leader_id;
		$department->line_manager_id 	= 	$request->line_manager_id;
		$department->required_emp 		= $request->required_emp;
		$department->save();

		return redirect()->route('departments.index')->with('success', 'Department Updated');
	}

	public function delete(Request $request)
	{
		$department 	= 	Department::find($request->id);
		$this->authorize('delete', $department);
		$department->delete();
		return json_encode("done");
	}

	public function departmentEmployees()
	{
		$this->authorize('hrUpdateEmployee', new Employee());
		$data['departments'] 			= Department::withCount('employees')->paginate(20);
		$data['employeeDepartments']  	=  Employee::select('id','name','department_id')->get()->groupBy('department.name');
		return view('department.departmentEmployee', $data);
	}

	public function managerUpdate(Request $request)
	{
		// optional code need to be terminate

		$department     		= Department::find($request->departmentId);
		$managerRoleId			= Role::where('name','manager')->first()->id;
		$teamLeaderRoleId		= Role::where('name','Team Leader')->first()->id;

		if(!empty($request->old_manager))
		{
			$currentManager		= Employee::find($request->old_manager);

			if(!empty($currentManager) && count($currentManager->managerDepartments)==1)
			{
				$currentManager->user->roles()->detach([$managerRoleId]);
			}
		}
		if(!empty($request->old_team_leader))
		{
			$currentTeamLeader		= Employee::find($request->old_team_leader);
			if(!empty($currentTeamLeader) && count($currentManager->teamLeaderDepartments)==1)
			{
				$currentTeamLeader->user->roles()->detach([$managerRoleId]);
			}
		}
		if(!empty($request->teamleader))
		{
            $department->team_leader_id     =   $request->teamleader;
        }
		if(!empty($request->manager_id))
		{
			$manager 	= Employee::find($request->manager_id)->user;
			if(!$manager->hasRole('manager'))
			{
				$manager->roles()->attach($managerRoleId);
			}
		}
        if(!empty($request->teamleader))
        {
			$teamLeader 	= Employee::find($request->teamleader)->user;
			if(!$teamLeader->hasRole('Team Leader'))
			{
				$teamLeader->roles()->attach($teamLeaderRoleId);
			}
        }
        
		$department->manager_id	= $request->manager_id;

		$department->save();
		return 'done';
	}
}
