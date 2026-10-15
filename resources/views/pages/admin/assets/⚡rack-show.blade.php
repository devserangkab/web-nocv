<?php

use App\Models\Activity;
use App\Models\Document;
use App\Models\Rack;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new class extends Component
{
    #[Layout('layouts::admin')]
    #[Title('Detail Rack')]

    public Rack $rack;

    public string $tab = 'overview';

    public function mount(string $rackId): void
    {
        $this->rack = Rack::query()
            ->with(['location', 'servers'])
            ->where('rack_id', $rackId)
            ->firstOrFail();

        $this->tab = request()->query('tab', 'overview');
    }

    public function tabs(): array
    {
        return [
            ['key' => 'overview', 'label' => 'Overview', 'icon' => 'ti-info-circle'],
            ['key' => 'relations', 'label' => 'Relasi', 'icon' => 'ti-topology-star'],
            ['key' => 'documents', 'label' => 'Dokumentasi', 'icon' => 'ti-folder', 'count' => Document::where('documentable_type', Rack::class)->where('documentable_id', $this->rack->id)->count()],
            ['key' => 'activities', 'label' => 'Aktivitas', 'icon' => 'ti-activity', 'count' => Activity::where('subject_type', Rack::class)->where('subject_id', $this->rack->id)->count()],
            ['key' => 'gallery', 'label' => 'Gallery', 'icon' => 'ti-photo', 'count' => $this->rack->medias()->count()],
        ];
    }
};
?>

<div>
    <x-asset.detail-header
        :back-route="route('admin.assets.racks.index')"
        :title="$rack->name"
        :code="$rack->rack_id"
        subtitle="Detail rack beserta server yang ditempatkan di dalamnya."
    />

    <x-asset.tab-nav
        :url="route('admin.assets.racks.show', ['rackId' => $rack->rack_id])"
        :current="$tab"
        :tabs="$this->tabs()"
    />

    {{-- Tab: Overview --}}
    @if ($tab === 'overview')
        <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
            <div class="ina-card ina-card--stack p-6">
                <h2 class="text-base font-semibold text-content-primary">Informasi Umum</h2>
                <p class="mt-0.5 text-sm text-content-secondary">Informasi dasar rack.</p>

                <div class="pt-5">
                    <dl class="space-y-2 text-sm">
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-content-tertiary">Kode</dt>
                            <dd class="font-medium text-content-primary">{{ $rack->rack_id }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-content-tertiary">Nama</dt>
                            <dd class="font-medium text-content-primary">{{ $rack->name }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-content-tertiary">Server Room</dt>
                            <dd class="text-right font-medium">
                                @if ($rack->location !== null)
                                    <a href="{{ route('admin.server-rooms.show', $rack->location->location_id) }}" wire:navigate class="text-guide-600 hover:underline">{{ $rack->location->name }}</a>
                                @else
                                    <span class="text-content-primary">-</span>
                                @endif
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>

            <div class="ina-card ina-card--stack p-6">
                <h2 class="text-base font-semibold text-content-primary">Spesifikasi</h2>
                <p class="mt-0.5 text-sm text-content-secondary">Kapasitas fisik rack.</p>

                <div class="pt-5">
                    <dl class="space-y-2 text-sm">
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-content-tertiary">Brand</dt>
                            <dd class="font-medium text-content-primary">{{ $rack->brand ?? '-' }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-content-tertiary">Kapasitas</dt>
                            <dd class="font-medium text-content-primary">{{ $rack->units !== null ? $rack->units.' U' : '-' }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-content-tertiary">Terisi</dt>
                            <dd class="font-medium text-content-primary">{{ $rack->servers->count() }} server</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-content-tertiary">Warna</dt>
                            <dd class="flex items-center gap-2 font-medium text-content-primary">
                                @if ($rack->color)
                                    <span class="h-3 w-3 rounded-full border border-stroke-primary" style="background-color: {{ $rack->color }}"></span>
                                    {{ $rack->color }}
                                @else
                                    -
                                @endif
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>
    @endif

    {{-- Tab: Relasi --}}
    @if ($tab === 'relations')
        <div class="mt-4">
            <div class="ina-card ina-card--stack p-6">
                <h2 class="text-base font-semibold text-content-primary">Server dalam Rack</h2>
                <p class="mt-0.5 text-sm text-content-secondary">Server yang ditempatkan di {{ $rack->name }}.</p>

                @if ($rack->servers->isNotEmpty())
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-stroke-primary bg-background-secondary text-left text-[12px] font-medium text-content-secondary">
                                    <th scope="col" class="h-10 px-4 font-medium">Kode</th>
                                    <th scope="col" class="h-10 px-4 font-medium">Nama</th>
                                    <th scope="col" class="h-10 px-4 font-medium">Brand</th>
                                    <th scope="col" class="h-10 px-4 font-medium text-right">Posisi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($rack->servers as $server)
                                    <tr class="border-b border-stroke-primary transition-colors duration-150 h-14 last:border-b-0 hover:bg-neutral-50" wire:key="srv-{{ $server->id }}">
                                        <td class="h-14 px-4 text-[13px] text-content-primary">{{ $server->server_id }}</td>
                                        <td class="h-14 max-w-xs truncate px-4 font-medium">
                                            <a href="{{ route('admin.assets.servers.show', $server->server_id) }}" wire:navigate class="text-content-primary hover:text-guide-600" title="{{ $server->server_name }}">
                                                {{ $server->server_name }}
                                            </a>
                                        </td>
                                        <td class="h-14 px-4 text-content-secondary">{{ $server->brand ?? '-' }}</td>
                                        <td class="h-14 px-4 text-right text-content-secondary">{{ $server->position !== null ? 'U'.$server->position : '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="py-6 text-center text-sm text-content-tertiary">Belum ada server di rack ini.</p>
                @endif
            </div>
        </div>
    @endif

    {{-- Tab: Dokumentasi --}}
    @if ($tab === 'documents')
        @php $documents = Document::query()->with('addedBy')->where('documentable_type', Rack::class)->where('documentable_id', $this->rack->id)->orderBy('document_id')->get(); @endphp
        <div class="mt-4">
            <x-asset.documents-panel :documents="$documents" name="{{ $rack->name }}" :add-href="route('admin.documents.index', ['type' => Rack::class, 'id' => $rack->id])" />
        </div>
    @endif

    {{-- Tab: Aktivitas --}}
    @if ($tab === 'activities')
        @php $activities = Activity::query()->with('user')->where('subject_type', Rack::class)->where('subject_id', $this->rack->id)->orderByDesc('id')->get(); @endphp
        <div class="mt-4">
            <x-asset.activities-panel :activities="$activities" name="{{ $rack->name }}" />
        </div>
    @endif

    {{-- Tab: Gallery --}}
    @if ($tab === 'gallery')
        @php $medias = $this->rack->medias()->orderByDesc('id')->get(); @endphp
        <div class="mt-4">
            <x-asset.gallery-panel :medias="$medias" name="{{ $rack->name }}" :add-href="route('admin.galleries.index', ['type' => Rack::class, 'id' => $rack->id])" />
        </div>
    @endif
</div>
