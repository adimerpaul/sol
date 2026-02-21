<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SocketEmitter
{
    public static function emit(string $event, array $payload = []): void
    {
        error_log('url socket_io: ' . config('services.socket_io.url', ''));
        error_log('timeout socket_io: ' . config('services.socket_io.timeout', 2));

        $baseUrl = rtrim((string) config('services.socket_io.url', ''), '/');
        if ($baseUrl === '') {
            return;
        }

        $timeout = (int) config('services.socket_io.timeout', 2);
        $body = [
            'event' => $event,
            'payload' => $payload,
        ];

        try {
            $res = Http::timeout(max(1, $timeout))
                ->asJson()
                ->acceptJson()
                ->post($baseUrl . '/emit', $body);

            if (!$res->successful()) {
                Log::warning('SocketEmitter emit failed', [
                    'url' => $baseUrl . '/emit',
                    'status' => $res->status(),
                    'body' => $res->body(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('SocketEmitter exception', [
                'url' => $baseUrl . '/emit',
                'error' => $e->getMessage(),
            ]);
        }
    }

    public static function votacion(array $payload = []): void
    {
        self::emit('votacion', $payload);
    }
}

