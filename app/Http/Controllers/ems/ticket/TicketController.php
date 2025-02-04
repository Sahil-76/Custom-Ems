<?php

namespace App\Http\Controllers\ems\ticket;

use App\User;
use Carbon\Carbon;
use App\Mail\Action;
use App\Models\Asset;
use App\Models\Ticket;
use App\Models\Employee;
use App\Models\ItemRequestAssign;
use App\Models\Software;
use App\Models\TicketLog;
use App\Models\Department;
use Illuminate\Support\Str;
use App\Models\AssetSubType;
use Illuminate\Http\Request;
use App\Models\TicketCategory;
use Yajra\DataTables\DataTables;
use App\Http\Controllers\Controller;
use App\Http\Requests\TicketRequest;
use Illuminate\Contracts\Mail\Mailer;

class TicketController extends Controller
{
    public function __construct(Mailer $mailer)
    {
        $this->mailer = $mailer;
    }

    public function ticketRaiseForm($type = null)
    {
        if ($type == 'IT')
        {
            $data['assetSubTypes']          =   AssetSubType::where('is_assignable', 1)->get();
            $data['priority']               =   ['Low' => 'Low', 'Medium' => 'Medium', 'High' => 'High'];
            $data['submitRoute']            =   'ticketRaise';
            $data['ticketCategories']       =   TicketCategory::orderBy('name')->where('type', 'IT')->pluck('name', 'name')->toArray();
            $data['employeeDepartments']    =   User::with('employee')->where('is_active', 1)->whereHas('employee', function ($employee) {
                                                $employee->where('department_id', auth()->user()->employee->department_id)->select('biometric_id', 'name');
                                                })->get()->groupBy('employee.department.name');

            return view('tickets.ticketRaiseForm', $data);
        } 
        else 
        {
            $data['ticketCategories']   =   TicketCategory::orderBy('name')->where('type', 'HR')->pluck('name', 'name')->toArray();
            $data['priority']           =   ['Low' => 'Low', 'Medium' => 'Medium', 'High' => 'High'];
            $data['submitRoute']        =   'ticketRaise';

            return view('tickets.hrTicketForm', $data);
        }
    }

    public function ticketRaise(TicketRequest $request)
    {
        if (!empty($request->department) && $request->department == 'IT') 
        {
            $category                   =       TicketCategory::where('type', 'IT')->where('name', $request->category)->first();
        } 
        else 
        {
            $category                   =       TicketCategory::where('type', 'HR')->where('name', $request->category)->first();
        }

        $ticket                         =       new Ticket();
        $ticket->ticket_category_id     =       $category->id;
        $ticket->subject                =       $request->subject;
        $ticket->description            =       $request->description;
        $ticket->priority               =       $request->priority;
        $ticket->user_id                =       !empty($request->user_id) ? $request->user_id : auth()->user()->id;
        $ticket->raised_by              =       auth()->user()->id;
        $ticket->asset_sub_type_id      =       $request->asset_sub_type_id;
        $ticket->model_no               =       $request->model_no;
        $ticket->save();

        $user_ids                       =       User::havingRole('ticketManager');

        $notificationReceivers          =       [];
        $email                          =       [];

        if ($category->type == 'IT') 
        {
            $users                      =       User::whereIn('id', $user_ids)->whereHas('employee.department', function ($query) {
                                                $query->where('name', 'IT');
                                                })->get();
            $link                       =       route('ticketDashboard');
        }

        if ($category->type == 'HR') 
        {
            $users                      =       User::whereIn('id', $user_ids)->whereHas('employee.department', function ($query) {
                                                $query->where('name', 'HR');
                                                })->get()->where('email', '<>', 'martha.folkes@theknowledgeacademy.com');
            $link                       =       route('hrRaiseTicket');
        }
        if ($users->isNotEmpty()) 
        {
            $notificationReceivers      =       $users->pluck('id', 'id')->toArray();

            $email                      =       $users->pluck('email', 'email')->toArray();
        }

        $subject                        =       "Ticket No. " . $ticket->id . " Raised by " . auth()->user()->name;

        send_notification($notificationReceivers, $subject, $link);

        $data['ticket']                 =       $ticket;
        $data['ticketLog']              =       null;
        $data['link']                   =       $link;
        $data['message']                =       $subject;
        $data['users']                  =       $users;
        $message                        =       $subject;
        $message                        =       (new Action($users, $data, $subject, 'email.ticket'))->onQueue('emails');

        $this->mailer->to($email)->later(Carbon::now(), $message);

        return redirect()->back()->with('success', 'Ticket Raised Successfully');
    }

