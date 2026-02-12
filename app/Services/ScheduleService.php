<?php
namespace App\Services;

use App\Http\Controllers\EmailController;
use App\Models\Schedules;
use App\Models\Patrol;
use App\Models\Area;
use App\Models\PatrolScan;
use App\Models\Site;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;

class ScheduleService
{
    /* public function verifySchedules()
    {
        $now = Carbon::now('Africa/Kinshasa');
        $toleranceMinutes = 5;

        $schedules = Schedules::where('status', 'actif')
            ->whereDate('date', '<=', $now->toDateString())
            ->get();

        Log::info("🕒 Vérification des plannings à {$now->format('Y-m-d H:i:s')}");
        Log::info("Nombre de plannings à vérifier : " . $schedules->count());

        foreach ($schedules as $schedule) {
            try {
                Log::info("🔍 Vérification du planning ID: {$schedule->id}");

                $start = $this->parseDateTime($schedule->date, $schedule->start_time);
                $end = $schedule->end_time
                    ? $this->parseDateTime($schedule->date, $schedule->end_time)
                    : $now;

                $toleranceStart = $start->copy()->subMinutes($toleranceMinutes);
                $toleranceEnd = $end->copy()->addMinutes($toleranceMinutes);

                Log::info("Plage tolérée : {$toleranceStart} → {$toleranceEnd}");

                $patrol = Patrol::with('agent')
                    ->where('site_id', $schedule->site_id)
                    ->whereBetween('started_at', [$toleranceStart, $toleranceEnd])
                    ->latest()
                    ->first();

                if (!$patrol) {
                    if ($now->gt($toleranceEnd)) {
                        Log::warning("❌ Aucune patrouille trouvée pour le planning ID {$schedule->id}.");
                        $this->sendFailureEmail($schedule, null, null, $now);
                        $schedule->status = 'fail';
                        $schedule->save();
                    }
                    continue;
                }

                $startedAt = Carbon::parse($patrol->started_at)->setTimezone('Africa/Kinshasa');

                if ($startedAt->lt($start) || $startedAt->gt($end)) {
                    if ($now->gt($toleranceEnd)) {
                        Log::warning("⚠️ Patrouille hors créneau strict (start={$start}, end={$end}).");
                        $this->sendFailureEmail($schedule, $patrol->agent ?? null, $patrol->photo ?? null, $now);
                        $schedule->status = 'fail';
                        $schedule->save();
                    }
                    continue;
                }

                // Vérification des zones scannées
                $allAreas = Area::where('site_id', $schedule->site_id)->pluck('id')->toArray();
                $scannedAreas = PatrolScan::where('patrol_id', $patrol->id)->pluck('area_id')->unique()->toArray();

                $totalAreas = count($allAreas);
                $scannedCount = count($scannedAreas);
                $ratio = $totalAreas > 0 ? ($scannedCount / $totalAreas) : 0;

                $newStatus = 'fail';
                if ($scannedCount > 0) {
                    $newStatus = ($ratio < 1.0 && $ratio >= 0.5) ? 'partial' : 'success';
                } else {
                    Log::warning("Patrouille ID {$patrol->id} sans scan de zone.");
                }

                if ($newStatus === 'fail') {
                    $this->sendFailureEmail($schedule, $patrol->agent ?? null, $patrol->photo ?? null, $now);
                }

                // Ne pas écraser un échec existant
                if ($schedule->status !== 'fail') {
                    $schedule->status = $newStatus;
                    $schedule->save();
                    Log::info("✅ Planning ID {$schedule->id} → statut mis à jour : {$newStatus}");
                }

            } catch (\Exception $e) {
                Log::error("💥 Erreur sur le planning ID {$schedule->id} : " . $e->getMessage());
            }
        }
    } */

