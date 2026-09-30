<?php

use App\Mail\OrderDelivered;
use App\Mail\OrderPaid;
use App\Mail\OrderPaymentFailed;
use App\Mail\OrderPlaced;
use App\Models\Order\Order;
use App\Models\Order\OrderShop;
use App\Models\Order\OrderShopItem;
use App\Models\Shop\Shop;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

beforeEach(function () {
    Carbon::setTestNow('2026-09-15 12:00:00');
    config()->set('app.url', 'https://store.example.test');
    URL::forceRootUrl('https://store.example.test');

    $order = new Order([
        'reference' => 'INV-&<script>alert("mail")</script>',
        'access_token' => 'guest-access-token',
        'total_checkout' => 125000,
        'total_shipping' => 15000,
        'application_fee' => 2500,
        'insurance_fee' => 1000,
        'payment_fee' => 1500,
        'total' => 145000,
    ]);

    $shop = new Shop([
        'name' => 'Toko <img src=x onerror=alert("shop")>',
    ]);

    $item = new OrderShopItem([
        'product_data' => ['name' => 'Produk <script>alert("product")</script>'],
        'quantity' => 2,
        'price' => 62500,
        'total' => 125000,
    ]);

    $orderShop = new OrderShop([
        'total_checkout' => 125000,
        'total_shipping' => 15000,
        'total' => 140000,
    ]);

    $order->setRelation('orderShops', collect([$orderShop]));
    $orderShop->setRelation('order', $order);
    $orderShop->setRelation('shop', $shop);
    $orderShop->setRelation('items', collect([$item]));

    $user = new User([
        'name' => 'Mail Test User',
        'email' => 'mail-test@example.test',
    ]);
    $user->forceFill(['id' => 42]);

    $resetMessage = (new ResetPassword('fixed-reset-token'))->toMail($user);
    $verificationMessage = (new VerifyEmail)->toMail($user);

    $this->order = $order;
    $this->renderedOrderEmails = [
        'emails.orders.placed' => (new OrderPlaced($order))->render(),
        'emails.orders.paid' => (new OrderPaid($order))->render(),
        'emails.orders.payment-failed' => (new OrderPaymentFailed($order))->render(),
        'emails.orders.delivered' => (new OrderDelivered($orderShop))->render(),
    ];
    $this->renderedEmails = [
        ...$this->renderedOrderEmails,
        'auth.password-reset' => (string) $resetMessage->render(),
        'auth.email-verification' => (string) $verificationMessage->render(),
    ];
    $this->authMessages = [
        'auth.password-reset' => $resetMessage,
        'auth.email-verification' => $verificationMessage,
    ];
});

afterEach(function () {
    Carbon::setTestNow();
    URL::forceRootUrl(null);
});

test('every shipped transactional email renders with its expected copy and action', function () {
    $transactionalViews = collect(File::allFiles(resource_path('views/emails')))
        ->map(fn (SplFileInfo $file): string => Str::of($file->getPathname())
            ->after(resource_path('views').DIRECTORY_SEPARATOR)
            ->replace(DIRECTORY_SEPARATOR, '.')
            ->beforeLast('.blade.php')
            ->toString())
        ->sort()
        ->values()
        ->all();

    expect(array_keys($this->renderedOrderEmails))
        ->toEqualCanonicalizing($transactionalViews)
        ->and($this->renderedEmails)->toHaveCount(6)
        ->and($this->renderedOrderEmails['emails.orders.placed'])
        ->toContain('Pesanan Anda Berhasil Dibuat!')
        ->toContain('Rincian Pembelanjaan:')
        ->toContain('Ringkasan Pembayaran:')
        ->and($this->renderedOrderEmails['emails.orders.paid'])
        ->toContain('Pembayaran Berhasil Diterima!')
        ->toContain('Ringkasan Transaksi:')
        ->and($this->renderedOrderEmails['emails.orders.payment-failed'])
        ->toContain('Pembayaran Gagal / Kedaluwarsa')
        ->toContain('Jika Anda masih ingin melakukan pembelian, silakan buat pesanan baru.')
        ->and($this->renderedOrderEmails['emails.orders.delivered'])
        ->toContain('Pesanan Anda Telah Sampai!')
        ->toContain('Rincian Produk:')
        ->toContain('Jangan lupa berikan ulasan untuk produk dan toko ya!');

    foreach ($this->renderedOrderEmails as $html) {
        expect($html)
            ->toContain('Kode Transaksi Anda')
            ->toContain('Cek Detail Pesanan')
            ->toContain('guest-access-token');
    }

    expect($this->renderedOrderEmails['emails.orders.placed'])
        ->toContain('Rp125.000')
        ->toContain('Rp15.000')
        ->toContain('Rp1.000')
        ->toContain('Rp2.500')
        ->toContain('Rp1.500')
        ->toContain('Rp145.000')
        ->and($this->renderedOrderEmails['emails.orders.paid'])
        ->toContain('Rp145.000')
        ->and($this->renderedOrderEmails['emails.orders.payment-failed'])
        ->toContain('Rp145.000')
        ->and($this->renderedOrderEmails['emails.orders.delivered'])
        ->toContain('Rp125.000');

    foreach ($this->authMessages as $name => $message) {
        expect($message)->toBeInstanceOf(MailMessage::class)
            ->and($this->renderedEmails[$name])
            ->toContain($message->actionText)
            ->toContain(e($message->actionUrl));
    }
});

