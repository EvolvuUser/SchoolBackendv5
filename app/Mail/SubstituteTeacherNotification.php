<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SubstituteTeacherNotification extends Mailable
{
    use Queueable, SerializesModels;
    public $substitutionData;

    /**
     * Create a new message instance.
     */
    public function __construct($substitutionData)
    {
        $this->substitutionData = $substitutionData;
    }

    /**
     * Get the message envelope.
     */
    

    /**
     * Get the message content definition.
     */
   

     public function build()
     {
         // dd("Hello");
         return $this->view('emails.substitute_teacher')
                     ->with('substitution', $this->substitutionData);
     }
}