    public function verifySchedules()
    {
        $now = Carbon::now('Africa/Kinshasa');
        $toleranceMinutes = 15;

        // On récupère tous les plannings "actif"
        $allSchedules = Schedules::where('status', 'actif')->get();

        // On filtre uniquement ceux dont la date est aujourd’hui ou avant
        $schedules = $allSchedules->filter(function ($schedule) use ($now) {
            try {
                $scheduleDate = $this->parseDate($schedule->date);
                return $scheduleDate->lessThanOrEqualTo($now);
            } catch (\Exception $e) {
                Log::error("Erreur lors du parsing de la date du planning ID {$schedule->id} : {$schedule->date}");
                return false;
            }
        });

        Log::info("🕒 Vérification des plannings à {$now->format('Y-m-d H:i:s')}");
        Log::info("Nombre de plannings à vérifier : " . $schedules->count());

        foreach ($schedules as $schedule) {
            try {
                Log::info("🔍 Vérification du planning ID: {$schedule->id}");

                $start = $this->parseDateTime($schedule->date, $schedule->start_time);
                $end = $schedule->end_time
                    ? $this->parseDateTime($schedule->date, $schedule->end_time)
                    : $start->copy()->addHours(2); // ✅ end par défaut si absent

                $toleranceStart = $start->copy()->subMinutes($toleranceMinutes);
                $toleranceEnd = $end->copy()->addMinutes($toleranceMinutes);

                Log::info("Plage tolérée : {$toleranceStart} → {$toleranceEnd}");

                // ✅ On ignore les plannings pas encore commencés
                if ($now->lt($toleranceStart)) {
                    Log::info("⏳ Le planning ID {$schedule->id} n’a pas encore commencé. On attend (start = {$start}).");
                    continue;
                }

                $patrol = Patrol::with('agent')
                    ->where('site_id', $schedule->site_id)
                    ->whereBetween('started_at', [$toleranceStart, $toleranceEnd])
                    ->latest()
                    ->first();

                if (!$patrol) {
                    if ($now->gt($toleranceEnd)) {
                        Log::warning("❌ Aucune patrouille trouvée pour le planning ID {$schedule->id}.");
                        $this->sendFailureEmail($schedule, null, null, $now);
                        $schedule->status = 'fail';
                        $schedule->save();
                    }
                    continue;
                }

                $startedAt = Carbon::parse($patrol->started_at)->setTimezone('Africa/Kinshasa');

                if ($startedAt->lt($start) || $startedAt->gt($end)) {
                    if ($now->gt($toleranceEnd)) {
                        Log::warning("Patrouille hors créneau strict (start={$start}, end={$end}).");
                        $this->sendFailureEmail($schedule, $patrol, $now);
                        $schedule->status = 'fail';
                        $schedule->save();
                    }
                    continue;
                }

                // Vérification des zones scannées
                $allAreas = Area::where('site_id', $schedule->site_id)->pluck('id')->toArray();
                $scannedAreas = PatrolScan::where('patrol_id', $patrol->id)->pluck('area_id')->unique()->toArray();

                $totalAreas = count($allAreas);
                $scannedCount = count($scannedAreas);
                $ratio = $totalAreas > 0 ? ($scannedCount / $totalAreas) : 0;
                $newStatus = 'fail';

                if ($scannedCount > 0) {
                    $newStatus = ($ratio < 1.0 && $ratio >= 0.5) ? 'partial' : 'success';
                } else {
                    Log::warning("Patrouille ID {$patrol->id} sans scan de zone.");
                }

                if ($newStatus === 'fail') {
                    $this->sendFailureEmail($schedule, $patrol , $now);
                }

                if ($schedule->status !== 'fail') {
                    $schedule->status = $newStatus;
                    $schedule->save();
                    Log::info("✅ Planning ID {$schedule->id} → statut mis à jour : {$newStatus}");
                }

            } catch (\Exception $e) {
                Log::error("💥 Erreur sur le planning ID {$schedule->id} : " . $e->getMessage());
            }
        }
    }



