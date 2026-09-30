<div class="hidden border-b bg-gray-100 py-1.5 text-xs text-gray-500 md:block">
    @if($this->links->isNotEmpty())
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-6 px-6">
            @foreach(['left', 'right'] as $position)
                <nav aria-label="{{ $position === 'left' ? 'Informasi website' : 'Promo dan bantuan' }}" class="flex flex-wrap gap-4">
                    @foreach($this->links->where('position', $position) as $link)
                        <a wire:key="header-link-{{ $link->id }}" href="{{ $link->destination === 'page' ? route('content.page', ['slug' => $link->page->slug]) : $link->url }}" @if($link->destination === 'page') wire:navigate @endif class="transition hover:text-zinc-900">{{ $link->label }}</a>
                    @endforeach
                </nav>
            @endforeach
        </div>
    @endif
</div>
