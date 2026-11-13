<?php

namespace App\Services;

use App\Services\Payments\PaymentGatewaySettingsService;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Throwable;

class MidtransService
{
    public string $baseUrl;

    private array $configuration;

    public function __construct(
        public ?string $serverKey = null,
        public ?string $clientKey = null,
        public ?bool $isProduction = null,
    ) {
        $this->configuration = app(PaymentGatewaySettingsService::class)->midtransConfiguration();
        $this->serverKey = $this->serverKey ?? $this->configuration['server_key'];
        $this->clientKey = $this->clientKey ?? $this->configuration['client_key'];
        $this->isProduction = $this->isProduction ?? (bool) $this->configuration['is_production'];
        $this->baseUrl = $this->isProduction ? 'https://api.midtrans.com' : 'https://api.sandbox.midtrans.com';
    }

    public function timezone(): string
    {
        return $this->configuration['timezone'];
    }

    /**
     * Creates a bank transfer payment transaction through Midtrans API.
     *
     * This method initiates a bank transfer payment by sending a charge request
     * to Midtrans with the specified order details and bank selection.
     *
     * @param  string  $orderId  Unique identifier for the order/transaction
     * @param  int  $amount  Transaction amount in the smallest currency unit (e.g., cents for IDR)
     * @param  string  $bank  Bank code for the transfer (default: 'bca').
     *                        Supported banks: bca, bni, bri, cimb
     * @return array Returns an associative array containing:
     *               - successful (bool): Whether the API request was successful
     *               - transaction_id (string|null): Midtrans transaction identifier
     *               - account (string|null): Virtual account number for the transfer
     *               - code (string|null): Biller code if applicable
     *               - message (string): Success message or error description
     *
     * @throws \Exception When an invalid bank code is provided
     */
    public function createBankTransfer(
        string $orderId,
        int $amount,
        string $bank = 'bca',
    ): array {
        // Validate bank
        $validBanks = [
            'bca',
            'bni',
            'bri',
            'cimb',
        ];
        if (! in_array($bank, $validBanks, true)) {
            throw new \Exception('Invalid bank. Valid banks are: '.implode(', ', $validBanks));
        }

        // Param
        $params = [
            'payment_type' => 'bank_transfer',
            'transaction_details' => [
                'order_id' => $orderId,
                'gross_amount' => $amount,
            ],
            'bank_transfer' => [
                'bank' => $bank,
            ],
        ];

        // Response
        $response = Http::withBasicAuth((string) $this->serverKey, '')
            ->connectTimeout((float) $this->configuration['connect_timeout'])
            ->timeout((float) $this->configuration['timeout'])
            ->retry((int) $this->configuration['retries'], 200, throw: false)
            ->post($this->baseUrl.'/v2/charge', $params);
        $responseJson = $response->json();
        $responseJson = is_array($responseJson) ? $responseJson : [];

        return [
            'successful' => $response->successful(),
            'transaction_id' => $responseJson['transaction_id'] ?? null,
            'account' => $responseJson['va_numbers'][0]['va_number'] ?? null,
            'code' => $responseJson['biller_code'] ?? null,
            'redirect_url' => $responseJson['redirect_url'] ?? null,
            'message' => $responseJson['message'] ?? 'Failed to create bank transfer',
            'data' => $responseJson,
        ];
    }

