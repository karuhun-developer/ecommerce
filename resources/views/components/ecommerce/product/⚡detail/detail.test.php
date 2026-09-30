<?php

use App\Models\Location\Location;
use App\Models\Product\Product;
use App\Models\Product\ProductFlat;
use App\Models\Shop\Shop;
use App\Models\User;
use Dom\HTMLDocument;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Js;
use Livewire\Livewire;
use Sqids\Sqids;

it('registers the ecommerce.product.detail component', function () {
    expect(Livewire::exists('ecommerce.product.detail'))->toBeTrue();
});

it('renders the selected product and its shop', function () {
    $owner = User::factory()->create();
    $shop = Shop::factory()->for($owner)->create(['name' => 'Visible Shop']);
    Location::factory()->for($owner)->create([
        'shop_id' => $shop->id,
        'type' => 'origin',
    ]);
    $product = Product::factory()->for($shop)->create();
    $productFlat = ProductFlat::factory()->for($product)->create([
        'shop_id' => $shop->id,
        'name' => 'Visible Product Variant',
    ]);

    Livewire::test('ecommerce.product.detail', ['product' => $product])
        ->assertSee($productFlat->name)
        ->assertSee($shop->name)
        ->assertSet('variants', []);
});

it('renders every product flat identifier for variant selection', function () {
    $owner = User::factory()->create();
    $shop = Shop::factory()->for($owner)->create();
    Location::factory()->for($owner)->create([
        'shop_id' => $shop->id,
        'type' => 'origin',
    ]);
    $product = Product::factory()->for($shop)->create();
    $firstFlat = ProductFlat::factory()->for($product)->create(['shop_id' => $shop->id]);
    $secondFlat = ProductFlat::factory()->for($product)->create(['shop_id' => $shop->id]);

    Livewire::test('ecommerce.product.detail', ['product' => $product])
        ->assertSee("activeFlatProduct == {$firstFlat->id}", false)
        ->assertSee("activeFlatProduct == {$secondFlat->id}", false);
});

it('renders accessible selection and quantity controls with responsive layout contracts', function () {
    $owner = User::factory()->create();
    $shop = Shop::factory()->for($owner)->create();
    Location::factory()->for($owner)->create([
        'shop_id' => $shop->id,
        'type' => 'origin',
    ]);
    $product = Product::factory()->for($shop)->create();
    $productFlat = ProductFlat::factory()->for($product)->create([
        'shop_id' => $shop->id,
        'name' => 'Varian Aksesibel',
    ]);

    $component = Livewire::test('ecommerce.product.detail', ['product' => $product])
        ->set('variants', [[
            'product_flat_id' => $productFlat->id,
            'label' => 'Varian Aksesibel',
        ]])
        ->assertSee('flex-col lg:flex-row', false)
        ->assertSee('w-full lg:w-[30%]', false)
        ->assertSee('w-full lg:w-[45%]', false)
        ->assertSee('w-full lg:w-[25%]', false)
        ->assertSeeHtml('data-product-thumbnail')
        ->assertSeeHtml('data-product-variant')
        ->assertSeeHtml('data-quantity-decrement')
        ->assertSeeHtml('data-quantity-increment');

    $document = HTMLDocument::createFromString($component->html(true), LIBXML_NOERROR);
    $variantButton = $document->querySelector('[data-product-variant]');
    $quantityInput = $document->querySelector('#quantity-'.$productFlat->id);
    $quantityLabel = $document->querySelector('label[for="quantity-'.$productFlat->id.'"]');
    $decrementButton = $document->querySelector('[data-quantity-decrement]');
    $incrementButton = $document->querySelector('[data-quantity-increment]');

    expect($variantButton?->getAttribute('type'))->toBe('button')
        ->and($variantButton?->getAttribute('x-bind:aria-pressed'))->toContain('activeFlatProduct')
        ->and($quantityInput?->getAttribute('type'))->toBe('number')
        ->and($quantityLabel?->textContent)->toContain('Jumlah Varian Aksesibel')
        ->and($decrementButton?->getAttribute('type'))->toBe('button')
        ->and($decrementButton?->getAttribute('aria-label'))->toBe('Kurangi jumlah Varian Aksesibel')
        ->and($incrementButton?->getAttribute('type'))->toBe('button')
        ->and($incrementButton?->getAttribute('aria-label'))->toBe('Tambah jumlah Varian Aksesibel');

    expect($component->html(true))
        ->toContain('data-product-thumbnail')
        ->toContain('type="button"')
        ->toContain('x-bind:aria-label')
        ->toContain('x-bind:aria-pressed');
});

