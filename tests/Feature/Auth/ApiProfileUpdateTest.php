<?php

use App\Http\Controllers\Api\V1\Auth\AuthenticatedController;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

beforeEach(function () {
    Route::middleware('auth')->put('/test-api/profile', [AuthenticatedController::class, 'update']);
});

it('updates the authenticated profile through a DTO and clears cached profile data', function () {
    $user = User::factory()->create(['phone' => '628123456789']);
    $other = User::factory()->create();
    Cache::put('me:user'.$user->id, ['name' => $user->name]);

    $this->actingAs($user)->putJson('/test-api/profile', [
        'name' => 'Updated profile', 'email' => 'updated@example.test',
    ])->assertSuccessful()->assertJsonPath('data.name', 'Updated profile');

    expect($user->fresh()->email)->toBe('updated@example.test')
        ->and($user->fresh()->phone)->toBe('628123456789')
        ->and($other->fresh()->name)->toBe($other->name)
        ->and(Cache::has('me:user'.$user->id))->toBeFalse();
});

it('validates profile input before updating an optional password', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $this->actingAs($user)->putJson('/test-api/profile', [
        'name' => 'Invalid', 'email' => $other->email,
        'password' => 'short', 'password_confirmation' => 'different',
    ])->assertUnprocessable()->assertJsonValidationErrors(['email', 'password']);
    expect($user->fresh()->name)->toBe($user->name);

    $this->putJson('/test-api/profile', [
        'name' => $user->name, 'email' => $user->email,
        'password' => 'UpdatedPassword123!', 'password_confirmation' => 'UpdatedPassword123!',
    ])->assertSuccessful();
    expect(Hash::check('UpdatedPassword123!', $user->fresh()->password))->toBeTrue();
});
