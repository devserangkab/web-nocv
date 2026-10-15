<?php

use App\Models\Activity;
use App\Models\Document;
use App\Models\Proxmox;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new class extends Component
{
    #[Layout('layouts::admin')]
    #[Title('Detail Proxmox')]

    public Proxmox $proxmox;

    public string $tab = 'overview';

    public function mount(string $proxmoxId): void
    {
        $this->proxmox = Proxmox::query()
            ->with(['server', 'vms'])
            ->where('proxmox_id', $proxmoxId)
            ->firstOrFail();

        $this->tab = request()->query('tab', 'overview');
    }

    public function tabs(): array
    {
        return [
            ['key' => 'overview', 'label' => 'Overview', 'icon' => 'ti-info-circle'],
            ['key' => 'relations', 'label' => 'Relasi', 'icon' => 'ti-topology-star'],
            ['key' => 'documents', 'label' => 'Dokumentasi', 'icon' => 'ti-folder', 'count' => Document::where('documentable_type', Proxmox::class)->where('documentable_id', $this->proxmox->id)->count()],
            ['key' => 'activities', 'label' => 'Aktivitas', 'icon' => 'ti-activity', 'count' => Activity::where('subject_type', Proxmox::class)->where('subject_id', $this->proxmox->id)->count()],
            ['key' => 'gallery', 'label' => 'Gallery', 'icon' => 'ti-photo', 'count' => $this->proxmox->medias()->count()],
        ];
    }
};
?>

