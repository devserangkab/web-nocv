<?php

use App\Enums\ApplicationStatus;
use App\Models\Activity;
use App\Models\Application;
use App\Models\Document;
use App\Models\Location;
use App\Models\Panel;
use App\Models\Proxmox;
use App\Models\Rack;
use App\Models\Server;
use App\Models\Vms;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new class extends Component
{
    #[Layout('layouts::admin')]
    #[Title('Dashboard')]

    public function stats(): array
    {
        // Jumlah server per server room menghitung server via rack + langsung,
        // dengan COALESCE agar server di rack tidak dihitung ganda.
        $serverCounts = Server::query()
            ->selectRaw('COALESCE(r.location_id, s.location_id) as room_location_id, COUNT(*) as total')
            ->from('servers as s')
            ->leftJoin('racks as r', 'r.rack_id', '=', 's.rack_id')
            ->whereRaw('COALESCE(r.location_id, s.location_id) IS NOT NULL')
            ->groupBy(DB::raw('COALESCE(r.location_id, s.location_id)'))
            ->pluck('total', 'room_location_id');

        $locations = Location::query()
            ->withCount(['racks', 'servers'])
            ->orderBy('location_id')
            ->get()
            ->map(fn (Location $location) => [
                'id' => $location->location_id,
                'name' => $location->name,
                'racks' => $location->racks_count,
                'servers' => ($serverCounts[(string) $location->location_id] ?? 0) + $location->servers_count,
            ]);

        return [
            'locations' => $locations,
            'totals' => [
                'location' => Location::count(),
                'rack' => Rack::count(),
                'server' => Server::count(),
                'proxmox' => Proxmox::count(),
                'vm' => Vms::count(),
                'panel' => Panel::count(),
                'application' => Application::count(),
                'nas' => \App\Models\Nas::count(),
                'document' => Document::count(),
                'media' => \App\Models\Media::count(),
            ],
            'weeklyActivity' => Activity::where('created_at', '>=', now()->subDays(7))->count(),
        ];
    }

    public function recentActivities()
    {
        return Activity::query()
            ->with(['user', 'subject'])
            ->orderByDesc('id')
            ->limit(5)
            ->get();
    }

    public function recentDocuments()
    {
        return Document::query()
            ->with(['addedBy', 'documentable'])
            ->orderByDesc('id')
            ->limit(5)
            ->get();
    }

    public function productionApps(): int
    {
        return Application::where('status', ApplicationStatus::Production)->count();
    }
};
?>

