<?php

use App\Models\Activity;
use App\Models\Document;
use App\Models\Hosting;
use App\Models\Waf;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new class extends Component
{
    use \App\Livewire\Concerns\InteractsWithWafForm;

    #[Layout('layouts::admin')]
    #[Title('Detail WAF')]

    public Waf $waf;

    public string $tab = 'overview';

    /** Konteks list asal (page/filter) untuk tombol kembali dan link tab. */
    public array $returnQuery = [];

    public function mount(string $wafId): void
    {
        $this->waf = Waf::query()
            ->with(['vm.proxmox.server', 'server'])
            ->where('waf_id', $wafId)
            ->firstOrFail();

        $this->tab = request()->query('tab', 'overview');
        $this->returnQuery = collect(request()->query())->except('tab')->all();
    }

    /**
     * Host efektif WAF: VM bila terpasang di VM, atau Server langsung.
     */
    public function host(): ?object
    {
        return $this->waf->vm ?? $this->waf->server;
    }

    /**
     * Aplikasi yang berjalan di host tempat WAF ini terpasang.
     */
    public function hostApplications()
    {
        $host = $this->host();

        if ($host === null) {
            return collect();
        }

        return Hosting::query()
            ->with('application')
            ->where('hostable_type', get_class($host))
            ->where('hostable_id', $host->id)
            ->get();
    }

    public function tabs(): array
    {
        return [
            ['key' => 'overview', 'label' => 'Overview', 'icon' => 'ti-info-circle'],
            ['key' => 'relations', 'label' => 'Relasi', 'icon' => 'ti-topology-star'],
            ['key' => 'documents', 'label' => 'Dokumentasi', 'icon' => 'ti-folder', 'count' => Document::where('documentable_type', Waf::class)->where('documentable_id', $this->waf->id)->count()],
            ['key' => 'activities', 'label' => 'Aktivitas', 'icon' => 'ti-activity', 'count' => Activity::where('subject_type', Waf::class)->where('subject_id', $this->waf->id)->count()],
            ['key' => 'gallery', 'label' => 'Gallery', 'icon' => 'ti-photo', 'count' => $this->waf->medias()->count()],
        ];
    }
};
?>

