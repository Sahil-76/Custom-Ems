<?php

namespace App\Mail;

use App\User;
use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Contracts\Queue\ShouldQueue;

class TicketForwarded extends Mailable
{
    use Queueable, SerializesModels;

    public $ticket;
    public $user;
    /**
     * Create a new message instance.
     */
    public function __construct(Ticket $ticket, User $user)
    {
        $this->ticket   = $ticket;
        $this->user     = $user;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Ticket Forwarded',
        );
    }

    /**
     * Get the message content definition.
     */
    // public function content(): Content
    // {
    //     return new Content(
    //         view: 'view.name',
    //     );
    // }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }

    public function build()
    {
        return $this->view('email.tickets.ticketForwarded')
        ->with('ticket',$this->ticket)
        ->with('user',$this->user)
        ->subject('Ticket Forwarded');
    }
}