<div>
    <x-asset.detail-header
        :back-route="route('admin.assets.virtualization.index', ['tab' => 'proxmox'])"
        :title="$proxmox->name"
        :code="$proxmox->proxmox_id"
        subtitle="Detail host Proxmox beserta server host dan VM di atasnya."
    />

    <x-asset.tab-nav
        :url="route('admin.assets.proxmox.show', ['proxmoxId' => $proxmox->proxmox_id])"
        :current="$tab"
        :tabs="$this->tabs()"
    />

    {{-- Tab: Overview --}}
    @if ($tab === 'overview')
        <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
            <div class="ina-card ina-card--stack p-6">
                <h2 class="text-base font-semibold text-content-primary">Informasi Umum</h2>
                <p class="mt-0.5 text-sm text-content-secondary">Informasi dasar host Proxmox.</p>

                <div class="pt-5">
                    <dl class="space-y-2 text-sm">
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-content-tertiary">Kode</dt>
                            <dd class="font-medium text-content-primary">{{ $proxmox->proxmox_id }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-content-tertiary">Nama</dt>
                            <dd class="font-medium text-content-primary">{{ $proxmox->name }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-content-tertiary">Versi</dt>
                            <dd class="font-medium text-content-primary">{{ $proxmox->version ?? '-' }}</dd>
                        </div>
                        <div class="flex items-start justify-between gap-3">
                            <dt class="shrink-0 text-content-tertiary">URL</dt>
                            <dd class="text-right font-medium">
                                @if ($proxmox->url !== null)
                                    <a href="{{ $proxmox->url }}" target="_blank" rel="noopener noreferrer" class="text-guide-600 underline-offset-2 hover:underline">{{ $proxmox->url }}</a>
                                @else
                                    <span class="text-content-primary">-</span>
                                @endif
                            </dd>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-content-tertiary">Username</dt>
                            <dd class="font-medium text-content-primary">{{ $proxmox->username ?? '-' }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <div class="ina-card ina-card--stack p-6">
                <h2 class="text-base font-semibold text-content-primary">Ringkasan</h2>
                <p class="mt-0.5 text-sm text-content-secondary">Isi host saat ini.</p>

                <div class="pt-5">
                    <dl class="space-y-2 text-sm">
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-content-tertiary">Server Host</dt>
                            <dd class="font-medium text-right">
                                @if ($proxmox->server !== null)
                                    <a href="{{ route('admin.assets.servers.show', $proxmox->server->server_id) }}" wire:navigate class="text-guide-600 hover:underline">{{ $proxmox->server->server_name }}</a>
                                @else
                                    <span class="text-content-primary">-</span>
                                @endif
                            </dd>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-content-tertiary">Jumlah VM</dt>
                            <dd class="font-medium text-content-primary">{{ $proxmox->vms->count() }}</dd>
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
                <h2 class="text-base font-semibold text-content-primary">Server Host</h2>
                <p class="mt-0.5 text-sm text-content-secondary">Server tempat Proxmox ini terinstall.</p>

                @if ($proxmox->server !== null)
                    <div class="flex items-center gap-3 rounded-lg border border-stroke-primary p-4">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-guide-50 text-guide-600">
                            <i class="ti ti-server text-base"></i>
                        </span>
                        <div class="min-w-0 flex-1">
                            <a href="{{ route('admin.assets.servers.show', $proxmox->server->server_id) }}" wire:navigate class="block truncate text-sm font-medium text-content-primary hover:text-guide-600">
                                {{ $proxmox->server->server_name }}
                            </a>
                            <p class="truncate text-xs text-content-tertiary">{{ $proxmox->server->server_id }} · {{ $proxmox->server->brand ?? 'Tanpa brand' }}</p>
                        </div>
                    </div>
                @else
                    <p class="py-6 text-center text-sm text-content-tertiary">Proxmox ini belum ditautkan ke server mana pun.</p>
                @endif
            </div>

            <div class="ina-card ina-card--stack p-6">
                <h2 class="text-base font-semibold text-content-primary">Virtual Machine</h2>
                <p class="mt-0.5 text-sm text-content-secondary">VM yang berjalan di host ini.</p>

                @if ($proxmox->vms->isNotEmpty())
                    <ul class="divide-y divide-stroke-primary">
                        @foreach ($proxmox->vms as $vm)
                            <li class="flex items-center gap-3 py-3" wire:key="vm-{{ $vm->id }}">
                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-background-tertiary text-content-secondary">
                                    <i class="ti ti-box-multiple-3 text-base"></i>
                                </span>
                                <div class="min-w-0 flex-1">
                                    <a href="{{ route('admin.assets.vms.show', $vm->vm_id) }}" wire:navigate class="block truncate text-sm font-medium text-content-primary hover:text-guide-600">
                                        {{ $vm->name }}
                                    </a>
                                    <p class="truncate text-xs text-content-tertiary">{{ $vm->vm_id }}{{ $vm->proxmox_number !== null ? ' · VMID '.$vm->proxmox_number : '' }} · {{ $vm->os ?? 'OS tidak dicatat' }}</p>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="py-6 text-center text-sm text-content-tertiary">Belum ada VM di host ini.</p>
                @endif
            </div>
        </div>
    @endif

    {{-- Tab: Dokumentasi --}}
    @if ($tab === 'documents')
        @php $documents = Document::query()->with('addedBy')->where('documentable_type', Proxmox::class)->where('documentable_id', $this->proxmox->id)->orderBy('document_id')->get(); @endphp
        <div class="mt-4">
            <x-asset.documents-panel :documents="$documents" name="{{ $proxmox->name }}" :add-href="route('admin.documents.index', ['type' => Proxmox::class, 'id' => $proxmox->id])" />
        </div>
    @endif

    {{-- Tab: Aktivitas --}}
    @if ($tab === 'activities')
        @php $activities = Activity::query()->with('user')->where('subject_type', Proxmox::class)->where('subject_id', $this->proxmox->id)->orderByDesc('id')->get(); @endphp
        <div class="mt-4">
            <x-asset.activities-panel :activities="$activities" name="{{ $proxmox->name }}" />
        </div>
    @endif

    {{-- Tab: Gallery --}}
    @if ($tab === 'gallery')
        @php $medias = $this->proxmox->medias()->orderByDesc('id')->get(); @endphp
        <div class="mt-4">
            <x-asset.gallery-panel :medias="$medias" name="{{ $proxmox->name }}" :add-href="route('admin.galleries.index', ['type' => Proxmox::class, 'id' => $proxmox->id])" />
        </div>
    @endif
</div>
