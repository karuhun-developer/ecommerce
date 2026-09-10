<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class BiteshipService
{
    public string $baseUrl = 'https://api.biteship.com/v1';

    public string $apiKey;

    public function __construct(?string $apiKey = null)
    {
        $this->apiKey = $apiKey ?? config('services.biteship.key');
    }

    /** @return array<string, string> */
    public function getHeaders(): array
    {
        return [
            'Authorization' => $this->apiKey,
            'Content-Type' => 'application/json',
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function sendRequest(string $method, string $endpoint, array $data = []): array
    {
        $url = "{$this->baseUrl}{$endpoint}";

        try {
            $response = match (strtoupper($method)) {
                'GET' => $this->request()->get($url, $data),
                'POST' => $this->request()->post($url, $data),
                'DELETE' => $this->request()->delete($url, $data),
                default => throw new \InvalidArgumentException("Unsupported HTTP method: {$method}"),
            };
        } catch (ConnectionException $exception) {
            Log::error('Biteship API connection failed', [
                'method' => strtoupper($method),
                'endpoint' => $endpoint,
            ]);

            throw new RuntimeException('Biteship API request failed.', previous: $exception);
        }

        if ($response->failed()) {
            Log::error('Biteship API request failed', [
                'method' => strtoupper($method),
                'endpoint' => $endpoint,
                'status' => $response->status(),
            ]);

            throw new RuntimeException('Biteship API request failed.');
        }

        $responseData = $response->json();

        if (! is_array($responseData) || (isset($responseData['success']) && ! $responseData['success'])) {
            Log::error('Biteship API returned an unsuccessful response', [
                'method' => strtoupper($method),
                'endpoint' => $endpoint,
                'status' => $response->status(),
            ]);

            throw new RuntimeException('Biteship API request failed.');
        }

        return $responseData;
    }

    private function request(): PendingRequest
    {
        return Http::withHeaders($this->getHeaders())
            ->connectTimeout(5)
            ->timeout(15)
            ->retry(3, 200, throw: false);
    }

    // ==========================================
    // COURIERS API
    // ==========================================

    public function couriers(): array
    {
        return Cache::remember('biteship:couriers', now()->addDay(), function () {
            $response = $this->sendRequest('GET', '/couriers');

            return $response['couriers'] ?? $response;
        });
    }

    // ==========================================
    // RATES API
    // ==========================================

    public function getRates(array $payload): array
    {
        return $this->sendRequest('POST', '/rates/couriers', $payload);
    }

    // ==========================================
    // LOCATIONS API
    // ==========================================

    public function createLocation(array $payload): array
    {
        return $this->sendRequest('POST', '/locations', $payload);
    }

    public function getLocation(string $id): array
    {
        return $this->sendRequest('GET', "/locations/{$id}");
    }

    public function updateLocation(string $id, array $payload): array
    {
        return $this->sendRequest('POST', "/locations/{$id}", $payload);
    }

    public function deleteLocation(string $id): array
    {
        return $this->sendRequest('DELETE', "/locations/{$id}");
    }

    // ==========================================
    // MAPS API
    // ==========================================

    public function getMapsAreas(array $query): array
    {
        $query['type'] = 'single';

        return $this->sendRequest('GET', '/maps/areas', $query);
    }

    // ==========================================
    // DRAFT ORDERS API
    // ==========================================

    public function createDraftOrder(array $payload): array
    {
        return $this->sendRequest('POST', '/draft_orders', $payload);
    }

    public function getDraftOrder(string $id): array
    {
        return $this->sendRequest('GET', "/draft_orders/{$id}");
    }

    public function updateDraftOrder(string $id, array $payload): array
    {
        return $this->sendRequest('POST', "/draft_orders/{$id}", $payload);
    }

    public function confirmDraftOrder(string $id): array
    {
        return $this->sendRequest('POST', "/draft_orders/{$id}/confirm");
    }

    public function deleteDraftOrder(string $id): array
    {
        return $this->sendRequest('DELETE', "/draft_orders/{$id}");
    }

    public function getDraftOrderRates(string $id): array
    {
        return $this->sendRequest('GET', "/draft_orders/{$id}/rates");
    }

    // ==========================================
    // ORDERS API
    // ==========================================

    public function createOrder(array $payload): array
    {
        return $this->sendRequest('POST', '/orders', $payload);
    }

    public function getOrder(string $id): array
    {
        return $this->sendRequest('GET', "/orders/{$id}");
    }

    public function cancelOrder(string $id, string $reason = ''): array
    {
        $payload = $reason ? ['cancellation_reason' => $reason] : [];

        return $this->sendRequest('POST', "/orders/{$id}/cancel", $payload);
    }

    // ==========================================
    // TRACKING API
    // ==========================================

    public function getTrackingById(string $id): array
    {
        return $this->sendRequest('GET', "/tracking/{$id}");
    }

    public function getTrackingByWaybill(string $waybillId, string $courierCode): array
    {
        return $this->sendRequest('GET', "/trackings/{$waybillId}/couriers/{$courierCode}");
    }
}
