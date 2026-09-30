<?php

use App\Actions\Api\V1\Callback\HandleBiteshipCallbackAction;
use App\Enums\PaymentGatewayDriver;
use App\Enums\PaymentStatus;
use App\Mail\OrderDelivered;
use App\Mail\OrderPaid;
use App\Mail\OrderPaymentFailed;
use App\Models\Order\Order;
use App\Models\Order\OrderShop;
use App\Models\Order\OrderShopShipment;
use App\Models\Payment\Payment;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Testing\TestResponse;
use Symfony\Component\Process\Process;
use Tests\TestCase;

uses(DatabaseMigrations::class);

beforeEach(function () {
    Http::preventStrayRequests();
});

function postRawPaywuzCallback(
    TestCase $testCase,
    string $rawBody,
    ?string $signature,
    ?string $event = 'transaction.paid',
    ?string $delivery = 'delivery-123',
): TestResponse {
    $server = [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_ACCEPT' => 'application/json',
    ];

    if ($signature !== null) {
        $server['HTTP_X_PAYWUZ_SIGNATURE'] = $signature;
    }

    if ($event !== null) {
        $server['HTTP_X_PAYWUZ_EVENT'] = $event;
    }

    if ($delivery !== null) {
        $server['HTTP_X_PAYWUZ_DELIVERY'] = $delivery;
    }

    return $testCase->call(
        'POST',
        route('api.v1.paywuz.callback'),
        server: $server,
        content: $rawBody,
    );
}

function paywuzSignature(string $rawBody, string $apiKey = 'paywuz-test-key'): string
{
    return 'sha256='.hash_hmac('sha256', $rawBody, $apiKey);
}

/** @param array<string, mixed> $overrides */
function fakePaywuzStatus(Payment $payment, string $status, array $overrides = []): void
{
    $providerCode = match (strtolower((string) $payment->channel)) {
        'qris' => 'QRIS',
        'bni' => 'BNIVA',
        'bri' => 'BRIVA',
        'va' => 'VA',
        default => 'BCAVA',
    };

    Http::preventStrayRequests();
    Http::fake([
        'https://api.paywuz.id/v1/transactions/'.rawurlencode((string) $payment->order_id) => Http::response([
            'data' => array_merge([
                'id' => $payment->transaction_id,
                'orderId' => $payment->order_id,
                'amount' => (int) $payment->amount,
                'totalPayment' => (int) $payment->total,
                'paymentMethod' => $providerCode,
                'paymentNumber' => $payment->account_number,
                'paymentUrl' => null,
                'status' => $status,
                'expiresAt' => $payment->expired_at?->toIso8601String(),
                'createdAt' => now()->subMinute()->toIso8601String(),
            ], $overrides),
        ]),
    ]);
}

it('handles duplicate Midtrans settlement callbacks once', function () {
    Mail::fake();
    config(['midtrans.server_key' => 'midtrans-test-secret']);

    $order = Order::factory()->create();
    $payment = Payment::factory()->create([
        'payable_type' => Order::class,
        'payable_id' => $order->getKey(),
        'order_id' => 'midtrans-order-123',
        'paid_at' => null,
        'expired_at' => now()->addDay(),
    ]);

    $payload = [
        'order_id' => $payment->order_id,
        'status_code' => '200',
        'gross_amount' => '118000.00',
        'transaction_status' => 'settlement',
    ];
    $payload['signature_key'] = hash(
        'sha512',
        $payload['order_id'].$payload['status_code'].$payload['gross_amount'].'midtrans-test-secret',
    );
    Http::fake([
        'https://api.sandbox.midtrans.com/v2/midtrans-order-123/status' => Http::response([
            'transaction_id' => $payment->transaction_id,
            'order_id' => $payment->order_id,
            'gross_amount' => (string) $payment->total,
            'payment_type' => 'bank_transfer',
            'transaction_status' => 'settlement',
            'va_numbers' => [['bank' => 'bca', 'va_number' => $payment->account_number]],
        ]),
    ]);

    $this->postJson(route('api.v1.midtrans.callback'), $payload)->assertOk();
    $this->postJson(route('api.v1.midtrans.callback'), $payload)->assertOk();

    expect($payment->refresh()->paid_at)->not->toBeNull()
        ->and($order->refresh()->status)->toBeTrue();

    Http::assertSentCount(2);
    Mail::assertSent(OrderPaid::class, 1);
    Mail::assertNotSent(OrderPaymentFailed::class);
});

