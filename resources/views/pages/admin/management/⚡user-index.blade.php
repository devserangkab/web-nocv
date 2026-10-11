<?php

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

new class extends Component
{
    use WithPagination;

    #[Layout('layouts::admin')]
    #[Title('Users')]

    public string $search = '';

    public string $roleFilter = 'ALL';

    public bool $showModal = false;

    public bool $showDeleteModal = false;

    public ?int $editingId = null;

    public ?int $deletingId = null;

    #[Validate('required', message: 'Name wajib diisi.')]
    #[Validate('max:255', message: 'Name maksimal 255 karakter.')]
    public string $name = '';

    #[Validate('required', message: 'Email wajib diisi.')]
    #[Validate('email', message: 'Email harus format email yang valid.')]
    public string $email = '';

    #[Validate('nullable', message: 'Password minimal 8 karakter.')]
    #[Validate('min:8', message: 'Password minimal 8 karakter.')]
    #[Validate('same:passwordConfirmation', message: 'Konfirmasi password tidak cocok.')]
    public string $password = '';

    public string $passwordConfirmation = '';

    #[Validate('required', message: 'Role wajib dipilih.')]
    public string $role = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedRoleFilter(): void
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
        $user = User::with('roles')->findOrFail($id);

        $this->editingId = $user->id;
        $this->name = (string) $user->name;
        $this->email = (string) $user->email;
        $this->password = '';
        $this->passwordConfirmation = '';
        $this->role = $user->roles->first()?->name ?? '';

        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate();

        if ($this->editingId === null && $this->password === '') {
            $this->addError('password', 'Password wajib diisi.');

            return;
        }

        $taken = User::query()
            ->where('email', $this->email)
            ->when($this->editingId !== null, fn ($query) => $query->where('id', '!=', $this->editingId))
            ->exists();

        if ($taken) {
            $this->addError('email', 'Email sudah terdaftar.');

            return;
        }

        $data = [
            'name' => $this->name,
            'email' => $this->email,
        ];

        if ($this->password !== '') {
            $data['password'] = $this->password;
        }

        // Field password ter-cast 'hashed', jadi teks polos langsung menyimpan versi hash.
        $user = $this->editingId !== null ? User::findOrFail($this->editingId) : User::create($data);

        if ($this->editingId !== null) {
            $user->update($data);
        }

        $user->syncRoles([$this->role]);

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
        $user = $this->deletingId !== null ? User::find($this->deletingId) : null;

        if ($user !== null) {
            if ($user->id === auth()->id()) {
                $this->addError('deletingId', 'Tidak bisa menghapus akun sendiri.');

                return;
            }

            if ($user->hasRole('superadmin')) {
                $this->addError('deletingId', 'User superadmin tidak bisa dihapus.');

                return;
            }

            $user->delete();
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
        $this->email = '';
        $this->password = '';
        $this->passwordConfirmation = '';
        $this->role = '';
    }

    /**
     * Filter role: "Semua Role" + daftar role.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public function roleFilterOptions(): array
    {
        return [
            [
                'value' => 'ALL',
                'label' => 'Semua Role',
            ],
            ...$this->roleOptions(),
        ];
    }

    /**
     * Opsi role untuk form.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public function roleOptions(): array
    {
        return Role::query()
            ->orderBy('name')
            ->get()
            ->map(fn (Role $role) => [
                'value' => $role->name,
                'label' => $role->name,
            ])
            ->all();
    }

    public function users(): LengthAwarePaginator
    {
        return User::query()
            ->with('roles')
            ->when($this->roleFilter !== 'ALL', fn ($query) => $query->whereHas(
                'roles',
                fn ($role) => $role->where('name', $this->roleFilter)
            ))
            ->when($this->search !== '', fn ($query) => $query->where(function ($query) {
                $query->where('name', 'like', "%{$this->search}%")
                    ->orWhere('email', 'like', "%{$this->search}%");
            }))
            ->orderBy('name')
            ->paginate(10);
    }

    public function deletingUser(): ?User
    {
        return $this->deletingId !== null ? User::with('roles')->find($this->deletingId) : null;
    }
};
?>

<div>
    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-semibold text-content-primary">Users</h1>
            <p class="mt-1 text-sm text-content-secondary">Daftar user, role, dan akses ke aplikasi.</p>
        </div>
        <button type="button" wire:click="openCreate" class="ina-button ina-button--primary ina-button--md">
            <i class="ti ti-plus text-lg" aria-hidden="true"></i>
            Tambah User
        </button>
    </div>

    {{-- Filter kiri + Search kanan --}}
    <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div class="w-44">
            <x-idds.select
                wire-prop="roleFilter"
                :value="$roleFilter"
                :options="$this->roleFilterOptions()"
                placeholder="Semua Role"
                aria-label="Filter role user"
            />
        </div>

        <div class="w-full max-w-sm">
            <div class="ina-input-search ina-input-search--size-md">
                <div class="ina-input-search__wrapper">
                    <i class="ti ti-search ina-input-search__prefix-icon" aria-hidden="true"></i>
                    <input
                        type="search"
                        class="ina-input-search__input"
                        placeholder="Cari name atau email..."
                        wire:model.live.debounce.300ms="search"
                        aria-label="Cari user"
                    >
                </div>
            </div>
        </div>
    </div>

    {{-- Table --}}
    @php $users = $this->users(); @endphp
    @if ($users->count() > 0)
        <div class="mt-4 overflow-hidden rounded-lg border border-stroke-primary bg-background-primary">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-stroke-primary bg-background-secondary text-left text-[12px] font-medium text-content-secondary">
                            <th scope="col" class="h-10 px-4 font-medium">Name</th>
                            <th scope="col" class="h-10 px-4 font-medium">Email</th>
                            <th scope="col" class="h-10 px-4 font-medium">Role</th>
                            <th scope="col" class="h-10 px-4 font-medium">Dibuat</th>
                            <th scope="col" class="h-10 px-4 font-medium text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($users as $user)
                            <tr class="border-b border-stroke-primary transition-colors duration-150 h-14 last:border-b-0 hover:bg-neutral-50">
                                <td class="h-10 px-4 font-medium text-content-primary">
                                    <span class="flex items-center gap-2">
                                        <i class="ti ti-user text-content-tertiary" aria-hidden="true"></i>
                                        {{ $user->name }}
                                    </span>
                                </td>
                                <td class="h-10 px-4 text-content-secondary">{{ $user->email }}</td>
                                <td class="h-10 px-4">
                                    @if ($user->roles->isNotEmpty())
                                        <span class="ina-badge ina-badge--soft ina-badge--info ina-badge--sm">{{ $user->roles->first()->name }}</span>
                                    @else
                                        <span class="ina-badge ina-badge--soft ina-badge--neutral ina-badge--sm">Tanpa Role</span>
                                    @endif
                                </td>
                                <td class="h-10 px-4 text-content-secondary">{{ $user->created_at->format('d M Y') }}</td>
                                <td class="h-10 px-4">
                                    <div class="flex items-center justify-end gap-1">
                                        <button
                                            type="button"
                                            wire:click="edit({{ $user->id }})"
                                            class="flex h-8 w-8 items-center justify-center rounded-md text-content-tertiary transition-colors duration-150 hover:bg-background-tertiary hover:text-content-primary"
                                            aria-label="Ubah {{ $user->name }}"
                                            title="Ubah"
                                        >
                                            <i class="ti ti-pencil text-base"></i>
                                        </button>
                                        <button
                                            type="button"
                                            wire:click="askDelete({{ $user->id }})"
                                            class="flex h-8 w-8 items-center justify-center rounded-md text-content-tertiary transition-colors duration-150 hover:bg-negative-50 hover:text-negative-600"
                                            aria-label="Hapus {{ $user->name }}"
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

        <div class="mt-6">{{ $users->links('vendor.pagination.tailwind') }}</div>
    @else
        {{-- Empty state (IDDS) --}}
        <div class="ina-card ina-card--stack mt-4 items-center px-6 py-12 text-center">
            <div class="flex h-12 w-12 items-center justify-center rounded-full bg-background-tertiary">
                <i class="ti ti-users text-2xl text-content-tertiary"></i>
            </div>
            <h2 class="mt-4 text-base font-semibold text-content-primary">
                {{ $search !== '' || $roleFilter !== 'ALL' ? 'Tidak ada user untuk filter ini' : 'Belum ada user' }}
            </h2>
            <p class="mt-2 max-w-sm text-sm text-content-secondary">
                {{ $search !== '' || $roleFilter !== 'ALL' ? 'Ubah kata kunci atau filter role.' : 'User yang ditambahkan akan tampil di sini berserta role-nya.' }}
            </p>
            @if ($search === '' && $roleFilter === 'ALL')
                <button type="button" wire:click="openCreate" class="ina-button ina-button--primary ina-button--md mt-6">
                    <i class="ti ti-plus text-lg" aria-hidden="true"></i>
                    Tambah User
                </button>
            @else
                <button type="button" wire:click="$set('roleFilter', 'ALL'); $set('search', '')" class="ina-button ina-button--secondary ina-button--md mt-6">
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
            aria-labelledby="users-modal-title"
            @click.stop
        >
            <div class="flex items-start justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-content-primary" id="users-modal-title">
                        {{ $editingId !== null ? 'Ubah User' : 'Tambah User' }}
                    </h2>
                    <p class="mt-1 text-sm text-content-secondary">
                        {{ $editingId !== null ? 'Kosongkan password bila tidak diubah.' : 'Set password untuk akses pertama kali.' }}
                    </p>
                </div>
                <button type="button" class="text-content-tertiary hover:text-content-primary" wire:click="closeModal" aria-label="Tutup">
                    <i class="ti ti-x text-lg"></i>
                </button>
            </div>

            <form class="mt-6 space-y-5" wire:submit="save">
                <div class="ina-text-field">
                    <label for="user-name" class="ina-text-field__label">
                        Name
                        <span class="ina-text-field__required">*</span>
                    </label>
                    <div class="ina-text-field__wrapper ina-text-field__wrapper--size-md {{ $errors->has('name') ? 'ina-text-field__wrapper--status-error' : '' }}">
                        <input
                            type="text"
                            id="user-name"
                            wire:model="name"
                            class="ina-text-field__input"
                            placeholder="Elly Marcian"
                            required
                        >
                    </div>
                    @error('name')
                        <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="ina-text-field">
                    <label for="user-email" class="ina-text-field__label">
                        Email
                        <span class="ina-text-field__required">*</span>
                    </label>
                    <div class="ina-text-field__wrapper ina-text-field__wrapper--size-md {{ $errors->has('email') ? 'ina-text-field__wrapper--status-error' : '' }}">
                        <input
                            type="email"
                            id="user-email"
                            wire:model="email"
                            class="ina-text-field__input"
                            placeholder="elly@serangkab.go.id"
                            required
                        >
                    </div>
                    @error('email')
                        <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="ina-text-field">
                        <label for="user-password" class="ina-text-field__label">
                            Password
                            @if ($editingId === null)
                                <span class="ina-text-field__required">*</span>
                            @endif
                        </label>
                        <div class="ina-text-field__wrapper ina-text-field__wrapper--size-md {{ $errors->has('password') ? 'ina-text-field__wrapper--status-error' : '' }}">
                            <input
                                type="password"
                                id="user-password"
                                wire:model="password"
                                class="ina-text-field__input"
                                autocomplete="new-password"
                            >
                        </div>
                        @error('password')
                            <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="ina-text-field">
                        <label for="user-password-confirmation" class="ina-text-field__label">
                            Ulangi Password
                        </label>
                        <div class="ina-text-field__wrapper ina-text-field__wrapper--size-md {{ $errors->has('passwordConfirmation') ? 'ina-text-field__wrapper--status-error' : '' }}">
                            <input
                                type="password"
                                id="user-password-confirmation"
                                wire:model="passwordConfirmation"
                                class="ina-text-field__input"
                                autocomplete="new-password"
                            >
                        </div>
                        @error('passwordConfirmation')
                            <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="ina-text-field">
                    <label for="user-role" class="ina-text-field__label">
                        Role
                        <span class="ina-text-field__required">*</span>
                    </label>
                    <x-idds.select
                        wire-prop="role"
                        :value="$role"
                        :options="$this->roleOptions()"
                        placeholder="Pilih role..."
                        aria-label="Pilih role user"
                        :error="$errors->has('role')"
                    />
                    @error('role')
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
            aria-labelledby="users-delete-title"
            @click.stop
        >
            @php $deleting = $this->deletingUser(); @endphp
            <div class="flex items-start justify-between">
                <div class="flex items-start gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-negative-50 text-negative-600">
                        <i class="ti ti-alert-triangle text-lg"></i>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-content-primary" id="users-delete-title">Hapus User</h2>
                        <p class="mt-1 text-sm text-content-secondary">
                            User <span class="font-medium text-content-primary">{{ $deleting?->name ?? '-' }}</span>
                            ({{ $deleting?->email ?? '-' }}) akan dihapus dari daftar. Tindakan ini tidak bisa dibatalkan.
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