it('escapes product descriptions and safely serializes merchant values into javascript', function () {
    $owner = User::factory()->create();
    $shopName = 'Shop \' " </script><script>window.shopXss = true</script>';
    $variantName = 'Variant \' " </script><script>window.variantXss = true</script>';
    $description = '<img src=x onerror="window.descriptionXss = true"><script>window.rawXss = true</script>';
    $shop = Shop::factory()->for($owner)->create(['name' => $shopName]);
    Location::factory()->for($owner)->create([
        'shop_id' => $shop->id,
        'type' => 'origin',
    ]);
    $product = Product::factory()->for($shop)->create();
    ProductFlat::factory()->for($product)->create([
        'shop_id' => $shop->id,
        'name' => $variantName,
        'description' => $description,
    ]);

    $html = Livewire::test('ecommerce.product.detail', ['product' => $product])->html(true);

    expect($html)
        ->not->toContain($description)
        ->not->toContain('<script>window.shopXss = true</script>')
        ->not->toContain('<script>window.variantXss = true</script>')
        ->toContain('shop_name: '.Js::from($shopName)->toHtml())
        ->toContain('name: '.Js::from($variantName)->toHtml());
});

it('buys the exact selected variant with its canonical cart payload', function () {
    $owner = User::factory()->create();
    $shop = Shop::factory()->for($owner)->create(['name' => 'Exact Variant Shop']);
    Location::factory()->for($owner)->create([
        'shop_id' => $shop->id,
        'type' => 'origin',
    ]);
    $product = Product::factory()->for($shop)->create();
    ProductFlat::factory()->for($product)->create(['shop_id' => $shop->id]);
    $selectedFlat = ProductFlat::factory()->for($product)->create([
        'shop_id' => $shop->id,
        'name' => 'Exact Selected Variant',
        'price' => 125_000,
        'stock' => 8,
    ]);
    $expectedCheckoutUrl = route('checkout', [
        'items' => (new Sqids)->encode([$selectedFlat->id]),
    ]);

    Livewire::test('ecommerce.product.detail', ['product' => $product])
        ->call('buyNow', $selectedFlat->id, 3)
        ->assertDispatched('buy-now', function (string $event, array $params) use ($expectedCheckoutUrl, $selectedFlat, $shop): bool {
            expect($params['item'])->toMatchArray([
                'id' => $selectedFlat->id,
                'shop_id' => $shop->id,
                'shop_name' => $shop->name,
                'name' => $selectedFlat->name,
                'price' => 125_000.0,
                'qty' => 3,
            ])->and($params['checkoutUrl'])->toBe($expectedCheckoutUrl);

            return $event === 'buy-now';
        });
});

it('clamps buy now quantity to the selected variant stock', function (mixed $quantity, int $expectedQuantity) {
    $owner = User::factory()->create();
    $shop = Shop::factory()->for($owner)->create();
    Location::factory()->for($owner)->create([
        'shop_id' => $shop->id,
        'type' => 'origin',
    ]);
    $product = Product::factory()->for($shop)->create();
    $selectedFlat = ProductFlat::factory()->for($product)->create([
        'shop_id' => $shop->id,
        'stock' => 5,
        'is_unlimited_stock' => false,
    ]);

    Livewire::test('ecommerce.product.detail', ['product' => $product])
        ->call('buyNow', $selectedFlat->id, $quantity)
        ->assertDispatched('buy-now', fn (string $event, array $params): bool => $params['item']['qty'] === $expectedQuantity);
})->with([
    'null quantity' => [null, 1],
    'non numeric quantity' => ['invalid', 1],
    'zero quantity' => [0, 1],
    'negative quantity' => [-9, 1],
    'partially numeric quantity' => ['99junk', 1],
    'quantity above stock' => [99, 5],
]);

