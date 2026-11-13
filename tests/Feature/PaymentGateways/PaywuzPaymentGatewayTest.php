<?php

use App\Data\Payments\CreatePaymentData;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Services\Payments\PaymentTransactionNotFoundException;
use App\Services\Payments\PaywuzPaymentGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    Http::preventStrayRequests();

    config()->set([
        'payment.drivers.paywuz.api_key' => 'paywuz-api-key',
        'payment.drivers.paywuz.base_url' => 'https://api.paywuz.id/v1',
        'payment.drivers.paywuz.connect_timeout' => 3,
        'payment.drivers.paywuz.timeout' => 10,
        'payment.drivers.paywuz.fee_by_merchant' => false,
    ]);
});

it('fetches and normalizes Paywuz payment methods', function () {
    Http::fake([
        'https://api.paywuz.id/v1/payment-methods' => Http::response([
            'data' => [
                [
                    'code' => 'QRIS',
                    'name' => 'QRIS',
                    'type' => 'qris',
                    'fee' => ['flatIdr' => 290, 'percentBps' => 70, 'totalIdr' => 290],
                    'limits' => ['minIdr' => 10_000, 'maxIdr' => 50_000_000],
                ],
                [
                    'code' => 'BNIVA',
                    'name' => 'BNI Virtual Account',
                    'type' => 'virtual_account',
                    'fee' => ['flatIdr' => 3_400, 'percentBps' => 0, 'totalIdr' => 3_400],
                    'limits' => ['minIdr' => 10_000, 'maxIdr' => 50_000_000],
                ],
                [
                    'code' => 'VA',
                    'name' => 'Virtual Account',
                    'type' => 'meta',
                    'fee' => ['flatIdr' => 0, 'percentBps' => 0, 'totalIdr' => 0],
                    'limits' => ['minIdr' => 10_000, 'maxIdr' => 50_000_000],
                ],
            ],
        ]),
    ]);

    $methods = app(PaywuzPaymentGateway::class)->paymentMethods();

    expect($methods)->toHaveCount(3)
        ->and($methods[0]->paymentMethod)->toBe(PaymentMethod::Qris)
        ->and($methods[0]->providerCode)->toBe('QRIS')
        ->and($methods[0]->flatFee)->toBe(290)
        ->and($methods[0]->percentageFeeBasisPoints)->toBe(70)
        ->and($methods[1]->paymentMethod)->toBe(PaymentMethod::Bni)
        ->and($methods[1]->providerCode)->toBe('BNIVA')
        ->and($methods[1]->minimumAmount)->toBe(10_000)
        ->and($methods[1]->maximumAmount)->toBe(50_000_000)
        ->and($methods[2]->paymentMethod)->toBe(PaymentMethod::Va)
        ->and($methods[2]->providerCode)->toBe('VA')
        ->and($methods[2]->type)->toBe('meta');

    Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
        && $request->url() === 'https://api.paywuz.id/v1/payment-methods'
        && $request->hasHeader('Authorization', 'Bearer paywuz-api-key'));
    Http::assertSentCount(1);
});

