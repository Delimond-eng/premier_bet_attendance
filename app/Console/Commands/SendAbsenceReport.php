<?php

namespace App\Console\Commands;

use App\Models\Site;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use App\Services\AbsenceReportService;
use App\Mail\AbsenceReportPerSite;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class SendAbsenceReport extends Command
{
    protected $signature = 'report:absences';
    protected $description = 'Génère et envoie les rapports PDF des absences par site';

    public function handle(AbsenceReportService $service)
    {
        $packs   = $service->collectAbsences(Carbon::now('Africa/Kinshasa'));
        $now = Carbon::now()->setTimezone("Africa/Kinshasa");

        $pdf = Pdf::loadView('pdf.absence_site', [
            'packs' => $packs,
            'now'   => Carbon::now('Africa/Kinshasa'),
        ]);

        $emails = $this->getEmails();
        $to = array_map('trim', explode(';', $emails));
        Mail::to($to)->send(new AbsenceReportPerSite( $now, $pdf->output()));

        $this->info('Rapports envoyés.');
        return Command::SUCCESS;
    }


    private function getEmails(){
        $sites = Site::whereHas('areas')->get();

        $emails = $sites->pluck('emails')   
                ->filter()         
                ->map(fn($e) => explode(';', $e)) 
                ->flatten()         
                ->map(fn($e) => trim($e)) 
                ->unique()        
                ->implode(';');   
        return $emails;
    }
}