it('accepts an exact raw-body Paywuz HMAC and reconciles the payment', function () {
    config(['payment.drivers.paywuz.api_key' => 'paywuz-test-key']);

    $order = Order::factory()->create();
    $payment = Payment::factory()->create([
        'driver' => PaymentGatewayDriver::Paywuz->value,
        'payable_type' => Order::class,
        'payable_id' => $order->getKey(),
        'order_id' => 'paywuz-order-raw-body',
        'paid_at' => null,
        'expired_at' => now()->addDay(),
    ]);
    Mail::fake();

    $rawBody = "{\n  \"orderId\": \"{$payment->order_id}\",\n  \"status\": \"success\"\n}";
    fakePaywuzStatus($payment, 'success');

    postRawPaywuzCallback($this, $rawBody, paywuzSignature($rawBody))
        ->assertOk();

    expect($payment->refresh()->paid_at)->not->toBeNull()
        ->and($order->refresh()->status)->toBeTrue();

    Http::assertSentCount(1);
    Mail::assertSent(OrderPaid::class, 1);
    Mail::assertNotSent(OrderPaymentFailed::class);
});

it('rejects Paywuz callbacks with missing or blank authentication headers', function (?string $signature, ?string $event, ?string $delivery) {
    config(['payment.drivers.paywuz.api_key' => 'paywuz-test-key']);

    $order = Order::factory()->create();
    $payment = Payment::factory()->create([
        'driver' => PaymentGatewayDriver::Paywuz->value,
        'payable_type' => Order::class,
        'payable_id' => $order->getKey(),
        'order_id' => 'paywuz-order-missing-header-'.fake()->unique()->numerify('####'),
        'paid_at' => null,
        'expired_at' => now()->addDay(),
    ]);
    Mail::fake();

    $rawBody = json_encode([
        'orderId' => $payment->order_id,
        'status' => 'paid',
    ], JSON_THROW_ON_ERROR);

    postRawPaywuzCallback(
        $this,
        $rawBody,
        $signature === 'valid' ? paywuzSignature($rawBody) : $signature,
        $event,
        $delivery,
    )->assertUnauthorized();

    expect($payment->refresh()->paid_at)->toBeNull()
        ->and($order->refresh()->status)->toBeFalse();

    Mail::assertNothingSent();
})->with([
    'missing signature' => [null, 'transaction.paid', 'delivery-123'],
    'blank signature' => [' ', 'transaction.paid', 'delivery-123'],
    'missing event' => ['valid', null, 'delivery-123'],
    'blank event' => ['valid', ' ', 'delivery-123'],
    'missing delivery' => ['valid', 'transaction.paid', null],
    'blank delivery' => ['valid', 'transaction.paid', ' '],
]);

it('rejects an invalid Paywuz signature before mutating payment state', function () {
    config(['payment.drivers.paywuz.api_key' => 'paywuz-test-key']);

    $order = Order::factory()->create();
    $payment = Payment::factory()->create([
        'driver' => PaymentGatewayDriver::Paywuz->value,
        'payable_type' => Order::class,
        'payable_id' => $order->getKey(),
        'order_id' => 'paywuz-order-invalid-signature',
        'paid_at' => null,
        'expired_at' => now()->addDay(),
    ]);
    Mail::fake();

    $rawBody = json_encode(['orderId' => $payment->order_id, 'status' => 'paid'], JSON_THROW_ON_ERROR);

    postRawPaywuzCallback($this, $rawBody, 'sha256='.str_repeat('0', 64))
        ->assertForbidden();

    expect($payment->refresh()->paid_at)->toBeNull()
        ->and($order->refresh()->status)->toBeFalse();

    Mail::assertNothingSent();
});

it('rejects a Paywuz signature generated from re-encoded JSON', function () {
    config(['payment.drivers.paywuz.api_key' => 'paywuz-test-key']);

    $order = Order::factory()->create();
    $payment = Payment::factory()->create([
        'driver' => PaymentGatewayDriver::Paywuz->value,
        'payable_type' => Order::class,
        'payable_id' => $order->getKey(),
        'order_id' => 'paywuz-order-reencoded',
        'paid_at' => null,
        'expired_at' => now()->addDay(),
    ]);
    Mail::fake();

    $signedBody = json_encode(['orderId' => $payment->order_id, 'status' => 'paid'], JSON_THROW_ON_ERROR);
    $sentBody = json_encode(['status' => 'paid', 'orderId' => $payment->order_id], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);

    postRawPaywuzCallback($this, $sentBody, paywuzSignature($signedBody))
        ->assertForbidden();

    expect($payment->refresh()->paid_at)->toBeNull()
        ->and($order->refresh()->status)->toBeFalse();

    Mail::assertNothingSent();
});

