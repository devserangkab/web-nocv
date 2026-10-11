<?php

use App\Enums\PanelType;
use App\Models\Panel;
use App\Models\Server;
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
    #[Title('Panels')]

    public string $search = '';

    public string $typeFilter = 'ALL';

    public bool $showModal = false;

    public bool $showDeleteModal = false;

    public ?int $editingId = null;

    public ?int $deletingId = null;

    #[Validate('required', message: 'Host wajib dipilih.')]
    public string $host = '';

    #[Validate('required', message: 'Nama panel wajib diisi.')]
    #[Validate('max:255', message: 'Nama panel maksimal 255 karakter.')]
    public string $name = '';

    #[Validate('required', message: 'Jenis panel wajib dipilih.')]
    public string $type = '';

    #[Validate('nullable|url', message: 'Internet address harus berupa URL yang valid (contoh: https://panel.domain:7800).')]
    public string $internet_address = '';

    #[Validate('nullable|url', message: 'Internal address harus berupa URL yang valid (contoh: https://10.10.1.11:7800).')]
    public string $internal_address = '';

    #[Validate('max:20', message: 'Version maksimal 20 karakter.')]
    public string $version = '';

    #[Validate('max:255', message: 'Username maksimal 255 karakter.')]
    public string $username = '';

    #[Validate('max:255', message: 'Password maksimal 255 karakter.')]
    public string $password = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedTypeFilter(): void
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
        $panel = Panel::findOrFail($id);

        $this->editingId = $panel->id;
        $this->host = $panel->vm_id !== null
            ? 'vm:'.$panel->vm_id
            : 'srv:'.$panel->server_id;
        $this->name = (string) $panel->name;
        $this->type = $panel->type->value;
        $this->internet_address = (string) $panel->internet_address;
        $this->internal_address = (string) $panel->internal_address;
        $this->version = (string) $panel->version;
        $this->username = (string) $panel->username;
        $this->password = '';

        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate();

        [$kind, $code] = explode(':', $this->host, 2);

        if ($kind === 'vm' && ! Vms::where('vm_id', $code)->exists()) {
            $this->addError('host', 'VM tidak ditemukan.');

            return;
        }

        if ($kind === 'srv' && ! Server::where('server_id', $code)->exists()) {
            $this->addError('host', 'Server tidak ditemukan.');

            return;
        }

        $data = [
            'vm_id' => $kind === 'vm' ? $code : null,
            'server_id' => $kind === 'srv' ? $code : null,
            'name' => $this->name,
            'internet_address' => $this->internet_address === '' ? null : $this->internet_address,
            'internal_address' => $this->internal_address === '' ? null : $this->internal_address,
            'type' => PanelType::from($this->type),
            'version' => $this->version === '' ? null : $this->version,
        ];

        // Kredensial: kosong saat edit = biarkan nilai lama
        if ($this->editingId === null || $this->username !== '') {
            $data['username'] = $this->username === '' ? null : $this->username;
        }

        if ($this->editingId === null || $this->password !== '') {
            $data['password'] = $this->password === '' ? null : $this->password;
        }

        if ($this->editingId !== null) {
            Panel::findOrFail($this->editingId)->update($data);
        } else {
            Panel::create($data);
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
            Panel::findOrFail($this->deletingId)->delete();
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
        $this->host = '';
        $this->name = '';
        $this->type = '';
        $this->internet_address = '';
        $this->internal_address = '';
        $this->version = '';
        $this->username = '';
        $this->password = '';
    }

    /**
     * Opsi jenis panel untuk filter.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public function typeFilterOptions(): array
    {
        return [
            [
                'value' => 'ALL',
                'label' => 'Semua Jenis',
            ],
            ...$this->typeOptions(),
        ];
    }

    /**
     * Opsi jenis panel untuk form.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public function typeOptions(): array
    {
        return collect(PanelType::cases())
            ->map(fn (PanelType $type) => [
                'value' => $type->value,
                'label' => $type->value,
            ])
            ->all();
    }

    /**
     * Opsi host untuk form: tergrup "Di VM" dan "Di Server". Wajib memilih.
     *
     * @return array<int, array{label: string|null, options: array<int, array{value: string, label: string}>}>
     */
    public function hostOptions(): array
    {
        return [
            [
                'label' => 'Di VM',
                'options' => Vms::query()
                    ->orderBy('vm_id')
                    ->get()
                    ->map(fn (Vms $vm) => [
                        'value' => 'vm:'.$vm->vm_id,
                        'label' => "{$vm->name} ({$vm->vm_id})",
                    ])
                    ->all(),
            ],
            [
                'label' => 'Di Server',
                'options' => Server::query()
                    ->orderBy('server_id')
                    ->get()
                    ->map(fn (Server $server) => [
                        'value' => 'srv:'.$server->server_id,
                        'label' => "{$server->server_name} ({$server->server_id})",
                    ])
                    ->all(),
            ],
        ];
    }

    public function panels(): LengthAwarePaginator
    {
        return Panel::query()
            ->with(['vm', 'server'])
            ->when($this->typeFilter !== 'ALL', fn ($query) => $query->where('type', $this->typeFilter))
            ->when($this->search !== '', fn ($query) => $query->where(function ($query) {
                $query->where('name', 'like', "%{$this->search}%")
                    ->orWhere('panel_id', 'like', "%{$this->search}%")
                    ->orWhere('version', 'like', "%{$this->search}%")
                    ->orWhereHas('vm', fn ($vm) => $vm
                        ->where('name', 'like', "%{$this->search}%")
                        ->orWhere('vm_id', 'like', "%{$this->search}%"))
                    ->orWhereHas('server', fn ($server) => $server
                        ->where('server_name', 'like', "%{$this->search}%")
                        ->orWhere('server_id', 'like', "%{$this->search}%"));
            }))
            ->orderBy('panel_id')
            ->paginate(10);
    }

    public function deletingPanel(): ?Panel
    {
        return $this->deletingId !== null ? Panel::with(['vm', 'server'])->find($this->deletingId) : null;
    }
};
?>