<div>
    <x-asset.detail-header
        :back-route="route('admin.assets.wafs.index')"
        :title="$waf->name"
        subtitle="Detail WAF beserta host dan relasi terkait."
        :back-query="$this->returnQuery"
    >
        @slot('right')
            @can('infrastructure.manage')
            <button
                type="button"
                wire:click="edit({{ $waf->id }})"
                class="ina-button ina-button--secondary ina-button--md"
            >
                <i class="ti ti-pencil text-base" aria-hidden="true"></i>
                Edit WAF
            </button>
            @endcan
        @endslot
    </x-asset.detail-header>

    <x-asset.tab-nav
        :url="route('admin.assets.wafs.show', ['wafId' => $waf->waf_id])"
        :current="$tab"
        :tabs="$this->tabs()"
        :carry="$this->returnQuery"
    />

    {{-- Tab: Overview --}}
    @if ($tab === 'overview')
        <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
            <div class="ina-card ina-card--stack p-6">
                <h2 class="text-base font-semibold text-content-primary">Informasi Umum</h2>
                <p class="mt-0.5 text-sm text-content-secondary">Informasi dasar WAF.</p>

                <div class="pt-5">
                    <dl class="space-y-2 text-sm">
                        <div class="flex justify-between gap-3">
                            <dt class="text-content-tertiary">Nama</dt>
                            <dd class="font-medium text-content-primary text-right">{{ $waf->name }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-content-tertiary">Teknologi</dt>
                            <dd class="font-medium text-content-primary text-right">{{ $waf->tech->value }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-content-tertiary">Versi</dt>
                            <dd class="font-medium text-content-primary text-right">{{ $waf->version ?? '-' }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <div class="ina-card ina-card--stack p-6">
                <h2 class="text-base font-semibold text-content-primary">Akses</h2>
                <p class="mt-0.5 text-sm text-content-secondary">URL manajemen WAF.</p>

                <div class="pt-5">
                    <dl class="space-y-2 text-sm">
                        <div class="flex items-start justify-between gap-3">
                            <dt class="shrink-0 text-content-tertiary">URL Eksternal</dt>
                            <dd class="text-right font-medium">
                                @if ($waf->internet_address !== null)
                                    <a href="{{ $waf->internet_address }}" target="_blank" rel="noopener noreferrer" class="text-guide-600 underline-offset-2 hover:underline">{{ $waf->internet_address }}</a>
                                @else
                                    <span class="text-content-primary">-</span>
                                @endif
                            </dd>
                        </div>
                        <div class="flex items-start justify-between gap-3">
                            <dt class="shrink-0 text-content-tertiary">URL Internal</dt>
                            <dd class="text-right font-medium">
                                @if ($waf->internal_address !== null)
                                    <a href="{{ $waf->internal_address }}" target="_blank" rel="noopener noreferrer" class="text-guide-600 underline-offset-2 hover:underline">{{ $waf->internal_address }}</a>
                                @else
                                    <span class="text-content-primary">-</span>
                                @endif
                            </dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-content-tertiary">Username</dt>
                            <dd class="text-right font-medium text-content-primary">
                                @if (filled($waf->username))
                                    <span class="inline-flex items-center gap-1.5" x-data="{ copied: false }">
                                        <span>{{ $waf->username }}</span>
                                        <button
                                            type="button"
                                            class="text-content-tertiary transition-colors duration-150 hover:text-content-primary"
                                            @click="copyToClipboard($refs.value.innerText.trim()); copied = true; setTimeout(() => copied = false, 1500)"
                                            aria-label="Salin username"
                                            title="Salin username"
                                        >
                                            <i class="ti text-base" :class="copied ? 'ti-check text-guide-600' : 'ti-copy'"></i>
                                        </button>
                                        <span x-ref="value" hidden>{{ $waf->username }}</span>
                                    </span>
                                @else
                                    -
                                @endif
                            </dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-content-tertiary">Password</dt>
                            <dd class="text-right font-medium text-content-primary">
                                @if (filled($waf->password))
                                    <span class="inline-flex items-center gap-1.5" x-data="{ show: false, copied: false }">
                                        <span x-show="!show">••••••••</span>
                                        <span x-show="show" x-cloak>{{ $waf->password }}</span>
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
                                        <span x-ref="value" hidden>{{ $waf->password }}</span>
                                    </span>
                                @else
                                    -
                                @endif
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>

            <div class="ina-card ina-card--stack p-6">
                <h2 class="text-base font-semibold text-content-primary">Host</h2>
                <p class="mt-0.5 text-sm text-content-secondary">Tempat WAF ini terpasang.</p>

                <div class="pt-5">
                    @php $host = $this->host(); @endphp
                    @if ($host !== null)
                        <div class="flex items-center gap-3 rounded-lg border border-stroke-primary p-4">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-guide-50 text-guide-600">
                                <i class="ti {{ $waf->vm !== null ? 'ti-box-multiple-3' : 'ti-server' }} text-base"></i>
                            </span>
                            <div class="min-w-0 flex-1">
                                @if ($waf->vm !== null)
                                    <a href="{{ route('admin.assets.vms.show', $waf->vm->vm_id) }}" wire:navigate class="block truncate text-sm font-medium text-content-primary hover:text-guide-600">
                                        {{ $waf->vm->name }}
                                    </a>
                                    <p class="truncate text-xs text-content-tertiary">VM · {{ $waf->vm->vm_id }}</p>
                                @else
                                    <a href="{{ route('admin.assets.servers.show', $waf->server->server_id) }}" wire:navigate class="block truncate text-sm font-medium text-content-primary hover:text-guide-600">
                                        {{ $waf->server->server_name }}
                                    </a>
                                    <p class="truncate text-xs text-content-tertiary">Server · {{ $waf->server->server_id }}</p>
                                @endif
                            </div>
                        </div>
                    @else
                        <p class="py-4 text-center text-sm text-content-tertiary">WAF ini belum ditautkan ke host mana pun.</p>
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- Tab: Relasi --}}
    @if ($tab === 'relations')
        <div class="mt-4 grid grid-cols-1 gap-4 xl:grid-cols-2">
            <div class="ina-card ina-card--stack p-6">
                <h2 class="text-base font-semibold text-content-primary">Host</h2>
                <p class="mt-0.5 text-sm text-content-secondary">Rantai infrastruktur tempat WAF ini terpasang.</p>

                @php $host = $this->host(); @endphp
                @if ($host !== null)
                    <div class="space-y-2">
                        {{-- Rantai: Server → (Proxmox → VM) → WAF --}}
                        @if ($waf->vm !== null)
                            @if ($waf->vm->proxmox?->server !== null)
                                <div class="flex items-center gap-2 rounded-lg border border-stroke-primary p-3">
                                    <i class="ti ti-server text-base text-content-tertiary"></i>
                                    <a href="{{ route('admin.assets.servers.show', $waf->vm->proxmox->server->server_id) }}" wire:navigate class="truncate text-sm font-medium text-content-primary hover:text-guide-600">
                                        {{ $waf->vm->proxmox->server->server_name }}
                                    </a>
                                    <span class="ml-auto text-[11px] text-content-tertiary">Server</span>
                                </div>
                                <div class="ml-4 border-l border-dashed border-stroke-primary pl-4">
                                    <div class="flex items-center gap-2 rounded-lg border border-stroke-primary p-3">
                                        <i class="ti ti-stack-2 text-base text-content-tertiary"></i>
                                        <a href="{{ route('admin.assets.proxmox.show', $waf->vm->proxmox->proxmox_id) }}" wire:navigate class="truncate text-sm font-medium text-content-primary hover:text-guide-600">
                                            {{ $waf->vm->proxmox->name }}
                                        </a>
                                        <span class="ml-auto text-[11px] text-content-tertiary">Proxmox</span>
                                    </div>
                                    <div class="ml-4 mt-2 border-l border-dashed border-stroke-primary pl-4">
                                        <div class="flex items-center gap-2 rounded-lg border border-stroke-primary p-3">
                                            <i class="ti ti-box-multiple-3 text-base text-content-tertiary"></i>
                                            <a href="{{ route('admin.assets.vms.show', $waf->vm->vm_id) }}" wire:navigate class="truncate text-sm font-medium text-content-primary hover:text-guide-600">
                                                {{ $waf->vm->name }}
                                            </a>
                                            <span class="ml-auto text-[11px] text-content-tertiary">VM</span>
                                        </div>
                                        <div class="ml-4 mt-2 border-l border-dashed border-stroke-primary pl-4">
                                            <div class="flex items-center gap-2 rounded-lg border border-guide-200 bg-guide-50 p-3">
                                                <i class="ti ti-shield-lock text-base text-guide-600"></i>
                                                <span class="truncate text-sm font-semibold text-guide-700">{{ $waf->name }}</span>
                                                <span class="ml-auto text-[11px] text-guide-600">WAF ini</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <div class="flex items-center gap-2 rounded-lg border border-stroke-primary p-3">
                                    <i class="ti ti-box-multiple-3 text-base text-content-tertiary"></i>
                                    <a href="{{ route('admin.assets.vms.show', $waf->vm->vm_id) }}" wire:navigate class="truncate text-sm font-medium text-content-primary hover:text-guide-600">
                                        {{ $waf->vm->name }}
                                    </a>
                                    <span class="ml-auto text-[11px] text-content-tertiary">VM</span>
                                </div>
                                <div class="ml-4 mt-2 border-l border-dashed border-stroke-primary pl-4">
                                    <div class="flex items-center gap-2 rounded-lg border border-guide-200 bg-guide-50 p-3">
                                        <i class="ti ti-shield-lock text-base text-guide-600"></i>
                                        <span class="truncate text-sm font-semibold text-guide-700">{{ $waf->name }}</span>
                                        <span class="ml-auto text-[11px] text-guide-600">WAF ini</span>
                                    </div>
                                </div>
                            @endif
                        @else
                            <div class="flex items-center gap-2 rounded-lg border border-stroke-primary p-3">
                                <i class="ti ti-server text-base text-content-tertiary"></i>
                                <a href="{{ route('admin.assets.servers.show', $waf->server->server_id) }}" wire:navigate class="truncate text-sm font-medium text-content-primary hover:text-guide-600">
                                    {{ $waf->server->server_name }}
                                </a>
                                <span class="ml-auto text-[11px] text-content-tertiary">Server</span>
                            </div>
                            <div class="ml-4 mt-2 border-l border-dashed border-stroke-primary pl-4">
                                <div class="flex items-center gap-2 rounded-lg border border-guide-200 bg-guide-50 p-3">
                                    <i class="ti ti-shield-lock text-base text-guide-600"></i>
                                    <span class="truncate text-sm font-semibold text-guide-700">{{ $waf->name }}</span>
                                    <span class="ml-auto text-[11px] text-guide-600">WAF ini</span>
                                </div>
                            </div>
                        @endif
                    </div>
                @else
                    <p class="py-6 text-center text-sm text-content-tertiary">WAF ini belum ditautkan ke host mana pun.</p>
                @endif
            </div>

            <div class="ina-card ina-card--stack p-6">
                <h2 class="text-base font-semibold text-content-primary">Aplikasi pada Host</h2>
                <p class="mt-0.5 text-sm text-content-secondary">Aplikasi yang berjalan di host tempat WAF ini terpasang.</p>

                @php $hostApplications = $this->hostApplications(); @endphp
                @if ($hostApplications->isNotEmpty())
                    <ul class="divide-y divide-stroke-primary">
                        @foreach ($hostApplications as $hosting)
                            <li class="flex items-center gap-3 py-3" wire:key="hst-{{ $hosting->id }}">
                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-background-tertiary text-content-secondary">
                                    <i class="ti ti-apps text-base"></i>
                                </span>
                                <div class="min-w-0 flex-1">
                                    <a href="{{ route('admin.applications.show', $hosting->application->application_id) }}" wire:navigate class="block truncate text-sm font-medium text-content-primary hover:text-guide-600">
                                        {{ $hosting->application->name }}
                                    </a>
                                    <p class="truncate text-xs text-content-tertiary">{{ $hosting->application->application_id }} · {{ $hosting->application->type->value }}</p>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="py-6 text-center text-sm text-content-tertiary">Tidak ada aplikasi yang tercatat di host ini.</p>
                @endif
            </div>
        </div>
    @endif

    {{-- Tab: Dokumentasi --}}
    @if ($tab === 'documents')
        @php $documents = Document::query()->with('addedBy')->where('documentable_type', Waf::class)->where('documentable_id', $this->waf->id)->orderBy('document_id')->get(); @endphp
        <div class="mt-4">
            <x-asset.documents-panel :documents="$documents" name="{{ $waf->name }}" :add-href="route('admin.documents.index', ['type' => Waf::class, 'id' => $waf->id])" />
        </div>
    @endif

    {{-- Tab: Aktivitas --}}
    @if ($tab === 'activities')
        @php $activities = Activity::query()->with('user')->where('subject_type', Waf::class)->where('subject_id', $this->waf->id)->orderByDesc('id')->get(); @endphp
        <div class="mt-4">
            <x-asset.activities-panel :activities="$activities" name="{{ $waf->name }}" />
        </div>
    @endif

    {{-- Tab: Gallery --}}
    @if ($tab === 'gallery')
        @php $medias = $this->waf->medias()->orderByDesc('id')->get(); @endphp
        <div class="mt-4">
            <x-asset.gallery-panel :medias="$medias" name="{{ $waf->name }}" :add-href="route('admin.galleries.index', ['type' => Waf::class, 'id' => $waf->id])" />
        </div>
    @endif

    @include('pages.admin.assets.partials.waf-form-modal')
</div>
