<?php
use App\Models\Content\Page;
use Illuminate\View\View;

use function Laravel\Folio\name;
use function Laravel\Folio\render;

name('content.page');
render(function (View $view, string $slug): View {
    return $view->with('page', Page::query()->where('slug', $slug)->where('published', true)->firstOrFail());
});
?>
<x-layouts.ecommerce :title="$page->title">
    <article class="mx-auto max-w-4xl px-6 py-12">
        <a href="{{ route('home') }}" wire:navigate class="text-sm text-zinc-500 hover:text-zinc-900">Kembali ke beranda</a>
        <h1 class="mt-6 text-3xl font-bold tracking-tight text-zinc-900 md:text-4xl">{{ $page->title }}</h1>
        <div class="mt-8 text-zinc-600 leading-relaxed break-words [&_h2]:text-2xl [&_h2]:font-bold [&_h2]:mt-8 [&_h3]:text-xl [&_h3]:font-semibold [&_p]:my-4 [&_ul]:list-disc [&_ul]:pl-6 [&_ol]:list-decimal [&_ol]:pl-6 [&_a]:underline [&_img]:max-w-full [&_table]:block [&_table]:overflow-x-auto">{!! $page->body !!}</div>
    </article>
</x-layouts.ecommerce>