it('caps unlimited stock buy now quantity at one hundred', function (mixed $quantity) {
    $owner = User::factory()->create();
    $shop = Shop::factory()->for($owner)->create();
    Location::factory()->for($owner)->create([
        'shop_id' => $shop->id,
        'type' => 'origin',
    ]);
    $product = Product::factory()->for($shop)->create();
    $selectedFlat = ProductFlat::factory()->for($product)->create([
        'shop_id' => $shop->id,
        'stock' => 0,
        'is_unlimited_stock' => true,
    ]);

    Livewire::test('ecommerce.product.detail', ['product' => $product])
        ->call('buyNow', $selectedFlat->id, $quantity)
        ->assertDispatched('buy-now', fn (string $event, array $params): bool => $params['item']['qty'] === 100);
})->with([
    'just above maximum' => [101],
    'far above maximum' => [999],
]);

it('renders quantity inputs with stock aware limits capped at one hundred', function () {
    $owner = User::factory()->create();
    $shop = Shop::factory()->for($owner)->create();
    Location::factory()->for($owner)->create([
        'shop_id' => $shop->id,
        'type' => 'origin',
    ]);
    $product = Product::factory()->for($shop)->create();
    $unlimitedFlat = ProductFlat::factory()->for($product)->create([
        'shop_id' => $shop->id,
        'stock' => 0,
        'is_unlimited_stock' => true,
    ]);
    $limitedFlat = ProductFlat::factory()->for($product)->create([
        'shop_id' => $shop->id,
        'stock' => 12,
        'is_unlimited_stock' => false,
    ]);

    $html = Livewire::test('ecommerce.product.detail', ['product' => $product])->html(true);
    $document = HTMLDocument::createFromString($html, LIBXML_NOERROR);

    expect($document->querySelector('#quantity-'.$unlimitedFlat->id)?->getAttribute('max'))->toBe('100')
        ->and($document->querySelector('#quantity-'.$limitedFlat->id)?->getAttribute('max'))->toBe('12')
        ->and($html)->toContain('normalizeQuantity(qty + 1, 100)')
        ->toContain('normalizeQuantity(qty + 1, 12)');
});

it('synchronizes the displayed and submitted quantity when switching to a lower stock variant', function () {
    $owner = User::factory()->create();
    $shop = Shop::factory()->for($owner)->create();
    Location::factory()->for($owner)->create([
        'shop_id' => $shop->id,
        'type' => 'origin',
    ]);
    $product = Product::factory()->for($shop)->create();
    $unlimitedFlat = ProductFlat::factory()->for($product)->create([
        'shop_id' => $shop->id,
        'stock' => 0,
        'is_unlimited_stock' => true,
    ]);
    $limitedFlat = ProductFlat::factory()->for($product)->create([
        'shop_id' => $shop->id,
        'stock' => 12,
        'is_unlimited_stock' => false,
    ]);

    $component = Livewire::test('ecommerce.product.detail', ['product' => $product])
        ->set('variants', [
            ['product_flat_id' => $unlimitedFlat->id, 'label' => 'Unlimited'],
            ['product_flat_id' => $limitedFlat->id, 'label' => 'Limited'],
        ]);
    $document = HTMLDocument::createFromString($component->html(true), LIBXML_NOERROR);
    $root = $document->querySelector('[x-data*="activeFlatProduct"]');
    $variantButton = collect(iterator_to_array($document->querySelectorAll('[data-product-variant]')))
        ->first(fn ($button): bool => str_contains((string) $button->getAttribute('@click'), (string) $limitedFlat->id));
    $actionBox = collect(iterator_to_array($document->querySelectorAll('[x-show]')))
        ->first(fn ($element): bool => str_contains((string) $element->getAttribute('x-show'), (string) $limitedFlat->id)
            && str_contains($element->textContent, 'Atur jumlah dan catatan'));
    $buttons = collect(iterator_to_array($actionBox?->querySelectorAll('button') ?? []));
    $addToCartButton = $buttons->first(fn ($button): bool => str_contains($button->textContent, 'Keranjang'));
    $buyNowButton = $buttons->first(fn ($button): bool => str_contains($button->textContent, 'Beli Sekarang'));

    expect($root)->not->toBeNull()
        ->and($variantButton)->not->toBeNull()
        ->and($actionBox)->not->toBeNull()
        ->and($addToCartButton)->not->toBeNull()
        ->and($buyNowButton)->not->toBeNull();

    $dataExpression = base64_encode((string) $root->getAttribute('x-data'));
    $variantExpression = base64_encode((string) $variantButton->getAttribute('@click'));
    $addToCartExpression = base64_encode((string) $addToCartButton->getAttribute('@click'));
    $buyNowExpression = base64_encode((string) $buyNowButton->getAttribute('x-on:click'));
    $script = <<<JS
const decode = (value) => Buffer.from(value, 'base64').toString('utf8');
const scope = new Function('return (' + decode('{$dataExpression}') + ');')();
const execute = (expression, extras = {}) => {
    return new Function(
        '\$scope',
        '\$extras',
        'with (\$scope) { with (\$extras) { ' + decode(expression) + ' } }',
    )(scope, extras);
};
const addedItems = [];
const buyNowCalls = [];

scope.qty = 100;
execute('{$variantExpression}');
execute('{$addToCartExpression}', {
    \$store: { cart: { add: (item) => addedItems.push(item) } },
    \$flux: { modal: () => ({ show() {} }) },
});
execute('{$buyNowExpression}', {
    \$wire: { buyNow: (id, qty) => buyNowCalls.push({ id, qty }) },
});

console.log(JSON.stringify({
    activeFlatProduct: scope.activeFlatProduct,
    displayedQuantity: scope.qty,
    cartProductFlatId: addedItems[0]?.id,
    cartQuantity: addedItems[0]?.qty,
    buyNowProductFlatId: buyNowCalls[0]?.id,
    buyNowQuantity: buyNowCalls[0]?.qty,
}));
JS;

    $result = Process::path(base_path())->run(['node', '--input-type=module', '--eval', $script]);

    if ($result->failed()) {
        $this->fail($result->errorOutput() ?: $result->output());
    }

    expect(json_decode(trim($result->output()), true, flags: JSON_THROW_ON_ERROR))->toBe([
        'activeFlatProduct' => $limitedFlat->id,
        'displayedQuantity' => 12,
        'cartProductFlatId' => $limitedFlat->id,
        'cartQuantity' => 12,
        'buyNowProductFlatId' => $limitedFlat->id,
        'buyNowQuantity' => 12,
    ]);

    Livewire::test('ecommerce.product.detail', ['product' => $product])
        ->call('buyNow', $limitedFlat->id, 100)
        ->assertDispatched('buy-now', fn (string $event, array $params): bool => $params['item']['qty'] === 12);
});

