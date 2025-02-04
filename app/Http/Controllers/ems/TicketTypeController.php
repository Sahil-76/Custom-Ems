<?php

namespace App\Http\Controllers\ems;

use App\User;
use App\Models\TicketType;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class TicketTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {

        $ticketTypes            =   TicketType::with('responsibleUsers');
        $data['ticketTypes']    =   $ticketTypes->get();
        
        return view('Ticket.type.index',$data);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $data['users']              =   User::havingRole('Ticket Responsible','name');
        return view('Ticket.type.create', $data);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'user_ids'              =>  'required',
            'name'                  =>  ['required','unique:ticket_types,name,'],
        ]);
        $type             =   TicketType::create(['name' => $request->name, 'is_active' => isset($request->is_active) ? 1: 0]);
        $type->responsibleUsers()->sync($request->user_ids);

        return redirect()->route('ticket-type.index')->with('success','Ticket Type Created.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        
        $data['type']               =   TicketType::findOrFail($id);
        $data['users']              =   User::havingRole('Ticket Responsible','name');
        $data['responsibleUsers']   =   $data['type']->responsibleUsers;

        return view('Ticket.type.edit',$data);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'user_ids'              =>  'required',
            'name'                  =>  ['required','unique:ticket_types,name,' . $id . ',id'],
        ]);

        $type             =   TicketType::findOrFail($id);
        $type->name       =   $request->name;
        $type->is_active  =   isset($request->is_active) ? 1: 0;
        $type->save();
        $type->responsibleUsers()->sync($request->user_ids);


        return redirect()->route('ticket-type.index')->with('success','Ticket Type Updated.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

}
