<?php

namespace App\Data\Callbacks;

use InvalidArgumentException;

final readonly class MidtransCallbackData
{
    public function __construct(
        public string $orderId,
        public string $statusCode,
        public string $grossAmount,
        public string $signatureKey,
        public string $transactionStatus,
    ) {}

    public static function fromArray(array $payload): self
    {
        foreach (['order_id', 'status_code', 'gross_amount', 'signature_key', 'transaction_status'] as $field) {
            if (! isset($payload[$field]) || ! is_scalar($payload[$field])) {
                throw new InvalidArgumentException("Missing Midtrans callback field: {$field}", 400);
            }
        }

        return new self((string) $payload['order_id'], (string) $payload['status_code'], (string) $payload['gross_amount'], (string) $payload['signature_key'], (string) $payload['transaction_status']);
    }
}
