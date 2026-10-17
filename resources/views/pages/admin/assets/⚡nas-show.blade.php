<?php

use App\Enums\NasStatus;
use App\Models\Activity;
use App\Models\Document;
use App\Models\Nas;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new class extends Component
{
    #[Layout('layouts::admin')]
    #[Title('Detail NAS')]

    public Nas $nas;

    public string $tab = 'overview';

    public function mount(string $nasId): void
    {
        $this->nas = Nas::query()
            ->with(['rack.location', 'location'])
            ->where('nas_id', $nasId)
            ->firstOrFail();

        $this->tab = request()->query('tab', 'overview');
    }

    public function tabs(): array
    {
        return [
            ['key' => 'overview', 'label' => 'Overview', 'icon' => 'ti-info-circle'],
            ['key' => 'relations', 'label' => 'Relasi', 'icon' => 'ti-topology-star'],
            ['key' => 'documents', 'label' => 'Dokumentasi', 'icon' => 'ti-folder', 'count' => Document::where('documentable_type', Nas::class)->where('documentable_id', $this->nas->id)->count()],
            ['key' => 'activities', 'label' => 'Aktivitas', 'icon' => 'ti-activity', 'count' => Activity::where('subject_type', Nas::class)->where('subject_id', $this->nas->id)->count()],
            ['key' => 'gallery', 'label' => 'Gallery', 'icon' => 'ti-photo', 'count' => $this->nas->medias()->count()],
        ];
    }
};
?>