    protected function parseDateTime($date, $time)
    {
        try {
            $date = trim($date);
            $time = trim($time);

            // Si $time contient déjà une date complète
            if (preg_match('/\d{4}-\d{2}-\d{2}/', $time)) {
                return Carbon::parse($time, 'Africa/Kinshasa');
            }

            // Normalisation de la date
            $carbonDate = $this->parseDate($date);
            $datetime = $carbonDate->format('Y-m-d') . ' ' . $time;

            return Carbon::createFromFormat('Y-m-d H:i', $datetime, 'Africa/Kinshasa');

        } catch (\Exception $e) {
            Log::error("⛔ Erreur de parsing sur date={$date}, time={$time} : " . $e->getMessage());
            throw $e;
        }
    }

    protected function parseDate($date)
    {
        try {
            $date = trim($date);

            if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $date)) {
                return Carbon::createFromFormat('d/m/Y', $date);
            }

            if (preg_match('/^\d{4}-\d{2}-\d{2}( \d{2}:\d{2}:\d{2})?$/', $date)) {
                return Carbon::parse($date);
            }

            throw new \Exception("Format de date non reconnu : $date");

        } catch (\Exception $e) {
            throw $e;
        }
    }





    /* protected function parseDateTime($date, $time)
    {
        try {
            $date = trim($date);
            $time = trim($time);

            // Si `$time` contient déjà une date complète, on l'utilise directement
            if (preg_match('/\d{4}-\d{2}-\d{2}/', $time)) {
                return Carbon::parse($time, 'Africa/Kinshasa');
            }

            // Sinon, on assemble la date et l'heure
            if (strlen($date) > 10) {
                $date = Carbon::parse($date)->format('Y-m-d');
            }

            $datetime = "{$date} {$time}";

            return Carbon::parse($datetime, 'Africa/Kinshasa');

        } catch (\Exception $e) {
            Log::error("⛔ Erreur de parsing sur date={$date}, time={$time} : " . $e->getMessage());
            throw $e;
        }
    } */



    protected function sendFailureEmail($schedule, $patrol = null, $now = null)
    {
        try {
            $now = $now ?? Carbon::now('Africa/Kinshasa');
            $site = Site::find($schedule->site_id);
            $emails = $site?->emails; // Chaîne séparée par virgules
            $emailList = collect(explode(';', $emails))
                            ->map(fn($email) => trim($email))
                            ->filter()
                            ->toArray();

            $agentName = $patrol?->agent ? "{$patrol->agent->nom} {$patrol->agent->prenom}" : 'Non identifié';
            $photo = $patrol?->photo ?? null;

            // Zones attendues
            $expectedAreas = Area::where('site_id', $schedule->site_id)->pluck('libelle')->toArray();

            // Zones scannées
            $scannedAreaIds = $patrol
                ? PatrolScan::where('patrol_id', $patrol->id)->pluck('area_id')->unique()->toArray()
                : [];

            $scannedAreas = Area::whereIn('id', $scannedAreaIds)->pluck('libelle')->toArray();
            $missingAreas = array_diff($expectedAreas, $scannedAreas);

            // Préparation du contenu
            $subject = "[Alerte] Patrouille non respectée - {$site->nom} - {$schedule->libelle}";

            $body = view('emails.patrol_failure_alert', [
                'schedule'      => $schedule,
                'site'          => $site,
                'agentName'     => $agentName,
                'missingAreas'  => $missingAreas,
                'scannedAreas'  => $scannedAreas,
                'photo'         => $photo,
                'now'           => $now,
            ])->render();

            // Envoi du mail
            Mail::html($body, function ($message) use ($subject, $emailList) {
                $message->to($emailList);
                $message->subject($subject);
            });

            Log::info("📤 Email d'alerte envoyé pour planning ID {$schedule->id}");

        } catch (\Exception $e) {
            Log::error("📛 Erreur lors de l'envoi de l'alerte email : " . $e->getMessage());
        }
    }



}
