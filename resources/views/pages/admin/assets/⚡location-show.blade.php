<?php

use App\Models\Activity;
use App\Models\Document;
use App\Models\Location;
use App\Models\Server;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new class extends Component
{
    #[Layout('layouts::admin')]
    #[Title('Detail Server Room')]

    public Location $location;

    public string $tab = 'overview';

    public function mount(string $locationId): void
    {
        $this->location = Location::query()
            ->with(['racks.servers'])
            ->where('location_id', $locationId)
            ->firstOrFail();

        $this->tab = request()->query('tab', 'overview');
    }

    public function tabs(): array
    {
        return [
            ['key' => 'overview', 'label' => 'Overview', 'icon' => 'ti-info-circle'],
            ['key' => 'relations', 'label' => 'Relasi', 'icon' => 'ti-topology-star'],
            ['key' => 'documents', 'label' => 'Dokumentasi', 'icon' => 'ti-folder', 'count' => Document::where('documentable_type', Location::class)->where('documentable_id', $this->location->id)->count()],
            ['key' => 'activities', 'label' => 'Aktivitas', 'icon' => 'ti-activity', 'count' => Activity::where('subject_type', Location::class)->where('subject_id', $this->location->id)->count()],
            ['key' => 'gallery', 'label' => 'Gallery', 'icon' => 'ti-photo', 'count' => $this->location->medias()->count()],
        ];
    }
};
?>

<div>
    <x-asset.detail-header
        :back-route="route('admin.server-rooms.index')"
        :title="$location->name"
        :code="$location->location_id"
        subtitle="Detail server room beserta rack dan server di dalamnya."
    />

    <x-asset.tab-nav
        :url="route('admin.server-rooms.show', ['locationId' => $location->location_id])"
        :current="$tab"
        :tabs="$this->tabs()"
    />

    {{-- Tab: Overview --}}
    @if ($tab === 'overview')
        <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
            <div class="ina-card ina-card--stack p-6">
                <h2 class="text-base font-semibold text-content-primary">Informasi Umum</h2>
                <p class="mt-0.5 text-sm text-content-secondary">Informasi dasar server room.</p>

                <div class="pt-5">
                    <dl class="space-y-2 text-sm">
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-content-tertiary">Kode</dt>
                            <dd class="font-medium text-content-primary">{{ $location->location_id }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-content-tertiary">Nama</dt>
                            <dd class="text-right font-medium text-content-primary">{{ $location->name }}</dd>
                        </div>
                        <div class="flex items-start justify-between gap-3">
                            <dt class="shrink-0 text-content-tertiary">Alamat</dt>
                            <dd class="text-right font-medium text-content-primary">{{ $location->address ?? '-' }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <div class="ina-card ina-card--stack p-6">
                <h2 class="text-base font-semibold text-content-primary">Ringkasan</h2>
                <p class="mt-0.5 text-sm text-content-secondary">Isi server room saat ini.</p>

                <div class="grid grid-cols-2 gap-4 pt-5">
                    <div class="rounded-lg border border-stroke-primary p-4">
                        <p class="text-xs text-content-tertiary">Rack</p>
                        <p class="mt-1 text-2xl font-semibold text-content-primary">{{ $location->racks->count() }}</p>
                    </div>
                    <div class="rounded-lg border border-stroke-primary p-4">
                        <p class="text-xs text-content-tertiary">Server</p>
                        <p class="mt-1 text-2xl font-semibold text-content-primary">
                            {{ $location->racks->sum(fn ($rack) => $rack->servers->count()) + $location->servers()->count() }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Tab: Relasi --}}
    @if ($tab === 'relations')
        <div class="mt-4 grid grid-cols-1 gap-4 xl:grid-cols-2">
            {{-- Rack di dalam server room --}}
            <div class="ina-card ina-card--stack p-6">
                <h2 class="text-base font-semibold text-content-primary">Rack</h2>
                <p class="mt-0.5 text-sm text-content-secondary">Rack yang ditempatkan di server room ini.</p>

                @if ($location->racks->isNotEmpty())
                    <ul class="divide-y divide-stroke-primary">
                        @foreach ($location->racks as $rack)
                            <li class="flex items-center gap-3 py-3" wire:key="rack-{{ $rack->id }}">
                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-background-tertiary text-content-secondary">
                                    <i class="ti ti-server-2 text-base"></i>
                                </span>
                                <div class="min-w-0 flex-1">
                                    <a href="{{ route('admin.assets.racks.show', $rack->rack_id) }}" wire:navigate class="block truncate text-sm font-medium text-content-primary hover:text-guide-600" title="{{ $rack->name }}">
                                        {{ $rack->name }}
                                    </a>
                                    <p class="truncate text-xs text-content-tertiary">{{ $rack->rack_id }} · {{ $rack->servers->count() }} server</p>
                                </div>
                                @if ($rack->color)
                                    <span class="h-3 w-3 shrink-0 rounded-full" style="background-color: {{ $rack->color }}"></span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="py-6 text-center text-sm text-content-tertiary">Belum ada rack di server room ini.</p>
                @endif
            </div>

            {{-- Server tanpa rack --}}
            <div class="ina-card ina-card--stack p-6">
                <h2 class="text-base font-semibold text-content-primary">Server Tanpa Rack</h2>
                <p class="mt-0.5 text-sm text-content-secondary">Server yang terhubung langsung ke server room ini.</p>

                @if ($location->servers()->count() > 0)
                    <ul class="divide-y divide-stroke-primary">
                        @foreach ($location->servers()->get() as $server)
                            <li class="flex items-center gap-3 py-3" wire:key="srv-{{ $server->id }}">
                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-background-tertiary text-content-secondary">
                                    <i class="ti ti-server text-base"></i>
                                </span>
                                <div class="min-w-0 flex-1">
                                    <a href="{{ route('admin.assets.servers.show', $server->server_id) }}" wire:navigate class="block truncate text-sm font-medium text-content-primary hover:text-guide-600" title="{{ $server->server_name }}">
                                        {{ $server->server_name }}
                                    </a>
                                    <p class="truncate text-xs text-content-tertiary">{{ $server->server_id }} · {{ $server->brand ?? 'Tanpa brand' }}</p>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="py-6 text-center text-sm text-content-tertiary">Semua server di server room ini berada dalam rack.</p>
                @endif
            </div>
        </div>
    @endif

    {{-- Tab: Dokumentasi --}}
    @if ($tab === 'documents')
        @php $documents = Document::query()->with('addedBy')->where('documentable_type', Location::class)->where('documentable_id', $this->location->id)->orderBy('document_id')->get(); @endphp
        <div class="mt-4">
            <x-asset.documents-panel :documents="$documents" name="{{ $location->name }}" :add-href="route('admin.documents.index', ['type' => Location::class, 'id' => $location->id])" />
        </div>
    @endif

    {{-- Tab: Aktivitas --}}
    @if ($tab === 'activities')
        @php $activities = Activity::query()->with(['user'])->where('subject_type', Location::class)->where('subject_id', $this->location->id)->orderByDesc('id')->get(); @endphp
        <div class="mt-4">
            <x-asset.activities-panel :activities="$activities" name="{{ $location->name }}" />
        </div>
    @endif

    {{-- Tab: Gallery --}}
    @if ($tab === 'gallery')
        @php $medias = $this->location->medias()->orderByDesc('id')->get(); @endphp
        <div class="mt-4">
            <x-asset.gallery-panel :medias="$medias" name="{{ $location->name }}" :add-href="route('admin.galleries.index', ['type' => Location::class, 'id' => $location->id])" />
        </div>
    @endif
</div>