    public function myTickets()
    {

        $data['tickets']                =       Ticket::with('actionBy', 'ticketCategory', 'raisedBy','assetSubType')->where('user_id', auth()->user()->id)->orderByRaw("Field(status,'Pending','Assigned','Sorted', 'Closed')")
                                                ->orderBy('id', 'desc')->get();

        return view('tickets.myTickets', $data);
    }

    public function hrRaiseTicket(Request $request)
    {
        $this->authorize('hrDashboard', new User());
        
        $tickets                        =      Ticket::with('user.employee.department', 'ticketCategory', 'ticketLogs')
                                                ->whereNotIn('status', ['Sorted', 'Closed'])->whereHas('user', function ($query) {
                                                    $query->where('is_active', '1');
                                                })->orderByRaw("Field(status,'Pending','Reopen','Assigned','Forward')");

        $data['ticketCategory']         =       TicketCategory::where('type', 'HR')->pluck('name', 'name')->toArray();
        $data['priority']               =       ['Low' => 'Low', 'Medium' => 'Medium', 'High' => 'High'];
        $data['users']                  =       User::where('id', '<>', auth()->user()->id)->where('user_type', 'Employee')->with('employee.department')->wherehas('roles', function ($query) {
                                                $query->where('name', 'ticketSolver');
                                                })->get();

        $tickets                        =       $tickets->wherehas('ticketCategory', function ($query) {
                                                    $query->where('type', 'HR');
                                                });

        $data['departments']            =       Department::pluck('name','id')->toArray();
        $data['employeeDepartments']  	=       Employee::with('user')->select('biometric_id','user_id','department_id','name')->whereHas('user',function($user)
                                                {
                                                    $user->where('is_active',1)->where('user_type','Employee');
                                                })->get()->groupBy('department.name');

        $tickets                        =       $this->filter($tickets);

        $data['tickets']                =       $tickets ->get();

        return view('tickets.raiseTicketList', $data);
    }

    public function ticketHistory()
    {
        $this->authorize('ticketHistory', new Ticket());

        $tickets = Ticket::select(
            'ticket.*',
            'users.name as user_name',
            'departments.name as department_name',
            'ticket_categories.type as ticket_type',
            'ticket_categories.name as category'
        )
            ->leftJoin('users', 'ticket.user_id', '=', 'users.id')
            ->leftJoin('employee', 'employee.user_id', '=', 'users.id')
            ->leftJoin('ticket_categories', 'ticket_categories.id', '=', 'ticket.ticket_category_id')
            ->leftJoin('departments', 'departments.id', '=', 'employee.department_id');

        $tickets    = $tickets->whereIn('status', ['Closed', 'Sorted']);

        if (request()->ajax()) {
            return DataTables::of($tickets)
                ->addColumn('assignedBy', function ($tickets) {
                    return $tickets->assignedBy();
                })
                ->editColumn('created_at', function ($tickets) {
                    return getFormatedDateTime($tickets->created_at);
                })
                ->editColumn('ticket_type', function ($tickets) {
                    return ucfirst($tickets->ticket_type);
                })
                ->editColumn('category', function ($tickets) {
                    return ucfirst($tickets->category);
                })
                ->addColumn('description', function ($tickets) {
                    return '<textarea cols="15" rows="3" disabled>' . Str::before($tickets->description, " AnyDesk :") . '</textarea>';
                })
                ->filterColumn('department_name', function ($query, $keyword) {
                    $query->where('departments.name', 'like', "%$keyword%");
                })
                ->filterColumn('user_name', function ($query, $keyword) {
                    $query->where('users.name', 'like', "%$keyword%");
                })
                ->filterColumn('ticket_type', function ($query, $keyword) {
                    $query->where('ticket_categories.type', 'like', "%$keyword%");
                })
                ->filterColumn('category', function ($query, $keyword) {
                    $query->where('ticket_categories.name', 'like', "%$keyword%");
                })
                ->rawColumns(['assignedBy', 'description'])
                ->make(true);
        }
        $data['tickets'] = $tickets;

        return view('tickets.ticketHistory', $data);
    }

