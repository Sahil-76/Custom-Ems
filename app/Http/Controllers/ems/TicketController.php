<?php

namespace App\Http\Controllers\ems;

use App\User;
use App\Models\Ticket;
use App\Models\TicketLog;
use App\Mail\TicketClosed;
use App\Mail\TicketRaised;
use App\Models\TicketType;
use Illuminate\Support\Str;
use App\Mail\TicketResponse;
use Illuminate\Http\Request;
use App\Mail\TicketForwarded;
use Illuminate\Support\Carbon;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Support\Facades\Storage;

class TicketController extends Controller
{
    public $mailer;
    public $bcc;
    public $dateFrom;
    public $dateTo;
    public $teamOnly=false;

    public function __construct(Mailer $mailer)
    {
        $this->mailer = $mailer;
    }
    
    public function index(){
        $data['tickets'] = Ticket::with('type')->where('created_by', auth()->user()->id)->get();
        return view('Ticket.index', $data);
    }

    public function store(Request $request)
    {
        // $exists = $this->isDuplicateTicket($request);

        // if ($exists) {
        //     return back()->with('failure', 'Too Many Attempts.');
        // }

        $ticketUser = Auth::user();

        $ticket = new Ticket();
        $ticket->priority = $request->priority;
        $ticket->description = $request->description;
        $ticket->type_id = $request->type;
        $ticket->status = 'New';

        if ($request->has('attachment')) {
            $relatedFiles = [];
            foreach ($request->file('attachment') as $file) {
                $name   = $file->getClientOriginalName();
                $name   = Str::random(10) . '-' . $name;
                Storage::disk('local')->putFileAs("ticket", $file, $name);

                $relatedFiles[] = $name;
            }

            $ticket->files = json_encode($relatedFiles);
        }
        $ticket->created_by = $ticketUser->id;
        $ticket->save();

        $log = new TicketLog();
        $log->ticket_id = $ticket->id;
        $log->status = 'New';
        $log->description = $request->description;
        $log->action_by = $ticketUser->id;
        $log->save();

        //Email Working

        $type               = TicketType::with('responsibleUsers')->find($request->type);

        $data['user']       = $ticketUser;
        $data['ticket']     = $ticket;
        $data['to']         = $ticketUser->email;

        $responsibleUsers   = $type->responsibleUsers()->active()->pluck('email')->toArray();
        if (!empty($responsibleUsers)) {
            $data['bcc']    = $responsibleUsers;
        }

        $message = (new TicketRaised($ticket, $ticketUser))->onQueue('emails');

        Mail::to(auth()->user()->email)->later(now()->addSeconds(1), $message);



        return back()->with('success', 'Ticket Raised.');
    }

    public function show($id)
    {
        $currentUser            = Auth::user();

        $ticket                 = Ticket::with(['createdBy.employee', 'type.responsibleUsers', 'logs' => function ($query) {
            $query->with('actionBy')->orderBy('created_at', 'desc');
        }]);

        // if (!$currentUser->hasRole('Ticket Responsible') && !$currentUser->hasRole('admin') && $this->teamOnly == false) {
        //     $ticket = $ticket->where('created_by', $currentUser->id);
        // }elseif($this->teamOnly){
        //     $ticket = $ticket->whereHas('createdBy', function($user){
        //         $user->whereHas('employee', function($employee){
        //             $employee->meOrTeam();
        //         });
        //     });
        // }

        $ticket                         = $ticket->findOrFail($id);
        $responsibleUsers               = $ticket->type->responsibleUsers->pluck('name', 'id')->toArray();
        $data['currenResponsibleUser']  = array_key_exists(auth()->user()->id, $responsibleUsers);
        $data['responsibles']           = empty($responsibleUsers) ? [] : implode(',', $responsibleUsers);
        
        $data['ticketTypes']            = TicketType::active()->where('name', '<>', $ticket->type->name)->pluck('name', 'id')->toArray();
        $data['ticket']         =   $ticket;
        // $data['mentionUserUrl'] = route('getProvideUsers', ['roles' => ['Ticket Responsible']]);
        $data['teamOnly']  = $this->teamOnly;

        return view('Ticket.show', $data);
    }