it('normalizes Paywuz fee metadata and filters unsupported methods independently', function () {
    config()->set('payment.drivers.paywuz.fee_by_merchant', true);

    Http::fake([
        'https://api.paywuz.id/v1/payment-methods' => Http::response([
            'data' => [
                [
                    'code' => 'QRIS',
                    'name' => 'QRIS',
                    'type' => 'qris',
                    'feeByMerchant' => false,
                    'fee' => ['flatIdr' => 290, 'percentBps' => 70],
                    'limits' => ['minIdr' => 10_000, 'maxIdr' => 50_000_000],
                ],
                [
                    'code' => 'MANDIRIVA',
                    'name' => 'Mandiri Virtual Account',
                    'type' => 'virtual_account',
                    'fee' => ['flatIdr' => 4_000, 'percentBps' => 0, 'totalIdr' => 4_000],
                    'limits' => ['minIdr' => 10_000, 'maxIdr' => 50_000_000],
                ],
                [
                    'code' => 'PERMATAVA',
                    'name' => 'Permata Virtual Account',
                    'type' => 'virtual_account',
                    'fee' => ['flatIdr' => 3_500, 'percentBps' => 0],
                    'limits' => ['minIdr' => 10_000, 'maxIdr' => 50_000_000],
                ],
                [
                    'code' => 'ALL',
                    'name' => 'All Methods',
                    'type' => 'meta',
                    'fee' => ['flatIdr' => 0, 'percentBps' => 0],
                    'limits' => ['minIdr' => 10_000, 'maxIdr' => 50_000_000],
                ],
                [
                    'code' => 'CARD',
                    'name' => 'Unsupported Card',
                    'type' => 'card',
                    'fee' => ['flatIdr' => 0, 'percentBps' => 0],
                    'limits' => ['minIdr' => 10_000, 'maxIdr' => 50_000_000],
                ],
                'malformed-active-entry',
            ],
        ]),
    ]);

    $methods = app(PaywuzPaymentGateway::class)->paymentMethods();

    expect($methods)->toHaveCount(3)
        ->and(array_column($methods, 'providerCode'))->toBe(['QRIS', 'MANDIRIVA', 'PERMATAVA'])
        ->and($methods[0]->totalFee)->toBeNull()
        ->and($methods[0]->feeByMerchant)->toBeFalse()
        ->and($methods[0]->feeTiers)->toBe([
            [
                'minimumAmount' => null,
                'maximumAmount' => 149_999,
                'flatFee' => 290,
                'percentageFeeBasisPoints' => 70,
            ],
            [
                'minimumAmount' => 150_000,
                'maximumAmount' => null,
                'flatFee' => 0,
                'percentageFeeBasisPoints' => 95,
            ],
        ])
        ->and($methods[1]->paymentMethod)->toBe(PaymentMethod::Va)
        ->and($methods[1]->totalFee)->toBe(4_000)
        ->and($methods[1]->feeByMerchant)->toBeTrue()
        ->and($methods[2]->paymentMethod)->toBe(PaymentMethod::Va)
        ->and($methods[2]->totalFee)->toBeNull();

    Http::assertSentCount(1);
});

it('normalizes provider-supplied Paywuz fee tiers', function () {
    Http::fake([
        'https://api.paywuz.id/v1/payment-methods' => Http::response([
            'data' => [[
                'code' => 'QRIS',
                'name' => 'QRIS',
                'type' => 'qris',
                'fee' => [
                    'flatIdr' => 290,
                    'percentBps' => 70,
                    'tiers' => [
                        ['minIdr' => null, 'maxIdr' => 149_999, 'flatIdr' => 290, 'percentBps' => 70],
                        ['minimumAmount' => 150_000, 'maximumAmount' => null, 'flatFee' => 0, 'percentageFeeBasisPoints' => 95],
                    ],
                ],
                'limits' => ['minIdr' => 10_000, 'maxIdr' => 50_000_000],
            ]],
        ]),
    ]);

    $method = app(PaywuzPaymentGateway::class)->paymentMethods()[0];

    expect($method->feeTiers)->toBe([
        [
            'minimumAmount' => null,
            'maximumAmount' => 149_999,
            'flatFee' => 290,
            'percentageFeeBasisPoints' => 70,
        ],
        [
            'minimumAmount' => 150_000,
            'maximumAmount' => null,
            'flatFee' => 0,
            'percentageFeeBasisPoints' => 95,
        ],
    ]);

    Http::assertSentCount(1);
});

