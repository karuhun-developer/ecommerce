<footer class="mt-16 border-t border-zinc-200 bg-white pb-20 md:pb-8">
    <div class="mx-auto flex max-w-7xl flex-col gap-10 px-6 py-12 lg:flex-row">
        <div class="grid flex-1 gap-8 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($this->groups as $group)
                @if($group->pages->isNotEmpty())
                    <nav wire:key="footer-group-{{ $group->id }}" aria-label="{{ $group->name }}" class="flex flex-col gap-4">
                        <h2 class="font-semibold text-zinc-900">{{ $group->name }}</h2>
                        <ul class="flex flex-col gap-3 text-sm text-zinc-500">
                            @foreach($group->pages as $page)
                                <li wire:key="footer-page-{{ $page->id }}"><a href="{{ route('content.page', ['slug' => $page->slug]) }}" wire:navigate class="hover:text-zinc-900">{{ $page->title }}</a></li>
                            @endforeach
                        </ul>
                    </nav>
                @endif
            @endforeach
        </div>
        <div class="flex flex-col items-start gap-4 lg:w-80">
            <a href="{{ route('home') }}" wire:navigate class="text-3xl font-bold tracking-tight text-zinc-900">Ecommerce.</a>
            <p class="text-sm text-zinc-500">Download aplikasi Ecommerce sekarang.</p>
            <div class="flex flex-wrap gap-4">
                <img src="{{ asset('images/google-play-badge.svg') }}" alt="Play Store" width="135" height="40" class="h-10 w-auto" />
                <img src="{{ asset('images/app-store-badge.svg') }}" alt="App Store" width="120" height="40" class="h-10 w-auto" />
            </div>
            @if(!empty($this->identity['tagline']))<p class="text-sm leading-relaxed text-zinc-500">{{ $this->identity['tagline'] }}</p>@endif
        </div>
    </div>
    <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-4 border-t border-zinc-200 px-6 pt-8 md:flex-row">
        <p class="text-sm text-zinc-500">&copy; {{ date('Y') }} {{ $this->identity['brand_name'] ?? config('app.name') }}. All rights reserved.</p>
        <div class="flex items-center gap-2 text-zinc-500">
            @if(!empty($this->identity['instagram_url']))
                <a href="{{ $this->identity['instagram_url'] }}" target="_blank" rel="noopener noreferrer" aria-label="Instagram" class="rounded-lg p-2 hover:text-zinc-900"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="size-6" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor" stroke="none"/></svg></a>
            @endif
            @if(!empty($this->identity['website_url']))<a href="{{ $this->identity['website_url'] }}" target="_blank" rel="noopener noreferrer" aria-label="Website" class="rounded-lg p-2 hover:text-zinc-900"><flux:icon.globe-alt class="size-6" /></a>@endif
            @if(!empty($this->identity['whatsapp_url']))
                <a href="{{ $this->identity['whatsapp_url'] }}" target="_blank" rel="noopener noreferrer" aria-label="WhatsApp" class="rounded-lg p-2 hover:text-zinc-900"><svg viewBox="0 0 24 24" fill="currentColor" class="size-6" aria-hidden="true"><path d="M20.52 3.48A11.91 11.91 0 0 0 12.05 0C5.47 0 .12 5.35.12 11.93c0 2.1.55 4.16 1.59 5.98L0 24l6.25-1.64a11.94 11.94 0 0 0 5.8 1.48h.01C18.64 23.84 24 18.5 24 11.92c0-3.19-1.24-6.18-3.48-8.44ZM12.05 21.83a9.9 9.9 0 0 1-5.05-1.38l-.36-.21-3.71.97.99-3.61-.23-.37a9.88 9.88 0 0 1-1.52-5.3c0-5.47 4.45-9.92 9.93-9.92a9.86 9.86 0 0 1 7.01 2.91 9.84 9.84 0 0 1 2.9 7.01c0 5.47-4.45 9.9-9.96 9.9Zm5.45-7.42c-.3-.15-1.76-.87-2.04-.97-.27-.1-.47-.15-.67.15-.2.3-.77.97-.94 1.17-.18.2-.35.22-.65.07-.3-.15-1.26-.46-2.4-1.48-.89-.79-1.48-1.76-1.66-2.06-.17-.3-.02-.46.13-.6.13-.13.3-.35.45-.52.15-.18.2-.3.3-.5.1-.2.05-.37-.03-.52-.07-.15-.67-1.62-.92-2.22-.24-.58-.49-.5-.67-.51h-.57c-.2 0-.52.07-.8.37-.27.3-1.04 1.02-1.04 2.49s1.07 2.89 1.22 3.09c.15.2 2.1 3.2 5.09 4.48.71.31 1.27.49 1.7.62.72.23 1.37.2 1.88.12.58-.09 1.76-.72 2.01-1.42.25-.7.25-1.3.17-1.42-.07-.13-.27-.2-.57-.35Z"/></svg></a>
            @endif
        </div>
    </div>
</footer>