test('customer-controlled values stay escaped in rendered transactional email html', function () {
    foreach ($this->renderedOrderEmails as $html) {
        $document = new DOMDocument;
        $document->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new DOMXPath($document);

        expect($html)
            ->not->toContain('<script>')
            ->not->toContain('<img src=x')
            ->and($xpath->query('//script')->length)->toBe(0)
            ->and($xpath->query('//img[contains(@src, "x")]')->length)->toBe(0)
            ->and($document->textContent)->toContain($this->order->reference);
    }

    foreach ([
        $this->renderedOrderEmails['emails.orders.placed'],
        $this->renderedOrderEmails['emails.orders.delivered'],
    ] as $html) {
        $document = new DOMDocument;
        $document->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING);

        expect($document->textContent)
            ->toContain('Toko <img src=x onerror=alert("shop")>')
            ->toContain('Produk <script>alert("product")</script>');
    }
});

test('transactional email render paths use the neutral theme and black call to action', function () {
    $legacyThemeTokens = [
        '#16a34a',
        '#dc2626',
        '#991b1b',
        '#fef2f2',
        'button-green',
        'button-success',
        'button-red',
        'button-error',
        'emerald',
        'lime',
    ];

    foreach ($this->renderedEmails as $html) {
        $normalizedHtml = strtolower($html);

        foreach ($legacyThemeTokens as $legacyThemeToken) {
            expect($normalizedHtml)->not->toContain($legacyThemeToken);
        }

        preg_match_all('/#[0-9a-f]{3,8}\b/i', $normalizedHtml, $hexColorMatches);

        expect(array_values(array_unique($hexColorMatches[0])))
            ->each->toBeIn([
                '#000',
                '#000000',
                '#171717',
                '#262626',
                '#525252',
                '#737373',
                '#e5e5e5',
                '#f5f5f5',
                '#fafafa',
                '#fff',
                '#ffffff',
            ]);

        preg_match_all('/rgba?\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)/i', $normalizedHtml, $rgbColorMatches, PREG_SET_ORDER);

        foreach ($rgbColorMatches as $rgbColorMatch) {
            expect($rgbColorMatch[1])->toBe($rgbColorMatch[2])
                ->and($rgbColorMatch[2])->toBe($rgbColorMatch[3]);
        }

        $document = new DOMDocument;
        $document->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING);
        $buttons = (new DOMXPath($document))->query('//a[contains(concat(" ", normalize-space(@class), " "), " button ")]');

        expect($buttons)->not->toBeFalse()
            ->and($buttons->length)->toBe(1)
            ->and($buttons->item(0)->getAttribute('style'))
            ->toContain('background-color: #262626')
            ->toContain('color: #ffffff');
    }

    foreach ($this->renderedOrderEmails as $html) {
        expect($html)
            ->toContain('background-color: #f5f5f5')
            ->toContain('border: 1px solid #e5e5e5')
            ->toContain('color: #171717');
    }
});

test('transactional email markup is semantic inline styled and mobile safe', function () {
    foreach ($this->renderedEmails as $html) {
        $normalizedHtml = strtolower($html);

        expect($normalizedHtml)
            ->toContain('<!doctype html')
            ->toContain('name="viewport"')
            ->toContain('width=device-width')
            ->toContain('@media only screen and (max-width: 600px)')
            ->toContain('width: 100% !important')
            ->not->toContain('cdn.tailwindcss.com')
            ->not->toContain('@tailwind')
            ->not->toMatch('/class="[^"]*\b(?:sm|md|lg|xl|dark):/');

        $document = new DOMDocument;
        $document->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new DOMXPath($document);
        $tablesWithoutPresentationRole = $xpath->query('//table[not(@role="presentation")]');
        $inlineStyledElements = $xpath->query('//*[@style]');

        expect($xpath->query('//html')->length)->toBe(1)
            ->and($xpath->query('//body')->length)->toBe(1)
            ->and($xpath->query('//a[@href]')->length)->toBeGreaterThanOrEqual(1)
            ->and($tablesWithoutPresentationRole)->not->toBeFalse()
            ->and($tablesWithoutPresentationRole->length)->toBe(0)
            ->and($inlineStyledElements)->not->toBeFalse()
            ->and($inlineStyledElements->length)->toBeGreaterThan(0);
    }
});

test('shared notification button aliases stay black and white', function (string $level) {
    $message = (new MailMessage)
        ->level($level)
        ->line('Shared notification theme check.')
        ->action('Open notification', 'https://store.example.test/notification');
    $html = (string) $message->render();

    $document = new DOMDocument;
    $document->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING);
    $buttons = (new DOMXPath($document))->query('//a[contains(concat(" ", normalize-space(@class), " "), " button ")]');

    expect(strtolower($html))
        ->not->toContain('#16a34a')
        ->not->toContain('#dc2626')
        ->and($buttons)->not->toBeFalse()
        ->and($buttons->length)->toBe(1)
        ->and($buttons->item(0)->getAttribute('style'))
        ->toContain('background-color: #262626')
        ->toContain('color: #ffffff');
})->with(['primary', 'success', 'error']);
