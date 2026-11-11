<?php

use App\Enums\ServerStatus;
use App\Models\Activity;
use App\Models\Document;
use App\Models\Hosting;
use App\Models\Panel;
use App\Models\Server;
use App\Models\Vms;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new class extends Component
{
    use \App\Livewire\Concerns\InteractsWithServerForm;

    #[Layout('layouts::admin')]
    #[Title('Detail Server')]

    public Server $server;

    public string $tab = 'overview';

    /** Konteks list asal (page/filter) untuk tombol kembali dan link tab. */
    public array $returnQuery = [];

    public function mount(string $serverId): void
    {
        $this->server = Server::query()
            ->with(['rack.location', 'location', 'proxmox.vms', 'panels', 'wafs', 'databases.schemas'])
            ->where('server_id', $serverId)
            ->firstOrFail();

        $this->tab = request()->query('tab', 'overview');
        $this->returnQuery = collect(request()->query())->except('tab')->all();
    }

    /**
     * Aplikasi yang dihosting di server ini atau pada aset di bawahnya:
     * VM di bawah Proxmox server, serta panel yang terpasang di server
     * dan di VM-VM tersebut.
     */
    public function hostings()
    {
        $vms = $this->server->proxmox?->vms ?? collect();
        $vmIds = $vms->pluck('id')->all();
        $vmCodes = $vms->pluck('vm_id')->all();

        $panelIds = Panel::query()
            ->where(function ($query) use ($vmCodes) {
                $query->where('server_id', $this->server->server_id)
                    ->orWhereIn('vm_id', $vmCodes);
            })
            ->pluck('id')
            ->all();

        return Hosting::query()
            ->with(['application', 'hostable'])
            ->where(function ($query) use ($vmIds, $panelIds) {
                $query->where(fn ($query) => $query
                        ->where('hostable_type', Server::class)
                        ->where('hostable_id', $this->server->id))
                    ->orWhere(fn ($query) => $query
                        ->where('hostable_type', Vms::class)
                        ->whereIn('hostable_id', $vmIds))
                    ->orWhere(fn ($query) => $query
                        ->where('hostable_type', Panel::class)
                        ->whereIn('hostable_id', $panelIds));
            })
            ->orderBy('id')
            ->get();
    }

    public function tabs(): array
    {
        return [
            ['key' => 'overview', 'label' => 'Overview', 'icon' => 'ti-info-circle'],
            ['key' => 'relations', 'label' => 'Relasi', 'icon' => 'ti-topology-star'],
            ['key' => 'documents', 'label' => 'Dokumentasi', 'icon' => 'ti-folder', 'count' => Document::where('documentable_type', Server::class)->where('documentable_id', $this->server->id)->count()],
            ['key' => 'activities', 'label' => 'Aktivitas', 'icon' => 'ti-activity', 'count' => Activity::where('subject_type', Server::class)->where('subject_id', $this->server->id)->count()],
            ['key' => 'gallery', 'label' => 'Gallery', 'icon' => 'ti-photo', 'count' => $this->server->medias()->count()],
        ];
    }
};
?>