    public function raiseTicketAction(Request $request)
    {
        $ticket            = Ticket::find($request->id);
        $ticket->status    = $request->action;
        $ticket->save();

        $ticketLog               = new TicketLog();
        $ticketLog->ticket_id    = $ticket->id;
        $ticketLog->action       = $request->action;
        $ticketLog->assigned_to  = $request->assigned_to ?? null;
        $ticketLog->remarks      = $request->remarks ?? null;
        $ticketLog->action_by    = auth()->user()->id;
        $ticketLog->save();
        switch ($request->action) {
            case ($request->action == 'Assigned' || $request->action == 'Forward'):

                $notificationReceivers  = [$ticketLog->assignedTo->id];
                $email                  =   [$ticketLog->assignedTo->email];
                $link                   = route('assignedTicket');
                break;

            case 'Sorted':
                $notificationReceivers  = [$ticket->user_id];
                $email                  = [$ticket->user->email];
                $link                   = route('myTickets');
                break;


            case 'Reopen':
                $user_ids       = User::havingRole('ticketManager');
                if ($ticket->ticketCategory->type == 'IT') {
                    $users = User::whereIn('id', $user_ids)->whereHas('employee.department', function ($query) {
                        $query->where('name', 'IT');
                    })->get();
                } else {
                    $users = User::whereIn('id', $user_ids)->whereHas('employee.department', function ($query) {
                        $query->where('name', 'HR');
                    })->get()->where('email', '<>', 'martha.folkes@theknowledgeacademy.com');
                }
                if ($users->isNotEmpty()) {
                    $notificationReceivers  = $users->pluck('id', 'id')->toArray();

                    $email  = $users->pluck('email', 'email')->toArray();
                }
                $link   = route('hrRaiseTicket');
                break;
        }

        $subject        = "Ticket No. " . $ticket->id . " " . $request->action . " by " . auth()->user()->name;
        // $email  = $employees->pluck('office_email','office_email')->toArray();
        if ($request->action != 'Closed') {
            $data['ticket']     = $ticket;
            $data['ticketLog']  = $ticketLog;
            $data['link']       = $link;
            $message            = $subject;
            $data['message'] = $subject;
            send_notification($notificationReceivers, $subject, $link);
            // send_email("email.ticket", $data, $subject, $message,$email,null);

            $message = (new Action(null, $data, $subject, 'email.ticket'))->onQueue('emails');
            $this->mailer->to($email)->later(Carbon::now(), $message);
        }
        return back()->with('success', 'Action Performed');
    }

    public function assignedTickets()
    {
        $data['assignedTickets']  =  Ticket::with('ticketLogs.actionBy', 'user.employee.department')->whereHas('ticketLogs', function ($query) {
            $query->where('assigned_to', auth()->user()->id);
        })->get();

        return view('tickets.assignedTickets', $data);
    }

    public function ticketDetail(Request $request, $id)
    {

        $ticketDetail           =       Ticket::with('ticketLogs.actionBy','assetSubType' ,'ticketLogs.assignedTo', 'user.assetAssignments', 'ticketCategory');
        $data['ticketLogs']     =       TicketLog::with('actionBy', 'assignedTo')->where('ticket_id', $id)->orderBy('created_at', 'desc')->get();
        $data['status']         =       array('Sorted' => 'Sorted', 'Forward' => 'Forward');
        $data['users']          =       User::where('id', '<>', auth()->user()->id)->with('employee.department')->wherehas('roles', function ($query) {
                                        $query->where('name', 'ticketSolver');
                                        })->get();

        if (auth()->user()->hasRole('powerUser')) 
        {
            $ticketDetail       =       $ticketDetail->whereHas('user.employee', function ($employee) {
                                        $employee->where('department_id', auth()->user()->employee->department_id);
                                        })->find($id);
        } 
        elseif (auth()->user()->hasRole('ticketSolver')) 
        {
            $ticketDetail       =       $ticketDetail->find($id);
        } else 
        {
            $ticketDetail       =       $ticketDetail->where('user_id', auth()->user()->id)->find($id);
        }

        if (empty($ticketDetail)) {
            abort(404);
        }
        $data['barcode']        =       null;

        if($ticketDetail->user->assetAssignments->isNotEmpty())
        {
            $data['barcode']    =       $ticketDetail->user->assetAssignments->
                                        where("sub_type_id",$ticketDetail->asset_sub_type_id)->first()->barcode ?? null;
        }

        $data['ticketDetail']   =       $ticketDetail;

        return view('tickets.ticketDetail', $data);
    }

