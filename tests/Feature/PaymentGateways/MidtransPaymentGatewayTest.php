<?php

use App\Data\Payments\CreatePaymentData;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Services\Payments\MidtransPaymentGateway;
use App\Services\Payments\PaymentTransactionNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    Http::preventStrayRequests();

    config()->set([
        'midtrans.server_key' => 'midtrans-server-key',
        'midtrans.client_key' => 'midtrans-client-key',
        'midtrans.is_production' => false,
    ]);
});

it('creates a normalized Midtrans QRIS payment', function () {
    Http::fake([
        'https://api.sandbox.midtrans.com/v2/charge' => Http::response([
            'transaction_id' => 'midtrans-transaction-id',
            'order_id' => 'ORDER-1001',
            'gross_amount' => '100700.00',
            'payment_type' => 'qris',
            'transaction_status' => 'pending',
            'transaction_time' => '2026-09-14 10:30:00',
            'expiry_time' => '2026-09-14 11:30:00',
            'actions' => [
                [
                    'name' => 'generate-qr-code',
                    'method' => 'GET',
                    'url' => 'https://api.sandbox.midtrans.com/v2/qris/qr-code',
                ],
            ],
        ], 201),
    ]);

    $result = app(MidtransPaymentGateway::class)->createPayment(new CreatePaymentData(
        orderId: 'ORDER-1001',
        amount: 100_700,
        paymentMethod: PaymentMethod::Qris,
    ));

    expect($result->id)->toBe('midtrans-transaction-id')
        ->and($result->orderId)->toBe('ORDER-1001')
        ->and($result->amount)->toBe(100_700)
        ->and($result->totalPayment)->toBe(100_700)
        ->and($result->paymentMethod)->toBe(PaymentMethod::Qris)
        ->and($result->providerCode)->toBe('qris')
        ->and($result->paymentNumber)->toBeNull()
        ->and($result->paymentUrl)->toBe('https://api.sandbox.midtrans.com/v2/qris/qr-code')
        ->and($result->status)->toBe(PaymentStatus::Pending)
        ->and($result->expiresAt?->toIso8601String())->toBe('2026-09-14T11:30:00+07:00')
        ->and($result->createdAt?->toIso8601String())->toBe('2026-09-14T10:30:00+07:00');

    Http::assertSent(function (Request $request): bool {
        return $request->method() === 'POST'
            && $request->url() === 'https://api.sandbox.midtrans.com/v2/charge'
            && $request->hasHeader('Authorization', 'Basic '.base64_encode('midtrans-server-key:'))
            && $request->data() === [
                'payment_type' => 'qris',
                'transaction_details' => [
                    'order_id' => 'ORDER-1001',
                    'gross_amount' => 100_700,
                ],
            ];
    });
    Http::assertSentCount(1);
});

it('fetches and normalizes a Midtrans transaction status', function () {
    Http::fake([
        'https://api.sandbox.midtrans.com/v2/ORDER-1002/status' => Http::response([
            'transaction_id' => 'midtrans-status-id',
            'order_id' => 'ORDER-1002',
            'gross_amount' => '104500.00',
            'payment_type' => 'bank_transfer',
            'transaction_status' => 'settlement',
            'transaction_time' => '2026-09-14 09:00:00',
            'expiry_time' => '2026-09-15 09:00:00',
            'va_numbers' => [
                ['bank' => 'bni', 'va_number' => '880812345678'],
            ],
        ]),
    ]);

    $result = app(MidtransPaymentGateway::class)->paymentStatus('ORDER-1002');

    expect($result->paymentMethod)->toBe(PaymentMethod::Bni)
        ->and($result->providerCode)->toBe('bni')
        ->and($result->paymentNumber)->toBe('880812345678')
        ->and($result->status)->toBe(PaymentStatus::Paid);

    Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
        && $request->url() === 'https://api.sandbox.midtrans.com/v2/ORDER-1002/status'
        && $request->hasHeader('Authorization', 'Basic '.base64_encode('midtrans-server-key:')));
    Http::assertSentCount(1);
});

it('reports a Midtrans 404 status as an explicit recoverable not found', function () {
    Http::fake([
        'https://api.sandbox.midtrans.com/v2/ORDER-NOT-FOUND/status' => Http::response([
            'status_message' => 'Transaction does not exist',
        ], 404),
    ]);

    expect(fn () => app(MidtransPaymentGateway::class)->paymentStatus('ORDER-NOT-FOUND'))
        ->toThrow(PaymentTransactionNotFoundException::class);

    Http::assertSentCount(1);
});

it('rejects malformed successful Midtrans responses', function () {
    Http::fake([
        'https://api.sandbox.midtrans.com/v2/charge' => Http::response([
            'transaction_status' => 'pending',
        ], 201),
    ]);

    expect(fn () => app(MidtransPaymentGateway::class)->createPayment(new CreatePaymentData(
        orderId: 'ORDER-1003',
        amount: 10_000,
        paymentMethod: PaymentMethod::Qris,
    )))->toThrow(UnexpectedValueException::class, 'Midtrans returned an invalid payment response.');

    Http::assertSentCount(1);
});