<div>
    <x-asset.detail-header
        :back-route="route('admin.assets.servers.index')"
        :title="$server->server_name"
        subtitle="Detail server beserta lokasi dan relasi terkait."
        :back-query="$this->returnQuery"
    >
        @slot('right')
            @can('infrastructure.manage')
            <button
                type="button"
                wire:click="edit({{ $server->id }})"
                class="ina-button ina-button--secondary ina-button--md"
            >
                <i class="ti ti-pencil text-base" aria-hidden="true"></i>
                Edit Server
            </button>
            @endcan
        @endslot
    </x-asset.detail-header>

    <x-asset.tab-nav
        :url="route('admin.assets.servers.show', ['serverId' => $server->server_id])"
        :current="$tab"
        :tabs="$this->tabs()"
        :carry="$this->returnQuery"
    />

    {{-- Tab: Overview --}}
    @if ($tab === 'overview')
        <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
            <div class="ina-card ina-card--stack p-6">
                <h2 class="text-base font-semibold text-content-primary">Informasi Umum</h2>
                <p class="mt-0.5 text-sm text-content-secondary">Informasi dasar server.</p>

                <div class="pt-5">
                    <dl class="space-y-2 text-sm">
                        <div class="flex justify-between gap-3">
                            <dt class="text-content-tertiary">Nama</dt>
                            <dd class="font-medium text-content-primary text-right">{{ $server->server_name }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-content-tertiary">Brand</dt>
                            <dd class="text-right font-medium text-content-primary">{{ $server->brand ?? '-' }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-content-tertiary">OS</dt>
                            <dd class="font-medium text-content-primary text-right">{{ $server->os ?? '-' }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-content-tertiary">Status</dt>
                            <dd class="font-medium text-content-primary text-right">{{ $server->status->value }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <div class="ina-card ina-card--stack p-6">
                <h2 class="text-base font-semibold text-content-primary">Spesifikasi</h2>
                <p class="mt-0.5 text-sm text-content-secondary">Komponen fisik server.</p>

                <div class="pt-5">
                    <dl class="space-y-2 text-sm">
                        <div class="flex justify-between gap-3">
                            <dt class="text-content-tertiary">CPU</dt>
                            <dd class="text-right font-medium text-content-primary">{{ $server->cpu ?? '-' }}</dd>
                        </div>
                        <div class="flex items-start justify-between gap-3">
                            <dt class="shrink-0 text-content-tertiary">Storage</dt>
                            <dd class="text-right font-medium text-content-primary">
                                @if ($server->storage !== null)
                                    {{ $server->storage }}
                                    @if ($server->storage_size !== null)
                                        <span class="text-xs text-content-tertiary">({{ number_format($server->storage_size / 1024) }} GB)</span>
                                    @endif
                                @else
                                    -
                                @endif
                            </dd>
                        </div>
                        <div class="flex items-start justify-between gap-3">
                            <dt class="shrink-0 text-content-tertiary">Memory</dt>
                            <dd class="text-right font-medium text-content-primary">
                                @if ($server->memory !== null)
                                    {{ $server->memory }}
                                    @if ($server->memory_size !== null)
                                        <span class="text-xs text-content-tertiary">({{ number_format($server->memory_size / 1024) }} GB)</span>
                                    @endif
                                @else
                                    -
                                @endif
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>

            <div class="ina-card ina-card--stack p-6">
                <h2 class="text-base font-semibold text-content-primary">Lokasi Fisik</h2>
                <p class="mt-0.5 text-sm text-content-secondary">Penempatan server.</p>

                <div class="pt-5">
                    <dl class="space-y-2 text-sm">
                        <div class="flex justify-between gap-3">
                            <dt class="text-content-tertiary">Server Room</dt>
                            <dd class="font-medium text-right">
                                @if ($server->effective() !== null)
                                    <a href="{{ route('admin.server-rooms.show', $server->effective()->location_id) }}" wire:navigate class="text-guide-600 hover:underline">{{ $server->effective()->name }}</a>
                                @else
                                    <span class="text-content-primary">-</span>
                                @endif
                            </dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-content-tertiary">Rack</dt>
                            <dd class="font-medium text-right">
                                @if ($server->rack !== null)
                                    <a href="{{ route('admin.assets.racks.show', $server->rack->rack_id) }}" wire:navigate class="text-guide-600 hover:underline">{{ $server->rack->name }}</a>
                                @else
                                    <span class="text-content-tertiary">Tanpa rack</span>
                                @endif
                            </dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-content-tertiary">Posisi</dt>
                            <dd class="font-medium text-content-primary text-right">{{ $server->position !== null ? 'U'.$server->position : '-' }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-content-tertiary">Static IP</dt>
                            <dd class="font-medium text-content-primary text-right">{{ $server->static_ip ?? '-' }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-content-tertiary">Public IP</dt>
                            <dd class="font-medium text-content-primary text-right">{{ $server->public_ip ?? '-' }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-content-tertiary">Username</dt>
                            <dd class="text-right font-medium text-content-primary">
                                @if (filled($server->username))
                                    <span class="inline-flex items-center gap-1.5" x-data="{ copied: false }">
                                        <span>{{ $server->username }}</span>
                                        <button
                                            type="button"
                                            class="text-content-tertiary transition-colors duration-150 hover:text-content-primary"
                                            @click="copyToClipboard($refs.value.innerText.trim()); copied = true; setTimeout(() => copied = false, 1500)"
                                            aria-label="Salin username"
                                            title="Salin username"
                                        >
                                            <i class="ti text-base" :class="copied ? 'ti-check text-guide-600' : 'ti-copy'"></i>
                                        </button>
                                        <span x-ref="value" hidden>{{ $server->username }}</span>
                                    </span>
                                @else
                                    -
                                @endif
                            </dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-content-tertiary">Password</dt>
                            <dd class="text-right font-medium text-content-primary">
                                @if (filled($server->password))
                                    <span class="inline-flex items-center gap-1.5" x-data="{ show: false, copied: false }">
                                        <span x-show="!show">••••••••</span>
                                        <span x-show="show" x-cloak>{{ $server->password }}</span>
                                        <button
                                            type="button"
                                            class="text-content-tertiary transition-colors duration-150 hover:text-content-primary"
                                            @click="show = !show"
                                            :aria-label="show ? 'Sembunyikan password' : 'Tampilkan password'"
                                        >
                                            <i class="ti text-base" :class="show ? 'ti-eye-off' : 'ti-eye'"></i>
                                        </button>
                                        <button
                                            type="button"
                                            class="text-content-tertiary transition-colors duration-150 hover:text-content-primary"
                                            @click="copyToClipboard($refs.value.innerText.trim()); copied = true; setTimeout(() => copied = false, 1500)"
                                            aria-label="Salin password"
                                            title="Salin password"
                                        >
                                            <i class="ti text-base" :class="copied ? 'ti-check text-guide-600' : 'ti-copy'"></i>
                                        </button>
                                        <span x-ref="value" hidden>{{ $server->password }}</span>
                                    </span>
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
        <div class="mt-4 grid grid-cols-1 gap-4 xl:grid-cols-2">
            {{-- Proxmox --}}
            <div class="ina-card ina-card--stack p-6">
                <h2 class="text-base font-semibold text-content-primary">Proxmox</h2>
                <p class="mt-0.5 text-sm text-content-secondary">Host virtualisasi pada server ini.</p>

                @if ($server->proxmox !== null)
                    <div class="flex items-center gap-3 rounded-lg border border-stroke-primary p-4">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-guide-50 text-guide-600">
                            <i class="ti ti-stack-2 text-base"></i>
                        </span>
                        <div class="min-w-0 flex-1">
                            <a href="{{ route('admin.assets.proxmox.show', $server->proxmox->proxmox_id) }}" wire:navigate class="block truncate text-sm font-medium text-content-primary hover:text-guide-600">
                                {{ $server->proxmox->name }}
                            </a>
                            <p class="truncate text-xs text-content-tertiary">{{ $server->proxmox->proxmox_id }} · {{ $server->proxmox->version ?? 'Versi tidak dicatat' }} · {{ $server->proxmox->vms->count() }} VM</p>
                        </div>
                    </div>
                @else
                    <p class="py-6 text-center text-sm text-content-tertiary">Server ini tidak menjalankan Proxmox.</p>
                @endif
            </div>

            {{-- Panel langsung --}}
            <div class="ina-card ina-card--stack p-6">
                <h2 class="text-base font-semibold text-content-primary">Panel</h2>
                <p class="mt-0.5 text-sm text-content-secondary">Panel yang terpasang langsung di server ini.</p>

                @if ($server->panels->isNotEmpty())
                    <ul class="divide-y divide-stroke-primary">
                        @foreach ($server->panels as $panel)
                            <li class="flex items-center gap-3 py-3" wire:key="pnl-{{ $panel->id }}">
                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-background-tertiary text-content-secondary">
                                    <i class="ti ti-app-window text-base"></i>
                                </span>
                                <div class="min-w-0 flex-1">
                                    <a href="{{ route('admin.assets.panels.show', $panel->panel_id) }}" wire:navigate class="block truncate text-sm font-medium text-content-primary hover:text-guide-600">
                                        {{ $panel->name }}
                                    </a>
                                    <p class="truncate text-xs text-content-tertiary">{{ $panel->type->value }}{{ $panel->version !== null ? ' · '.$panel->version : '' }}</p>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="py-6 text-center text-sm text-content-tertiary">Tidak ada panel langsung di server ini.</p>
                @endif
            </div>

            {{-- WAF langsung --}}
            <div class="ina-card ina-card--stack p-6">
                <h2 class="text-base font-semibold text-content-primary">WAF</h2>
                <p class="mt-0.5 text-sm text-content-secondary">Web Application Firewall yang terpasang langsung di server ini.</p>

                @if ($server->wafs->isNotEmpty())
                    <ul class="divide-y divide-stroke-primary">
                        @foreach ($server->wafs as $waf)
                            <li class="flex items-center gap-3 py-3" wire:key="waf-{{ $waf->id }}">
                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-background-tertiary text-content-secondary">
                                    <i class="ti ti-shield-lock text-base"></i>
                                </span>
                                <div class="min-w-0 flex-1">
                                    <a href="{{ route('admin.assets.wafs.show', $waf->waf_id) }}" wire:navigate class="block truncate text-sm font-medium text-content-primary hover:text-guide-600">
                                        {{ $waf->name }}
                                    </a>
                                    <p class="truncate text-xs text-content-tertiary">{{ $waf->tech->value }}{{ $waf->version !== null ? ' · '.$waf->version : '' }}</p>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="py-6 text-center text-sm text-content-tertiary">Tidak ada WAF langsung di server ini.</p>
                @endif
            </div>

            {{-- Database Server di server ini --}}
            <div class="ina-card ina-card--stack p-6">
                <h2 class="text-base font-semibold text-content-primary">Database Server</h2>
                <p class="mt-0.5 text-sm text-content-secondary">Instance database yang ter-install langsung di server ini.</p>

                @if ($server->databases->isNotEmpty())
                    <ul class="divide-y divide-stroke-primary">
                        @foreach ($server->databases as $instance)
                            <li class="flex items-center gap-3 py-3" wire:key="dbi-{{ $instance->id }}">
                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-background-tertiary text-content-secondary">
                                    <i class="ti ti-database text-base"></i>
                                </span>
                                <div class="min-w-0 flex-1">
                                    <a href="{{ route('admin.assets.databases.show', $instance->dbi_id) }}" wire:navigate class="block truncate text-sm font-medium text-content-primary hover:text-guide-600">
                                        {{ $instance->name }}
                                    </a>
                                    <p class="truncate text-xs text-content-tertiary">{{ trim($instance->tech->value.' '.($instance->version ?? '')) }} · {{ $instance->schemas->count() }} db</p>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="py-6 text-center text-sm text-content-tertiary">Tidak ada instance database di server ini.</p>
                @endif
            </div>

            {{-- Aplikasi --}}
            <div class="ina-card ina-card--stack p-6 xl:col-span-2">
                <h2 class="text-base font-semibold text-content-primary">Aplikasi</h2>
                <p class="mt-0.5 text-sm text-content-secondary">Aplikasi yang dihosting di server ini atau pada VM dan panel di bawahnya.</p>

                @php $hostings = $this->hostings(); @endphp
                @if ($hostings->isNotEmpty())
                    <ul class="divide-y divide-stroke-primary">
                        @foreach ($hostings as $hosting)
                            <li class="flex items-center gap-3 py-3" wire:key="hst-{{ $hosting->id }}">
                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-background-tertiary text-content-secondary">
                                    <i class="ti ti-apps text-base"></i>
                                </span>
                                <div class="min-w-0 flex-1">
                                    <a href="{{ route('admin.applications.show', $hosting->application->application_id) }}" wire:navigate class="block truncate text-sm font-medium text-content-primary hover:text-guide-600">
                                        {{ $hosting->application->name }}
                                    </a>
                                    <p class="truncate text-xs text-content-tertiary">
                                        {{ $hosting->application->application_id }} · {{ $hosting->application->type->value }}
                                        @if ($hosting->hostable_type === Panel::class)
                                            · di Panel {{ $hosting->hostable->name }}
                                        @elseif ($hosting->hostable_type === Vms::class)
                                            · di VM {{ $hosting->hostable->name }}
                                        @endif
                                    </p>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="py-6 text-center text-sm text-content-tertiary">Tidak ada aplikasi yang tercatat di server ini.</p>
                @endif
            </div>
        </div>
    @endif

    {{-- Tab: Dokumentasi --}}
    @if ($tab === 'documents')
        @php $documents = Document::query()->with('addedBy')->where('documentable_type', Server::class)->where('documentable_id', $this->server->id)->orderBy('document_id')->get(); @endphp
        <div class="mt-4">
            <x-asset.documents-panel :documents="$documents" name="{{ $server->server_name }}" :add-href="route('admin.documents.index', ['type' => Server::class, 'id' => $server->id])" />
        </div>
    @endif

    {{-- Tab: Aktivitas --}}
    @if ($tab === 'activities')
        @php $activities = Activity::query()->with('user')->where('subject_type', Server::class)->where('subject_id', $this->server->id)->orderByDesc('id')->get(); @endphp
        <div class="mt-4">
            <x-asset.activities-panel :activities="$activities" name="{{ $server->server_name }}" />
        </div>
    @endif

    {{-- Tab: Gallery --}}
    @if ($tab === 'gallery')
        @php $medias = $this->server->medias()->orderByDesc('id')->get(); @endphp
        <div class="mt-4">
            <x-asset.gallery-panel :medias="$medias" name="{{ $server->server_name }}" :add-href="route('admin.galleries.index', ['type' => Server::class, 'id' => $server->id])" />
        </div>
    @endif

    @include('pages.admin.assets.partials.server-form-modal')
</div>
