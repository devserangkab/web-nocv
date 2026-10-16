<?php

use App\Enums\VmsStatus;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use App\Actions\Assets\CreateProxmox;
use App\Actions\Assets\CreateVms;
use App\Actions\Assets\DeleteProxmox;
use App\Actions\Assets\DeleteVms;
use App\Actions\Assets\UpdateProxmox;
use App\Actions\Assets\UpdateVms;
use App\Models\Proxmox;
use App\Models\Server;
use App\Models\Vms;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    use \App\Livewire\Concerns\InteractsWithProxmoxForm;

    use \App\Livewire\Concerns\InteractsWithVmsForm;


    #[Layout('layouts::admin')]
    #[Title('Virtualisasi')]

    public string $tab = 'proxmox';

    // Proxmox
    public string $proxmoxSearch = '';


    public bool $showProxmoxDeleteModal = false;


    public ?int $deletingProxmoxId = null;







    // VM
    public string $vmSearch = '';

    public string $proxmoxFilter = 'ALL';


    public bool $showVmsDeleteModal = false;


    public ?int $deletingVmsId = null;














    public function mount(?string $tab = null): void
    {
        $this->tab = $tab ?? request()->query('tab', 'proxmox');
    }

    public function updatedProxmoxSearch(): void
    {
        $this->resetPage('proxmox_page');
    }

    public function updatedVmSearch(): void
    {
        $this->resetPage('vms_page');
    }

    public function updatedProxmoxFilter(): void
    {
        $this->resetPage('vms_page');
    }

    // ===== Proxmox =====

    public function askDeleteProxmox(int $id): void
    {
        $this->deletingProxmoxId = $id;
        $this->showProxmoxDeleteModal = true;
    }

    public function deleteProxmox(): void
    {
        if ($this->deletingProxmoxId !== null) {
            app(DeleteProxmox::class)(Proxmox::findOrFail($this->deletingProxmoxId));
        }

        $this->showProxmoxDeleteModal = false;
        $this->deletingProxmoxId = null;
    }

    public function proxmoxs(): LengthAwarePaginator
    {
        return Proxmox::query()
            ->with('server')
            ->withCount('vms')
            ->when($this->proxmoxSearch !== '', fn ($query) => $query->where(function ($query) {
                $query->where('name', 'like', "%{$this->proxmoxSearch}%")
                    ->orWhere('proxmox_id', 'like', "%{$this->proxmoxSearch}%")
                    ->orWhere('version', 'like', "%{$this->proxmoxSearch}%")
                    ->orWhereHas('server', fn ($server) => $server
                        ->where('server_name', 'like', "%{$this->proxmoxSearch}%")
                        ->orWhere('server_id', 'like', "%{$this->proxmoxSearch}%"));
            }))
            ->orderBy('proxmox_id')
            ->paginate(10, pageName: 'proxmox_page');
    }

    public function deletingProxmox(): ?Proxmox
    {
        return $this->deletingProxmoxId !== null ? Proxmox::with('server')->find($this->deletingProxmoxId) : null;
    }

    // ===== VM =====

    public function askDeleteVms(int $id): void
    {
        $this->deletingVmsId = $id;
        $this->showVmsDeleteModal = true;
    }

    public function deleteVms(): void
    {
        if ($this->deletingVmsId !== null) {
            app(DeleteVms::class)(Vms::findOrFail($this->deletingVmsId));
        }

        $this->showVmsDeleteModal = false;
        $this->deletingVmsId = null;
    }

    /**
     * Opsi host Proxmox untuk filter: "Semua Proxmox" + daftar host.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public function proxmoxFilterOptions(): array
    {
        $options = [
            [
                'value' => 'ALL',
                'label' => 'Semua Proxmox',
            ],
        ];

        foreach ($this->proxmoxOptions() as $proxmox) {
            $options[] = $proxmox;
        }

        return $options;
    }

    /**
     * Opsi host Proxmox untuk form.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public function vms(): LengthAwarePaginator
    {
        return Vms::query()
            ->with('proxmox.server')
            ->withCount(['panels', 'hostings'])
            ->when($this->proxmoxFilter !== 'ALL', fn ($query) => $query->where('proxmox_id', $this->proxmoxFilter))
            ->when($this->vmSearch !== '', fn ($query) => $query->where(function ($query) {
                $query->where('name', 'like', "%{$this->vmSearch}%")
                    ->orWhere('vm_id', 'like', "%{$this->vmSearch}%")
                    ->orWhere('os', 'like', "%{$this->vmSearch}%");
            }))
            ->orderBy('proxmox_id')
            ->orderBy('proxmox_number')
            ->paginate(10, pageName: 'vms_page');
    }

    public function deletingVm(): ?Vms
    {
        return $this->deletingVmsId !== null ? Vms::with('proxmox')->find($this->deletingVmsId) : null;
    }
};
?>

<div>
    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-semibold text-content-primary">Virtualisasi</h1>
            <p class="mt-1 text-sm text-content-secondary">Host Proxmox dan virtual machine yang berjalan di atasnya.</p>
        </div>
        @if ($tab === 'proxmox')
            @can('infrastructure.manage')
            <button type="button" wire:click="openProxmoxCreate" class="ina-button ina-button--primary ina-button--md">
                <i class="ti ti-plus text-lg" aria-hidden="true"></i>
                Tambah Proxmox
            </button>
            @endcan
        @else
            @can('infrastructure.manage')
            <button type="button" wire:click="openVmsCreate" class="ina-button ina-button--primary ina-button--md">
                <i class="ti ti-plus text-lg" aria-hidden="true"></i>
                Tambah VM
            </button>
            @endcan
        @endif
    </div>

    {{-- Tab navigation --}}
    <div class="mt-6 flex gap-1 w-fit rounded-lg border border-stroke-primary bg-background-primary p-1">
        <a
            href="{{ route('admin.assets.virtualization.index', ['tab' => 'proxmox']) }}"
            wire:navigate
            @class([
                'flex h-9 items-center gap-2 rounded-md px-4 text-[13px] font-medium transition-colors duration-150',
                'bg-guide-50 text-guide-600' => $tab === 'proxmox',
                'text-content-secondary hover:bg-neutral-100 hover:text-content-primary' => $tab !== 'proxmox',
            ])
        >
            <i class="ti ti-stack-2 text-base"></i>
            Proxmox
        </a>
        <a
            href="{{ route('admin.assets.virtualization.index', ['tab' => 'vms']) }}"
            wire:navigate
            @class([
                'flex h-9 items-center gap-2 rounded-md px-4 text-[13px] font-medium transition-colors duration-150',
                'bg-guide-50 text-guide-600' => $tab === 'vms',
                'text-content-secondary hover:bg-neutral-100 hover:text-content-primary' => $tab !== 'vms',
            ])
        >
            <i class="ti ti-box-multiple-3 text-base"></i>
            Virtual Machine
        </a>
    </div>

    @if ($tab === 'proxmox')
        @include('pages::admin.assets.partials.virtualization-proxmox')
    @else
        @include('pages::admin.assets.partials.virtualization-vms')
    @endif
</div>
