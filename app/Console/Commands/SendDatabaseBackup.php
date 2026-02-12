<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class SendDatabaseBackup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:send';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export DB et envoyer le backup par email';

    public function handle()
    {
        $date = Carbon::now()->format('Y-m-d_H-i-s');
        $filename = "backup_{$date}.sql";
        $backupPath = storage_path("app/backups/{$filename}");

        // Création du dossier s'il n'existe pas
        if (!Storage::exists('backups')) {
            Storage::makeDirectory('backups');
        }

        try {
            // Connexion à la base
            $db = config('database.connections.mysql');
            $mysqli = new \mysqli($db['host'], $db['username'], $db['password'], $db['database']);

            if ($mysqli->connect_error) {
                throw new \Exception("Erreur de connexion MySQL : " . $mysqli->connect_error);
            }

            $tables = [];
            $result = $mysqli->query("SHOW TABLES");
            while ($row = $result->fetch_array()) {
                $tables[] = $row[0];
            }

            $sqlScript = "";
            foreach ($tables as $table) {
                // Création table
                $result = $mysqli->query("SHOW CREATE TABLE $table");
                $row = $result->fetch_assoc();
                $sqlScript .= "\n\n" . $row['Create Table'] . ";\n\n";

                // Insertion des données
                $result = $mysqli->query("SELECT * FROM $table");
                while ($row = $result->fetch_assoc()) {
                    $columns = array_map(function ($v) { return "`" . $v . "`"; }, array_keys($row));
                    $values = array_map(function ($v) use ($mysqli) {
                        return "'" . $mysqli->real_escape_string($v) . "'";
                    }, array_values($row));
                    $sqlScript .= "INSERT INTO `$table` (" . implode(", ", $columns) . ") VALUES (" . implode(", ", $values) . ");\n";
                }
            }

            // Sauvegarde du fichier
            file_put_contents($backupPath, $sqlScript);

            // Envoi email
            $emails = ["gastondelimond@gmail.com", "lionnelnawej11@gmail.com"];
            foreach ($emails as $email) {
                try {
                    Mail::raw("Ci-joint la sauvegarde de la base de données du {$date}.", function ($message) use ($email, $backupPath, $filename) {
                        $message->to($email)
                                ->subject('Sauvegarde automatique de la base de données')
                                ->attach($backupPath);
                    });
                } catch (\Exception $e) {
                    Log::error("Erreur d'envoi à {$email} : " . $e->getMessage());
                }
            }

            // Supprimer après envoi
            if (file_exists($backupPath)) {
                unlink($backupPath);
            }

            Log::info("Backup réussi !");
        } catch (\Exception $e) {
            Log::error("Erreur de backup : " . $e->getMessage());
        }
        $this->info("Backup réussi !");
    }

}
