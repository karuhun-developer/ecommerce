<?php

use App\Actions\Ecommerce\Review\AcceptOrderReviewAction;
use App\Actions\Ecommerce\Review\DeleteOrderReviewAction;
use App\Actions\Ecommerce\Review\RejectOrderReviewAction;
use App\Models\Order\Order;
use App\Models\Order\OrderReview;
use App\Models\Order\OrderShop;
use App\Models\Product\Product;
use App\Models\Shop\Shop;
use App\Models\Spatie\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Livewire;

use function Pest\Laravel\mock;

function createCmsReviewRecord(User $owner, string $comment, string $status = 'pending'): array
{
    $buyer = User::factory()->create();
    $shop = Shop::factory()->for($owner)->create();
    $product = Product::factory()->for($shop)->create();
    $order = Order::factory()->for($buyer)->create(['status' => true]);
    $orderShop = OrderShop::factory()->for($order)->for($shop)->create();
    $review = OrderReview::query()->create([
        'user_id' => $buyer->id,
        'order_shop_id' => $orderShop->id,
        'reviewable_type' => Product::class,
        'reviewable_id' => $product->id,
        'rating' => 4.5,
        'comment' => $comment,
        'status' => $status,
    ]);

    return compact('shop', 'product', 'orderShop', 'review');
}

it('registers the cms.review.table component', function () {
    expect(Livewire::exists('cms.review.table'))->toBeTrue();
});

it('shows only reviews belonging to the current tenant', function () {
    $owner = User::factory()->create();
    $foreignOwner = User::factory()->create();
    $owned = createCmsReviewRecord($owner, 'visible-owned-review');
    $foreign = createCmsReviewRecord($foreignOwner, 'hidden-foreign-review');
    $this->actingAs($owner);

    Livewire::test('cms.review.table')
        ->assertSee($owned['review']->comment)
        ->assertDontSee($foreign['review']->comment);
});

it('keeps tenant scope grouped while searching comments', function () {
    $owner = User::factory()->create();
    createCmsReviewRecord($owner, 'ordinary-owned-comment');
    $foreign = createCmsReviewRecord(User::factory()->create(), 'scope-escape-needle');
    $this->actingAs($owner);

    Livewire::test('cms.review.table')
        ->set('search', 'scope-escape-needle')
        ->assertDontSee($foreign['review']->comment);
});

it('does not moderate another tenants review', function (string $method, string $message) {
    $owner = User::factory()->create();
    $foreign = createCmsReviewRecord(User::factory()->create(), 'foreign-moderation-review');
    $this->actingAs($owner);

    Livewire::test('cms.review.table')
        ->call($method, $foreign['review']->id)
        ->assertDispatched('toast', type: 'error', message: $message);

    expect($foreign['review']->fresh())
        ->not->toBeNull()
        ->status->toBe('pending');
})->with([
    'accept' => ['accept', 'Failed to accept review. Please try again.'],
    'reject' => ['reject', 'Failed to reject review. Please try again.'],
    'delete' => ['delete', 'Failed to delete review. Please try again.'],
]);

it('rejects direct moderation actions for another tenant', function (string $actionClass) {
    $owner = User::factory()->create();
    $foreign = createCmsReviewRecord(User::factory()->create(), 'foreign-direct-action-review');

    expect(fn () => app($actionClass)->execute($foreign['review'], $owner))
        ->toThrow(ModelNotFoundException::class);
})->with([
    AcceptOrderReviewAction::class,
    RejectOrderReviewAction::class,
    DeleteOrderReviewAction::class,
]);

it('allows the tenant owner to moderate an accessible review', function (string $method, ?string $expectedStatus) {
    $owner = User::factory()->create();
    $owned = createCmsReviewRecord($owner, 'owned-moderation-review');
    $this->actingAs($owner);

    Livewire::test('cms.review.table')->call($method, $owned['review']->id);

    if ($expectedStatus === null) {
        expect($owned['review']->fresh())->toBeNull();

        return;
    }

    expect($owned['review']->fresh()->status)->toBe($expectedStatus);
})->with([
    'accept' => ['accept', 'approved'],
    'reject' => ['reject', 'rejected'],
    'delete' => ['delete', null],
]);

it('allows a superadmin to see and moderate reviews from every tenant', function () {
    $superadmin = User::factory()->create();
    $role = Role::query()->create([
        'name' => 'superadmin',
        'guard_name' => 'api',
    ]);
    $superadmin->assignRole($role);
    $foreign = createCmsReviewRecord(User::factory()->create(), 'superadmin-visible-review');
    $this->actingAs($superadmin);

    Livewire::test('cms.review.table')
        ->assertSee($foreign['review']->comment)
        ->call('accept', $foreign['review']->id);

    expect($foreign['review']->fresh()->status)->toBe('approved');
});

it('does not expose internal moderation exceptions', function (string $method, string $actionClass, string $message) {
    $owner = User::factory()->create();
    $owned = createCmsReviewRecord($owner, 'internal-error-review');
    $this->actingAs($owner);

    mock($actionClass)
        ->shouldReceive('execute')
        ->once()
        ->andThrow(new RuntimeException('moderation-secret-detail'));

    Livewire::test('cms.review.table')
        ->call($method, $owned['review']->id)
        ->assertDispatched('toast', type: 'error', message: $message)
        ->assertDontSee('moderation-secret-detail');
})->with([
    'accept' => ['accept', AcceptOrderReviewAction::class, 'Failed to accept review. Please try again.'],
    'reject' => ['reject', RejectOrderReviewAction::class, 'Failed to reject review. Please try again.'],
    'delete' => ['delete', DeleteOrderReviewAction::class, 'Failed to delete review. Please try again.'],
]);
