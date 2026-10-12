<?php

use App\Enums\VmsStatus;
use App\Models\Proxmox;
use App\Models\Vms;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    #[Layout('layouts::admin')]
    #[Title('Vms')]

    public string $search = '';

    public string $proxmoxFilter = 'ALL';

    public bool $showModal = false;

    public bool $showDeleteModal = false;

    public ?int $editingId = null;

    public ?int $deletingId = null;

    #[Validate('required', message: 'Host Proxmox wajib dipilih.')]
    public string $proxmox_id = '';

    #[Validate('nullable|integer|min:1', message: 'VMID harus berupa angka (minimal 1).')]
    public string $proxmox_number = '';

    #[Validate('required', message: 'Nama VM wajib diisi.')]
    #[Validate('max:255', message: 'Nama VM maksimal 255 karakter.')]
    public string $name = '';

    #[Validate('nullable|ip', message: 'Local IP harus berupa alamat IP yang valid.')]
    public string $local_ip = '';

    #[Validate('nullable|ip', message: 'Public IP harus berupa alamat IP yang valid.')]
    public string $public_ip = '';

    #[Validate('nullable|integer|min:1', message: 'CPU harus berupa jumlah core (minimal 1).')]
    public string $cpu = '';

    #[Validate('nullable|integer|min:0', message: 'RAM harus berupa angka megabyte (MB).')]
    public string $ram = '';

    #[Validate('nullable|integer|min:0', message: 'Storage harus berupa angka megabyte (MB).')]
    public string $storage = '';

    #[Validate('max:255', message: 'OS maksimal 255 karakter.')]
    public string $os = '';

    #[Validate('in:ON,OFF,DESTROYED', message: 'Status harus ON, OFF, atau DESTROYED.')]
    public string $status = VmsStatus::Off->value;

    #[Validate('max:1000', message: 'Note maksimal 1000 karakter.')]
    public string $note = '';

    #[Validate('max:255', message: 'Username maksimal 255 karakter.')]
    public string $username = '';

    #[Validate('max:255', message: 'Password maksimal 255 karakter.')]
    public string $password = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedProxmoxFilter(): void
    {
        $this->resetPage();
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $vm = Vms::findOrFail($id);

        $this->editingId = $vm->id;
        $this->proxmox_id = (string) $vm->proxmox_id;
        $this->proxmox_number = (string) $vm->proxmox_number;
        $this->name = (string) $vm->name;
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

        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'proxmox_id' => $this->proxmox_id,
            'proxmox_number' => $this->proxmox_number === '' ? null : (int) $this->proxmox_number,
            'name' => $this->name,
            'local_ip' => $this->local_ip === '' ? null : $this->local_ip,
            'public_ip' => $this->public_ip === '' ? null : $this->public_ip,
            'cpu' => $this->cpu === '' ? null : (int) $this->cpu,
            'ram' => $this->ram === '' ? null : (int) $this->ram,
            'storage' => $this->storage === '' ? null : (int) $this->storage,
            'os' => $this->os === '' ? null : $this->os,
            'status' => $this->status,
            'note' => $this->note === '' ? null : $this->note,
        ];

        // Kredensial: kosong saat edit = biarkan nilai lama
        if ($this->editingId === null || $this->username !== '') {
            $data['username'] = $this->username === '' ? null : $this->username;
        }

        if ($this->editingId === null || $this->password !== '') {
            $data['password'] = $this->password === '' ? null : $this->password;
        }

        if ($this->editingId !== null) {
            Vms::findOrFail($this->editingId)->update($data);
        } else {
            Vms::create($data);
        }

        $this->showModal = false;
        $this->resetForm();
    }

    public function askDelete(int $id): void
    {
        $this->deletingId = $id;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        if ($this->deletingId !== null) {
            Vms::findOrFail($this->deletingId)->delete();
        }

        $this->showDeleteModal = false;
        $this->deletingId = null;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->resetValidation();
        $this->proxmox_id = '';
        $this->proxmox_number = '';
        $this->name = '';
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
            ->when($this->proxmoxFilter !== 'ALL', fn ($query) => $query->where('proxmox_id', $this->proxmoxFilter))
            ->when($this->search !== '', fn ($query) => $query->where(function ($query) {
                $query->where('name', 'like', "%{$this->search}%")
                    ->orWhere('vm_id', 'like', "%{$this->search}%")
                    ->orWhere('os', 'like', "%{$this->search}%");
            }))
            ->orderBy('proxmox_id')
            ->orderBy('proxmox_number')
            ->paginate(10);
    }

    public function deletingVm(): ?Vms
    {
        return $this->deletingId !== null ? Vms::with('proxmox')->find($this->deletingId) : null;
    }
};
?>

