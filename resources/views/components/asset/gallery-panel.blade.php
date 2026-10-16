{{-- Panel tab Gallery pada halaman detail entitas (grid thumbnail media polymorphic) --}}
@props([
    'medias',
    'name' => 'entitas',
    'addHref' => null,
])

<div class="ina-card ina-card--stack p-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-base font-semibold text-content-primary">Gallery</h2>
            <p class="mt-0.5 text-sm text-content-secondary">Foto dan bukti visual terkait {{ $name }}.</p>
        </div>
        @if ($addHref && auth()->user()?->can('media.create'))
            <a href="{{ $addHref }}" wire:navigate class="ina-button ina-button--primary ina-button--sm">
                <i class="ti ti-photo-up text-base" aria-hidden="true"></i>
                Tambah
            </a>
        @endif
    </div>

    @if ($medias->isNotEmpty())
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 xl:grid-cols-4">
            @foreach ($medias as $media)
                <figure class="overflow-hidden rounded-lg border border-stroke-primary bg-background-secondary" wire:key="media-{{ $media->id }}">
                    @if ($media->isVideo())
                        <video src="{{ route('admin.media.show', $media->media_id) }}" controls preload="metadata" class="h-36 w-full bg-black object-contain"></video>
                    @else
                        <img
                            src="{{ route('admin.media.show', ['mediaId' => $media->media_id, 't' => 1]) }}"
                            alt="{{ $media->title ?? 'Media' }}"
                            loading="lazy"
                            class="h-36 w-full object-cover"
                        >
                    @endif
                    @if ($media->title !== null || $media->description !== null)
                        <figcaption class="px-3 py-2">
                            @if ($media->title !== null)
                                <p class="truncate text-xs font-medium text-content-primary" title="{{ $media->title }}">{{ $media->title }}</p>
                            @endif
                            @if ($media->description !== null)
                                <p class="mt-0.5 line-clamp-2 text-[11px] text-content-tertiary" title="{{ $media->description }}">{{ $media->description }}</p>
                            @endif
                        </figcaption>
                    @endif
                </figure>
            @endforeach
        </div>
    @else
        {{-- Empty state --}}
        <div class="flex flex-col items-center px-6 py-10 text-center">
            <x-asset.illustration name="nodata" size="sm" />
            <p class="mt-4 text-sm font-medium text-content-primary">Belum ada media terkait</p>
            <p class="mt-2 max-w-sm text-sm text-content-secondary">Media terkait {{ $name }} akan tampil di sini. Tambahkan lewat tombol di atas atau melalui menu Gallery.</p>
        </div>
    @endif
</div>
