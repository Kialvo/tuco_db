<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OutreachMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public string $subjectLine;
    public string $bodyText;

    public function __construct(string $subjectLine, string $bodyText)
    {
        $this->subjectLine = $subjectLine;
        $this->bodyText    = $bodyText;
    }

    public function build()
    {
        return $this->subject($this->subjectLine)
            ->view('emails.outreach')
            ->with([
                'bodyText' => $this->bodyText,
            ]);
    }
}