<div>
    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-semibold text-content-primary">Panels</h1>
            <p class="mt-1 text-sm text-content-secondary">Daftar control panel (aaPanel/cPanel) terpasang di VM atau server.</p>
        </div>
        <button type="button" wire:click="openCreate" class="ina-button ina-button--primary ina-button--md">
            <i class="ti ti-plus text-lg" aria-hidden="true"></i>
            Tambah Panel
        </button>
    </div>

    {{-- Filter kiri + Search kanan --}}
    <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div class="w-44">
            <x-idds.select
                wire-prop="typeFilter"
                :value="$typeFilter"
                :options="$this->typeFilterOptions()"
                placeholder="Semua Jenis"
                aria-label="Filter jenis panel"
            />
        </div>

        <div class="w-full max-w-sm">
            <div class="ina-input-search ina-input-search--size-md">
                <div class="ina-input-search__wrapper">
                    <i class="ti ti-search ina-input-search__prefix-icon" aria-hidden="true"></i>
                    <input
                        type="search"
                        class="ina-input-search__input"
                        placeholder="Cari name, kode, address, atau host..."
                        wire:model.live.debounce.300ms="search"
                        aria-label="Cari panel"
                    >
                </div>
            </div>
        </div>
    </div>

    {{-- Table --}}
    @php $panels = $this->panels(); @endphp
    @if ($panels->count() > 0)
        <div class="mt-4 overflow-hidden rounded-lg border border-stroke-primary bg-background-primary">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-stroke-primary bg-background-secondary text-left text-[12px] font-medium text-content-secondary">
                            <th scope="col" class="h-10 px-4 font-medium">Kode</th>
                            <th scope="col" class="h-10 px-4 font-medium">Jenis</th>
                            <th scope="col" class="h-10 px-4 font-medium">Name</th>
                            <th scope="col" class="h-10 px-4 font-medium">Host</th>
                            <th scope="col" class="h-10 px-4 font-medium">Internet Address</th>
                            <th scope="col" class="h-10 px-4 font-medium">Internal Address</th>
                            <th scope="col" class="h-10 px-4 font-medium">Version</th>
                            <th scope="col" class="h-10 px-4 font-medium text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($panels as $panel)
                            <tr class="border-b border-stroke-primary transition-colors duration-150 h-14 last:border-b-0 hover:bg-neutral-50">
                                <td class="h-10 px-4 text-[13px] text-content-primary">{{ $panel->panel_id }}</td>
                                <td class="h-10 px-4">
                                    <span class="ina-badge ina-badge--soft {{ $panel->type === PanelType::AaPanel ? 'ina-badge--info' : 'ina-badge--brand' }} ina-badge--sm">
                                        {{ $panel->type->value }}
                                    </span>
                                </td>
                                <td class="h-10 px-4 font-medium text-content-primary">{{ $panel->name }}</td>
                                <td class="h-10 px-4 text-content-secondary">
                                    @if ($panel->vm !== null)
                                        {{ $panel->vm->name }}
                                        <span class="block text-[11px] text-content-tertiary">{{ $panel->vm->vm_id }}</span>
                                    @elseif ($panel->server !== null)
                                        {{ $panel->server->server_name }}
                                        <span class="block text-[11px] text-content-tertiary">{{ $panel->server->server_id }}</span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="h-10 max-w-xs truncate px-4 text-content-secondary" title="{{ $panel->internet_address }}">{{ $panel->internet_address ?? '-' }}</td>
                                <td class="h-10 max-w-xs truncate px-4 text-content-secondary" title="{{ $panel->internal_address }}">{{ $panel->internal_address ?? '-' }}</td>
                                <td class="h-10 px-4 text-content-secondary">{{ $panel->version ?? '-' }}</td>
                                <td class="h-10 px-4">
                                    <div class="flex items-center justify-end gap-1">
                                        <button
                                            type="button"
                                            wire:click="edit({{ $panel->id }})"
                                            class="flex h-8 w-8 items-center justify-center rounded-md text-content-tertiary transition-colors duration-150 hover:bg-background-tertiary hover:text-content-primary"
                                            aria-label="Ubah {{ $panel->name }}"
                                            title="Ubah"
                                        >
                                            <i class="ti ti-pencil text-base"></i>
                                        </button>
                                        <button
                                            type="button"
                                            wire:click="askDelete({{ $panel->id }})"
                                            class="flex h-8 w-8 items-center justify-center rounded-md text-content-tertiary transition-colors duration-150 hover:bg-negative-50 hover:text-negative-600"
                                            aria-label="Hapus {{ $panel->name }}"
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

        <div class="mt-6">{{ $panels->links('vendor.pagination.tailwind') }}</div>
    @else
        {{-- Empty state (IDDS) --}}
        <div class="ina-card ina-card--stack mt-4 items-center px-6 py-12 text-center">
            <div class="flex h-12 w-12 items-center justify-center rounded-full bg-background-tertiary">
                <i class="ti ti-app-window text-2xl text-content-tertiary"></i>
            </div>
            <h2 class="mt-4 text-base font-semibold text-content-primary">
                {{ $search !== '' || $typeFilter !== 'ALL' ? 'Tidak ada panel untuk filter ini' : 'Belum ada panel' }}
            </h2>
            <p class="mt-2 max-w-sm text-sm text-content-secondary">
                {{ $search !== '' || $typeFilter !== 'ALL' ? 'Ubah kata kunci atau filter jenis.' : 'Panel yang ditambahkan akan tampil di sini, terpasang di VM atau server.' }}
            </p>
            @if ($search === '' && $typeFilter === 'ALL')
                <button type="button" wire:click="openCreate" class="ina-button ina-button--primary ina-button--md mt-6">
                    <i class="ti ti-plus text-lg" aria-hidden="true"></i>
                    Tambah Panel
                </button>
            @else
                <button type="button" wire:click="$set('typeFilter', 'ALL'); $set('search', '')" class="ina-button ina-button--secondary ina-button--md mt-6">
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
            aria-labelledby="panels-modal-title"
            @click.stop
        >
            <div class="flex items-start justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-content-primary" id="panels-modal-title">
                        {{ $editingId !== null ? 'Ubah Panel' : 'Tambah Panel' }}
                    </h2>
                    <p class="mt-1 text-sm text-content-secondary">
                        {{ $editingId !== null ? 'Perbarui data panel.' : 'Kode akan terisi otomatis setelah disimpan.' }}
                    </p>
                </div>
                <button type="button" class="text-content-tertiary hover:text-content-primary" wire:click="closeModal" aria-label="Tutup">
                    <i class="ti ti-x text-lg"></i>
                </button>
            </div>

            <form class="mt-6 space-y-5" wire:submit="save">
                <div class="ina-text-field">
                    <label for="panel-host" class="ina-text-field__label">
                        Host
                        <span class="ina-text-field__required">*</span>
                    </label>
                    <x-idds.select
                        wire-prop="host"
                        :value="$host"
                        :groups="$this->hostOptions()"
                        placeholder="Pilih host..."
                        aria-label="Pilih host panel"
                        :error="$errors->has('host')"
                    />
                    @error('host')
                        <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="ina-text-field">
                    <label for="panel-name" class="ina-text-field__label">
                        Name
                        <span class="ina-text-field__required">*</span>
                    </label>
                    <div class="ina-text-field__wrapper ina-text-field__wrapper--size-md {{ $errors->has('name') ? 'ina-text-field__wrapper--status-error' : '' }}">
                        <input
                            type="text"
                            id="panel-name"
                            wire:model="name"
                            class="ina-text-field__input"
                            placeholder="aapanel-web-1"
                            required
                        >
                    </div>
                    @error('name')
                        <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="ina-text-field">
                    <label for="panel-type" class="ina-text-field__label">
                        Jenis Panel
                        <span class="ina-text-field__required">*</span>
                    </label>
                    <x-idds.select
                        wire-prop="type"
                        :value="$type"
                        :options="$this->typeOptions()"
                        placeholder="Pilih jenis..."
                        aria-label="Pilih jenis panel"
                        :error="$errors->has('type')"
                    />
                    @error('type')
                        <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="ina-text-field">
                        <label for="panel-internet" class="ina-text-field__label">
                            Internet Address
                        </label>
                        <div class="ina-text-field__wrapper ina-text-field__wrapper--size-md {{ $errors->has('internet_address') ? 'ina-text-field__wrapper--status-error' : '' }}">
                            <input
                                type="text"
                                id="panel-internet"
                                wire:model="internet_address"
                                class="ina-text-field__input"
                                placeholder="https://panel.domain:7800"
                            >
                        </div>
                        @error('internet_address')
                            <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="ina-text-field">
                        <label for="panel-internal" class="ina-text-field__label">
                            Internal Address
                        </label>
                        <div class="ina-text-field__wrapper ina-text-field__wrapper--size-md {{ $errors->has('internal_address') ? 'ina-text-field__wrapper--status-error' : '' }}">
                            <input
                                type="text"
                                id="panel-internal"
                                wire:model="internal_address"
                                class="ina-text-field__input"
                                placeholder="https://192.0.2.10:7800"
                            >
                        </div>
                        @error('internal_address')
                            <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="ina-text-field">
                    <label for="panel-version" class="ina-text-field__label">
                        Version
                    </label>
                    <div class="ina-text-field__wrapper ina-text-field__wrapper--size-md {{ $errors->has('version') ? 'ina-text-field__wrapper--status-error' : '' }}">
                        <input
                            type="text"
                            id="panel-version"
                            wire:model="version"
                            class="ina-text-field__input"
                            placeholder="7.0.11"
                        >
                    </div>
                    @error('version')
                        <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="ina-text-field">
                        <label for="panel-username" class="ina-text-field__label">
                            Username
                        </label>
                        <div class="ina-text-field__wrapper ina-text-field__wrapper--size-md {{ $errors->has('username') ? 'ina-text-field__wrapper--status-error' : '' }}">
                            <input
                                type="text"
                                id="panel-username"
                                wire:model="username"
                                class="ina-text-field__input"
                                autocomplete="off"
                                placeholder="admin"
                            >
                        </div>
                        @error('username')
                            <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="ina-text-field">
                        <label for="panel-password" class="ina-text-field__label">
                            Password
                        </label>
                        <div class="ina-text-field__wrapper ina-text-field__wrapper--size-md {{ $errors->has('password') ? 'ina-text-field__wrapper--status-error' : '' }}">
                            <input
                                type="password"
                                id="panel-password"
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
            aria-labelledby="panels-delete-title"
            @click.stop
        >
            @php $deleting = $this->deletingPanel(); @endphp
            <div class="flex items-start justify-between">
                <div class="flex items-start gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-negative-50 text-negative-600">
                        <i class="ti ti-alert-triangle text-lg"></i>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-content-primary" id="panels-delete-title">Hapus Panel</h2>
                        <p class="mt-1 text-sm text-content-secondary">
                            Panel <span class="font-medium text-content-primary">{{ $deleting?->name ?? '-' }}</span>
                            @if ($deleting?->vm !== null)
                                di VM <span class="font-medium text-content-primary">{{ $deleting?->vm?->name }}</span>
                            @elseif ($deleting?->server !== null)
                                di server <span class="font-medium text-content-primary">{{ $deleting?->server?->server_name }}</span>
                            @endif
                            akan dihapus
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