    public function cancelEquipmentProblem(Request $request)
    {
        Ticket::find($request->id)->update(['status' => 'Closed']);
    }

    public function sendReminder()
    {
        $pendingTickets = Ticket::whereHas('user', function ($user) {


            $user->where('is_active', '1');
        })->where('status', 'Pending')->wherehas('ticketCategory', function ($query) {

            $query->where('type', 'IT');
        })->get();

        if ($pendingTickets->isNotEmpty()) {
            $user_ids               = User::havingRole('IT');
            $link                   = route('hrRaiseTicket');
            $data['pendingTickets'] = $pendingTickets;
            $data['link']           = $link;
            $subject                = "Pending Tickets";
            $message                = "Pending Tickets";
            $email                  = User::find($user_ids)->pluck('email')->toArray();
            send_email("email.pendingTickets", $data, $subject, $message, $email, null);
        }

        return "done";
    }
    //ticket category
    public function categoryView()
    {
        if (!auth()->user()->hasRole('admin')) {
            abort(403);
        }
        return view('tickets.ticketCategoryList');
    }

    public function categoryList(Request $request)
    {

        $pageIndex                  =        $request->pageIndex;
        $pageSize                   =        $request->pageSize;
        $ticketCategory             =        TicketCategory::query();

        if (!empty($request->get('name'))) 
        {
            $ticketCategory         =       $ticketCategory->where('name', 'like', '%' . $request->get('name') . '%');
        }
        if (!empty($request->get('type'))) 
        {
            $ticketCategory         =       $ticketCategory->where('type', 'like', '%' . $request->get('type') . '%');
        }
        $data['itemsCount']         =       $ticketCategory->count();
        $data['data']               =       $ticketCategory->limit($pageSize)->offset(($pageIndex - 1) * $pageSize)->get();

        return json_encode($data);
    }

    public function categoryInsert(Request $request)
    {
        $data                       =               new TicketCategory();
        $data->name                 =               $request->name;
        $data->type                 =               $request->type;

        $data->save();

        return json_encode($data);
    }

    public function categoryUpdate(Request $request)
    {
        $ticketCategory                     =            $request->id;
        $data                               =            TicketCategory::find($ticketCategory);
        $data->name                         =            $request->name;
        $data->type                         =            $request->type;

        $data->save();

        return json_encode($data);
    }

    public function categoryDelete(Request $request)
    {
        $data     =     TicketCategory::find($request->id);

        $data->delete();

        return json_encode("done");
    }

    public function departmentTickets()
    {
        $this->authorize('powerUser', new User());
        
        $currentYear        =   Carbon::now()->year;
        $tickets            =   Ticket::with('user', 'ticketCategory')->whereHas('ticketCategory', function ($query) {
                                $query->where('type', 'IT');
                                })->whereHas('user.employee', function ($departments) {
                                $departments->where('department_id', auth()->user()->employee->department_id);
                                })->whereYear('created_at', '=', $currentYear);
                                
        $data['tickets']    =   $tickets->orderBy('created_at', 'desc')->get();

        return view('tickets.departmentTicket', $data);
    }

    public function getDepartmentTickets()
    {
        $currentYear = Carbon::now()->year;

        $tickets = Ticket::select('ticket.*', 'users.name as userName', 'ticket_categories.name as category', 'ticket_categories.type as TicketType', 'power_user.name as raisedBy')
            ->leftJoin('users', 'ticket.user_id', '=', 'users.id')
            ->leftJoin('users as power_user', 'ticket.raised_by', '=', 'power_user.id')
            ->leftJoin('ticket_categories', 'ticket.ticket_category_id', '=', 'ticket_categories.id');

        $tickets->where('ticket_categories.type', 'IT')->whereHas('user.employee', function ($departments) {
            $departments->where('department_id', auth()->user()->employee->department_id);
        })->whereYear('ticket.created_at', '=', $currentYear);

        return DataTables::of($tickets)
            ->addIndexColumn()
            ->addColumn('detail', function ($tickets) {
                $btn = '<a href="' . route("ticketDetail", ['id' => $tickets->id]) . '"class="btn btn-warning btn-lg p-3">Detail</a>  &nbsp;';
                return $btn;
            })
            ->filterColumn('userName', function ($query, $keyword) {
                $query->where('users.name', 'like', "%$keyword%");
            })
            ->filterColumn('category', function ($query, $keyword) {
                $query->where('ticket_categories.name', 'like', "%$keyword%");
            })
            ->filterColumn('TicketType', function ($query, $keyword) {
                $query->where('ticket_categories.type', 'like', "%$keyword%");
            })
            ->filterColumn('raisedBy', function ($query, $keyword) {
                $query->where('power_user.name', 'like', "%$keyword%");
            })
            ->rawColumns(['detail'])
            ->make(true);
    }

