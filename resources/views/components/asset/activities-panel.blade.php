{{-- Panel tab Aktivitas pada halaman detail entitas (berdasarkan activities polymorphic) --}}
@props([
    'activities',
    'name' => 'entitas',
])

<div class="ina-card ina-card--stack p-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-base font-semibold text-content-primary">Aktivitas</h2>
            <p class="mt-0.5 text-sm text-content-secondary">Pencatatan pekerjaan terkait {{ $name }}.</p>
        </div>
    </div>

    @if ($activities->isNotEmpty())
        <ul class="divide-y divide-stroke-primary">
            @foreach ($activities as $activity)
                <li class="flex items-start gap-3 py-3" wire:key="act-{{ $activity->id }}">
                    <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-background-tertiary text-content-secondary">
                        <i class="ti ti-activity text-base"></i>
                    </span>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="truncate text-sm font-medium text-content-primary" title="{{ $activity->activity }}">{{ $activity->activity }}</p>
                            @if ($activity->category !== null)
                                <span class="ina-badge ina-badge--soft ina-badge--neutral ina-badge--sm">{{ $activity->category }}</span>
                            @endif
                            <span class="ina-badge ina-badge--soft ina-badge--sm {{ $activity->type === \App\Enums\ActivityType::Audit ? 'ina-badge--info' : 'ina-badge--brand' }}">{{ $activity->type->label() }}</span>
                        </div>
                        @if ($activity->description !== null)
                            <p class="mt-1 line-clamp-2 text-xs text-content-secondary" title="{{ $activity->description }}">{{ $activity->description }}</p>
                        @endif
                        <p class="mt-1 text-xs text-content-tertiary">
                            {{ $activity->user?->name ?? 'Sistem' }}
                            · {{ $activity->created_at->format('d M Y H:i') }}
                            @if ($activity->finished_at !== null)
                                · Selesai {{ $activity->finished_at->format('d M Y H:i') }}
                            @else
                                · Berjalan
                            @endif
                        </p>
                    </div>
                </li>
            @endforeach
        </ul>
    @else
        {{-- Empty state --}}
        <div class="flex flex-col items-center px-6 py-10 text-center">
            <x-asset.illustration name="nodata" size="sm" />
            <p class="mt-4 text-sm font-medium text-content-primary">Belum ada aktivitas terkait</p>
            <p class="mt-2 max-w-sm text-sm text-content-secondary">Aktivitas akan muncul setelah pekerjaan dicatat melalui modul Aktivitas.</p>
        </div>
    @endif
</div>