it('rejects signed malformed Paywuz JSON without mutation', function () {
    config(['payment.drivers.paywuz.api_key' => 'paywuz-test-key']);

    $order = Order::factory()->create();
    $payment = Payment::factory()->create([
        'driver' => PaymentGatewayDriver::Paywuz->value,
        'payable_type' => Order::class,
        'payable_id' => $order->getKey(),
        'order_id' => 'paywuz-order-malformed-json',
        'paid_at' => null,
        'expired_at' => now()->addDay(),
    ]);
    Mail::fake();

    $rawBody = '{"orderId":';

    postRawPaywuzCallback($this, $rawBody, paywuzSignature($rawBody))
        ->assertBadRequest();

    expect($payment->refresh()->paid_at)->toBeNull()
        ->and($order->refresh()->status)->toBeFalse();

    Mail::assertNothingSent();
});

it('maps Paywuz statuses safely', function (string $event, string $providerStatus, PaymentStatus $authoritativeStatus, bool $isPaid, bool $isFailed) {
    config(['payment.drivers.paywuz.api_key' => 'paywuz-test-key']);

    $order = Order::factory()->create();
    $payment = Payment::factory()->create([
        'driver' => PaymentGatewayDriver::Paywuz->value,
        'payable_type' => Order::class,
        'payable_id' => $order->getKey(),
        'order_id' => 'paywuz-status-'.str_replace('_', '-', $providerStatus),
        'paid_at' => null,
        'expired_at' => now()->addDay(),
    ]);
    $originalExpiry = $payment->expired_at->copy();
    Mail::fake();

    $rawBody = json_encode([
        'orderId' => $payment->order_id,
        'status' => $providerStatus,
    ], JSON_THROW_ON_ERROR);
    fakePaywuzStatus($payment, match ($authoritativeStatus) {
        PaymentStatus::Paid => 'success',
        PaymentStatus::Failed => 'failed',
        PaymentStatus::Cancelled => 'cancelled',
        default => 'settlement',
    });

    postRawPaywuzCallback(
        $this,
        $rawBody,
        paywuzSignature($rawBody),
        $event,
        'delivery-'.$providerStatus,
    )->assertOk();

    $payment->refresh();
    $order->refresh();

    expect($payment->paid_at !== null)->toBe($isPaid)
        ->and($payment->expired_at->isPast())->toBe($isFailed)
        ->and($order->status)->toBe($isPaid);

    if (! $isPaid && ! $isFailed) {
        expect($payment->expired_at->equalTo($originalExpiry))->toBeTrue();
    }

    if ($isPaid) {
        Mail::assertSent(OrderPaid::class, 1);
        Mail::assertNotSent(OrderPaymentFailed::class);
    } elseif ($isFailed) {
        Mail::assertSent(OrderPaymentFailed::class, 1);
        Mail::assertNotSent(OrderPaid::class);
    } else {
        Mail::assertNothingSent();
    }
})->with([
    'settlement remains pending' => ['transaction.settlement', 'settlement', PaymentStatus::Pending, false, false],
    'paid requires success' => ['transaction.paid', 'success', PaymentStatus::Paid, true, false],
    'failed' => ['transaction.failed', 'failed', PaymentStatus::Failed, false, true],
    'cancelled' => ['transaction.cancelled', 'cancelled', PaymentStatus::Cancelled, false, true],
]);

it('rejects unknown or inconsistent Paywuz event and status pairs', function (string $event, string $status) {
    config(['payment.drivers.paywuz.api_key' => 'paywuz-test-key']);

    $order = Order::factory()->create();
    $payment = Payment::factory()->create([
        'driver' => PaymentGatewayDriver::Paywuz->value,
        'payable_type' => Order::class,
        'payable_id' => $order->getKey(),
        'order_id' => 'paywuz-invalid-pair-'.fake()->unique()->numerify('####'),
        'paid_at' => null,
        'expired_at' => now()->addDay(),
    ]);
    $originalExpiry = $payment->expired_at->copy();
    Mail::fake();
    Http::fake();

    $rawBody = json_encode(['orderId' => $payment->order_id, 'status' => $status], JSON_THROW_ON_ERROR);

    postRawPaywuzCallback($this, $rawBody, paywuzSignature($rawBody), $event)
        ->assertBadRequest();

    expect($payment->refresh()->paid_at)->toBeNull()
        ->and($payment->expired_at->equalTo($originalExpiry))->toBeTrue()
        ->and($order->refresh()->status)->toBeFalse();

    Http::assertNothingSent();
    Mail::assertNothingSent();
})->with([
    'settlement cannot claim success' => ['transaction.settlement', 'success'],
    'paid cannot claim settlement' => ['transaction.paid', 'settlement'],
    'unknown event' => ['transaction.reviewing', 'reviewing'],
    'unknown status' => ['transaction.paid', 'paid'],
]);

