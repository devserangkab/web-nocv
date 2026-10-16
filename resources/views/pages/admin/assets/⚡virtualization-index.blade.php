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

    #[Layout('layouts::admin')]
    #[Title('Virtualisasi')]

    public string $tab = 'proxmox';

    // Proxmox
    public string $proxmoxSearch = '';

    public bool $showProxmoxModal = false;

    public bool $showProxmoxDeleteModal = false;

    public ?int $editingProxmoxId = null;

    public ?int $deletingProxmoxId = null;

    public string $proxmox_name = '';

    public string $proxmox_url = '';

    public string $proxmox_version = '';

    public string $proxmox_username = '';

    public string $proxmox_password = '';

    public string $assign_server_id = '';

    // VM
    public string $vmSearch = '';

    public string $proxmoxFilter = 'ALL';

    public bool $showVmsModal = false;

    public bool $showVmsDeleteModal = false;

    public ?int $editingVmsId = null;

    public ?int $deletingVmsId = null;

    public string $proxmox_id = '';

    public string $proxmox_number = '';

    public string $vm_name = '';

    public string $local_ip = '';

    public string $public_ip = '';

    public string $cpu = '';

    public string $ram = '';

    public string $storage = '';

    public string $os = '';

    public string $status = VmsStatus::Off->value;

    public string $note = '';

    public string $username = '';

    public string $password = '';

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

    public function openProxmoxCreate(): void
    {
        $this->resetProxmoxForm();
        $this->showProxmoxModal = true;
    }

    public function editProxmox(int $id): void
    {
        $proxmox = Proxmox::findOrFail($id);

        $this->editingProxmoxId = $proxmox->id;
        $this->proxmox_name = (string) $proxmox->name;
        $this->proxmox_url = (string) $proxmox->url;
        $this->proxmox_version = (string) $proxmox->version;
        $this->proxmox_username = (string) $proxmox->username;
        $this->proxmox_password = '';
        $this->assign_server_id = (string) $proxmox->server_id;

        $this->showProxmoxModal = true;
    }

    public function saveProxmox(): void
    {
        $data = [
            'name' => $this->proxmox_name,
            'url' => $this->proxmox_url === '' ? null : $this->proxmox_url,
            'version' => $this->proxmox_version === '' ? null : $this->proxmox_version,
            'assign_server_id' => $this->assign_server_id,
            'username' => $this->proxmox_username,
            'password' => $this->proxmox_password,
        ];

        if ($this->editingProxmoxId !== null) {
            app(UpdateProxmox::class)(Proxmox::findOrFail($this->editingProxmoxId), $data);
        } else {
            app(CreateProxmox::class)($data);
        }

        $this->showProxmoxModal = false;
        $this->resetProxmoxForm();
    }

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

    public function closeProxmoxModal(): void
    {
        $this->showProxmoxModal = false;
        $this->resetProxmoxForm();
    }

    private function resetProxmoxForm(): void
    {
        $this->editingProxmoxId = null;
        $this->resetValidation();
        $this->proxmox_name = '';
        $this->proxmox_url = '';
        $this->proxmox_version = '';
        $this->proxmox_username = '';
        $this->proxmox_password = '';
        $this->assign_server_id = '';
    }

    /**
     * Opsi server untuk form: server yang sudah dipakai Proxmox lain di-disable.
     */
    public function serverOptions(): array
    {
        return Server::query()
            ->with('proxmox')
            ->orderBy('server_id')
            ->get()
            ->map(fn (Server $server) => [
                'value' => $server->server_id,
                'label' => "{$server->server_name} ({$server->server_id})",
                'taken' => $server->proxmox !== null && $server->proxmox->id !== $this->editingProxmoxId,
            ])
            ->all();
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

    public function openVmsCreate(): void
    {
        $this->resetVmsForm();
        $this->showVmsModal = true;
    }

    public function editVms(int $id): void
    {
        $vm = Vms::findOrFail($id);

        $this->editingVmsId = $vm->id;
        $this->proxmox_id = (string) $vm->proxmox_id;
        $this->proxmox_number = (string) $vm->proxmox_number;
        $this->vm_name = (string) $vm->name;
        $this->local_ip = (string) $vm->local_ip;
        $this->public_ip = (string) $vm->public_ip;
        $this->cpu = (string) $vm->cpu;
        $this->ram = (string) $vm->ram;
        $this->storage = (string) $vm->storage;
        $this->os = (string) $vm->os;
        $this->status = $vm->status?->value ?? VmsStatus::Off->value;
        $this->note = (string) $vm->note;
        $this->username = (string) $vm->username;
        $this->password = '';

        $this->showVmsModal = true;
    }

    public function saveVms(): void
    {
        $data = [
            'proxmox_id' => $this->proxmox_id,
            'proxmox_number' => $this->proxmox_number === '' ? null : (int) $this->proxmox_number,
            'name' => $this->vm_name,
            'local_ip' => $this->local_ip === '' ? null : $this->local_ip,
            'public_ip' => $this->public_ip === '' ? null : $this->public_ip,
            'cpu' => $this->cpu === '' ? null : (int) $this->cpu,
            'ram' => $this->ram === '' ? null : (int) $this->ram,
            'storage' => $this->storage === '' ? null : (int) $this->storage,
            'os' => $this->os === '' ? null : $this->os,
            'status' => $this->status,
            'note' => $this->note === '' ? null : $this->note,
            'username' => $this->username,
            'password' => $this->password,
        ];

        if ($this->editingVmsId !== null) {
            app(UpdateVms::class)(Vms::findOrFail($this->editingVmsId), $data);
        } else {
            app(CreateVms::class)($data);
        }

        $this->showVmsModal = false;
        $this->resetVmsForm();
    }

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

    public function closeVmsModal(): void
    {
        $this->showVmsModal = false;
        $this->resetVmsForm();
    }

    private function resetVmsForm(): void
    {
        $this->editingVmsId = null;
        $this->resetValidation();
        $this->proxmox_id = '';
        $this->proxmox_number = '';
        $this->vm_name = '';
        $this->local_ip = '';
        $this->public_ip = '';
        $this->cpu = '';
        $this->ram = '';
        $this->storage = '';
        $this->os = '';
        $this->status = VmsStatus::Off->value;
        $this->note = '';
        $this->username = '';
        $this->password = '';
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
    public function proxmoxOptions(): array
    {
        return Proxmox::query()
            ->orderBy('proxmox_id')
            ->get()
            ->map(fn (Proxmox $proxmox) => [
                'value' => $proxmox->proxmox_id,
                'label' => "{$proxmox->name} ({$proxmox->proxmox_id})",
            ])
            ->all();
    }

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
