<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MailNotify extends Mailable
{
    use Queueable, SerializesModels;

    public $titre, $photo, $agent, $site, $datetime;

    public function __construct($titre, $photo, $agent, $site, $datetime)
    {
        $this->titre = $titre;
        $this->photo = $photo;
        $this->agent = $agent;
        $this->site = $site;
        $this->datetime = $datetime;
    }

    public function envelope()
    {
        return new Envelope(
            subject: $this->titre
        );
    }

    public function content()
    {
        return new Content(
            view: 'emails.notify',
        );
    }

    public function attachments()
    {
        return [];
    }
}