it('deduplicates Paywuz callback side effects', function () {
    config(['payment.drivers.paywuz.api_key' => 'paywuz-test-key']);

    $order = Order::factory()->create();
    $payment = Payment::factory()->create([
        'driver' => PaymentGatewayDriver::Paywuz->value,
        'payable_type' => Order::class,
        'payable_id' => $order->getKey(),
        'order_id' => 'paywuz-order-duplicate',
        'paid_at' => null,
        'expired_at' => now()->addDay(),
    ]);
    Mail::fake();

    $rawBody = json_encode(['orderId' => $payment->order_id, 'status' => 'success'], JSON_THROW_ON_ERROR);
    $signature = paywuzSignature($rawBody);
    fakePaywuzStatus($payment, 'success');

    postRawPaywuzCallback($this, $rawBody, $signature, 'transaction.paid', 'delivery-duplicate')->assertOk();
    postRawPaywuzCallback($this, $rawBody, $signature, 'transaction.paid', 'delivery-duplicate')->assertOk();

    expect($payment->refresh()->paid_at)->not->toBeNull()
        ->and($order->refresh()->status)->toBeTrue();

    Http::assertSentCount(1);
    Mail::assertSent(OrderPaid::class, 1);
    Mail::assertNotSent(OrderPaymentFailed::class);
});

