<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Google\Client;
use Google\Service\Drive;
use Google\Service\Drive\DriveFile;
use Symfony\Component\Process\Process;

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
    protected $description = 'Exporter la base de données et l\'envoyer sur Google Drive via OAuth 2.0 (Compatible Local & HostGator)';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $this->info("Démarrage de la sauvegarde...");

        // Désactiver la limite de temps PHP (crucial pour les exports longs en ligne)
        set_time_limit(0);

        // Formatage du nom du fichier
        $date = Carbon::now()->format('d-m-Y_H-i-s');
        $filename = "Premierbet-{$date}.sql";

        $backupDir = storage_path("app/backups");
        $backupPath = "{$backupDir}/{$filename}";

        if (!file_exists($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        try {
            $db = config('database.connections.mysql');

            // 1. Détection dynamique de mysqldump (Local vs HostGator)
            $mysqldump = env('MYSQLDUMP_PATH');

            if (empty($mysqldump)) {
                if (PHP_OS_FAMILY === 'Windows') {
                    // Local : Chemin XAMPP par défaut
                    $xamppPath = 'E:\Vendor\Xampp\mysql\bin\mysqldump.exe';
                    $mysqldump = file_exists($xamppPath) ? $xamppPath : 'mysqldump.exe';
                } else {
                    // Sur HostGator (Linux), mysqldump est accessible directement dans le PATH
                    $mysqldump = 'mysqldump';
                }
            }

            $this->info("Exportation de la base [{$db['database']}] avec {$mysqldump}...");

            // Options optimisées pour la production :
            // --no-tablespaces : Indispensable sur HostGator (évite l'erreur de privilège PROCESS sur mutualisé)
            // --single-transaction : Permet de dumper sans verrouiller les tables (important pour InnoDB)
            // --quick : Exporte ligne par ligne pour économiser la RAM
            // --set-charset : Garantit l'encodage correct (utf8mb4)
            $process = new Process([
                $mysqldump,
                '--user=' . ($db['username'] ?? ''),
                '--host=' . ($db['host'] ?? '127.0.0.1'),
                '--port=' . ($db['port'] ?? '3306'),
                '--no-tablespaces',
                '--single-transaction',
                '--quick',
                '--set-charset=utf8mb4',
                $db['database']
            ], null, ['MYSQL_PWD' => $db['password'] ?? '']);

            $process->setTimeout(900); // 15 minutes max

            // Flux d'écriture direct pour économiser la mémoire vive (RAM)
            $fileHandle = fopen($backupPath, 'w');
            $errorOutput = '';
            $process->run(function ($type, $buffer) use ($fileHandle, &$errorOutput) {
                if ($type === Process::OUT) {
                    fwrite($fileHandle, $buffer);
                } else {
                    $errorOutput .= $buffer;
                }
            });
            fclose($fileHandle);

            if (!$process->isSuccessful()) {
                if (file_exists($backupPath)) unlink($backupPath);
                throw new \Exception("Erreur mysqldump : " . ($errorOutput ?: "Code " . $process->getExitCode()));
            }

            $fileSize = filesize($backupPath);
            if ($fileSize === 0) {
                throw new \Exception("Le fichier de sauvegarde généré est vide.");
            }

            // 2. Connexion Google Drive (OAuth 2.0)
            $this->info("Connexion à Google Drive...");
            $client = new Client();
            $client->setClientId(env('GOOGLE_DRIVE_CLIENT_ID'));
            $client->setClientSecret(env('GOOGLE_DRIVE_CLIENT_SECRET'));

            $refreshToken = env('GOOGLE_DRIVE_REFRESH_TOKEN');
            if (empty($refreshToken)) {
                throw new \Exception("GOOGLE_DRIVE_REFRESH_TOKEN est manquant dans le fichier .env");
            }

            $client->fetchAccessTokenWithRefreshToken($refreshToken);
            $service = new Drive($client);

            // 3. Téléversement
            $folderId = env('GOOGLE_DRIVE_FOLDER_ID');
            $fileMetadata = new DriveFile(['name' => $filename]);

            if (!empty($folderId)) {
                $fileMetadata->setParents([$folderId]);
            }

            $this->info("Téléversement de {$filename} vers Google Drive...");
            $client->setDefer(true);
            $request = $service->files->create($fileMetadata);
            $client->setDefer(false);

            // Upload par morceaux (chunks) de 1Mo pour la stabilité sur serveur mutualisé (HostGator)
            $media = new \Google\Http\MediaFileUpload($client, $request, 'application/sql', null, true, 1024 * 1024);
            $media->setFileSize($fileSize);

            $status = false;
            $handle = fopen($backupPath, "rb");
            while (!$status && !feof($handle)) {
                $chunk = fread($handle, 1024 * 1024);
                $status = $media->nextChunk($chunk);
            }
            fclose($handle);

            if ($status instanceof DriveFile) {
                $this->info("Sauvegarde réussie ! Fichier : {$filename}");
                Log::info("Backup réussi vers Google Drive : {$filename}");
            } else {
                throw new \Exception("Le téléversement vers Google Drive a échoué.");
            }

            if (file_exists($backupPath)) unlink($backupPath);
            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("ERREUR : " . $e->getMessage());
            Log::error("Erreur Backup OAuth Drive : " . $e->getMessage());
            if (isset($backupPath) && file_exists($backupPath)) unlink($backupPath);
            return Command::FAILURE;
        }
    }
}
