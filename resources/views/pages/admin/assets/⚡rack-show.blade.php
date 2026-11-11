<?php

use App\Models\Activity;
use App\Models\Document;
use App\Models\Nas;
use App\Models\Rack;
use App\Models\Server;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new class extends Component
{
    #[Layout('layouts::admin')]
    #[Title('Detail Rack')]

    public Rack $rack;

    public string $tab = 'overview';

    /** Konteks list asal (page/filter) untuk tombol kembali dan link tab. */
    public array $returnQuery = [];

    public function mount(string $rackId): void
    {
        $this->rack = Rack::query()
            ->with(['location', 'servers', 'nas'])
            ->where('rack_id', $rackId)
            ->firstOrFail();

        $this->tab = request()->query('tab', 'overview');
        $this->returnQuery = collect(request()->query())->except('tab')->all();
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

    /**
     * Isi rak gabungan (Server + NAS) terurut position — kosong di akhir.
     */
    public function rackItems()
    {
        $rack = $this->rack;

        return $rack
            ->servers()
            ->get()
            ->map(fn (Server $s) => (object) [
                'kind' => 'server',
                'id' => $s->id,
                'code' => $s->server_id,
                'name' => $s->server_name,
                'brand' => $s->brand,
                'position' => $s->position,
                'detail' => $s->brand ?? '-',
                'url' => route('admin.assets.servers.show', $s->server_id),
            ])
            ->concat($rack->nas()->get()->map(fn (Nas $n) => (object) [
                'kind' => 'nas',
                'id' => $n->id,
                'code' => $n->nas_id,
                'name' => $n->name,
                'brand' => $n->brand,
                'position' => $n->position,
                'detail' => ($n->bays !== null ? $n->bays.' bay' : '-').($n->raid_level !== null ? ' · '.$n->raid_level : ''),
                'url' => route('admin.assets.nas.show', $n->nas_id),
            ]))
            ->sortBy(fn ($item) => $item->position ?? PHP_INT_MAX)
            ->values();
    }
};
?>

<div>
    <x-asset.detail-header
        :back-route="route('admin.assets.racks.index')"
        :title="$rack->name"
        subtitle="Detail rack beserta server yang ditempatkan di dalamnya."
        :back-query="$this->returnQuery"
    />

    <x-asset.tab-nav
        :url="route('admin.assets.racks.show', ['rackId' => $rack->rack_id])"
        :current="$tab"
        :tabs="$this->tabs()"
        :carry="$this->returnQuery"
    />

    {{-- Tab: Overview --}}
    @if ($tab === 'overview')
        <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
            <div class="ina-card ina-card--stack p-6">
                <h2 class="text-base font-semibold text-content-primary">Informasi Umum</h2>
                <p class="mt-0.5 text-sm text-content-secondary">Informasi dasar rack.</p>

                <div class="pt-5">
                    <dl class="space-y-2 text-sm">
                        <div class="flex justify-between gap-3">
                            <dt class="text-content-tertiary">Nama</dt>
                            <dd class="font-medium text-content-primary text-right">{{ $rack->name }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
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
                        <div class="flex justify-between gap-3">
                            <dt class="text-content-tertiary">Brand</dt>
                            <dd class="font-medium text-content-primary text-right">{{ $rack->brand ?? '-' }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-content-tertiary">Kapasitas</dt>
                            <dd class="font-medium text-content-primary text-right">{{ $rack->units !== null ? $rack->units.' U' : '-' }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-content-tertiary">Terisi</dt>
                            <dd class="font-medium text-content-primary text-right">{{ $rack->servers->count() }} server</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-content-tertiary">Warna</dt>
                            <dd class="font-medium text-content-primary text-right">
                                @if ($rack->color)
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
            @php $rackItems = $this->rackItems(); @endphp
            <div class="ina-card ina-card--stack p-6">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <h2 class="text-base font-semibold text-content-primary">Isi Rack</h2>
                        <p class="mt-0.5 text-sm text-content-secondary">Server &amp; NAS yang ditempatkan di {{ $rack->name }}.</p>
                    </div>
                    <div class="flex items-center gap-2 text-xs text-content-secondary">
                        <span><span class="font-semibold text-content-primary">{{ $rack->servers->count() }}</span> server</span>
                        <span><span class="font-semibold text-content-primary">{{ $rack->nas->count() }}</span> NAS</span>
                    </div>
                </div>

                @if ($rackItems->isNotEmpty())
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-stroke-primary bg-background-secondary text-left text-[12px] font-medium text-content-secondary">
                                    <th scope="col" class="h-10 px-4 font-medium text-right">U</th>
                                    <th scope="col" class="h-10 px-4 font-medium">Perangkat</th>
                                    <th scope="col" class="h-10 px-4 font-medium">Nama</th>
                                    <th scope="col" class="h-10 px-4 font-medium">Info</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($rackItems as $item)
                                    <tr class="border-b border-stroke-primary transition-colors duration-150 h-14 last:border-b-0 hover:bg-neutral-50" wire:key="item-{{ $item->kind }}-{{ $item->id }}">
                                        <td class="h-14 px-4 text-right text-content-secondary">{{ $item->position !== null ? 'U'.$item->position : '-' }}</td>
                                        <td class="h-14 px-4">
                                            <span class="ina-badge ina-badge--soft ina-badge--sm {{ $item->kind === 'server' ? 'ina-badge--brand' : 'ina-badge--info' }}">
                                                {{ $item->kind === 'server' ? 'Server' : 'NAS' }}
                                            </span>
                                        </td>
                                        <td class="h-14 max-w-xs truncate px-4 font-medium">
                                            <a href="{{ $item->url }}" wire:navigate class="text-content-primary hover:text-guide-600" title="{{ $item->name }}">
                                                {{ $item->name }}
                                            </a>
                                        </td>
                                        <td class="h-14 px-4 text-content-secondary">{{ $item->detail }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <x-asset.illustration name="nodata" size="sm" />
                    <p class="mt-4 text-center text-sm text-content-tertiary">Belum ada isi rack.</p>
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