    public function list(Request $request)
    {
        $this->teamOnly = false;

        if ($request->ajax()) {
            $count                                       = !empty(request()->count) ? request()->count : 2;

            if ($request->has('ticket_type_load')) {
                $tickets = $this->loadMoreTickets($request->ticket_type_load, $count);
            } else {
                $tickets['ticketNew']           = $this->loadMoreTickets('new_tickets', $count);
                $tickets['ticketNeedResponse']  = $this->loadMoreTickets('need_tickets', $count);
                $tickets['ticketResponded']     = $this->loadMoreTickets('responded_tickets', $count);
                $tickets['ticketWaitingOnDecision']      = $this->loadMoreTickets('waiting_on_decision', $count);
                $tickets['ticketClosed']        = $this->loadMoreTickets('closed_tickets', $count);
            }
            return $tickets;
        }
        if ($this->teamOnly) {
            $data['users']              =   User::active()->whereHas('employee', function ($employee) {
                $employee->meOrTeam();
            })->pluck('name', 'id')->toArray();
        } else {

            $data['users']              =   User::pluck('name', 'id')->toArray();
        }
        $data['responsibleUsers']   =   User::havingRole('Ticket Responsible', 'name');
        $data['priorities']         =   ['Low' => 'Low', 'Medium' => 'Medium', 'High' => 'High'];
        $data['ticketTypes']        =   ['New', 'Need Response', 'Responded', 'Waiting on Decision', 'Closed'];
        $data['types']              =   TicketType::pluck('name', 'id')->toArray();
        // $data['branches']           =   Branch::pluck('name', 'name')->toArray();
        // $data['regions']            =   Region::pluck('name', 'name')->toArray();
        $data['teamOnly']           =   $this->teamOnly;

        return view('Ticket.list', $data);
    }

    private function loadMoreTickets($type, $count)
    {
        switch ($type) {
            case 'new_tickets':
                $status = 'New';
                $blade  = 'ticketNew';
                break;
            case 'need_tickets':
                $status = 'Need Response';
                $blade  = 'ticketNeedResponse';
                break;
            case 'responded_tickets':
                $status = 'Responded';
                $blade  = 'ticketResponded';
                break;
            case 'waiting_on_decision':
                $status = 'Waiting on Decision';
                $blade  = 'ticketWaitingOnDecision';
                break;
            case 'closed_tickets':
                $status = 'Closed';
                $blade  = 'ticketClosed';
                break;
        }

        if(!auth()->user()->hasRole('admin') && !auth()->user()->hasRole('hr') && auth()->user()->hasRole('Ticket Responsible')){  
        $tickets            =   Ticket::whereHas('type.responsibleUsers',function($user) {
                $user->where('users.id',auth()->user()->id);
            })->with('type.responsibleUsers', 'createdBy')->where('status', $status)->orderBy('created_at', 'desc');
        }
        else {
            $tickets            =   Ticket::with('type.responsibleUsers', 'createdBy')->where('status', $status)->orderBy('created_at', 'desc');
        }

        // if($status == 'Responded'){
        //     dd($tickets->get());
        // }
        $tickets            =   $this->filter($tickets);

        $render[$blade]     =   $tickets->paginate($count);
        return view('Ticket.' . $blade, $render)->render();
    }

    private function filter($tickets)
    {

        // if (auth()->user()->cannot('waitingOnDecision', new Ticket())) {
        //     $tickets->whereHas('type', function ($query) {
        //         $query->whereNotIn('ticket_types.name', ['HR Queries', 'HR Queries - Incentives']);
        //     });
        // }

        if (request()->filled('ticket_no')) {
            $tickets->where('id', request()->ticket_no);
        }
        if (!empty(request()->get('priority'))) {
            $tickets->where('priority', request()->priority);
        }
        if (!empty(request()->get('type_id'))) {
            $tickets->where('type_id', request()->type_id);
        }
        if (!empty(request()->get('type_name')) && request()->type_name!='Total') {
            $tickets->whereHas('type', function($type){
                $type->where('name', request()->type_name);
            });
        }
        if (!empty(request()->get('dateTo')) || !empty(request()->get('dateFrom'))) {
            $tickets->whereDate('created_at', '<=', request()->dateTo)
                ->whereDate('created_at', '>=', request()->dateFrom);
        }
        if (!empty(request()->get('responsibleUser'))) {
            $tickets->whereHas('type', function ($query) {
                $query->wherehas('responsibleUsers', function ($responsibleUsers) {
                    $responsibleUsers->where('user_id', request()->responsibleUser);
                });
            });
        }

        if (!empty(request()->get('created_by'))) {
            $tickets->where('created_by', request()->created_by);
        }


        return $tickets;
    }