    public function dashboard(Request $request)
    {
        $this->authorize('ItTicketDashboard', new Ticket());
        if($request->ajax())
        {
            $count                              =       !empty(request()->count) ? request()->count :5;

            if($request->has('ticket_type_load'))
            {
                $tickets                        =       $this->loadMoreTickets($request->ticket_type_load, $count);
            }
            else
            {
                $tickets['ticketLaptop']        =       $this->loadMoreTickets('Laptop', $count);
                $tickets['ticketMouse']         =       $this->loadMoreTickets('Mouse', $count);
                $tickets['ticketHeadphone']     =       $this->loadMoreTickets('Headphone', $count);
                $tickets['ticketCharger']       =       $this->loadMoreTickets('Charger', $count);
                $tickets['ticketUPS']           =       $this->loadMoreTickets('UPS', $count);
            }

            return $tickets;
        }

        $data['categories']                     =       TicketCategory::pluck('name', 'id')->toArray();
        $data['types']                          =       TicketCategory::pluck('type', 'type')->toArray();
        $data['priorities']                     =       ['Low'=>'Low','Medium'=>'Medium','High'=>'High'];
        $data['ticketTypes']                    =       ['Laptop','Mouse','Headphone','Charger','UPS'];
        $data['departments']                    =       Department::pluck('name','id')->toArray();
        $data['employeeDepartments']  	        =       Employee::with('user')->select('biometric_id','user_id','department_id','name')->whereHas('user',function($user)
                                                        {
                                                            $user->where('is_active',1)->where('user_type','Employee');
                                                        })->get()->groupBy('department.name');

        return view('tickets.dashboard',$data);
    }

    private function loadMoreTickets($type, $count)
    {
        switch ($type) {
            case 'Laptop':
                $status = 'Laptop';
                $icon   =  'fa-laptop';
                break;
            case 'Mouse':
                $status = 'Mouse';
                $icon   =  'fa-mouse';
                break;
            case 'Headphone':
                $status = 'Headphone';
                $icon   =  'fa-headphones';
                break;
            case 'Charger':
                $status = 'Charger';
                $icon   =  'fa-battery-three-quarters';
                break;
            case 'UPS':
                $status = 'UPS';
                $icon   =  'fa-charging-station';
                break;
        }

        $tickets            =   Ticket::with('ticketLogs.actionBy', 'ticketLogs.assignedTo', 'user')->whereHas('assetSubType',function($q) use($status){
            $q->where('name',$status);
        })->whereIn('status',['Pending','Reopen'])->orderBy('created_at', 'desc');
        $data['subType']    =   $status;
        $data['icon']       =   $icon;
        $tickets            =   $this->filter($tickets);
        $data['tickets']    =   $tickets->paginate($count);
        $render['ticketAsset']     =   $tickets->paginate($count);
        return view('tickets.dashboard.' . 'ticketAsset', $render, $data)->render();
    }

    function filter($tickets)
    {
        if(request()->has('priority'))
        {
            $tickets->where('priority',request()->priority);
        }
        if(request()->has('dateTo') && request()->has('dateFrom') )
        {   
            $tickets->whereDate('created_at', '>=', request()->dateFrom)
                    ->whereDate('created_at', '<=', request()->dateTo);
        }
        if(request()->has('department')  )
        {   
            $tickets->whereHas('user.employee',function($query){
                $query->where('department_id',request()->department);
            });
        }
        if(request()->has('user')  )
        {   
            $tickets->where('user_id',request()->user);
        }

        return $tickets;
    }
}
