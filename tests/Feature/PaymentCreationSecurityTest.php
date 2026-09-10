<?php

use App\Actions\Ecommerce\Payment\CreatePaymentAction;
use App\Models\Order\Order;
use App\Models\Payment\Payment;
use App\Models\User;
use App\Services\MidtransService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;

uses(DatabaseMigrations::class);

it('creates one payment and calls Midtrans outside a database transaction', function () {
    $user = User::factory()->create();
    $order = Order::factory()->for($user)->create([
        'total' => 100_000,
    ]);

    $midtransService = Mockery::mock(MidtransService::class);
    $midtransService->shouldReceive('createQris')
        ->once()
        ->withArgs(function (string $orderId, int $amount): bool {
            expect(DB::transactionLevel())->toBe(0)
                ->and($orderId)->not->toBeEmpty()
                ->and($amount)->toBe(100_700);

            return true;
        })
        ->andReturn([
            'successful' => true,
            'transaction_id' => 'midtrans-transaction-id',
            'account' => 'https://example.test/qris.png',
            'code' => null,
        ]);

    $action = new CreatePaymentAction($midtransService);

    $firstPayment = $action->handle($order, 'qris', $user);
    $secondPayment = $action->handle($order->refresh(), 'qris', $user);

    expect($secondPayment->is($firstPayment))->toBeTrue()
        ->and(Payment::query()->count())->toBe(1)
        ->and((float) $firstPayment->fee)->toBe(700.0)
        ->and((float) $firstPayment->total)->toBe(100_700.0)
        ->and((float) $order->refresh()->payment_fee)->toBe(700.0);
});

it('rejects payment creation by non owners and invalid guest tokens', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $registeredOrder = Order::factory()->for($owner)->create();
    $guestOrder = Order::factory()->guest()->create([
        'access_token' => 'valid-guest-token',
    ]);

    $midtransService = Mockery::mock(MidtransService::class);
    $midtransService->shouldNotReceive('createQris');
    $action = new CreatePaymentAction($midtransService);

    expect(fn () => $action->handle($registeredOrder, 'qris', $otherUser))
        ->toThrow(AuthorizationException::class);

    expect(fn () => $action->handle($guestOrder, 'qris', guestToken: 'invalid-guest-token'))
        ->toThrow(AuthorizationException::class);
});