    public function update(Request $request, $id)
    {
        $ticket             = Ticket::findOrFail($id);

        if (auth()->user()->id == $ticket->created_by) {
            $status =   'Need Response';
        } else {
            $status =   'Responded';
        }

        $ticket->status =   $status;
        $ticket->save();

        $ticketLog = TicketLog::create([
            'ticket_id'     => $ticket->id,
            'status'        => $status,
            'description'   => $request->comment,
            'action_by'     => auth()->user()->id,
        ]);
        if ($request->email == 'TKA') {
            $users       = $ticket->type->responsibleUsers;
            foreach ($users as $user) {
                $data['to']     = $user->email;
                $data['ticket'] = $ticket;
                $data['user']   = $user;

                // EmailController::request_email('Ticket Response Notification', $data);

                $message    = (new TicketResponse($ticket, $user))->onQueue('emails');

                $this->mailer->to($user)->later(Carbon::now()->addSeconds(10), $message);
            }
        } else {
            $data['to']     = $request->email;
            $data['ticket'] = $ticket;
            $data['user']   = $ticket->createdBy;

            // EmailController::request_email('Ticket Response Notification', $data);

            $user       =  User::where('email', $request->email)->first();
            $message    = (new TicketResponse($ticket, $user))->onQueue('emails');

            $this->mailer->to($user)->later(Carbon::now()->addSeconds(10), $message);
        }

        if ($request->has('documents')) {

            $relatedFiles = [];

            // dd($request->file('documents'));
            foreach ($request->file('documents') as $file) {
                $name       =  $file->getClientOriginalName();
                $name       =  str_replace(' ', '', Carbon::now()->format('dmyHis') . '_' . $name);
                $name       =  str_replace('-', '_', $name);
                $name       =  removeSpecialCharacter($name);
                Storage::disk('local')->putFileAs('/ticket/', $file, $name);

                $relatedFiles[] = $name;
            }

            $filesArray  = json_encode($relatedFiles);
            $ticketLog->document =  $filesArray;
            $ticketLog->save();
        }


        return back()->with('success', 'Comment Added.');
    }

    public function ticketForward(Request $request)
    {
        $ticketUser             = auth()->user();
        $ticket                 = Ticket::findOrFail($request->ticket_id);
        $currentType            = $ticket->type->name;
        $ticket->type_id        = $request->ticket_type;
        $ticket->save();
        $type               = TicketType::with('responsibleUsers')->find($ticket->type_id);
        $log                = new TicketLog();
        $log->ticket_id     = $ticket->id;
        $log->status        = 'New';
        $log->description   = 'Ticket Forwarded from '.$currentType.' to ' . $type->name;
        $log->action_by     = $ticketUser->id;
        $log->save();

        if ($type->responsibleUsers->isNotEmpty()) {
            foreach ($type->responsibleUsers as $user) {
                $message = (new TicketForwarded($ticket, $user))->onQueue('emails');
                $this->mailer->to($user)->later(Carbon::now()->addSeconds(10), $message);
            }
        }
        return back()->with('success', 'Ticket Forwarded');
    }

    public function statusUpdate($id)
    {
        $ticket             = Ticket::findOrFail($id);
        $ticket->status     = 'Waiting on Decision';
        $ticket->save();

        return back()->with('success', 'Ticket Updated.');
    }
    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $ticket                 =   Ticket::findOrFail($id);
        $ticket->status         =   'Closed';
        $ticket->save();

        $user                   =   Auth::user();
        $createdBy              =   $ticket->createdBy;
        TicketLog::create([
            'ticket_id'     =>  $ticket->id,
            'status'        =>  'Closed',
            'description'   =>  'Ticked Closed',
            'action_by'     =>  $user->id
        ]);
        if ($user->id == $ticket->created_by) {
            $route = 'ticket.index';
        } else {

            $data['to']     = $createdBy->email;
            $data['ticket'] = $ticket;
            $data['user']   = $createdBy;

            // EmailController::request_email('Ticket Closed Notification', $data);

            $message    = (new TicketClosed($ticket, $user))->onQueue('emails');

            $this->mailer->to($user)->later(Carbon::now()->addSeconds(10), $message);
            $route = 'ticket.list';
        }

