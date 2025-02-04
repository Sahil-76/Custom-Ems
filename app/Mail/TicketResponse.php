<?php

namespace App\Mail;

use App\Models\Ticket;
use App\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TicketResponse extends Mailable
{
    use Queueable, SerializesModels;

    public $ticket;
    public $user;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Ticket $ticket, User $user)
    {
        $this->ticket   = $ticket;
        $this->user     = $user;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        // return $this->markdown('emails.tickets.ticketResponse')
        return $this->view('email.tickets.response1')
        ->with('ticket',$this->ticket)
        ->with('user',$this->user)
        ->subject('Ticket Responded');
    }
}
