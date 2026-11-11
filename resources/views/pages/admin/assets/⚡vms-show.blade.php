<?php

use App\Enums\VmsStatus;
use App\Models\Activity;
use App\Models\Document;
use App\Models\Hosting;
use App\Models\Panel;
use App\Models\Proxmox;
use App\Models\Vms;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new class extends Component
{
    use \App\Livewire\Concerns\InteractsWithVmsForm;

    #[Layout('layouts::admin')]
    #[Title('Detail Virtual Machine')]

    public Vms $vm;

    public string $tab = 'overview';

    /** Konteks list asal (page/filter) untuk tombol kembali dan link tab. */
    public array $returnQuery = [];

    public function mount(string $vmId): void
    {
        $this->vm = Vms::query()
            ->with(['proxmox.server', 'panels', 'wafs', 'databases.schemas'])
            ->where('vm_id', $vmId)
            ->firstOrFail();

        $this->tab = request()->query('tab', 'overview');
        $this->returnQuery = collect(request()->query())->except('tab')->all();
    }

    /**
     * Aplikasi yang dihosting di VM ini atau di panel yang terpasang di VM itu.
     */
    public function hostings()
    {
        $panelIds = Panel::query()
            ->where('vm_id', $this->vm->vm_id)
            ->pluck('id')
            ->all();

        return Hosting::query()
            ->with(['application', 'hostable'])
            ->where(function ($query) use ($panelIds) {
                $query->where(fn ($query) => $query
                        ->where('hostable_type', Vms::class)
                        ->where('hostable_id', $this->vm->id))
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
            ['key' => 'documents', 'label' => 'Dokumentasi', 'icon' => 'ti-folder', 'count' => Document::where('documentable_type', Vms::class)->where('documentable_id', $this->vm->id)->count()],
            ['key' => 'activities', 'label' => 'Aktivitas', 'icon' => 'ti-activity', 'count' => Activity::where('subject_type', Vms::class)->where('subject_id', $this->vm->id)->count()],
            ['key' => 'gallery', 'label' => 'Gallery', 'icon' => 'ti-photo', 'count' => $this->vm->medias()->count()],
        ];
    }
};
?>

<div>
    <x-asset.detail-header
        :back-route="route('admin.assets.virtualization.index', ['tab' => 'vms'])"
        :title="$vm->name"
        subtitle="Detail virtual machine beserta relasi terkait."
        :back-query="$this->returnQuery"
    >
        @slot('right')
            @can('infrastructure.manage')
            <button
                type="button"
                wire:click="editVms({{ $vm->id }})"
                class="ina-button ina-button--secondary ina-button--md"
            >
                <i class="ti ti-pencil text-base" aria-hidden="true"></i>
                Edit VM
            </button>
            @endcan
        @endslot
    </x-asset.detail-header>

    <x-asset.tab-nav
        :url="route('admin.assets.vms.show', ['vmId' => $vm->vm_id])"
        :current="$tab"
        :tabs="$this->tabs()"
        :carry="$this->returnQuery"
    />

    {{-- Tab: Overview --}}
    @if ($tab === 'overview')
        <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
            <div class="ina-card ina-card--stack p-6">
                <h2 class="text-base font-semibold text-content-primary">Informasi Umum</h2>
                <p class="mt-0.5 text-sm text-content-secondary">Informasi dasar VM.</p>

                <div class="pt-5">
                    <dl class="space-y-2 text-sm">
                        <div class="flex justify-between gap-3">
                            <dt class="text-content-tertiary">Nama</dt>
                            <dd class="font-medium text-content-primary text-right">{{ $vm->name }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-content-tertiary">VMID</dt>
                            <dd class="font-medium text-content-primary text-right">{{ $vm->proxmox_number ?? '-' }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-content-tertiary">Proxmox Induk</dt>
                            <dd class="font-medium text-right">
                                @if ($vm->proxmox !== null)
                                    <a href="{{ route('admin.assets.proxmox.show', $vm->proxmox->proxmox_id) }}" wire:navigate class="text-guide-600 hover:underline">{{ $vm->proxmox->name }}</a>
                                @else
                                    <span class="text-content-primary">-</span>
                                @endif
                            </dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-content-tertiary">Status</dt>
                            <dd class="font-medium text-content-primary text-right">{{ $vm->status->value }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <div class="ina-card ina-card--stack p-6">
                <h2 class="text-base font-semibold text-content-primary">Spesifikasi</h2>
                <p class="mt-0.5 text-sm text-content-secondary">Alokasi resource VM.</p>

                <div class="pt-5">
                    <dl class="space-y-2 text-sm">
                        <div class="flex justify-between gap-3">
                            <dt class="text-content-tertiary">OS</dt>
                            <dd class="font-medium text-content-primary text-right">{{ $vm->os ?? '-' }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-content-tertiary">CPU</dt>
                            <dd class="font-medium text-content-primary text-right">{{ $vm->cpu !== null ? $vm->cpu.' core' : '-' }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-content-tertiary">RAM</dt>
                            <dd class="font-medium text-content-primary text-right">{{ $vm->ram !== null ? number_format($vm->ram / 1024).' GB' : '-' }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-content-tertiary">Storage</dt>
                            <dd class="font-medium text-content-primary text-right">{{ $vm->storage !== null ? number_format($vm->storage / 1024).' GB' : '-' }}</dd>
                        </div>
                        <div class="flex items-start justify-between gap-3">
                            <dt class="shrink-0 text-content-tertiary">Local IP</dt>
                            <dd class="text-right font-medium text-content-primary">{{ $vm->local_ip ?? '-' }}</dd>
                        </div>
                        <div class="flex items-start justify-between gap-3">
                            <dt class="shrink-0 text-content-tertiary">Public IP</dt>
                            <dd class="text-right font-medium text-content-primary">{{ $vm->public_ip ?? '-' }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-content-tertiary">Username</dt>
                            <dd class="text-right font-medium text-content-primary">
                                @if (filled($vm->username))
                                    <span class="inline-flex items-center gap-1.5" x-data="{ copied: false }">
                                        <span>{{ $vm->username }}</span>
                                        <button
                                            type="button"
                                            class="text-content-tertiary transition-colors duration-150 hover:text-content-primary"
                                            @click="copyToClipboard($refs.value.innerText.trim()); copied = true; setTimeout(() => copied = false, 1500)"
                                            aria-label="Salin username"
                                            title="Salin username"
                                        >
                                            <i class="ti text-base" :class="copied ? 'ti-check text-guide-600' : 'ti-copy'"></i>
                                        </button>
                                        <span x-ref="value" hidden>{{ $vm->username }}</span>
                                    </span>
                                @else
                                    -
                                @endif
                            </dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-content-tertiary">Password</dt>
                            <dd class="text-right font-medium text-content-primary">
                                @if (filled($vm->password))
                                    <span class="inline-flex items-center gap-1.5" x-data="{ show: false, copied: false }">
                                        <span x-show="!show">••••••••</span>
                                        <span x-show="show" x-cloak>{{ $vm->password }}</span>
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
                                        <span x-ref="value" hidden>{{ $vm->password }}</span>
                                    </span>
                                @else
                                    -
                                @endif
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>

            @if ($vm->note !== null)
                <div class="ina-card ina-card--stack p-6">
                    <h2 class="text-base font-semibold text-content-primary">Catatan</h2>
                    <p class="mt-0.5 text-sm text-content-secondary">Keterangan tambahan VM.</p>
                    <p class="whitespace-pre-line pt-5 text-sm text-content-primary">{{ $vm->note }}</p>
                </div>
            @endif
        </div>
    @endif

    {{-- Tab: Relasi --}}
    @if ($tab === 'relations')
        <div class="mt-4 grid grid-cols-1 gap-4 xl:grid-cols-2">
            {{-- Proxmox induk --}}
            <div class="ina-card ina-card--stack p-6">
                <h2 class="text-base font-semibold text-content-primary">Proxmox Induk</h2>
                <p class="mt-0.5 text-sm text-content-secondary">Host virtualisasi tempat VM ini berjalan.</p>

                @if ($vm->proxmox !== null)
                    <div class="flex items-center gap-3 rounded-lg border border-stroke-primary p-4">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-guide-50 text-guide-600">
                            <i class="ti ti-stack-2 text-base"></i>
                        </span>
                        <div class="min-w-0 flex-1">
                            <a href="{{ route('admin.assets.proxmox.show', $vm->proxmox->proxmox_id) }}" wire:navigate class="block truncate text-sm font-medium text-content-primary hover:text-guide-600">
                                {{ $vm->proxmox->name }}
                            </a>
                            <p class="truncate text-xs text-content-tertiary">
                                {{ $vm->proxmox->proxmox_id }}
                                @if ($vm->proxmox->server !== null)
                                    · di server {{ $vm->proxmox->server->server_name }}
                                @endif
                            </p>
                        </div>
                    </div>
                @else
                    <p class="py-6 text-center text-sm text-content-tertiary">VM ini tidak memiliki host Proxmox.</p>
                @endif
            </div>

            {{-- Panel --}}
            <div class="ina-card ina-card--stack p-6">
                <h2 class="text-base font-semibold text-content-primary">Panel</h2>
                <p class="mt-0.5 text-sm text-content-secondary">Panel yang terpasang di VM ini.</p>

                @if ($vm->panels->isNotEmpty())
                    <ul class="divide-y divide-stroke-primary">
                        @foreach ($vm->panels as $panel)
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
                    <p class="py-6 text-center text-sm text-content-tertiary">Tidak ada panel di VM ini.</p>
                @endif
            </div>

            {{-- WAF --}}
            <div class="ina-card ina-card--stack p-6">
                <h2 class="text-base font-semibold text-content-primary">WAF</h2>
                <p class="mt-0.5 text-sm text-content-secondary">Web Application Firewall yang terpasang di VM ini.</p>

                @if ($vm->wafs->isNotEmpty())
                    <ul class="divide-y divide-stroke-primary">
                        @foreach ($vm->wafs as $waf)
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
                    <p class="py-6 text-center text-sm text-content-tertiary">Tidak ada WAF di VM ini.</p>
                @endif
            </div>

            {{-- Database Server di VM ini --}}
            <div class="ina-card ina-card--stack p-6">
                <h2 class="text-base font-semibold text-content-primary">Database Server</h2>
                <p class="mt-0.5 text-sm text-content-secondary">Instance database yang ter-install di VM ini.</p>

                @if ($vm->databases->isNotEmpty())
                    <ul class="divide-y divide-stroke-primary">
                        @foreach ($vm->databases as $instance)
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
                    <p class="py-6 text-center text-sm text-content-tertiary">Tidak ada instance database di VM ini.</p>
                @endif
            </div>

            {{-- Aplikasi --}}
            <div class="ina-card ina-card--stack p-6 xl:col-span-2">
                <h2 class="text-base font-semibold text-content-primary">Aplikasi</h2>
                <p class="mt-0.5 text-sm text-content-secondary">Aplikasi yang dihosting di VM ini.</p>

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
                                        @endif
                                    </p>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="py-6 text-center text-sm text-content-tertiary">Tidak ada aplikasi yang dihosting di VM ini.</p>
                @endif
            </div>
        </div>
    @endif

    {{-- Tab: Dokumentasi --}}
    @if ($tab === 'documents')
        @php $documents = Document::query()->with('addedBy')->where('documentable_type', Vms::class)->where('documentable_id', $this->vm->id)->orderBy('document_id')->get(); @endphp
        <div class="mt-4">
            <x-asset.documents-panel :documents="$documents" name="{{ $vm->name }}" :add-href="route('admin.documents.index', ['type' => Vms::class, 'id' => $vm->id])" />
        </div>
    @endif

    {{-- Tab: Aktivitas --}}
    @if ($tab === 'activities')
        @php $activities = Activity::query()->with('user')->where('subject_type', Vms::class)->where('subject_id', $this->vm->id)->orderByDesc('id')->get(); @endphp
        <div class="mt-4">
            <x-asset.activities-panel :activities="$activities" name="{{ $vm->name }}" />
        </div>
    @endif

    {{-- Tab: Gallery --}}
    @if ($tab === 'gallery')
        @php $medias = $this->vm->medias()->orderByDesc('id')->get(); @endphp
        <div class="mt-4">
            <x-asset.gallery-panel :medias="$medias" name="{{ $vm->name }}" :add-href="route('admin.galleries.index', ['type' => Vms::class, 'id' => $vm->id])" />
        </div>
    @endif

    @include('pages.admin.assets.partials.vms-form-modal')
</div>