it('serializes concurrent Paywuz callbacks for the same delivery across processes', function () {
    $probeDirectory = sys_get_temp_dir().'/paywuz-callback-concurrency-'.bin2hex(random_bytes(8));
    $databasePath = $probeDirectory.'/database.sqlite';
    $gatePath = $probeDirectory.'/start.gate';
    $workerOneReadyPath = $probeDirectory.'/worker-1.ready';
    $workerTwoReadyPath = $probeDirectory.'/worker-2.ready';
    $lockPath = $probeDirectory.'/locks';
    $workers = [];

    mkdir($probeDirectory, 0700, true);
    mkdir($lockPath, 0700, true);
    touch($databasePath);

    $environment = [
        'APP_ENV' => 'testing',
        'DB_CONNECTION' => 'sqlite',
        'DB_DATABASE' => $databasePath,
        'CACHE_STORE' => 'array',
        'SESSION_DRIVER' => 'array',
        'QUEUE_CONNECTION' => 'sync',
        'MAIL_MAILER' => 'array',
    ];

    $artisan = function (array $arguments, array $extraEnvironment = []) use ($environment): Process {
        $process = new Process(
            [PHP_BINARY, 'artisan', ...$arguments, '--no-interaction'],
            base_path(),
            array_merge($environment, $extraEnvironment),
        );
        $process->setTimeout(60);

        return $process;
    };

    $seedScript = <<<'PHP'
$order = \App\Models\Order\Order::factory()->create();
\App\Models\Payment\Payment::factory()->create([
    'driver' => \App\Enums\PaymentGatewayDriver::Paywuz->value,
    'payable_type' => \App\Models\Order\Order::class,
    'payable_id' => $order->getKey(),
    'order_id' => 'paywuz-concurrent-delivery',
    'paid_at' => null,
    'expired_at' => now()->addDay(),
]);
PHP;

    $workerScript = <<<'PHP'
config([
    'payment.drivers.paywuz.api_key' => 'paywuz-concurrency-test-key',
    'payment.drivers.paywuz.base_url' => 'https://api.paywuz.id/v1',
    'cache.stores.file.path' => (string) getenv('PROBE_LOCK_PATH'),
    'cache.stores.file.lock_path' => (string) getenv('PROBE_LOCK_PATH'),
]);
touch((string) getenv('PROBE_READY'));
while (! file_exists((string) getenv('PROBE_GATE'))) {
    usleep(1000);
}
$payment = \App\Models\Payment\Payment::query()
    ->where('order_id', 'paywuz-concurrent-delivery')
    ->firstOrFail();
\Illuminate\Support\Facades\Mail::fake();
\Illuminate\Support\Facades\Http::preventStrayRequests();
\Illuminate\Support\Facades\Http::fake(function () use ($payment) {
    echo "PROVIDER_REQUEST\n";
    usleep(1500000);

    return \Illuminate\Support\Facades\Http::response(['data' => [
        'id' => $payment->transaction_id,
        'orderId' => $payment->order_id,
        'amount' => (int) $payment->amount,
        'totalPayment' => (int) $payment->total,
        'paymentMethod' => 'BCAVA',
        'paymentNumber' => $payment->account_number,
        'paymentUrl' => null,
        'status' => 'success',
        'expiresAt' => $payment->expired_at?->toIso8601String(),
        'createdAt' => now()->subMinute()->toIso8601String(),
    ]], 200);
});
$rawBody = json_encode([
    'orderId' => $payment->order_id,
    'status' => 'success',
], JSON_THROW_ON_ERROR);
$request = \Illuminate\Http\Request::create('/', 'POST', server: [
    'CONTENT_TYPE' => 'application/json',
    'HTTP_ACCEPT' => 'application/json',
    'HTTP_X_PAYWUZ_SIGNATURE' => 'sha256='.hash_hmac('sha256', $rawBody, 'paywuz-concurrency-test-key'),
    'HTTP_X_PAYWUZ_EVENT' => 'transaction.paid',
    'HTTP_X_PAYWUZ_DELIVERY' => 'same-concurrent-delivery',
], content: $rawBody);
try {
    app(\App\Actions\Api\V1\Callback\HandlePaywuzCallbackAction::class)->handle(new \App\Data\Callbacks\PaywuzCallbackData(
        $request->getContent(),
        $request->header('X-Paywuz-Signature'),
        $request->header('X-Paywuz-Event'),
        $request->header('X-Paywuz-Delivery'),
    ));
    echo "STATUS=200\n";
} catch (\Throwable $exception) {
    echo "STATUS=500\n";
    echo 'ERROR_CLASS='.$exception::class."\n";
    echo 'ERROR_MESSAGE='.$exception->getMessage()."\n";
}
PHP;

    $stateScript = <<<'PHP'
$payment = \App\Models\Payment\Payment::query()
    ->where('order_id', 'paywuz-concurrent-delivery')
    ->firstOrFail();
$markers = \Spatie\Activitylog\Models\Activity::query()
    ->where('log_name', 'paywuz')
    ->where('event', 'callback_processed')
    ->where('subject_type', $payment->getMorphClass())
    ->where('subject_id', $payment->getKey())
    ->count();
echo "MARKERS={$markers}\n";
echo 'PAID='.($payment->paid_at !== null ? 'yes' : 'no')."\n";
PHP;

    try {
        $artisan(['migrate:fresh', '--force'])->mustRun();
        $artisan(['tinker', '--execute', $seedScript])->mustRun();

        $workerOne = $artisan(['tinker', '--execute', $workerScript], [
            'PROBE_GATE' => $gatePath,
            'PROBE_READY' => $workerOneReadyPath,
            'PROBE_LOCK_PATH' => $lockPath,
        ]);
        $workerTwo = $artisan(['tinker', '--execute', $workerScript], [
            'PROBE_GATE' => $gatePath,
            'PROBE_READY' => $workerTwoReadyPath,
            'PROBE_LOCK_PATH' => $lockPath,
        ]);
        $workers = [$workerOne, $workerTwo];

        $workerOne->start();
        $workerTwo->start();

        $readyDeadline = microtime(true) + 15;

        while ((! file_exists($workerOneReadyPath) || ! file_exists($workerTwoReadyPath)) && microtime(true) < $readyDeadline) {
            usleep(10000);
        }

        expect($workerOneReadyPath)->toBeFile()
            ->and($workerTwoReadyPath)->toBeFile();

        touch($gatePath);
        $workerOne->wait();
        $workerTwo->wait();

        $workerOutput = $workerOne->getOutput().$workerOne->getErrorOutput()
            .$workerTwo->getOutput().$workerTwo->getErrorOutput();
        $stateProcess = $artisan(['tinker', '--execute', $stateScript]);
        $stateProcess->mustRun();
        $stateOutput = $stateProcess->getOutput().$stateProcess->getErrorOutput();
        $diagnostics = $workerOutput.$stateOutput;

        expect(preg_match_all('/^PROVIDER_REQUEST$/m', $workerOutput))->toBe(1, $diagnostics)
            ->and(preg_match_all('/^STATUS=200$/m', $workerOutput))->toBe(2, $diagnostics)
            ->and($stateOutput)->toContain("MARKERS=1\n", "PAID=yes\n");
    } finally {
        foreach ($workers as $worker) {
            if ($worker->isRunning()) {
                $worker->stop(1);
            }
        }

        File::deleteDirectory($probeDirectory);
    }
});