    /**
     * Creates a QRIS (Quick Response Code Indonesian Standard) payment transaction through Midtrans API.
     *
     * This method generates a QRIS payment by sending a charge request to Midtrans,
     * which returns a QR code URL that customers can scan to complete payment.
     *
     * @param  string  $orderId  Unique identifier for the order/transaction
     * @param  int  $amount  Transaction amount in the smallest currency unit (e.g., cents for IDR)
     * @return array Returns an associative array containing:
     *               - successful (bool): Whether the API request was successful
     *               - transaction_id (string|null): Midtrans transaction identifier
     *               - account (string|null): URL to the QR code image or payment page
     *               - code (null): Always null for QRIS payments
     *               - message (string): Success message or error description
     */
    public function createQris(
        string $orderId,
        int $amount,
    ): array {
        // Param
        $params = [
            'payment_type' => 'qris',
            'transaction_details' => [
                'order_id' => $orderId,
                'gross_amount' => $amount,
            ],
        ];

        // Response
        $response = Http::withBasicAuth((string) $this->serverKey, '')
            ->connectTimeout((float) $this->configuration['connect_timeout'])
            ->timeout((float) $this->configuration['timeout'])
            ->retry((int) $this->configuration['retries'], 200, throw: false)
            ->post($this->baseUrl.'/v2/charge', $params);
        $responseJson = $response->json();
        $responseJson = is_array($responseJson) ? $responseJson : [];

        return [
            'successful' => $response->successful(),
            'transaction_id' => $responseJson['transaction_id'] ?? null,
            'account' => $responseJson['actions'][0]['url'] ?? null,
            'code' => null,
            'redirect_url' => $responseJson['redirect_url'] ?? null,
            'message' => $responseJson['message'] ?? 'Failed to create qris',
            'data' => $responseJson,
        ];
    }

    /**
     * Creates a card payment transaction through Midtrans API.
     *
     * This method processes a card payment by sending a charge request to Midtrans
     * with the specified order details and card token.
     *
     * @param  string  $orderId  Unique identifier for the order/transaction
     * @param  int  $amount  Transaction amount in the smallest currency unit (e.g., cents for IDR)
     * @param  string  $cardToken  Tokenized representation of the customer's card details
     * @return array Returns an associative array containing:
     *               - successful (bool): Whether the API request was successful
     *               - transaction_id (string|null): Midtrans transaction identifier
     *               - account (null): Always null for card payments
     *               - code (null): Always null for card payments
     *               - message (string): Success message or error description
     */
    public function createCardTransaction(
        string $orderId,
        int $amount,
        string $cardToken,
    ): array {
        // Param
        $params = [
            'payment_type' => 'credit_card',
            'transaction_details' => [
                'order_id' => $orderId,
                'gross_amount' => $amount,
            ],
            'credit_card' => [
                'token_id' => $cardToken,
                'authentication' => true,
            ],
        ];

        // Response
        $response = Http::withBasicAuth((string) $this->serverKey, '')
            ->connectTimeout((float) $this->configuration['connect_timeout'])
            ->timeout((float) $this->configuration['timeout'])
            ->retry((int) $this->configuration['retries'], 200, throw: false)
            ->post($this->baseUrl.'/v2/charge', $params);
        $responseJson = $response->json();
        $responseJson = is_array($responseJson) ? $responseJson : [];

        return [
            'successful' => $response->successful(),
            'transaction_id' => $responseJson['transaction_id'] ?? null,
            'account' => $cardToken,
            'code' => null,
            'redirect_url' => $responseJson['redirect_url'] ?? null,
            'message' => $responseJson['message'] ?? 'Failed to create card transaction',
            'data' => $responseJson,
        ];
    }

    public function paymentStatus(string $orderId): array
    {
        $response = Http::withBasicAuth((string) $this->serverKey, '')
            ->connectTimeout((float) $this->configuration['connect_timeout'])
            ->timeout((float) $this->configuration['timeout'])
            ->retry(
                (int) $this->configuration['retries'],
                200,
                fn (Throwable $exception): bool => ! ($exception instanceof RequestException)
                    || $exception->response->serverError(),
                throw: false,
            )
            ->get($this->baseUrl.'/v2/'.rawurlencode($orderId).'/status');
        $responseJson = $response->json();
        $responseJson = is_array($responseJson) ? $responseJson : [];

        return [
            'successful' => $response->successful(),
            'http_status' => $response->status(),
            'message' => $responseJson['status_message'] ?? 'Failed to fetch transaction status',
            'data' => $responseJson,
        ];
    }

    public function validateSignature(string $orderId, string $statusCode, string $grossAmount, string $signatureKey): bool
    {
        if (blank($this->serverKey) || blank($signatureKey)) {
            return false;
        }

        $hashedKey = hash('sha512', $orderId.$statusCode.$grossAmount.$this->serverKey);

        return hash_equals($hashedKey, $signatureKey);
    }
}
