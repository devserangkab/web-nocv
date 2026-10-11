<?php

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

new class extends Component
{
    use WithPagination;

    #[Layout('layouts::admin')]
    #[Title('Roles')]

    public string $search = '';

    public bool $showModal = false;

    public bool $showDeleteModal = false;

    public ?int $editingId = null;

    public ?int $deletingId = null;

    #[Validate('required', message: 'Nama role wajib diisi.')]
    #[Validate('max:255', message: 'Nama role maksimal 255 karakter.')]
    public string $name = '';

    /**
     * Nama permission terpilih (modul.aksi), disinkron via checkbox.
     *
     * @var array<int, string>
     */
    public array $permissions = [];

    public function updatedSearch(): void
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
        $role = Role::findById($id, 'web');

        if ($role->name === 'superadmin') {
            $this->addError('name', 'Role superadmin tidak bisa diubah.');

            return;
        }

        $this->editingId = $role->id;
        $this->name = (string) $role->name;
        $this->permissions = $role->permissions->pluck('name')->all();

        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate();

        $taken = Role::query()
            ->where('name', $this->name)
            ->when($this->editingId !== null, fn ($query) => $query->where('id', '!=', $this->editingId))
            ->exists();

        if ($taken) {
            $this->addError('name', 'Nama role sudah dipakai.');

            return;
        }

        if ($this->editingId !== null) {
            $role = Role::findById($this->editingId, 'web');
            $role->update(['name' => $this->name]);
            $role->syncPermissions($this->permissions);
        } else {
            $role = Role::create(['name' => $this->name, 'guard_name' => 'web']);
            $role->syncPermissions($this->permissions);
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
        $role = $this->deletingId !== null ? Role::findById($this->deletingId, 'web') : null;

        if ($role !== null) {
            if ($role->name === 'superadmin') {
                $this->addError('deletingId', 'Role superadmin tidak bisa dihapus.');

                return;
            }

            if ($role->users()->exists()) {
                $this->addError('deletingId', 'Role masih dipakai oleh '.User::role($role->name)->count().' user.');

                return;
            }

            $role->delete();
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
        $this->name = '';
        $this->permissions = [];
    }

    /**
     * Label modul untuk grid permission.
     *
     * @return array<string, string>
     */
    private function moduleLabels(): array
    {
        return [
            'racks' => 'Rack',
            'servers' => 'Server',
            'proxmox' => 'Proxmox',
            'vms' => 'Virtual Machine',
            'panels' => 'Panel',
            'users' => 'User',
            'roles' => 'Role',
            'institutions' => 'Institution',
            'applications' => 'Application',
        ];
    }

    /**
     * Permission dikelompokkan per modul.
     *
     * @return Collection<string, array{label: string, actions: array<int, array{name: string, label: string, module: string}>}>
     */
    public function permissionGroups(): Collection
    {
        $groups = Permission::query()
            ->orderBy('name')
            ->get()
            ->map(fn (Permission $permission) => [
                'name' => $permission->name,
                'label' => Str::after($permission->name, '.'),
                'module' => Str::before($permission->name, '.'),
            ])
            ->groupBy('module');

        $labels = $this->moduleLabels();

        return $groups->map(fn ($items, $module) => [
            'label' => $labels[$module] ?? ucfirst($module),
            'actions' => $items->all(),
        ])->sortBy(fn (array $group) => $group['label']);
    }

    public function roles(): LengthAwarePaginator
    {
        return Role::query()
            ->with('permissions')
            ->withCount('users')
            ->when($this->search !== '', fn ($query) => $query->where('name', 'like', "%{$this->search}%"))
            ->orderBy('name')
            ->paginate(10);
    }

    public function deletingRole(): ?Role
    {
        return $this->deletingId !== null
            ? Role::findById($this->deletingId, 'web')
            : null;
    }
};
?>

<div>
    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-semibold text-content-primary">Roles</h1>
            <p class="mt-1 text-sm text-content-secondary">Kelola role beserta permission masing-masing modul.</p>
        </div>
        <button type="button" wire:click="openCreate" class="ina-button ina-button--primary ina-button--md">
            <i class="ti ti-plus text-lg" aria-hidden="true"></i>
            Tambah Role
        </button>
    </div>

    {{-- Search kanan --}}
    <div class="mt-6 flex justify-end">
        <div class="w-full max-w-sm">
            <div class="ina-input-search ina-input-search--size-md">
                <div class="ina-input-search__wrapper">
                    <i class="ti ti-search ina-input-search__prefix-icon" aria-hidden="true"></i>
                    <input
                        type="search"
                        class="ina-input-search__input"
                        placeholder="Cari nama role..."
                        wire:model.live.debounce.300ms="search"
                        aria-label="Cari role"
                    >
                </div>
            </div>
        </div>
    </div>

    {{-- Table --}}
    @php $roles = $this->roles(); @endphp
    @if ($roles->count() > 0)
        <div class="mt-4 overflow-hidden rounded-lg border border-stroke-primary bg-background-primary">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-stroke-primary bg-background-secondary text-left text-[12px] font-medium text-content-secondary">
                            <th scope="col" class="h-10 px-4 font-medium">Role</th>
                            <th scope="col" class="h-10 px-4 font-medium">Permission</th>
                            <th scope="col" class="h-10 px-4 font-medium">Users</th>
                            <th scope="col" class="h-10 px-4 font-medium text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($roles as $role)
                            <tr class="border-b border-stroke-primary transition-colors duration-150 h-14 last:border-b-0 hover:bg-neutral-50">
                                <td class="h-10 px-4">
                                    <span class="flex items-center gap-2 font-medium text-content-primary">
                                        <i class="ti ti-shield-check text-content-tertiary" aria-hidden="true"></i>
                                        {{ $role->name }}
                                    </span>
                                </td>
                                <td class="h-10 px-4 text-content-secondary">{{ $role->permissions->count() }} permission</td>
                                <td class="h-10 px-4 text-content-secondary">{{ $role->users_count }} user</td>
                                <td class="h-10 px-4">
                                    <div class="flex items-center justify-end gap-1">
                                        @if ($role->name !== 'superadmin')
                                            <button
                                                type="button"
                                                wire:click="edit({{ $role->id }})"
                                                class="flex h-8 w-8 items-center justify-center rounded-md text-content-tertiary transition-colors duration-150 hover:bg-background-tertiary hover:text-content-primary"
                                                aria-label="Ubah {{ $role->name }}"
                                                title="Ubah"
                                            >
                                                <i class="ti ti-pencil text-base"></i>
                                            </button>
                                            <button
                                                type="button"
                                                wire:click="askDelete({{ $role->id }})"
                                                class="flex h-8 w-8 items-center justify-center rounded-md text-content-tertiary transition-colors duration-150 hover:bg-negative-50 hover:text-negative-600"
                                                aria-label="Hapus {{ $role->name }}"
                                                title="Hapus"
                                            >
                                                <i class="ti ti-trash text-base"></i>
                                            </button>
                                        @else
                                            <span class="ina-badge ina-badge--soft ina-badge--neutral ina-badge--sm me-1">Terproteksi</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-6">{{ $roles->links('vendor.pagination.tailwind') }}</div>
    @else
        {{-- Empty state (IDDS) --}}
        <div class="ina-card ina-card--stack mt-4 items-center px-6 py-12 text-center">
            <div class="flex h-12 w-12 items-center justify-center rounded-full bg-background-tertiary">
                <i class="ti ti-shield-check text-2xl text-content-tertiary"></i>
            </div>
            <h2 class="mt-4 text-base font-semibold text-content-primary">
                {{ $search !== '' ? 'Tidak ada role untuk pencarian ini' : 'Belum ada role' }}
            </h2>
            <p class="mt-2 max-w-sm text-sm text-content-secondary">
                {{ $search !== '' ? 'Ubah kata kunci pencarian.' : 'Role yang ditambahkan akan tampil di sini berserta permission-nya.' }}
            </p>
            @if ($search === '')
                <button type="button" wire:click="openCreate" class="ina-button ina-button--primary ina-button--md mt-6">
                    <i class="ti ti-plus text-lg" aria-hidden="true"></i>
                    Tambah Role
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
            class="fixed left-1/2 top-1/2 z-50 w-full max-w-xl max-h-[calc(100vh-32px)] overflow-y-auto -translate-x-1/2 -translate-y-1/2 rounded-xl border border-stroke-primary bg-background-primary p-6 shadow-lg"
            x-show="open"
            x-transition.opacity.duration.150ms
            role="dialog"
            aria-modal="true"
            aria-labelledby="roles-modal-title"
            @click.stop
        >
            <div class="flex items-start justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-content-primary" id="roles-modal-title">
                        {{ $editingId !== null ? 'Ubah Role' : 'Tambah Role' }}
                    </h2>
                    <p class="mt-1 text-sm text-content-secondary">
                        Tandai permission yang boleh diakses role ini.
                    </p>
                </div>
                <button type="button" class="text-content-tertiary hover:text-content-primary" wire:click="closeModal" aria-label="Tutup">
                    <i class="ti ti-x text-lg"></i>
                </button>
            </div>

            <form class="mt-6 space-y-5" wire:submit="save">
                <div class="ina-text-field">
                    <label for="role-name" class="ina-text-field__label">
                        Nama Role
                        <span class="ina-text-field__required">*</span>
                    </label>
                    <div class="ina-text-field__wrapper ina-text-field__wrapper--size-md {{ $errors->has('name') ? 'ina-text-field__wrapper--status-error' : '' }}">
                        <input
                            type="text"
                            id="role-name"
                            wire:model="name"
                            class="ina-text-field__input"
                            placeholder="operator"
                            required
                        >
                    </div>
                    @error('name')
                        <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <p class="text-sm font-medium text-content-primary">Permission</p>
                    <div class="ina-text-area__wrapper ina-text-area__wrapper--size-sm mt-2 max-h-72 overflow-y-auto rounded-lg border border-stroke-primary bg-background-primary p-3">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            @foreach ($this->permissionGroups() as $group)
                                <div>
                                    <p class="mb-2 text-[11px] font-semibold uppercase tracking-wide text-content-tertiary">{{ $group['label'] }}</p>
                                    <div class="space-y-2">
                                        @foreach ($group['actions'] as $permission)
                                            <label class="flex w-fit cursor-pointer select-none items-center gap-2">
                                                <input
                                                    type="checkbox"
                                                    class="peer sr-only"
                                                    value="{{ $permission['name'] }}"
                                                    wire:model="permissions"
                                                >
                                                <span class="flex h-4 w-4 shrink-0 items-center justify-center rounded border border-stroke-primary bg-background-primary transition-colors duration-100 peer-checked:border-guide-600 peer-checked:bg-guide-600 peer-checked:[&>i]:opacity-100">
                                                    <i class="ti ti-check text-[10px] text-white opacity-0 transition-opacity duration-100" aria-hidden="true"></i>
                                                </span>
                                                <span class="text-sm text-content-secondary">{{ $permission['label'] }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <p class="mt-2 text-xs text-content-tertiary">Label di atas nama permission; mis. role "Operator Asset" cukup dapat view + create pada modul asset.</p>
                    @error('permissions')
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
            aria-labelledby="roles-delete-title"
            @click.stop
        >
            @php $deleting = $this->deletingRole(); @endphp
            <div class="flex items-start justify-between">
                <div class="flex items-start gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-negative-50 text-negative-600">
                        <i class="ti ti-alert-triangle text-lg"></i>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-content-primary" id="roles-delete-title">Hapus Role</h2>
                        <p class="mt-1 text-sm text-content-secondary">
                            Role <span class="font-medium text-content-primary">{{ $deleting?->name ?? '-' }}</span>
                            dengan {{ $deleting?->permissions->count() }} permission akan dihapus. Tindakan ini tidak bisa dibatalkan.
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
