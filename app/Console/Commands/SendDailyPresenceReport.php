<?php

namespace App\Console\Commands;

use App\Models\Site;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\HtmlString;

class SendDailyPresenceReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'presence:send-daily-report';

    protected $description = 'Génère et envoie le rapport PDF de présence chaque jour à 10h';
    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $date = Carbon::today()->setTimezone("Africa/Kinshasa")->startOfDay();

        $yesterday = $date->copy()->subDay();

        $sites = Site::with([
            'presences' => function ($query) use ($date, $yesterday) {
                $query->whereIn('date_reference', [
                    $date->toDateString(),
                    $yesterday->toDateString()
                ]);
            },
            'presences.agent.groupe.horaire',
            'agents',
            'secteur'
        ])->get();

        $totalPresences = 0;
        $totalAgents = 0;

        foreach ($sites as $site) {
            $presenceAttendue = $site->presence ?? 0;
            $totalAgents += $presenceAttendue;

            $filteredPresences = $site->presences->filter(function ($presence) use ($date) {
                $horaire = optional($presence->agent->groupe)->horaire;
                if (!$horaire) return false;

                try {
                    $presenceDate = Carbon::parse($presence->date_reference)->startOfDay();
                    $heureDebut = Carbon::parse($horaire->started_at);
                    $heureFin   = Carbon::parse($horaire->ended_at);
                } catch (\Exception $e) {
                    Log::error("⛔ Erreur parsing horaire via groupe de l'agent : " . $e->getMessage());
                    return false;
                }

                $isHoraire24h = $heureDebut->equalTo($heureFin);
                $shiftDeNuit  = $heureFin->lessThan($heureDebut);

                if ($isHoraire24h || $shiftDeNuit) {
                    // Présences liées à la veille pour horaires 24h/nuit
                    return $presenceDate->equalTo($date->copy()->subDay());
                }
                // Shifts de jour
                return $presenceDate->equalTo($date);
            })->values();

            $site->presences = $filteredPresences;
            $site->presences_count = $filteredPresences->count();
            $site->presence_expected = $presenceAttendue;
            $site->presence_rate = $presenceAttendue > 0 
                ? round(($filteredPresences->count() / $presenceAttendue) * 100, 1)
                : 0;

            $totalPresences += $site->presences_count;
        }

        $pdf = Pdf::loadView('pdf.reports.presence_simple_report', [
            'sites' => $sites,
            'totalPresences' => $totalPresences,
            'totalAgents' => $totalAgents,
            'date' => $date,
        ]);

        $filename = 'rapport-presence-' . $date->format('Y-m-d') . '.pdf';
        $pdfContent = $pdf->output();

        $emails = [
            'gradi.ikundomonsaba@suptech.tn',
            'gastondelimond@gmail.com',
            'lionnelnawej11@gmail.com',
        ];

        foreach ($emails as $email) {
            Mail::raw("Veuillez trouver ci-joint le rapport de présence du {$date->format('d/m/Y')}.", function ($message) use ($email, $pdfContent, $filename, $date) {
                $message->to($email)
                        ->subject("Rapport de présence du " . $date->format('d/m/Y'))
                        ->attachData($pdfContent, $filename, [
                            'mime' => 'application/pdf',
                        ]);
            });
        }

        $this->info("✅ Rapport envoyé aux destinataires avec succès.");
        return 1;
    }

}
