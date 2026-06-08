<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BiteshipService
{
    public string $baseUrl = 'https://api.biteship.com/v1';

    public string $apiKey;

    public function __construct(?string $apiKey = null)
    {
        $this->apiKey = $apiKey ?? config('services.biteship.key');
    }

    public function getHeaders(): array
    {
        return [
            'Authorization' => $this->apiKey,
            'Content-Type' => 'application/json',
        ];
    }

    /**
     * Base request handler to keep things DRY
     */
    private function sendRequest(string $method, string $endpoint, array $data = [])
    {
        $url = "{$this->baseUrl}{$endpoint}";

        $response = match (strtoupper($method)) {
            'GET' => Http::withHeaders($this->getHeaders())->get($url, $data),
            'POST' => Http::withHeaders($this->getHeaders())->post($url, $data),
            'DELETE' => Http::withHeaders($this->getHeaders())->delete($url, $data),
            default => throw new \InvalidArgumentException("Unsupported HTTP method: {$method}"),
        };

        if ($response->failed()) {
            Log::error("Failed to fetch from Biteship API [{$method} {$endpoint}]", [
                'status' => $response->status(),
                'body' => $response->body(),
                'payload' => $data,
            ]);

            throw new \Exception("Failed to fetch from Biteship API: {$response->body()}");
        }

        $responseData = $response->json();

        if (isset($responseData['success']) && ! $responseData['success']) {
            Log::error("Biteship API returned an unsuccessful response [{$method} {$endpoint}]", [
                'response' => $responseData,
            ]);

            throw new \Exception('Biteship API returned an unsuccessful response');
        }

        return $responseData;
    }

    // ==========================================
    // COURIERS API
    // ==========================================

    public function couriers()
    {
        return Cache::remember('biteship:couriers', now()->addDay(), function () {
            $response = $this->sendRequest('GET', '/couriers');

            return $response['couriers'] ?? $response;
        });
    }

    // ==========================================
    // RATES API
    // ==========================================

    public function getRates(array $payload)
    {
        return $this->sendRequest('POST', '/rates/couriers', $payload);
    }

    // ==========================================
    // LOCATIONS API
    // ==========================================

    public function createLocation(array $payload)
    {
        return $this->sendRequest('POST', '/locations', $payload);
    }

    public function getLocation(string $id)
    {
        return $this->sendRequest('GET', "/locations/{$id}");
    }

    public function updateLocation(string $id, array $payload)
    {
        return $this->sendRequest('POST', "/locations/{$id}", $payload);
    }

    public function deleteLocation(string $id)
    {
        return $this->sendRequest('DELETE', "/locations/{$id}");
    }

    // ==========================================
    // MAPS API
    // ==========================================

    public function getMapsAreas(array $query)
    {
        $query['type'] = 'single';
        return $this->sendRequest('GET', '/maps/areas', $query);
    }

    // ==========================================
    // DRAFT ORDERS API
    // ==========================================

    public function createDraftOrder(array $payload)
    {
        return $this->sendRequest('POST', '/draft_orders', $payload);
    }

    public function getDraftOrder(string $id)
    {
        return $this->sendRequest('GET', "/draft_orders/{$id}");
    }

    public function updateDraftOrder(string $id, array $payload)
    {
        return $this->sendRequest('POST', "/draft_orders/{$id}", $payload);
    }

    public function confirmDraftOrder(string $id)
    {
        return $this->sendRequest('POST', "/draft_orders/{$id}/confirm");
    }

    public function deleteDraftOrder(string $id)
    {
        return $this->sendRequest('DELETE', "/draft_orders/{$id}");
    }

    public function getDraftOrderRates(string $id)
    {
        return $this->sendRequest('GET', "/draft_orders/{$id}/rates");
    }

    // ==========================================
    // ORDERS API
    // ==========================================

    public function createOrder(array $payload)
    {
        return $this->sendRequest('POST', '/orders', $payload);
    }

    public function getOrder(string $id)
    {
        return $this->sendRequest('GET', "/orders/{$id}");
    }

    public function cancelOrder(string $id, string $reason = '')
    {
        $payload = $reason ? ['cancellation_reason' => $reason] : [];

        return $this->sendRequest('POST', "/orders/{$id}/cancel", $payload);
    }

    // ==========================================
    // TRACKING API
    // ==========================================

    public function getTrackingById(string $id)
    {
        return $this->sendRequest('GET', "/tracking/{$id}");
    }

    public function getTrackingByWaybill(string $waybillId, string $courierCode)
    {
        return $this->sendRequest('GET', "/trackings/{$waybillId}/couriers/{$courierCode}");
    }
}