it('sends the exact Paywuz provider code while retaining normalized VA semantics', function (?string $providerCode, string $expectedProviderCode) {
    Http::fake([
        'https://api.paywuz.id/v1/transactions' => Http::response([
            'data' => [
                'id' => 'paywuz-va-id',
                'orderId' => 'ORDER-VA',
                'amount' => 150_000,
                'totalPayment' => 153_400,
                'paymentMethod' => $expectedProviderCode,
                'paymentNumber' => null,
                'paymentUrl' => 'https://paywuz.id/pay/paywuz-va-id',
                'status' => 'pending',
                'expiresAt' => '2026-09-15T05:30:00.000Z',
                'createdAt' => '2026-09-14T04:30:00.000Z',
            ],
        ], 201),
    ]);

    $result = app(PaywuzPaymentGateway::class)->createPayment(new CreatePaymentData(
        orderId: 'ORDER-VA',
        amount: 150_000,
        paymentMethod: PaymentMethod::Va,
        providerCode: $providerCode,
    ));

    expect($result->paymentMethod)->toBe(PaymentMethod::Va)
        ->and($result->providerCode)->toBe($expectedProviderCode)
        ->and($result->paymentNumber)->toBeNull()
        ->and($result->paymentUrl)->toBe('https://paywuz.id/pay/paywuz-va-id')
        ->and($result->status)->toBe(PaymentStatus::Pending);

    Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
        && $request->url() === 'https://api.paywuz.id/v1/transactions'
        && $request->data()['paymentMethod'] === $expectedProviderCode);
    Http::assertSentCount(1);
})->with([
    'CIMB virtual account' => ['CIMBVA', 'CIMBVA'],
    'BSI virtual account' => ['BSIVA', 'BSIVA'],
    'Mandiri virtual account' => ['MANDIRIVA', 'MANDIRIVA'],
    'Permata virtual account' => ['PERMATAVA', 'PERMATAVA'],
    'Meta virtual account' => ['VA', 'VA'],
    'Normalized VA fallback' => [null, 'VA'],
]);

it('creates and normalizes a Paywuz QRIS payment with all optional fields', function () {
    Http::fake([
        'https://api.paywuz.id/v1/transactions' => Http::response([
            'data' => [
                'id' => 'paywuz-transaction-id',
                'orderId' => 'ORDER-2001',
                'amount' => 100_700,
                'totalPayment' => 100_700,
                'paymentMethod' => 'QRIS',
                'paymentNumber' => '00020101021226670016COM.NOBUBANK.WWW0118936005030000087914',
                'paymentUrl' => 'https://paywuz.id/pay/paywuz-transaction-id',
                'status' => 'pending',
                'expiresAt' => '2026-09-14T05:30:00.000Z',
                'createdAt' => '2026-09-14T04:30:00.000Z',
            ],
        ], 201),
    ]);

    $result = app(PaywuzPaymentGateway::class)->createPayment(new CreatePaymentData(
        orderId: 'ORDER-2001',
        amount: 100_700,
        paymentMethod: PaymentMethod::Qris,
        expiryMinutes: 60,
        redirectUrl: 'https://shop.example.test/orders/ORDER-2001',
        feeByMerchant: true,
        metadata: ['order_number' => 'ORDER-2001'],
        paymentReminderEnabled: false,
    ));

    expect($result->id)->toBe('paywuz-transaction-id')
        ->and($result->orderId)->toBe('ORDER-2001')
        ->and($result->amount)->toBe(100_700)
        ->and($result->totalPayment)->toBe(100_700)
        ->and($result->paymentMethod)->toBe(PaymentMethod::Qris)
        ->and($result->paymentNumber)->toBe('00020101021226670016COM.NOBUBANK.WWW0118936005030000087914')
        ->and($result->paymentUrl)->toBe('https://paywuz.id/pay/paywuz-transaction-id')
        ->and($result->providerCode)->toBe('QRIS')
        ->and($result->status)->toBe(PaymentStatus::Pending)
        ->and($result->expiresAt?->toIso8601String())->toBe('2026-09-14T05:30:00+00:00')
        ->and($result->createdAt?->toIso8601String())->toBe('2026-09-14T04:30:00+00:00');

    Http::assertSent(function (Request $request): bool {
        return $request->method() === 'POST'
            && $request->url() === 'https://api.paywuz.id/v1/transactions'
            && $request->hasHeader('Authorization', 'Bearer paywuz-api-key')
            && $request->data() === [
                'orderId' => 'ORDER-2001',
                'amount' => 100_700,
                'paymentMethod' => 'QRIS',
                'expiryMinutes' => 60,
                'redirectUrl' => 'https://shop.example.test/orders/ORDER-2001',
                'feeByMerchant' => true,
                'metadata' => ['order_number' => 'ORDER-2001'],
                'paymentReminderEnabled' => false,
            ];
    });
    Http::assertSentCount(1);
});