<div>
    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-semibold text-content-primary">Virtual Machine</h1>
            <p class="mt-1 text-sm text-content-secondary">Daftar virtual machine pada setiap host Proxmox.</p>
        </div>
        <button type="button" wire:click="openCreate" class="ina-button ina-button--primary ina-button--md">
            <i class="ti ti-plus text-lg" aria-hidden="true"></i>
            Tambah Vm
        </button>
    </div>

    {{-- Filter kiri + Search kanan --}}
    <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">

        <div>
            <div class="w-48">
                <x-idds.select
                    wire-prop="proxmoxFilter"
                    :value="$proxmoxFilter"
                    :options="$this->proxmoxFilterOptions()"
                    placeholder="Semua Proxmox"
                    aria-label="Filter host proxmox"
                />
            </div>
        </div>



        <div class="w-full max-w-sm">
            <div class="ina-input-search ina-input-search--size-md">
                <div class="ina-input-search__wrapper">
                    <i class="ti ti-search ina-input-search__prefix-icon" aria-hidden="true"></i>
                    <input
                        type="search"
                        class="ina-input-search__input"
                        placeholder="Cari name, kode, IP, atau OS..."
                        wire:model.live.debounce.300ms="search"
                        aria-label="Cari vm"
                    >
                </div>
            </div>
        </div>
    </div>

    {{-- Table --}}
    @php $vms = $this->vms(); @endphp
    @if ($vms->count() > 0)
        <div class="mt-4 overflow-hidden rounded-lg border border-stroke-primary bg-background-primary">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-stroke-primary bg-background-secondary text-left text-[12px] font-medium text-content-secondary">
                            <th scope="col" class="h-10 px-4 font-medium">Kode</th>
                            <th scope="col" class="h-10 px-4 font-medium">VMID</th>
                            <th scope="col" class="h-10 px-4 font-medium">Name</th>
                            <th scope="col" class="h-10 px-4 font-medium">Host</th>
                            <th scope="col" class="h-10 px-4 font-medium">Local IP</th>
                            <th scope="col" class="h-10 px-4 font-medium">Public IP</th>
                            <th scope="col" class="h-10 px-4 font-medium text-right">CPU</th>
                            <th scope="col" class="h-10 px-4 font-medium text-right">RAM</th>
                            <th scope="col" class="h-10 px-4 font-medium text-right">Storage</th>
                            <th scope="col" class="h-10 px-4 font-medium">OS</th>
                            <th scope="col" class="h-10 px-4 font-medium">Status</th>
                            <th scope="col" class="h-10 px-4 font-medium">Note</th>
                            <th scope="col" class="h-10 px-4 font-medium text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($vms as $vm)
                            <tr class="border-b border-stroke-primary transition-colors duration-150 h-14 last:border-b-0 hover:bg-neutral-50">
                                <td class="h-10 px-4 text-[13px] text-content-primary">{{ $vm->vm_id }}</td>
                                <td class="h-10 px-4 text-content-secondary">{{ $vm->proxmox_number ?? '-' }}</td>
                                <td class="h-10 px-4 font-medium text-content-primary">{{ $vm->name }}</td>
                                <td class="h-10 px-4 text-content-secondary">
                                    {{ $vm->proxmox?->name ?? '-' }}
                                    <span class="block text-[11px] text-content-tertiary">{{ $vm->proxmox?->proxmox_id }}</span>
                                </td>
                                <td class="h-10 px-4 text-content-secondary">{{ $vm->local_ip ?? '-' }}</td>
                                <td class="h-10 px-4 text-content-secondary">{{ $vm->public_ip ?? '-' }}</td>
                                <td class="h-10 px-4 text-right text-content-primary">{{ $vm->cpu !== null ? $vm->cpu.' core' : '-' }}</td>
                                <td class="h-10 px-4 text-right text-content-primary">{{ $vm->ram !== null ? number_format($vm->ram / 1024).' GB' : '-' }}</td>
                                <td class="h-10 px-4 text-right text-content-primary">{{ $vm->storage !== null ? number_format($vm->storage / 1024).' GB' : '-' }}</td>
                                <td class="h-10 px-4 text-content-secondary">{{ $vm->os ?? '-' }}</td>
                                <td class="h-10 px-4">
                                    <span class="ina-badge ina-badge--soft ina-badge--sm {{ $vm->status === VmsStatus::On ? 'ina-badge--positive' : ($vm->status === VmsStatus::Destroyed ? 'ina-badge--warning' : 'ina-badge--neutral') }}">
                                        {{ $vm->status->value }}
                                    </span>
                                </td>
                                <td class="h-10 max-w-xs truncate px-4 text-content-secondary" title="{{ $vm->note }}">{{ $vm->note ?? '-' }}</td>
                                <td class="h-10 px-4">
                                    <div class="flex items-center justify-end gap-1">
                                        <button
                                            type="button"
                                            wire:click="edit({{ $vm->id }})"
                                            class="flex h-8 w-8 items-center justify-center rounded-md text-content-tertiary transition-colors duration-150 hover:bg-background-tertiary hover:text-content-primary"
                                            aria-label="Ubah {{ $vm->name }}"
                                            title="Ubah"
                                        >
                                            <i class="ti ti-pencil text-base"></i>
                                        </button>
                                        <button
                                            type="button"
                                            wire:click="askDelete({{ $vm->id }})"
                                            class="flex h-8 w-8 items-center justify-center rounded-md text-content-tertiary transition-colors duration-150 hover:bg-negative-50 hover:text-negative-600"
                                            aria-label="Hapus {{ $vm->name }}"
                                            title="Hapus"
                                        >
                                            <i class="ti ti-trash text-base"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-6">{{ $vms->links('vendor.pagination.tailwind') }}</div>
    @else
        {{-- Empty state (IDDS) --}}
        <div class="ina-card ina-card--stack mt-4 items-center px-6 py-12 text-center">
            <div class="flex h-12 w-12 items-center justify-center rounded-full bg-background-tertiary">
                <i class="ti ti-box-multiple-3 text-2xl text-content-tertiary"></i>
            </div>
            <h2 class="mt-4 text-base font-semibold text-content-primary">
                {{ $search !== '' || $proxmoxFilter !== 'ALL' ? 'Tidak ada VM untuk filter ini' : 'Belum ada VM' }}
            </h2>
            <p class="mt-2 max-w-sm text-sm text-content-secondary">
                {{ $search !== '' || $proxmoxFilter !== 'ALL' ? 'Ubah kata kunci atau filter host.' : 'VM yang ditambahkan akan tampil di sini, terhubung ke host Proxmox.' }}
            </p>
            @if ($search === '' && $proxmoxFilter === 'ALL')
                <button type="button" wire:click="openCreate" class="ina-button ina-button--primary ina-button--md mt-6">
                    <i class="ti ti-plus text-lg" aria-hidden="true"></i>
                    Tambah Vm
                </button>
            @else
                <button type="button" wire:click="$set('proxmoxFilter', 'ALL'); $set('search', '')" class="ina-button ina-button--secondary ina-button--md mt-6">
                    Bersihkan Filter
                </button>
            @endif
        </div>
    @endif

    {{-- Modal Add/Edit (IDDS Task Modal) --}}
    <div
        x-data="{ open: $wire.entangle('showModal') }"
        x-on:keydown.escape.window="if (open) open = false"
        x-cloak
    >
        <div class="fixed inset-0 z-50 bg-black/50" x-show="open" x-transition.opacity @click="open = false" aria-hidden="true"></div>

        <div
            class="fixed left-1/2 top-1/2 z-50 w-full max-w-lg max-h-[calc(100vh-32px)] overflow-y-auto -translate-x-1/2 -translate-y-1/2 rounded-xl border border-stroke-primary bg-background-primary p-6 shadow-lg"
            x-show="open"
            x-transition.opacity.duration.150ms
            role="dialog"
            aria-modal="true"
            aria-labelledby="vms-modal-title"
            @click.stop
        >
            <div class="flex items-start justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-content-primary" id="vms-modal-title">
                        {{ $editingId !== null ? 'Ubah Vm' : 'Tambah Vm' }}
                    </h2>
                    <p class="mt-1 text-sm text-content-secondary">
                        {{ $editingId !== null ? 'Perbarui data VM.' : 'Kode akan terisi otomatis setelah disimpan.' }}
                    </p>
                </div>
                <button type="button" class="text-content-tertiary hover:text-content-primary" wire:click="closeModal" aria-label="Tutup">
                    <i class="ti ti-x text-lg"></i>
                </button>
            </div>

            <form class="mt-6 space-y-5" wire:submit="save">
                <div class="grid grid-cols-2 gap-4">
                    <div class="ina-text-field">
                        <label for="vm-host" class="ina-text-field__label">
                            Host Proxmox
                            <span class="ina-text-field__required">*</span>
                        </label>
                        <x-idds.select
                            wire-prop="proxmox_id"
                            :value="$proxmox_id"
                            :options="$this->proxmoxOptions()"
                            placeholder="Pilih host..."
                            aria-label="Pilih host proxmox"
                            :error="$errors->has('proxmox_id')"
                        />
                        @error('proxmox_id')
                            <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="ina-text-field">
                        <label for="vm-number" class="ina-text-field__label">
                            VMID
                        </label>
                        <div class="ina-text-field__wrapper ina-text-field__wrapper--size-md {{ $errors->has('proxmox_number') ? 'ina-text-field__wrapper--status-error' : '' }}">
                            <input
                                type="number"
                                min="1"
                                id="vm-number"
                                wire:model="proxmox_number"
                                class="ina-text-field__input"
                                placeholder="101"
                            >
                        </div>
                        @error('proxmox_number')
                            <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="ina-text-field">
                    <label for="vm-name" class="ina-text-field__label">
                        Name
                        <span class="ina-text-field__required">*</span>
                    </label>
                    <div class="ina-text-field__wrapper ina-text-field__wrapper--size-md {{ $errors->has('name') ? 'ina-text-field__wrapper--status-error' : '' }}">
                        <input
                            type="text"
                            id="vm-name"
                            wire:model="name"
                            class="ina-text-field__input"
                            placeholder="vm-web-1"
                            required
                            autofocus
                        >
                    </div>
                    @error('name')
                        <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="ina-text-field">
                        <label for="vm-local-ip" class="ina-text-field__label">
                            Local IP
                        </label>
                        <div class="ina-text-field__wrapper ina-text-field__wrapper--size-md {{ $errors->has('local_ip') ? 'ina-text-field__wrapper--status-error' : '' }}">
                            <input
                                type="text"
                                id="vm-local-ip"
                                wire:model="local_ip"
                                class="ina-text-field__input"
                                placeholder="192.0.2.10"
                            >
                        </div>
                        @error('local_ip')
                            <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="ina-text-field">
                        <label for="vm-public-ip" class="ina-text-field__label">
                            Public IP
                        </label>
                        <div class="ina-text-field__wrapper ina-text-field__wrapper--size-md {{ $errors->has('public_ip') ? 'ina-text-field__wrapper--status-error' : '' }}">
                            <input
                                type="text"
                                id="vm-public-ip"
                                wire:model="public_ip"
                                class="ina-text-field__input"
                                placeholder="198.51.100.7"
                            >
                        </div>
                        @error('public_ip')
                            <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="ina-text-field">
                        <label for="vm-username" class="ina-text-field__label">
                            Username
                        </label>
                        <div class="ina-text-field__wrapper ina-text-field__wrapper--size-md {{ $errors->has('username') ? 'ina-text-field__wrapper--status-error' : '' }}">
                            <input
                                type="text"
                                id="vm-username"
                                wire:model="username"
                                class="ina-text-field__input"
                                autocomplete="off"
                                placeholder="root"
                            >
                        </div>
                        @error('username')
                            <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="ina-text-field">
                        <label for="vm-password" class="ina-text-field__label">
                            Password
                        </label>
                        <div class="ina-text-field__wrapper ina-text-field__wrapper--size-md {{ $errors->has('password') ? 'ina-text-field__wrapper--status-error' : '' }}">
                            <input
                                type="password"
                                id="vm-password"
                                wire:model="password"
                                class="ina-text-field__input"
                                autocomplete="new-password"
                                placeholder="{{ $editingId !== null ? 'Kosongkan bila tidak diubah' : '••••••••' }}"
                            >
                        </div>
                        @error('password')
                            <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-4">
                    <div class="ina-text-field">
                        <label for="vm-cpu" class="ina-text-field__label">
                            CPU (core)
                        </label>
                        <div class="ina-text-field__wrapper ina-text-field__wrapper--size-md {{ $errors->has('cpu') ? 'ina-text-field__wrapper--status-error' : '' }}">
                            <input
                                type="number"
                                min="1"
                                id="vm-cpu"
                                wire:model="cpu"
                                class="ina-text-field__input"
                                placeholder="4"
                            >
                        </div>
                        @error('cpu')
                            <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="ina-text-field">
                        <label for="vm-ram" class="ina-text-field__label">
                            RAM (MB)
                        </label>
                        <div class="ina-text-field__wrapper ina-text-field__wrapper--size-md {{ $errors->has('ram') ? 'ina-text-field__wrapper--status-error' : '' }}">
                            <input
                                type="number"
                                min="0"
                                id="vm-ram"
                                wire:model="ram"
                                class="ina-text-field__input"
                                placeholder="8192"
                            >
                        </div>
                        @error('ram')
                            <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="ina-text-field">
                        <label for="vm-storage" class="ina-text-field__label">
                            Storage (MB)
                        </label>
                        <div class="ina-text-field__wrapper ina-text-field__wrapper--size-md {{ $errors->has('storage') ? 'ina-text-field__wrapper--status-error' : '' }}">
                            <input
                                type="number"
                                min="0"
                                id="vm-storage"
                                wire:model="storage"
                                class="ina-text-field__input"
                                placeholder="81920"
                            >
                        </div>
                        @error('storage')
                            <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="ina-text-field">
                    <label for="vm-os" class="ina-text-field__label">
                        OS
                    </label>
                    <div class="ina-text-field__wrapper ina-text-field__wrapper--size-md {{ $errors->has('os') ? 'ina-text-field__wrapper--status-error' : '' }}">
                        <input
                            type="text"
                            id="vm-os"
                            wire:model="os"
                            class="ina-text-field__input"
                            placeholder="Ubuntu Server 22.04 LTS"
                        >
                    </div>
                    @error('os')
                        <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="ina-text-area">
                    <label for="vm-note" class="ina-text-area__label">
                        Note
                    </label>
                    <div class="ina-text-area__wrapper ina-text-area__wrapper--size-sm {{ $errors->has('note') ? 'ina-text-area__wrapper--status-error' : '' }}">
                        <textarea
                            id="vm-note"
                            wire:model="note"
                            class="ina-text-area__input"
                            placeholder="Catatan tambahan VM..."
                        ></textarea>
                    </div>
                    @error('note')
                        <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Status â€” Toggle IDDS --}}
                <div class="ina-text-field">
                    <label for="vm-status" class="ina-text-field__label">
                        Status
                    </label>
                    <x-idds.select
                        wire-prop="status"
                        :value="$status"
                        :options="collect(VmsStatus::cases())->map(fn ($s) => ['value' => $s->value, 'label' => $s->value])->all()"
                        aria-label="Pilih status VM"
                        :error="$errors->has('status')"
                    />
                    @error('status')
                        <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" class="ina-button ina-button--secondary ina-button--md" wire:click="closeModal">
                        Batal
                    </button>
                    <button type="submit" class="ina-button ina-button--primary ina-button--md" wire:loading.attr="disabled">
                        {{ $editingId !== null ? 'Simpan Perubahan' : 'Simpan' }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal Konfirmasi Hapus (IDDS Confirm Modal) --}}
    <div
        x-data="{ open: $wire.entangle('showDeleteModal') }"
        x-on:keydown.escape.window="if (open) open = false"
        x-cloak
    >
        <div class="fixed inset-0 z-50 bg-black/50" x-show="open" x-transition.opacity @click="open = false" aria-hidden="true"></div>

        <div
            class="fixed left-1/2 top-1/2 z-50 w-full max-w-md max-h-[calc(100vh-32px)] overflow-y-auto -translate-x-1/2 -translate-y-1/2 rounded-xl border border-stroke-primary bg-background-primary p-6 shadow-lg"
            x-show="open"
            x-transition.opacity.duration.150ms
            role="dialog"
            aria-modal="true"
            aria-labelledby="vms-delete-title"
            @click.stop
        >
            @php $deleting = $this->deletingVm(); @endphp
            <div class="flex items-start justify-between">
                <div class="flex items-start gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-negative-50 text-negative-600">
                        <i class="ti ti-alert-triangle text-lg"></i>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-content-primary" id="vms-delete-title">Hapus Vm</h2>
                        <p class="mt-1 text-sm text-content-secondary">
                            VM <span class="font-medium text-content-primary">{{ $deleting?->name ?? '-' }}</span>
                            @if ($deleting?->proxmox !== null)
                                di host <span class="font-medium text-content-primary">{{ $deleting?->proxmox?->name }}</span> akan dihapus
                            @else
                                akan dihapus
                            @endif
                            dari daftar. Tindakan ini tidak bisa dibatalkan.
                        </p>
                        @error('deletingId')
                            <p class="mt-1.5 text-sm text-negative-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
                <button type="button" class="text-content-tertiary hover:text-content-primary" wire:click="$set('showDeleteModal', false)" aria-label="Tutup">
                    <i class="ti ti-x text-lg"></i>
                </button>
            </div>

            <div class="mt-6 flex justify-end gap-2">
                <button type="button" class="ina-button ina-button--secondary ina-button--md" wire:click="$set('showDeleteModal', false)">
                    Batal
                </button>
                <button
                    type="button"
                    class="ina-button ina-button--secondary ina-button--md bg-negative-600! text-white! hover:bg-negative-700!"
                    wire:loading.attr="disabled"
                    wire:click="delete"
                >
                    Hapus
                </button>
            </div>
        </div>
    </div>
</div>
