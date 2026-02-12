<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Carbon\Carbon;

class AbsenceReportPerSite extends Mailable
{
    use Queueable, SerializesModels;

    public $now;
    public $pdfBinary;

    public function __construct(Carbon $now, string $pdfBinary)
    {
        $this->now = $now;
        $this->pdfBinary = $pdfBinary;
    }

    public function build()
    {
        $filename = 'rapport_absences_' . $this->now->format('Ymd_His') . '.pdf';

        return $this->subject("Rapport d'absences ". $this->now->format('d/m/Y H:i'))
            ->view('emails.absences.simple') // vue simple d'email
            ->attachData($this->pdfBinary, $filename, [
                'mime' => 'application/pdf'
            ]);
    }
}