it('does not buy an out of stock variant', function () {
    $owner = User::factory()->create();
    $shop = Shop::factory()->for($owner)->create();
    Location::factory()->for($owner)->create([
        'shop_id' => $shop->id,
        'type' => 'origin',
    ]);
    $product = Product::factory()->for($shop)->create();
    $selectedFlat = ProductFlat::factory()->for($product)->create([
        'shop_id' => $shop->id,
        'stock' => 0,
        'is_unlimited_stock' => false,
    ]);

    Livewire::test('ecommerce.product.detail', ['product' => $product])
        ->call('buyNow', $selectedFlat->id, 1)
        ->assertNotDispatched('buy-now')
        ->assertDispatched(
            'toast',
            type: 'error',
            message: 'Produk sedang tidak tersedia.',
        );
});

it('renders a safe gallery placeholder until a product image is available', function () {
    $owner = User::factory()->create();
    $shop = Shop::factory()->for($owner)->create();
    Location::factory()->for($owner)->create(['shop_id' => $shop->id, 'type' => 'origin']);
    $product = Product::factory()->for($shop)->create();
    ProductFlat::factory()->for($product)->create(['shop_id' => $shop->id]);
    $html = Livewire::test('ecommerce.product.detail', ['product' => $product])->html(true);
    $document = HTMLDocument::createFromString($html, LIBXML_NOERROR);
    $root = $document->querySelector('[x-data*="activeFlatProduct"]');
    $imageTemplate = $document->querySelector('[data-product-image]');
    $placeholderTemplate = $document->querySelector('[data-product-image-placeholder]');

    expect($imageTemplate)->not->toBeNull()
        ->and($placeholderTemplate)->not->toBeNull()
        ->and($html)->toContain('Foto produk belum tersedia.');

    $image = HTMLDocument::createFromString($imageTemplate->innerHTML, LIBXML_NOERROR)->querySelector('img');
    $expressions = base64_encode(json_encode([
        'data' => $root->getAttribute('x-data'),
        'image' => $imageTemplate->getAttribute('x-if'),
        'placeholder' => $placeholderTemplate->getAttribute('x-if'),
        'src' => $image->getAttribute(':src'),
        'alt' => $image->getAttribute('x-bind:alt'),
    ], JSON_THROW_ON_ERROR));
    $script = <<<JS
const expressions = JSON.parse(Buffer.from('{$expressions}', 'base64').toString('utf8'));
const scope = new Function('return (' + expressions.data + ');')();
const evaluate = expression => new Function('scope', 'with (scope) { return (' + expression + '); }')(scope);
const render = () => {
    const showImage = Boolean(evaluate(expressions.image));
    return {
        showImage,
        showPlaceholder: Boolean(evaluate(expressions.placeholder)),
        src: showImage ? evaluate(expressions.src) : null,
        alt: showImage ? evaluate(expressions.alt) : null,
    };
};
const empty = render();
scope.images = [{ url: '/first.jpg', alt: 'Foto pertama' }, { url: '/second.jpg', alt: 'Foto kedua' }];
scope.activeImage = 1;
console.log(JSON.stringify({ empty, selected: render() }));
JS;

    $result = Process::path(base_path())->run(['node', '--input-type=module', '--eval', $script]);

    if ($result->failed()) {
        $this->fail($result->errorOutput() ?: $result->output());
    }

    expect(json_decode(trim($result->output()), true, flags: JSON_THROW_ON_ERROR))->toBe([
        'empty' => ['showImage' => false, 'showPlaceholder' => true, 'src' => null, 'alt' => null],
        'selected' => ['showImage' => true, 'showPlaceholder' => false, 'src' => '/second.jpg', 'alt' => 'Foto kedua'],
    ]);
});

