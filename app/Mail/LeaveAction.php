<?php

namespace App\Mail;

use App\User;
use App\Models\Leave;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Contracts\Queue\ShouldQueue;

class LeaveAction extends Mailable
{
    use Queueable, SerializesModels;

    public $leave;
    public $user;

    /**
     * Create a new message instance.
     */
    public function __construct(Leave $leave,User $user)
    {
        //
        $this->leave   = $leave;
        $this->user     = $user;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Leave Action',
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
        //return $this->markdown('email.leave')
        return $this->view('email.leave')
        ->with('leave',$this->leave)
        ->with('user',$this->user)
        ->subject('Leave Approved');
    }
}