it('retries a failed Paywuz notification without repeating the provider lookup', function () {
    config(['payment.drivers.paywuz.api_key' => 'paywuz-test-key']);
    Exceptions::fake();

    $order = Order::factory()->create();
    $payment = Payment::factory()->create([
        'driver' => PaymentGatewayDriver::Paywuz->value,
        'payable_type' => Order::class,
        'payable_id' => $order->getKey(),
        'order_id' => 'paywuz-order-notification-retry',
        'paid_at' => null,
        'expired_at' => now()->addDay(),
    ]);

    $rawBody = json_encode(['orderId' => $payment->order_id, 'status' => 'success'], JSON_THROW_ON_ERROR);
    $signature = paywuzSignature($rawBody);
    fakePaywuzStatus($payment, 'success');

    Event::listen(MessageSending::class, function (): never {
        throw new RuntimeException('simulated-mail-failure');
    });

    postRawPaywuzCallback($this, $rawBody, $signature, 'transaction.paid', 'delivery-notification-retry')
        ->assertStatus(500);

    expect($payment->refresh()->paid_at)->not->toBeNull()
        ->and($order->refresh()->status)->toBeTrue();

    Event::forget(MessageSending::class);
    Mail::fake();

    postRawPaywuzCallback($this, $rawBody, $signature, 'transaction.paid', 'delivery-notification-retry')
        ->assertOk();

    Http::assertSentCount(1);
    Mail::assertSent(OrderPaid::class, 1);
    Exceptions::assertReported(RuntimeException::class);
});

it('calls Paywuz authoritatively for distinct valid delivery ids', function () {
    config(['payment.drivers.paywuz.api_key' => 'paywuz-test-key']);

    $order = Order::factory()->create();
    $payment = Payment::factory()->create([
        'driver' => PaymentGatewayDriver::Paywuz->value,
        'payable_type' => Order::class,
        'payable_id' => $order->getKey(),
        'order_id' => 'paywuz-order-distinct-deliveries',
        'paid_at' => null,
        'expired_at' => now()->addDay(),
    ]);
    Mail::fake();

    $rawBody = json_encode(['orderId' => $payment->order_id, 'status' => 'success'], JSON_THROW_ON_ERROR);
    $signature = paywuzSignature($rawBody);
    fakePaywuzStatus($payment, 'success');

    postRawPaywuzCallback($this, $rawBody, $signature, 'transaction.paid', 'delivery-distinct-1')->assertOk();
    postRawPaywuzCallback($this, $rawBody, $signature, 'transaction.paid', 'delivery-distinct-2')->assertOk();

    Http::assertSentCount(2);
    Mail::assertSent(OrderPaid::class, 1);
});

it('rejects malformed Paywuz delivery ids before the authoritative lookup', function (string $deliveryId) {
    config(['payment.drivers.paywuz.api_key' => 'paywuz-test-key']);

    $order = Order::factory()->create();
    $payment = Payment::factory()->create([
        'driver' => PaymentGatewayDriver::Paywuz->value,
        'payable_type' => Order::class,
        'payable_id' => $order->getKey(),
        'order_id' => 'paywuz-order-invalid-delivery-'.fake()->unique()->numerify('####'),
        'paid_at' => null,
        'expired_at' => now()->addDay(),
    ]);
    Mail::fake();
    Http::preventStrayRequests();
    Http::fake();

    $rawBody = json_encode(['orderId' => $payment->order_id, 'status' => 'success'], JSON_THROW_ON_ERROR);

    postRawPaywuzCallback($this, $rawBody, paywuzSignature($rawBody), 'transaction.paid', $deliveryId)
        ->assertBadRequest();

    Http::assertNothingSent();
    Mail::assertNothingSent();
})->with([
    'surrounding whitespace' => [' delivery-malformed '],
    'oversized value' => [str_repeat('d', 256)],
    'non-ASCII value' => ['delivery-☃'],
]);