it('shares the canonical product link with browser fallbacks', function () {
    $owner = User::factory()->create();
    $shop = Shop::factory()->for($owner)->create();
    Location::factory()->for($owner)->create(['shop_id' => $shop->id, 'type' => 'origin']);
    $product = Product::factory()->for($shop)->create(['name' => 'Produk "Pilihan"']);
    ProductFlat::factory()->for($product)->create(['shop_id' => $shop->id]);
    $html = Livewire::test('ecommerce.product.detail', ['product' => $product])->html(true);
    $document = HTMLDocument::createFromString($html, LIBXML_NOERROR);
    $root = $document->querySelector('[x-data*="shareProduct"]');
    $button = $document->querySelector('[data-product-share]');
    expect($button?->getAttribute('type'))->toBe('button')
        ->and($button?->textContent)->toContain('Bagikan Produk')
        ->and($html)->toContain('aria-live="polite"');
    $expression = base64_encode($root->getAttribute('x-data'));
    $script = <<<JS
const source = Buffer.from('{$expression}', 'base64').toString('utf8');
const outcomes = [];
for (const mode of ['native', 'clipboard', 'abort', 'failure', 'manual']) {
    const calls = [];
    const navigator = mode === 'clipboard' ? { clipboard: { writeText: async url => calls.push(url) } }
        : mode === 'manual' ? {}
        : { share: async payload => {
            calls.push(payload);
            if (mode === 'abort') throw Object.assign(new Error(), { name: 'AbortError' });
            if (mode === 'failure') throw new Error('denied');
        } };
    const scope = new Function('navigator', 'return (' + source + ')')(navigator);
    await scope.shareProduct();
    outcomes.push({ mode, calls, sharing: scope.sharing, message: scope.shareMessage });
}
console.log(JSON.stringify(outcomes));
JS;
    $result = Process::path(base_path())->run(['node', '--input-type=module', '--eval', $script]);
    expect($result->successful())->toBeTrue($result->errorOutput());
    $outcomes = json_decode(trim($result->output()), true, flags: JSON_THROW_ON_ERROR);
    $url = route('product.detail', ['slug' => $product->slug]);
    expect($outcomes[0]['calls'])->toBe([['title' => $product->name, 'url' => $url]])
        ->and($outcomes[0]['message'])->toBe('Produk berhasil dibagikan.')
        ->and($outcomes[1]['calls'])->toBe([$url])
        ->and($outcomes[1]['message'])->toBe('Link produk berhasil disalin.')
        ->and($outcomes[2]['message'])->toBe('')
        ->and($outcomes[3]['message'])->toContain('Salin link')
        ->and($outcomes[4]['message'])->toContain('Salin link');
    foreach ($outcomes as $outcome) {
        expect($outcome['sharing'])->toBeFalse();
    }
});
