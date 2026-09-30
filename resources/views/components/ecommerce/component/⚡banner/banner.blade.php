<div>
    @if($this->banners->isNotEmpty())
        <section x-data="{ index: 0, count: {{ $this->banners->count() }} }" aria-label="Promosi" aria-roledescription="carousel" class="relative mb-8 overflow-hidden rounded-2xl bg-zinc-900">
            @foreach($this->banners as $banner)
                <div wire:key="public-banner-{{ $banner->id }}" x-show="index === {{ $loop->index }}" @if(!$loop->first) x-cloak @endif role="group" aria-label="{{ $loop->iteration }} dari {{ $loop->count }}" class="relative min-h-72 md:min-h-96">
                    <img src="{{ $banner->getFirstMediaUrl('banner') }}" alt="{{ $banner->image_alt }}" class="absolute inset-0 h-full w-full object-cover" @if($loop->first) fetchpriority="high" @else loading="lazy" @endif />
                    <div class="relative min-h-72 md:min-h-96 bg-linear-to-r from-black/75 to-black/15 flex items-center px-12 py-14 md:px-20">
                        <div class="max-w-xl flex flex-col items-start gap-4">
                            <h2 class="text-3xl font-bold tracking-tight text-white md:text-5xl">{{ $banner->title }}</h2>
                            @if($banner->subtitle)<p class="text-base text-white/90 md:text-lg">{{ $banner->subtitle }}</p>@endif
                            @if($banner->cta_label && $banner->cta_url)<flux:button href="{{ $banner->cta_url }}">{{ $banner->cta_label }}</flux:button>@endif
                        </div>
                    </div>
                </div>
            @endforeach
            @if($this->banners->count() > 1)
                <button type="button" x-on:click="index = (index - 1 + count) % count" aria-label="Banner sebelumnya" class="absolute left-2 top-1/2 -translate-y-1/2 rounded-full bg-black/40 p-3 text-white hover:bg-black/70 focus-visible:outline-2 focus-visible:outline-white"><flux:icon.chevron-left class="size-5" /></button>
                <button type="button" x-on:click="index = (index + 1) % count" aria-label="Banner berikutnya" class="absolute right-2 top-1/2 -translate-y-1/2 rounded-full bg-black/40 p-3 text-white hover:bg-black/70 focus-visible:outline-2 focus-visible:outline-white"><flux:icon.chevron-right class="size-5" /></button>
                <div class="absolute bottom-3 left-1/2 -translate-x-1/2 flex gap-2">
                    @foreach($this->banners as $banner)<button wire:key="banner-dot-{{ $banner->id }}" type="button" x-on:click="index = {{ $loop->index }}" :aria-current="index === {{ $loop->index }} ? 'true' : 'false'" aria-label="Tampilkan banner {{ $loop->iteration }}" class="flex size-8 items-center justify-center"><span class="h-2 rounded-full" :class="index === {{ $loop->index }} ? 'w-6 bg-white' : 'w-2 bg-white/50'"></span></button>@endforeach
                </div>
            @endif
        </section>
    @endif
</div>