it('does not follow HTTPS redirects to plaintext HTTP when creating a Paywuz payment', function () {
    $requests = [];

    Http::fake(function (Request $request) use (&$requests) {
        $requests[] = [
            'url' => $request->url(),
            'method' => $request->method(),
            'authorization' => $request->header('Authorization'),
            'body' => $request->body(),
        ];

        if (count($requests) === 1) {
            return Http::response(null, 307, [
                'Location' => 'http://api.paywuz.id/plaintext-capture',
            ]);
        }

        return Http::response(['data' => null]);
    });

    expect(fn () => app(PaywuzPaymentGateway::class)->createPayment(new CreatePaymentData(
        orderId: 'ORDER-REDIRECT',
        amount: 100_000,
        paymentMethod: PaymentMethod::Qris,
    )))->toThrow(UnexpectedValueException::class, 'Paywuz returned an invalid payment response.');

    $plaintextRequests = array_values(array_filter(
        $requests,
        static fn (array $request): bool => str_starts_with($request['url'], 'http://'),
    ));

    expect($requests)->toHaveCount(1)
        ->and($requests[0]['url'])->toBe('https://api.paywuz.id/v1/transactions')
        ->and($requests[0]['method'])->toBe('POST')
        ->and($requests[0]['authorization'])->toBe(['Bearer paywuz-api-key'])
        ->and($requests[0]['body'])->not->toBe('')
        ->and($plaintextRequests)->toBe([])
        ->and(array_filter($plaintextRequests, static fn (array $request): bool => $request['authorization'] !== []))->toBe([])
        ->and(array_filter($plaintextRequests, static fn (array $request): bool => $request['body'] !== ''))->toBe([]);
});

it('fetches and normalizes a Paywuz transaction status', function () {
    Http::fake([
        'https://api.paywuz.id/v1/transactions/ORDER%2F2002' => Http::response([
            'data' => [
                'id' => 'paywuz-status-id',
                'orderId' => 'ORDER/2002',
                'amount' => 100_000,
                'totalPayment' => 103_400,
                'paymentMethod' => 'BNIVA',
                'paymentNumber' => '880812345678',
                'paymentUrl' => null,
                'status' => 'success',
                'expiresAt' => '2026-09-15T04:30:00.000Z',
                'createdAt' => '2026-09-14T04:30:00.000Z',
            ],
        ]),
    ]);

    $result = app(PaywuzPaymentGateway::class)->paymentStatus('ORDER/2002');

    expect($result->paymentMethod)->toBe(PaymentMethod::Bni)
        ->and($result->providerCode)->toBe('BNIVA')
        ->and($result->paymentNumber)->toBe('880812345678')
        ->and($result->status)->toBe(PaymentStatus::Paid);

    Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
        && $request->url() === 'https://api.paywuz.id/v1/transactions/ORDER%2F2002'
        && $request->hasHeader('Authorization', 'Bearer paywuz-api-key'));
    Http::assertSentCount(1);
});

it('normalizes a Meta VA status after Paywuz resolves the selected bank code', function () {
    Http::fake([
        'https://api.paywuz.id/v1/transactions/ORDER-META-STATUS' => Http::response([
            'data' => [
                'id' => 'paywuz-meta-status-id',
                'orderId' => 'ORDER-META-STATUS',
                'amount' => 150_000,
                'totalPayment' => 153_400,
                'paymentMethod' => '014',
                'paymentNumber' => '880812345678',
                'paymentUrl' => 'https://paywuz.id/pay/paywuz-meta-status-id',
                'status' => 'pending',
                'expiresAt' => '2026-09-15T05:30:00.000Z',
                'createdAt' => '2026-09-14T04:30:00.000Z',
            ],
        ]),
    ]);

    $result = app(PaywuzPaymentGateway::class)->paymentStatus('ORDER-META-STATUS');

    expect($result->paymentMethod)->toBe(PaymentMethod::Bca)
        ->and($result->providerCode)->toBe('014')
        ->and($result->paymentNumber)->toBe('880812345678')
        ->and($result->paymentUrl)->toBe('https://paywuz.id/pay/paywuz-meta-status-id')
        ->and($result->status)->toBe(PaymentStatus::Pending);

    Http::assertSentCount(1);
});