        return redirect()->route($route)->with('success', 'Ticket Closed.');
    }

        
    function documentFiles(Request $request)
    {
 
        $documents =  TicketLog::find($request->id)->document;
        return json_encode($documents);
    }


    public function dashboard(Request $request)
{
    if (request()->filled('dateFrom') && request()->filled('dateTo')) {
        $this->dateFrom = request()->dateFrom;
        $this->dateTo = request()->dateTo;
    } else {
        $this->dateFrom = Carbon::today()->subWeeks(4)->format('Y-m-d');
        $this->dateTo = Carbon::today()->format('Y-m-d');
    }

    $types = TicketType::whereHas('tickets', function ($query) {
        $query->whereDate('created_at', '>=', $this->dateFrom)->whereDate('created_at', '<=', $this->dateTo);
    })->with(['tickets' => function ($query) {
        $query->whereDate('created_at', '>=', $this->dateFrom)->whereDate('created_at', '<=', $this->dateTo);
    }])->get();

    $tickets = Ticket::whereDate('created_at', '>=', $this->dateFrom)->whereDate('created_at', '<=', $this->dateTo)
        ->select('status', 'created_at')->get();

    $data['total'] = $tickets->count();
    $data['new'] = $tickets->where('status', 'New')->count();
    $data['responded'] = $tickets->where('status', 'Responded')->count();
    $data['needResponse'] = $tickets->where('status', 'Need Response')->count();
    $data['waitingOnDecision'] = $tickets->where('status', 'Waiting on Decision')->count();
    $data['closed'] = $tickets->where('status', 'Closed')->count();

    $barChartTypes = [];
    foreach ($types as $type) {
        $chart['type'] = $type->name . " (" . $type->tickets->count() . ")";
        $chart['new'] = $type->tickets->where('status', 'New')->count();
        $chart['responded'] = $type->tickets->where('status', 'Responded')->count();
        $chart['needResponse'] = $type->tickets->where('status', 'Need Response')->count();
        $chart['waitingOnDecision'] = $type->tickets->where('status', 'Waiting on Decision')->count();
        $chart['closed'] = $type->tickets->where('status', 'Closed')->count();
        $chart['total'] = $type->tickets->count();

        $barChartTypes[] = $chart;
    }

    $pieChart = $this->pieChart($tickets);
    $data['pieChartValues'] = $pieChart['values'];
    $data['pieChartLabels'] = $pieChart['labels'];
    $data['type'] = $types->first();

    $data['barChart'] = collect($barChartTypes)->sortByDesc('total')->values();
    $data['userTickets'] = $this->userTickets();
    $data['dateFrom'] = $this->dateFrom;
    $data['dateTo'] = $this->dateTo;

    

    return view('Ticket.dashboard', $data);
}

public function pieChart($tickets)
    {
        $pieChart           = [];
        $new                = $tickets->where('status', 'New')->count();
        $responded          = $tickets->where('status', 'Responded')->count();
        $needResponse       = $tickets->where('status', 'Need Response')->count();
        $waitingOnDecision  = $tickets->where('status', 'Waiting on Decision')->count();
        $closed             = $tickets->where('status', 'Closed')->count();

        $labels             = ["New ($new)", "Responded ($responded)", "Need Response ($needResponse)", "Waiting on Decision ($waitingOnDecision)", "Closed ($closed)"];

        $pieChart           = [$new, $responded, $needResponse, $waitingOnDecision, $closed];

        return ['labels' => $labels, 'values' => $pieChart];
    }




    public function userTickets()
    {
        $types = TicketType::pluck('name', 'id')->toArray();
        $users = User::whereHas('tickets', function ($tickets) {
            $tickets->whereDate('created_at', '>=', $this->dateFrom)
                ->whereDate('created_at', '<=', $this->dateTo);
        })->with(['tickets' => function ($tickets) {
            $tickets->whereDate('created_at', '>=', $this->dateFrom)
                ->whereDate('created_at', '<=', $this->dateTo)
                ->with('type');
        }])->get();

        $array = [];
        $usersArray = [];
        foreach ($users as $user) {
            $usersArray['name'] = $user->name;
            foreach ($types as $id => $type) {
                $usersArray[$type] = $user->tickets->where('type_id', $id)->count();
            }
            $usersArray['Total'] = $user->tickets->count();
            $array[] = $usersArray;
        }

        $types = array_merge($types, ['total' => 'Total']);

        return ['types' => $types, 'tickets' => $array];
    }
}