<div>
    <x-asset.detail-header
        :back-route="route('admin.assets.nas.index')"
        :title="$nas->name"
        subtitle="Detail perangkat NAS beserta lokasi dan dokumen terkait."
    >
        @slot('right')
            <span class="ina-badge ina-badge--soft ina-badge--md {{ $nas->status === NasStatus::On ? 'ina-badge--success' : 'ina-badge--neutral' }}">{{ $nas->status->value }}</span>
        @endslot
    </x-asset.detail-header>

    <x-asset.tab-nav
        :url="route('admin.assets.nas.show', ['nasId' => $nas->nas_id])"
        :current="$tab"
        :tabs="$this->tabs()"
    />

    {{-- Tab: Overview --}}
    @if ($tab === 'overview')
        <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
            <div class="ina-card ina-card--stack p-6">
                <h2 class="text-base font-semibold text-content-primary">Informasi Umum</h2>
                <p class="mt-0.5 text-sm text-content-secondary">Informasi dasar perangkat NAS.</p>

                <div class="pt-5">
                    <dl class="space-y-2 text-sm">
                        <div class="flex justify-between gap-3">
                            <dt class="text-content-tertiary">Nama</dt>
                            <dd class="font-medium text-content-primary text-right">{{ $nas->name }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-content-tertiary">Brand</dt>
                            <dd class="font-medium text-content-primary text-right">{{ $nas->brand ?? '-' }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-content-tertiary">OS</dt>
                            <dd class="font-medium text-content-primary text-right">{{ $nas->os ?? '-' }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-content-tertiary">Status</dt>
                            <dd class="font-medium text-content-primary text-right">{{ $nas->status->value }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <div class="ina-card ina-card--stack p-6">
                <h2 class="text-base font-semibold text-content-primary">Kapasitas</h2>
                <p class="mt-0.5 text-sm text-content-secondary">Penyimpanan perangkat.</p>

                <div class="pt-5">
                    <dl class="space-y-2 text-sm">
                        <div class="flex justify-between gap-3">
                            <dt class="text-content-tertiary">Storage</dt>
                            <dd class="font-medium text-content-primary text-right">{{ $nas->storage_size !== null ? number_format($nas->storage_size / 1024).' GB' : '-' }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-content-tertiary">Bays</dt>
                            <dd class="font-medium text-content-primary text-right">{{ $nas->bays !== null ? $nas->bays.' slot' : '-' }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-content-tertiary">Posisi</dt>
                            <dd class="font-medium text-content-primary text-right">{{ $nas->position !== null ? 'U'.$nas->position : ($nas->rack !== null ? '-' : 'Tanpa rack') }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-content-tertiary">RAID</dt>
                            <dd class="font-medium text-content-primary text-right">{{ $nas->raid_level ?? '-' }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <div class="ina-card ina-card--stack p-6">
                <h2 class="text-base font-semibold text-content-primary">Lokasi & Akses</h2>
                <p class="mt-0.5 text-sm text-content-secondary">Penempatan fisik dan kredensial.</p>

                <div class="pt-5">
                    <dl class="space-y-2 text-sm">
                        <div class="flex justify-between gap-3">
                            <dt class="text-content-tertiary">Server Room</dt>
                            <dd class="font-medium text-right">
                                @if ($nas->effective() !== null)
                                    <a href="{{ route('admin.server-rooms.show', $nas->effective()->location_id) }}" wire:navigate class="text-guide-600 hover:underline">{{ $nas->effective()->name }}</a>
                                @else
                                    <span class="text-content-primary">-</span>
                                @endif
                            </dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-content-tertiary">Rack</dt>
                            <dd class="font-medium text-right">
                                @if ($nas->rack !== null)
                                    <a href="{{ route('admin.assets.racks.show', $nas->rack->rack_id) }}" wire:navigate class="text-guide-600 hover:underline">{{ $nas->rack->name }}</a>
                                @else
                                    <span class="text-content-tertiary">Tanpa rack</span>
                                @endif
                            </dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-content-tertiary">Static IP</dt>
                            <dd class="font-medium text-content-primary text-right">{{ $nas->static_ip ?? '-' }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-content-tertiary">Username</dt>
                            <dd class="font-medium text-content-primary text-right">{{ $nas->username ?? '-' }}</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>
    @endif

    {{-- Tab: Relasi --}}
    @if ($tab === 'relations')
        <div class="mt-4 grid grid-cols-1 gap-4 xl:grid-cols-2">
            <div class="ina-card ina-card--stack p-6">
                <h2 class="text-base font-semibold text-content-primary">Server Room</h2>
                <p class="mt-0.5 text-sm text-content-secondary">Room tempat NAS ditempatkan.</p>

                @if ($nas->effective() !== null)
                    <div class="flex items-center gap-3 rounded-lg border border-stroke-primary p-4">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-guide-50 text-guide-600">
                            <i class="ti ti-building text-base"></i>
                        </span>
                        <div class="min-w-0 flex-1">
                            <a href="{{ route('admin.server-rooms.show', $nas->effective()->location_id) }}" wire:navigate class="block truncate text-sm font-medium text-content-primary hover:text-guide-600">
                                {{ $nas->effective()->name }}
                            </a>
                            <p class="truncate text-xs text-content-tertiary">{{ $nas->effective()->location_id }}{{ $nas->rack !== null ? ' · via rack '.$nas->rack->name : ' · langsung' }}</p>
                        </div>
                    </div>
                @else
                    <p class="py-6 text-center text-sm text-content-tertiary">NAS ini belum ditautkan ke lokasi mana pun.</p>
                @endif
            </div>

            <div class="ina-card ina-card--stack p-6">
                <h2 class="text-base font-semibold text-content-primary">Rack</h2>
                <p class="mt-0.5 text-sm text-content-secondary">{{ $nas->rack !== null ? 'NAS ditempatkan di dalam rack.' : 'NAS ditempatkan langsung di server room (tanpa rack).' }}</p>

                @if ($nas->rack !== null)
                    <div class="flex items-center gap-3 rounded-lg border border-stroke-primary p-4">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-guide-50 text-guide-600">
                            <i class="ti ti-server-2 text-base"></i>
                        </span>
                        <div class="min-w-0 flex-1">
                            <a href="{{ route('admin.assets.racks.show', $nas->rack->rack_id) }}" wire:navigate class="block truncate text-sm font-medium text-content-primary hover:text-guide-600">
                                {{ $nas->rack->name }}
                            </a>
                            <p class="truncate text-xs text-content-tertiary">{{ $nas->rack->rack_id }} · {{ $nas->rack->servers->count() }} server{{ $nas->position !== null ? ' · posisi U'.$nas->position : '' }}</p>
                        </div>
                    </div>
                @else
                    <p class="py-6 text-center text-sm text-content-tertiary">NAS ini tidak memakai rack.</p>
                @endif
            </div>

            <div class="ina-card ina-card--stack p-6 xl:col-span-2">
                <h2 class="text-base font-semibold text-content-primary">Catatan</h2>
                <p class="mt-0.5 text-sm text-content-secondary">NAS adalah perangkat mandiri — tidak memiliki relasi virtualisasi, panel, atau aplikasi.</p>
            </div>
        </div>
    @endif

    {{-- Tab: Dokumentasi --}}
    @if ($tab === 'documents')
        @php
            $documents = Document::query()->with('addedBy')->where('documentable_type', Nas::class)->where('documentable_id', $this->nas->id)->orderBy('document_id')->get();
            $baseUrl = route('admin.assets.nas.show', ['nasId' => $this->nas->nas_id]);
            $documents = $documents;
        @endphp
        <div class="mt-4">
            <x-asset.documents-panel
                :documents="$documents"
                :name="$nas->name"
                :add-href="route('admin.documents.index', ['type' => Nas::class, 'id' => $nas->id]).'#upload'"
            />
        </div>
    @endif

    {{-- Tab: Aktivitas --}}
    @if ($tab === 'activities')
        @php $activities = Activity::query()->with('user')->where('subject_type', Nas::class)->where('subject_id', $this->nas->id)->orderByDesc('id')->get(); @endphp
        <div class="mt-4">
            <x-asset.activities-panel :activities="$activities" :name="$nas->name" />
        </div>
    @endif

    {{-- Tab: Gallery --}}
    @if ($tab === 'gallery')
        @php $medias = $this->nas->medias()->orderByDesc('id')->get(); @endphp
        <div class="mt-4">
            <x-asset.gallery-panel
                :medias="$medias"
                :name="$nas->name"
                :add-href="route('admin.galleries.index', ['type' => Nas::class, 'id' => $nas->id])"
            />
        </div>
    @endif
</div>