it('treats Paywuz settlement as pending and explicit not found as recoverable', function () {
    Http::fake([
        'https://api.paywuz.id/v1/transactions/ORDER-SETTLEMENT' => Http::response([
            'data' => [
                'id' => 'paywuz-settlement-id',
                'orderId' => 'ORDER-SETTLEMENT',
                'amount' => 100_000,
                'totalPayment' => 103_400,
                'paymentMethod' => 'CIMBVA',
                'paymentNumber' => '880812345678',
                'paymentUrl' => null,
                'status' => 'settlement',
                'expiresAt' => null,
                'createdAt' => '2026-09-14T04:30:00.000Z',
            ],
        ]),
        'https://api.paywuz.id/v1/transactions/ORDER-NOT-FOUND' => Http::response([
            'message' => 'transaction does not exist',
        ], 404),
    ]);

    expect(app(PaywuzPaymentGateway::class)->paymentStatus('ORDER-SETTLEMENT')->status)
        ->toBe(PaymentStatus::Pending);

    expect(fn () => app(PaywuzPaymentGateway::class)->paymentStatus('ORDER-NOT-FOUND'))
        ->toThrow(PaymentTransactionNotFoundException::class);

    Http::assertSentCount(2);
});

it('rejects plaintext Paywuz base URLs outside local and testing environments before sending a request', function () {
    app()->detectEnvironment(static fn (): string => 'production');
    config()->set('payment.drivers.paywuz.base_url', 'http://api.paywuz.test/v1');
    Http::fake();

    expect(fn () => app(PaywuzPaymentGateway::class)->paymentMethods())
        ->toThrow(InvalidArgumentException::class, 'Paywuz base URL is not configured correctly.');

    Http::assertNothingSent();
});

it('allows plaintext Paywuz base URLs in explicit development environments', function (string $environment) {
    app()->detectEnvironment(static fn (): string => $environment);
    config()->set('payment.drivers.paywuz.base_url', 'http://127.0.0.1:8080/v1');
    Http::fake([
        'http://127.0.0.1:8080/v1/payment-methods' => Http::response(['data' => []]),
    ]);

    expect(app(PaywuzPaymentGateway::class)->paymentMethods())->toBe([]);

    Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
        && $request->url() === 'http://127.0.0.1:8080/v1/payment-methods');
    Http::assertSentCount(1);
})->with(['local', 'testing']);

it('rejects invalid creation input before sending a Paywuz request', function () {
    Http::fake();

    expect(fn () => new CreatePaymentData(
        orderId: '',
        amount: 0,
        paymentMethod: PaymentMethod::Qris,
    ))->toThrow(InvalidArgumentException::class);

    Http::assertNothingSent();
});

it('rejects malformed Paywuz payment responses', function () {
    Http::fake([
        'https://api.paywuz.id/v1/transactions' => Http::response([
            'data' => [
                'id' => 'paywuz-transaction-id',
                'orderId' => 'ORDER-2003',
                'amount' => 10_000,
                'totalPayment' => 10_000,
                'paymentMethod' => 'QRIS',
                'paymentNumber' => null,
                'paymentUrl' => null,
                'status' => 'pending',
                'expiresAt' => 'not-a-date',
                'createdAt' => '2026-09-14T04:30:00.000Z',
            ],
        ], 201),
    ]);

    expect(fn () => app(PaywuzPaymentGateway::class)->createPayment(new CreatePaymentData(
        orderId: 'ORDER-2003',
        amount: 10_000,
        paymentMethod: PaymentMethod::Qris,
    )))->toThrow(UnexpectedValueException::class, 'Paywuz returned an invalid payment response.');

    Http::assertSentCount(1);
});
