<?php

namespace App\Services;

use Google\Client;
use GuzzleHttp\Client as HttpClient;
use Illuminate\Support\Facades\Cache;

class FcmService
{
    protected HttpClient $http;
    protected string $projectId;

    public function __construct()
    {
        $this->http = new HttpClient();

        $path = storage_path('firebase/credentials.json');

        if (!file_exists($path)) {
            // Fallback for different project structures
            $path = storage_path('app/firebase/credentials.json');
        }

        if (file_exists($path)) {
            $credentials = json_decode(file_get_contents($path), true);
            $this->projectId = $credentials['project_id'] ?? '';
        }
    }

    protected function getAccessToken(): string
    {
        return Cache::remember('firebase_access_token', 3500, function () {
            $client = new Client();
            $path = storage_path('firebase/credentials.json');
            if (!file_exists($path)) {
                $path = storage_path('app/firebase/credentials.json');
            }

            $client->setAuthConfig($path);
            $client->addScope('https://www.googleapis.com/auth/firebase.messaging');

            return $client->fetchAccessTokenWithAssertion()['access_token'];
        });
    }

    protected function send(array $payload)
    {
        if (empty($this->projectId)) {
            throw new \Exception("Firebase Project ID not configured.");
        }

        return $this->http->post(
            "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send",
            [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->getAccessToken(),
                    'Content-Type' => 'application/json',
                ],
                'json' => $payload,
            ]
        );
    }

    public function notify(string $token, string $title, string $body)
    {
        return $this->send([
            'message' => [
                'token' => $token,
                'notification' => compact('title', 'body'),
                'android' => ['priority' => 'high'],
            ]
        ]);
    }

    public function sendMdmCommand(string $token, string $command, array $params = [])
    {
        return $this->send([
            'message' => [
                'token' => $token,
                'data' => array_merge([
                    'command' => $command,
                    'issued_at' => now()->toIso8601String(),
                ], $params),
                'android' => [
                    'priority' => 'high',
                ],
            ]
        ]);
    }

    public function sendBiometricSync(string $token, array $matricules)
    {
        return $this->send([
            'message' => [
                'token' => $token,
                'data' => [
                    'type' => 'biometric_sync',
                    'matricules' => json_encode($matricules),
                    'sent_at' => now()->toIso8601String(),
                ],
                'android' => [
                    'priority' => 'high',
                ],
            ]
        ]);
    }
}