it('ignores stale Paywuz delivery JSON and records a usable marker', function () {
    config(['payment.drivers.paywuz.api_key' => 'paywuz-test-key']);

    $order = Order::factory()->create();
    $payment = Payment::factory()->create([
        'driver' => PaymentGatewayDriver::Paywuz->value,
        'payable_type' => Order::class,
        'payable_id' => $order->getKey(),
        'order_id' => 'paywuz-order-stale-delivery-json',
        'paid_at' => null,
        'expired_at' => now()->addDay(),
    ]);
    Mail::fake();

    DB::table(config('activitylog.table_name'))->insert([
        'log_name' => 'paywuz',
        'description' => 'Paywuz callback delivery processed',
        'subject_type' => $payment->getMorphClass(),
        'subject_id' => $payment->getKey(),
        'event' => 'callback_processed',
        'properties' => json_encode(['delivery_id_sha256' => ['stale']], JSON_THROW_ON_ERROR),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $rawBody = json_encode(['orderId' => $payment->order_id, 'status' => 'success'], JSON_THROW_ON_ERROR);
    $signature = paywuzSignature($rawBody);
    fakePaywuzStatus($payment, 'success');

    postRawPaywuzCallback($this, $rawBody, $signature, 'transaction.paid', 'delivery-after-stale-json')->assertOk();
    postRawPaywuzCallback($this, $rawBody, $signature, 'transaction.paid', 'delivery-after-stale-json')->assertOk();

    $markerProperties = DB::table(config('activitylog.table_name'))
        ->where('log_name', 'paywuz')
        ->where('event', 'callback_processed')
        ->where('subject_type', $payment->getMorphClass())
        ->where('subject_id', $payment->getKey())
        ->pluck('properties');

    expect($markerProperties)->toHaveCount(2)
        ->and($markerProperties->contains(fn (string $properties): bool => str_contains(
            $properties,
            hash('sha256', 'delivery-after-stale-json'),
        )))->toBeTrue()
        ->and($markerProperties->contains(fn (string $properties): bool => str_contains(
            $properties,
            'delivery-after-stale-json',
        )))->toBeFalse();

    Http::assertSentCount(1);
    Mail::assertSent(OrderPaid::class, 1);
});

it('makes paid win over stale failed or cancelled Paywuz callbacks', function (string $firstStatus, string $lateStatus, bool $sendsFailureMail) {
    config(['payment.drivers.paywuz.api_key' => 'paywuz-test-key']);

    $order = Order::factory()->create();
    $payment = Payment::factory()->create([
        'driver' => PaymentGatewayDriver::Paywuz->value,
        'payable_type' => Order::class,
        'payable_id' => $order->getKey(),
        'order_id' => 'paywuz-terminal-'.str_replace('_', '-', $firstStatus),
        'paid_at' => null,
        'expired_at' => now()->addDay(),
    ]);
    Mail::fake();
    $statusResponse = fn (string $status): array => [
        'data' => [
            'id' => $payment->transaction_id,
            'orderId' => $payment->order_id,
            'amount' => (int) $payment->amount,
            'totalPayment' => (int) $payment->total,
            'paymentMethod' => 'BCAVA',
            'paymentNumber' => $payment->account_number,
            'paymentUrl' => null,
            'status' => $status,
            'expiresAt' => $payment->expired_at?->toIso8601String(),
            'createdAt' => now()->subMinute()->toIso8601String(),
        ],
    ];
    Http::fake([
        'https://api.paywuz.id/v1/transactions/'.rawurlencode((string) $payment->order_id) => Http::sequence()
            ->push($statusResponse($firstStatus))
            ->push($statusResponse($lateStatus)),
    ]);

    foreach ([$firstStatus, $lateStatus] as $index => $providerStatus) {
        $rawBody = json_encode([
            'orderId' => $payment->order_id,
            'status' => $providerStatus,
        ], JSON_THROW_ON_ERROR);

        postRawPaywuzCallback(
            $this,
            $rawBody,
            paywuzSignature($rawBody),
            $providerStatus === 'success' ? 'transaction.paid' : 'transaction.'.$providerStatus,
            'delivery-terminal-'.$index,
        )->assertOk();
    }

    $payment->refresh();
    $order->refresh();

    expect($payment->paid_at)->not->toBeNull()
        ->and($order->status)->toBeTrue();

    Mail::assertSent(OrderPaid::class, 1);

    if ($sendsFailureMail) {
        Mail::assertSent(OrderPaymentFailed::class, 1);
    } else {
        Mail::assertNotSent(OrderPaymentFailed::class);
    }
})->with([
    'paid then failed' => ['success', 'failed', false],
    'failed then paid' => ['failed', 'success', true],
    'paid then cancelled' => ['success', 'cancelled', false],
    'cancelled then paid' => ['cancelled', 'success', true],
]);

it('rejects a Paywuz paid callback when authoritative transaction data mismatches local principal', function () {
    config(['payment.drivers.paywuz.api_key' => 'paywuz-test-key']);
    Exceptions::fake();

    $order = Order::factory()->create();
    $payment = Payment::factory()->create([
        'driver' => PaymentGatewayDriver::Paywuz->value,
        'payable_type' => Order::class,
        'payable_id' => $order->getKey(),
        'order_id' => 'paywuz-mismatched-principal',
        'paid_at' => null,
        'expired_at' => now()->addDay(),
    ]);
    Mail::fake();
    fakePaywuzStatus($payment, 'success', ['amount' => (int) $payment->amount - 1]);

    $rawBody = json_encode(['orderId' => $payment->order_id, 'status' => 'success'], JSON_THROW_ON_ERROR);

    postRawPaywuzCallback($this, $rawBody, paywuzSignature($rawBody), 'transaction.paid')
        ->assertStatus(500)
        ->assertJsonPath('message', 'Callback could not be processed.')
        ->assertDontSee('mismatched', false);

    expect($payment->refresh()->paid_at)->toBeNull()
        ->and($order->refresh()->status)->toBeFalse();
    Mail::assertNothingSent();
    Exceptions::assertReported(UnexpectedValueException::class);
});

it('reports unexpected callback failures without leaking exception details', function () {
    Exceptions::fake();
    config(['payment.drivers.paywuz.api_key' => '']);

    postRawPaywuzCallback($this, '{}', 'signature')
        ->assertStatus(500)
        ->assertJsonPath('message', 'Callback could not be processed.')
        ->assertDontSee('Paywuz API key is not configured.', false);

    Exceptions::assertReported(fn (RuntimeException $exception): bool => $exception->getMessage() === 'Paywuz API key is not configured.');
});

it('does not let a Midtrans callback mutate a Paywuz payment', function () {
    config(['midtrans.server_key' => 'midtrans-test-secret']);

    $order = Order::factory()->create();
    $payment = Payment::factory()->create([
        'driver' => PaymentGatewayDriver::Paywuz->value,
        'payable_type' => Order::class,
        'payable_id' => $order->getKey(),
        'order_id' => 'shared-provider-order-id',
        'paid_at' => null,
        'expired_at' => now()->addDay(),
    ]);
    $originalExpiry = $payment->expired_at->copy();
    Mail::fake();

    $payload = [
        'order_id' => $payment->order_id,
        'status_code' => '200',
        'gross_amount' => '118000.00',
        'transaction_status' => 'settlement',
    ];
    $payload['signature_key'] = hash(
        'sha512',
        $payload['order_id'].$payload['status_code'].$payload['gross_amount'].'midtrans-test-secret',
    );

    $this->postJson(route('api.v1.midtrans.callback'), $payload)->assertNotFound();

    expect($payment->refresh()->paid_at)->toBeNull()
        ->and($payment->expired_at->equalTo($originalExpiry))->toBeTrue()
        ->and($order->refresh()->status)->toBeFalse();

    Mail::assertNothingSent();
});

it('deduplicates Biteship delivery callbacks and sends one email after commit', function () {
    Mail::fake();
    config([
        'services.biteship.webhook.header_key' => 'X-Biteship-Webhook-Secret',
        'services.biteship.webhook.header_secret' => 'biteship-test-secret',
    ]);

    $orderShop = OrderShop::factory()->create([
        'shipping_status' => false,
    ]);
    OrderShopShipment::factory()->create([
        'order_shop_id' => $orderShop->getKey(),
        'provider_event_key' => null,
        'courier_waybill_id' => 'WAYBILL-123',
        'courier_tracking_id' => 'TRACKING-123',
        'status' => 'allocated',
    ]);

    $payload = [
        'event' => 'order.status',
        'status' => 'delivered',
        'courier_waybill_id' => 'WAYBILL-123',
        'courier_tracking_id' => 'TRACKING-123',
        'courier_company' => 'jne',
        'courier_type' => 'reg',
    ];

    $this->withHeader('X-Biteship-Webhook-Secret', 'biteship-test-secret')
        ->postJson(route('api.v1.biteship.callback'), $payload)
        ->assertOk();

    $this->withHeader('X-Biteship-Webhook-Secret', 'biteship-test-secret')
        ->postJson(route('api.v1.biteship.callback'), array_reverse($payload, true))
        ->assertOk();

    expect($orderShop->refresh()->shipping_status)->toBeTrue()
        ->and(OrderShopShipment::query()->where('order_shop_id', $orderShop->getKey())->count())->toBe(2)
        ->and(OrderShopShipment::query()->whereNotNull('provider_event_key')->count())->toBe(1);

    Mail::assertSent(OrderDelivered::class, 1);
});

it('rejects Biteship callbacks when webhook authentication is incomplete', function (?string $headerKey, ?string $headerSecret, bool $sendHeader) {
    config([
        'services.biteship.webhook.header_key' => $headerKey,
        'services.biteship.webhook.header_secret' => $headerSecret,
    ]);

    $action = Mockery::mock(HandleBiteshipCallbackAction::class);
    $action->shouldNotReceive('handle');
    $this->app->instance(HandleBiteshipCallbackAction::class, $action);

    $request = $sendHeader && $headerKey
        ? $this->withHeader($headerKey, 'biteship-test-secret')
        : $this;

    $request->postJson(route('api.v1.biteship.callback'), ['event' => 'order.status'])
        ->assertUnauthorized();
})->with([
    'missing configured header key' => [null, 'biteship-test-secret', false],
    'missing configured secret' => ['X-Biteship-Webhook-Secret', null, true],
    'missing request header' => ['X-Biteship-Webhook-Secret', 'biteship-test-secret', false],
]);
