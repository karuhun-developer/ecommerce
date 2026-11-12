<?php

namespace App\Data\Payments;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Carbon\CarbonImmutable;
use InvalidArgumentException;

final readonly class PaymentTransactionData
{
    public function __construct(
        public string $id,
        public string $orderId,
        public int $amount,
        public int $totalPayment,
        public PaymentMethod $paymentMethod,
        public ?string $paymentNumber,
        public ?string $paymentUrl,
        public PaymentStatus $status,
        public ?CarbonImmutable $expiresAt = null,
        public ?CarbonImmutable $createdAt = null,
        public ?string $providerCode = null,
        public ?string $providerToken = null,
        public ?CarbonImmutable $paidAt = null,
        public ?string $providerPaymentId = null,
    ) {
        if (blank($this->id) || blank($this->orderId)) {
            throw new InvalidArgumentException('Payment transaction identifiers are required.');
        }

        if ($this->amount <= 0 || $this->totalPayment <= 0) {
            throw new InvalidArgumentException('Payment transaction amounts must be greater than zero.');
        }

        if ($this->providerCode !== null && blank($this->providerCode)) {
            throw new InvalidArgumentException('Payment provider code must not be blank.');
        }
    }
}