<div>
    {{-- Judul halaman --}}
    <h1 class="text-xl font-semibold text-content-primary">Dashboard</h1>
    <p class="mt-1 text-sm text-content-secondary">Ringkasan kondisi inventaris infrastruktur dan aplikasi.</p>

    {{-- Ringkasan statistik — setiap kartu menuju daftar terkait --}}
    @php $stats = $this->stats(); @endphp
    <div class="mt-6 grid grid-cols-1 xs:grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4 2xl:grid-cols-6">
        @foreach ([
            ['label' => 'Server Room', 'desc' => 'Ruang server', 'value' => $stats['totals']['location'], 'route' => route('admin.server-rooms.index'), 'icon' => 'ti-building'],
            ['label' => 'Rack', 'desc' => 'Rak server', 'value' => $stats['totals']['rack'], 'route' => route('admin.assets.racks.index'), 'icon' => 'ti-server-2'],
            ['label' => 'Server', 'desc' => 'Server fisik', 'value' => $stats['totals']['server'], 'route' => route('admin.assets.servers.index'), 'icon' => 'ti-server'],
            ['label' => 'Proxmox', 'desc' => 'Virtualisasi', 'value' => $stats['totals']['proxmox'], 'route' => route('admin.assets.virtualization.index'), 'icon' => 'ti-stack-2'],
            ['label' => 'Virtual Machine', 'desc' => 'VM dikelola', 'value' => $stats['totals']['vm'], 'route' => route('admin.assets.virtualization.index'), 'icon' => 'ti-box-multiple-3'],
            ['label' => 'Panel', 'desc' => 'Panel management', 'value' => $stats['totals']['panel'], 'route' => route('admin.assets.panels.index'), 'icon' => 'ti-app-window'],
            ['label' => 'NAS', 'desc' => 'Perangkat penyimpanan', 'value' => $stats['totals']['nas'], 'route' => route('admin.assets.nas.index'), 'icon' => 'ti-database'],
            ['label' => 'Aplikasi', 'desc' => 'Aplikasi dikelola', 'value' => $stats['totals']['application'], 'route' => route('admin.applications.index'), 'icon' => 'ti-apps'],
            ['label' => 'Aplikasi', 'desc' => 'Production', 'value' => $this->productionApps(), 'route' => route('admin.applications.index'), 'icon' => 'ti-rocket'],
            ['label' => 'Dokumen', 'desc' => 'Dokumen terunggah', 'value' => $stats['totals']['document'], 'route' => route('admin.documents.index'), 'icon' => 'ti-folder'],
            ['label' => 'Media Gallery', 'desc' => 'Media dokumentasi', 'value' => $stats['totals']['media'], 'route' => route('admin.galleries.index'), 'icon' => 'ti-photo'],
            ['label' => 'Aktivitas 7 Hari', 'desc' => 'Aktivitas minggu ini', 'value' => $stats['weeklyActivity'], 'route' => route('admin.activities.index'), 'icon' => 'ti-activity'],
        ] as $card)
            <a
                href="{{ $card['route'] }}"
                wire:navigate
                title="{{ $card['label'] }}"
                class="ina-card relative ina-card--stack p-4 transition-shadow duration-150 hover:shadow-md {{ $loop->iteration === 1 ? 'ina-accent-solid' : '' }}"
            >
                <div class="flex absolute right-2 top-2 h-10 w-10 items-center justify-center rounded-sm {{ $loop->iteration === 1 ? 'bg-white/15 text-white' : 'bg-guide-50 text-guide-600' }}">
                    <i class="ti {{ $card['icon'] }} text-xl"></i>
                </div>
                <p class="sm:text-xs max-w-[100px] font-medium {{ $loop->iteration === 1 ? 'text-white/80' : 'text-content-secondary' }}">{{ $card['label'] }}</p>
                <p class="mt-3 text-2xl grow font-semibold {{ $loop->iteration === 1 ? 'text-white' : 'text-content-primary' }}">{{ $card['value'] }}</p>
                <p class="mt-0.5 text-[11px] {{ $loop->iteration === 1 ? 'text-white/70' : 'text-content-tertiary' }}">{{ $card['desc'] }}</p>
            </a>
        @endforeach
    </div>

    <div class="mt-8 grid grid-cols-1 gap-6 xl:grid-cols-3">
        {{-- Aktivitas terbaru --}}
        @php $activities = $this->recentActivities(); @endphp
        <div class="ina-card ina-card--stack p-6 xl:col-span-2">
            <div class="flex items-center justify-between">
                <h2 class="text-base font-semibold text-content-primary">Aktivitas Terbaru</h2>
            </div>

            @if ($activities->isNotEmpty())
                <ul class="divide-y divide-stroke-primary">
                    @foreach ($activities as $activity)
                        <li class="flex items-start gap-3 py-3">
                            <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-background-tertiary text-content-secondary">
                                <i class="ti ti-activity text-base"></i>
                            </span>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2">
                                    <p class="truncate text-sm font-medium text-content-primary" title="{{ $activity->activity }}">{{ $activity->activity }}</p>
                                    <span class="ina-badge ina-badge--soft ina-badge--sm {{ $activity->type === \App\Enums\ActivityType::Audit ? 'ina-badge--info' : 'ina-badge--brand' }}">{{ $activity->type->label() }}</span>
                                </div>
                                <p class="mt-0.5 truncate text-xs text-content-tertiary">
                                    {{ $activity->user?->name ?? 'Sistem' }}
                                    @if ($activity->subject !== null)
                                        · {{ class_basename($activity->subject_type) }}
                                        @if (isset($activity->subject->name))
                                            — {{ $activity->subject->name }}
                                        @endif
                                    @endif
                                    · {{ $activity->created_at->diffForHumans() }}
                                </p>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="py-8 text-center text-sm text-content-tertiary">Belum ada aktivitas tercatat.</p>
            @endif
        </div>

        {{-- Dokumen terbaru --}}
        @php $documents = $this->recentDocuments(); @endphp
        <div class="ina-card ina-card--stack p-6">
            <h2 class="text-base font-semibold text-content-primary">Dokumen Terbaru</h2>

            @if ($documents->isNotEmpty())
                <ul class="divide-y divide-stroke-primary">
                    @foreach ($documents as $document)
                        <li class="flex items-center gap-3 py-3">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-background-tertiary text-content-secondary">
                                <i class="ti ti-file-text text-base"></i>
                            </span>
                            <div class="min-w-0 flex-1">
                                <a
                                    @if ($document->document !== null)
                                        href="{{ route('admin.documents.download', $document->document_id) }}"
                                    @endif
                                    class="block truncate text-sm font-medium text-content-primary hover:text-guide-600 {{ $document->document === null ? 'cursor-default' : '' }}"
                                    title="{{ $document->name }}"
                                >
                                    {{ $document->name }}
                                </a>
                                <p class="mt-0.5 truncate text-xs text-content-tertiary">
                                    @if ($document->documentable !== null)
                                        {{ class_basename($document->documentable_type) }}
                                        @if (isset($document->documentable->name))
                                            — {{ $document->documentable->name }}
                                        @endif
                                    @else
                                        Tidak terkait
                                    @endif
                                    · {{ $document->created_at->format('d M Y') }}
                                </p>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="py-8 text-center text-sm text-content-tertiary">Belum ada dokumen terunggah.</p>
            @endif
        </div>
    </div>

    {{-- Pintasan menuju modul utama --}}
    <div class="mt-8">
        <h2 class="text-base font-semibold text-content-primary">Pintasan</h2>
        <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4 2xl:grid-cols-6">
            @foreach ($stats['locations'] as $location)
                <div class="ina-card ina-card--stack p-5">
                    <div class="flex items-center gap-3">
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-guide-50 text-guide-600">
                            <i class="ti ti-building text-lg"></i>
                        </div>
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-content-primary" title="{{ $location['name'] }}">{{ $location['name'] }}</p>
                            <p class="truncate text-xs text-content-tertiary">{{ $location['id'] }}</p>
                        </div>
                    </div>
                    <div class="flex gap-4 pt-4 text-xs text-content-secondary">
                        <span><span class="font-semibold text-content-primary">{{ $location['racks'] }}</span> rack</span>
                        <span><span class="font-semibold text-content-primary">{{ $location['servers'] }}</span> server</span>
                    </div>
                    <a
                        href="{{ route('admin.server-rooms.show', $location['id']) }}"
                        wire:navigate
                        class="mt-3 inline-flex items-center gap-1 text-xs font-medium text-guide-600 hover:underline"
                    >
                        Lihat detail
                        <i class="ti ti-arrow-right text-xs"></i>
                    </a>
                </div>
            @endforeach
        </div>
    </div>
</div>
