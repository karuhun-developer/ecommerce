<?php

use App\Actions\Ecommerce\Checkout\StoreCheckoutAction;
use App\Models\Location\Location;
use App\Models\Product\Product;
use App\Models\Product\ProductFlat;
use App\Models\Shop\Shop;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

it('registers the ecommerce.checkout.checkout component', function () {
    expect(Livewire::exists('ecommerce.checkout.checkout'))->toBeTrue();
});

it('rejects a delivery location owned by another user', function () {
    $user = User::factory()->create();
    $foreignLocation = Location::factory()->create();
    $this->actingAs($user);

    expect(fn () => app(StoreCheckoutAction::class)->handle([
        'selected_location_id' => $foreignLocation->id,
        'shop_groups' => [],
    ]))->toThrow(ValidationException::class);
});

it('recalculates product prices totals and fees from canonical data', function () {
    Mail::fake();

    $user = User::factory()->create();
    $location = Location::factory()->for($user)->create();
    $shop = Shop::factory()->create();
    $product = Product::factory()->for($shop)->create();
    $productFlat = ProductFlat::factory()->for($product)->create([
        'shop_id' => $shop->id,
        'price' => 10_000,
        'stock' => 10,
        'status' => true,
    ]);
    $this->actingAs($user);

    $order = app(StoreCheckoutAction::class)->handle([
        'selected_location_id' => $location->id,
        'shop_groups' => [
            $shop->id => [
                'selected_rate' => [
                    'courier_code' => 'jne',
                    'courier_service_code' => 'reg',
                    'price' => 5_000,
                    'name' => 'JNE Regular',
                    'etd' => '2 days',
                ],
                'items' => [
                    $productFlat->id => [
                        'price' => 1,
                        'qty' => 2,
                        'total' => 2,
                        'raw' => ['price' => 1],
                    ],
                ],
                'total_checkout' => 1,
                'total_shipping' => 1,
                'total' => 2,
            ],
        ],
        'total_checkout' => 1,
        'total_rates' => 1,
        'application_fee' => 0,
        'insurance_fee' => 0,
    ]);

    $orderItem = $order->items()->firstOrFail();

    expect((float) $order->total_checkout)->toBe(20_000.0)
        ->and((float) $order->total_shipping)->toBe(5_000.0)
        ->and((float) $order->application_fee)->toBe(1_000.0)
        ->and((float) $order->insurance_fee)->toBe(2_500.0)
        ->and((float) $order->total)->toBe(28_500.0)
        ->and((float) $orderItem->price)->toBe(10_000.0)
        ->and((float) $orderItem->total)->toBe(20_000.0)
        ->and($orderItem->quantity)->toBe(2);
});

it('creates a tokenized guest order and normalizes the email field', function () {
    Mail::fake();

    $shop = Shop::factory()->create();
    $product = Product::factory()->for($shop)->create();
    $productFlat = ProductFlat::factory()->for($product)->create([
        'shop_id' => $shop->id,
        'price' => 10_000,
        'stock' => 10,
        'status' => true,
    ]);

    $order = app(StoreCheckoutAction::class)->handle([
        'selected_location_id' => null,
        'guest_data' => [
            'contact_name' => 'Guest Buyer',
            'contact_phone' => '081234567890',
            'email' => 'guest@example.com',
            'address' => 'Guest address',
            'note' => null,
            'postal_code' => '12345',
            'area_string' => 'Jakarta',
            'biteship_area_id' => 'IDNP6IDNC148IDND1198IDZ12950',
            'latitude' => -6.2,
            'longitude' => 106.8,
        ],
        'shop_groups' => [
            $shop->id => [
                'selected_rate' => [
                    'courier_code' => 'jne',
                    'courier_service_code' => 'reg',
                    'price' => 5_000,
                    'name' => 'JNE Regular',
                    'etd' => '2 days',
                ],
                'items' => [
                    $productFlat->id => ['qty' => 1],
                ],
            ],
        ],
    ]);

    expect($order->user_id)->toBeNull()
        ->and($order->access_token)->toHaveLength(64)
        ->and($order->guest_data['contact_email'])->toBe('guest@example.com')
        ->and($order->guest_data)->not->toHaveKey('email');
});
