<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Sends push notifications via the FCM HTTP v1 API using a Firebase service
 * account — WITHOUT any extra Composer package (Laravel HTTP + built-in
 * openssl for the OAuth2 JWT). Everything is fail-soft: with no credential
 * configured, or on any error, it logs and returns so a push failure never
 * breaks the calling flow.
 *
 * Setup: put the service-account JSON somewhere readable and set
 * `FIREBASE_CREDENTIALS=/absolute/path/to/service-account.json` in .env.
 */
class FcmService
{
    public function sendToToken(?string $token, string $title, string $body, array $data = []): void
    {
        if (empty($token)) {
            return;
        }

        try {
            $creds = $this->credentials();
            $projectId = $creds['project_id'] ?? null;
            $accessToken = $this->accessToken($creds);
            if (!$projectId || !$accessToken) {
                return;
            }

            Http::withToken($accessToken)
                ->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", [
                    'message' => [
                        'token' => $token,
                        'notification' => [
                            'title' => $title,
                            'body' => $body,
                        ],
                        // FCM data values must be strings.
                        'data' => array_map(fn ($v) => (string) $v, $data),
                    ],
                ]);
        } catch (\Throwable $e) {
            Log::warning('FCM send failed: ' . $e->getMessage());
        }
    }

    private function credentials(): array
    {
        $path = config('services.fcm.credentials');
        if (!$path || !file_exists($path)) {
            return [];
        }
        return json_decode(file_get_contents($path), true) ?: [];
    }

    private function accessToken(array $creds): ?string
    {
        if (empty($creds['client_email']) || empty($creds['private_key'])) {
            return null;
        }

        return Cache::remember('fcm_access_token', 3300, function () use ($creds) {
            $now = time();
            $jwt = $this->encodeJwt([
                'iss' => $creds['client_email'],
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                'aud' => 'https://oauth2.googleapis.com/token',
                'iat' => $now,
                'exp' => $now + 3600,
            ], $creds['private_key']);

            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]);

            return $response->json('access_token');
        });
    }

    private function encodeJwt(array $claims, string $privateKey): string
    {
        $seg = fn ($data) => rtrim(strtr(base64_encode(json_encode($data)), '+/', '-_'), '=');
        $signingInput = $seg(['alg' => 'RS256', 'typ' => 'JWT']) . '.' . $seg($claims);
        openssl_sign($signingInput, $signature, $privateKey, OPENSSL_ALGO_SHA256);
        return $signingInput . '.' . rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');
    }
}
