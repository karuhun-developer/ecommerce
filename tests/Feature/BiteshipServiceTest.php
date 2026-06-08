<?php

use App\Services\BiteshipService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

beforeEach(function () {
    // Inisialisasi service dengan fake API Key
    $this->service = new BiteshipService('test_fake_api_key_123');

    // Clear cache sebelum test berjalan biar data cache dari test sebelumnya gak nyangkut
    Cache::flush();
});

it('can fetch couriers and caches the result', function () {
    // Mocking response API
    Http::fake([
        'api.biteship.com/v1/couriers' => Http::response([
            'success' => true,
            'couriers' => [
                ['courier_name' => 'JNE', 'courier_code' => 'jne'],
                ['courier_name' => 'Paxel', 'courier_code' => 'paxel'],
            ],
        ], 200),
    ]);

    // Panggilan pertama (akan hit HTTP dan simpan ke Cache)
    $couriers = $this->service->couriers();

    expect($couriers)->toBeArray()->toHaveCount(2)
        ->and($couriers[0]['courier_code'])->toBe('jne');

    Http::assertSentCount(1);

    // Panggilan kedua (harus ngambil dari Cache, jadi HTTP request gak bertambah)
    $cachedCouriers = $this->service->couriers();

    expect($cachedCouriers)->toBe($couriers);
    Http::assertSentCount(1); // Tetap 1, membuktikan cache berjalan
});

it('can get rates from origin to destination', function () {
    Http::fake([
        'api.biteship.com/v1/rates/couriers' => Http::response([
            'success' => true,
            'pricing' => [
                ['company' => 'jne', 'price' => 15000],
            ],
        ], 200),
    ]);

    $payload = [
        'origin_postal_code' => 12530,
        'destination_postal_code' => 10110,
        'couriers' => 'jne',
        'items' => [
            ['name' => 'Book', 'weight' => 1000, 'quantity' => 1, 'value' => 149000],
        ],
    ];

    $response = $this->service->getRates($payload);

    expect($response)->toHaveKey('pricing')
        ->and($response['pricing'][0]['price'])->toBe(15000);

    // Pastikan request beneran dikirim dengan method POST ke endpoint yang sesuai
    Http::assertSent(function (Request $request) use ($payload) {
        return $request->method() === 'POST'
            && $request->url() === 'https://api.biteship.com/v1/rates/couriers'
            && $request['couriers'] === $payload['couriers'];
    });
});

it('can create a draft order', function () {
    Http::fake([
        'api.biteship.com/v1/draft_orders' => Http::response([
            'success' => true,
            'id' => 'd2bb3bf2-4ed6-4f2a-b783-41a0faf8bd8a',
            'status' => 'draft',
        ], 200),
    ]);

    $response = $this->service->createDraftOrder([
        'origin_contact_name' => 'Amir',
        'destination_contact_name' => 'John Doe',
        // ... (data lainnya)
    ]);

    expect($response)->toHaveKey('id', 'd2bb3bf2-4ed6-4f2a-b783-41a0faf8bd8a');
});

it('throws an exception and logs error when HTTP request fails (e.g. 500 error)', function () {
    Http::fake([
        '*' => Http::response('Internal Server Error', 500),
    ]);

    // Ekspektasi bahwa Log::error akan dipanggil 1 kali
    Log::shouldReceive('error')->once();

    // Memastikan function nge-throw Exception
    expect(fn () => $this->service->getMapsAreas(['input' => 'Bandung']))
        ->toThrow(Exception::class, 'Failed to fetch from Biteship API: Internal Server Error');
});

it('throws an exception and logs error when API returns success = false', function () {
    Http::fake([
        '*' => Http::response([
            'success' => false,
            'error' => 'Invalid API key or something went wrong',
        ], 200), // Kadang API me-return 200 tapi di bodynya success: false
    ]);

    Log::shouldReceive('error')->once();

    expect(fn () => $this->service->getLocation('random-id'))
        ->toThrow(Exception::class, 'Biteship API returned an unsuccessful response');
});
