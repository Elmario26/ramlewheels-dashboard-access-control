<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class WebsocketEmitter
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
        private readonly string $gatewayUrl,
        private readonly string $internalToken
    ) {
    }

    public function emitBookingUpdated(int|string $userId, array $booking): void
    {
        $this->postEvent('/events/booking-updated', [
            'userId' => $userId,
            'booking' => $booking,
        ]);
    }

    public function emitServiceUpdated(int|string $userId, array $booking): void
    {
        $this->postEvent('/events/service-updated', [
            'userId' => $userId,
            'booking' => $booking,
        ]);
    }

    private function postEvent(string $path, array $payload): void
    {
        $base = trim($this->gatewayUrl);
        if ($base === '' || $this->internalToken === '') {
            $this->logger->warning('WebSocket emit skipped: gateway not configured', [
                'path' => $path,
            ]);
            return;
        }

        $url = rtrim($base, '/').$path;

        try {
            $response = $this->httpClient->request('POST', $url, [
                'headers' => [
                    'x-internal-token' => $this->internalToken,
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ],
                'json' => $payload,
                'timeout' => 5.0,
            ]);

            $statusCode = $response->getStatusCode();
            if ($statusCode < 200 || $statusCode >= 300) {
                $this->logger->warning('WebSocket emit returned non-2xx response', [
                    'url' => $url,
                    'statusCode' => $statusCode,
                ]);
            }
        } catch (\Throwable $e) {
            // Non-blocking: API update should still succeed even if websocket gateway is down.
            $this->logger->warning('WebSocket emit failed', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
