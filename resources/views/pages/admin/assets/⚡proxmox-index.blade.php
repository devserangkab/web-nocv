<?php

use App\Models\Proxmox;
use App\Models\Server;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use App\Actions\Assets\CreateProxmox;
use App\Actions\Assets\DeleteProxmox;
use App\Actions\Assets\UpdateProxmox;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    #[Layout('layouts::admin')]
    #[Title('Proxmox')]

    public string $search = '';

    public bool $showModal = false;

    public bool $showDeleteModal = false;

    public ?int $editingId = null;

    public ?int $deletingId = null;

    public string $name = '';

    public string $url = '';

    public string $version = '';

    public string $username = '';

    public string $password = '';

    public string $assign_server_id = '';

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
        $proxmox = Proxmox::findOrFail($id);

        $this->editingId = $proxmox->id;
        $this->name = (string) $proxmox->name;
        $this->url = (string) $proxmox->url;
        $this->version = (string) $proxmox->version;
        $this->username = (string) $proxmox->username;
        $this->password = '';
        $this->assign_server_id = (string) $proxmox->server_id;

        $this->showModal = true;
    }

    public function save(): void
    {
        $data = [
            'name' => $this->name,
            'url' => $this->url === '' ? null : $this->url,
            'version' => $this->version === '' ? null : $this->version,
            'assign_server_id' => $this->assign_server_id,
            'username' => $this->username,
            'password' => $this->password,
        ];

        if ($this->editingId !== null) {
            app(UpdateProxmox::class)(Proxmox::findOrFail($this->editingId), $data);
        } else {
            app(CreateProxmox::class)($data);
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
            app(DeleteProxmox::class)(Proxmox::findOrFail($this->deletingId));
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
        $this->url = '';
        $this->version = '';
        $this->username = '';
        $this->password = '';
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
                'taken' => $server->proxmox !== null && $server->proxmox->id !== $this->editingId,
            ])
            ->all();
    }

    public function proxmoxs(): LengthAwarePaginator
    {
        return Proxmox::query()
            ->with('server')
            ->when($this->search !== '', fn ($query) => $query->where(function ($query) {
                $query->where('name', 'like', "%{$this->search}%")
                    ->orWhere('proxmox_id', 'like', "%{$this->search}%")
                    ->orWhere('version', 'like', "%{$this->search}%")
                    ->orWhereHas('server', fn ($server) => $server
                        ->where('server_name', 'like', "%{$this->search}%")
                        ->orWhere('server_id', 'like', "%{$this->search}%"));
            }))
            ->orderBy('proxmox_id')
            ->paginate(10);
    }

    public function deletingProxmox(): ?Proxmox
    {
        return $this->deletingId !== null ? Proxmox::with('server')->find($this->deletingId) : null;
    }
};
?>

<div>
    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-semibold text-content-primary">Proxmox</h1>
            <p class="mt-1 text-sm text-content-secondary">Daftar host Proxmox dan server tempat terinstall.</p>
        </div>
        <button type="button" wire:click="openCreate" class="ina-button ina-button--primary ina-button--md">
            <i class="ti ti-plus text-lg" aria-hidden="true"></i>
            Tambah Proxmox
        </button>
    </div>

    {{-- Searchbar --}}
    <div class="mt-6 max-w-sm sm:ml-auto w-full">
        <div class="ina-input-search ina-input-search--size-md">
            <div class="ina-input-search__wrapper">
                <i class="ti ti-search ina-input-search__prefix-icon" aria-hidden="true"></i>
                <input
                    type="search"
                    class="ina-input-search__input"
                    placeholder="Cari nama, kode, URL, atau server..."
                    wire:model.live.debounce.300ms="search"
                    aria-label="Cari proxmox"
                >
            </div>
        </div>
    </div>

    {{-- Table --}}
    @php $proxmoxs = $this->proxmoxs(); @endphp
    @if ($proxmoxs->count() > 0)
        <div class="mt-4 overflow-hidden rounded-lg border border-stroke-primary bg-background-primary">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-stroke-primary bg-background-secondary text-left text-[12px] font-medium text-content-secondary">
                            <th scope="col" class="h-10 px-4 font-medium">Kode</th>
                            <th scope="col" class="h-10 px-4 font-medium">Name</th>
                            <th scope="col" class="h-10 px-4 font-medium">URL</th>
                            <th scope="col" class="h-10 px-4 font-medium">Version</th>
                            <th scope="col" class="h-10 px-4 font-medium">Server</th>
                            <th scope="col" class="h-10 px-4 font-medium text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($proxmoxs as $proxmox)
                            <tr class="border-b border-stroke-primary transition-colors duration-150 h-14 last:border-b-0 hover:bg-neutral-50">
                                <td class="h-10 px-4 text-[13px] text-content-primary">{{ $proxmox->proxmox_id }}</td>
                                <td class="h-10 px-4 font-medium text-content-primary">{{ $proxmox->name }}</td>
                                <td class="h-10 px-4">
                                    @if ($proxmox->url !== null)
                                        <a
                                            href="{{ $proxmox->url }}"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="text-[13px] text-guide-600 underline-offset-2 hover:underline"
                                        >{{ $proxmox->url }}</a>
                                    @else
                                        <span class="text-content-tertiary">-</span>
                                    @endif
                                </td>
                                <td class="h-10 px-4">
                                    @if ($proxmox->version !== null)
                                        <span class="ina-badge ina-badge--soft ina-badge--neutral ina-badge--sm">v{{ $proxmox->version }}</span>
                                    @else
                                        <span class="text-content-tertiary">-</span>
                                    @endif
                                </td>
                                <td class="h-10 px-4">
                                    @if ($proxmox->server !== null)
                                        <div class="flex flex-col">
                                            <span class="truncate font-medium text-content-primary" title="{{ $proxmox->server->server_name }}">{{ $proxmox->server->server_name }}</span>
                                            <span class="text-[11px] text-content-tertiary">{{ $proxmox->server->server_id }}</span>
                                        </div>
                                    @else
                                        <span class="text-content-tertiary">-</span>
                                    @endif
                                </td>
                                <td class="h-10 px-4">
                                    <div class="flex items-center justify-end gap-1">
                                        <button
                                            type="button"
                                            wire:click="edit({{ $proxmox->id }})"
                                            class="flex h-8 w-8 items-center justify-center rounded-md text-content-tertiary transition-colors duration-150 hover:bg-background-tertiary hover:text-content-primary"
                                            aria-label="Ubah {{ $proxmox->name }}"
                                            title="Ubah"
                                        >
                                            <i class="ti ti-pencil text-base"></i>
                                        </button>
                                        <button
                                            type="button"
                                            wire:click="askDelete({{ $proxmox->id }})"
                                            class="flex h-8 w-8 items-center justify-center rounded-md text-content-tertiary transition-colors duration-150 hover:bg-negative-50 hover:text-negative-600"
                                            aria-label="Hapus {{ $proxmox->name }}"
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

        <div class="mt-6">{{ $proxmoxs->links('vendor.pagination.tailwind') }}</div>
    @else
        {{-- Empty state (IDDS) --}}
        <div class="ina-card ina-card--stack mt-4 items-center px-6 py-12 text-center">
            <div class="flex h-12 w-12 items-center justify-center rounded-full bg-background-tertiary">
                <i class="ti ti-virtualization text-2xl text-content-tertiary"></i>
            </div>
            <h2 class="mt-4 text-base font-semibold text-content-primary">
                {{ $search !== '' ? 'Tidak ada hasil untuk "'.$search.'"' : 'Belum ada Proxmox' }}
            </h2>
            <p class="mt-2 max-w-sm text-sm text-content-secondary">
                {{ $search !== '' ? 'Coba kata kunci lain atau bersihkan pencarian.' : 'Host Proxmox yang ditambahkan akan tampil di sini.' }}
            </p>
            @if ($search === '')
                <button type="button" wire:click="openCreate" class="ina-button ina-button--primary ina-button--md mt-6">
                    <i class="ti ti-plus text-lg" aria-hidden="true"></i>
                    Tambah Proxmox
                </button>
            @else
                <button type="button" wire:click="$set('search', '')" class="ina-button ina-button--secondary ina-button--md mt-6">
                    Bersihkan Pencarian
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
            class="fixed left-1/2 top-1/2 z-50 w-full max-w-md max-h-[calc(100vh-32px)] overflow-y-auto -translate-x-1/2 -translate-y-1/2 rounded-xl border border-stroke-primary bg-background-primary p-6 shadow-lg"
            x-show="open"
            x-transition.opacity.duration.150ms
            role="dialog"
            aria-modal="true"
            aria-labelledby="proxmox-modal-title"
            @click.stop
        >
            <div class="flex items-start justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-content-primary" id="proxmox-modal-title">
                        {{ $editingId !== null ? 'Ubah Proxmox' : 'Tambah Proxmox' }}
                    </h2>
                    <p class="mt-1 text-sm text-content-secondary">
                        {{ $editingId !== null ? 'Perbarui data Proxmox.' : 'Kode akan terisi otomatis setelah disimpan.' }}
                    </p>
                </div>
                <button type="button" class="text-content-tertiary hover:text-content-primary" wire:click="closeModal" aria-label="Tutup">
                    <i class="ti ti-x text-lg"></i>
                </button>
            </div>

            <form class="mt-6 space-y-5" wire:submit="save">
                <div class="ina-text-field">
                    <label for="proxmox-name" class="ina-text-field__label">
                        Name
                        <span class="ina-text-field__required">*</span>
                    </label>
                    <div class="ina-text-field__wrapper ina-text-field__wrapper--size-md {{ $errors->has('name') ? 'ina-text-field__wrapper--status-error' : '' }}">
                        <input
                            type="text"
                            id="proxmox-name"
                            wire:model="name"
                            class="ina-text-field__input"
                            placeholder="Proxmox VE 1"
                            required
                            autofocus
                        >
                    </div>
                    @error('name')
                        <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="ina-text-field">
                    <label for="proxmox-url" class="ina-text-field__label">
                        URL
                    </label>
                    <div class="ina-text-field__wrapper ina-text-field__wrapper--size-md {{ $errors->has('url') ? 'ina-text-field__wrapper--status-error' : '' }}">
                        <input
                            type="text"
                            id="proxmox-url"
                            wire:model="url"
                            class="ina-text-field__input"
                            placeholder="https://10.10.1.11:8006"
                        >
                    </div>
                    @error('url')
                        <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="ina-text-field">
                    <label for="proxmox-version" class="ina-text-field__label">
                        Version
                    </label>
                    <div class="ina-text-field__wrapper ina-text-field__wrapper--size-md {{ $errors->has('version') ? 'ina-text-field__wrapper--status-error' : '' }}">
                        <input
                            type="text"
                            id="proxmox-version"
                            wire:model="version"
                            class="ina-text-field__input"
                            placeholder="8.2.4"
                            maxlength="20"
                        >
                    </div>
                    @error('version')
                        <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="ina-text-field">
                        <label for="proxmox-username" class="ina-text-field__label">
                            Username
                        </label>
                        <div class="ina-text-field__wrapper ina-text-field__wrapper--size-md {{ $errors->has('username') ? 'ina-text-field__wrapper--status-error' : '' }}">
                            <input
                                type="text"
                                id="proxmox-username"
                                wire:model="username"
                                class="ina-text-field__input"
                                autocomplete="off"
                                placeholder="root@pam"
                            >
                        </div>
                        @error('username')
                            <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="ina-text-field">
                        <label for="proxmox-password" class="ina-text-field__label">
                            Password
                        </label>
                        <div class="ina-text-field__wrapper ina-text-field__wrapper--size-md {{ $errors->has('password') ? 'ina-text-field__wrapper--status-error' : '' }}">
                            <input
                                type="password"
                                id="proxmox-password"
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

                <div class="ina-text-field">
                    <label for="proxmox-server" class="ina-text-field__label">
                        Server
                    </label>
                    <x-idds.select
                        wire-prop="assign_server_id"
                        :value="$assign_server_id"
                        :options="collect($this->serverOptions())->prepend(['value' => '', 'label' => 'Tanpa Server'])->map(fn ($server) => ['value' => $server['value'], 'label' => $server['label'], 'disabled' => $server['taken'] ?? false])->values()->all()"
                        placeholder="Tanpa Server"
                        aria-label="Pilih server"
                        :error="$errors->has('assign_server_id')"
                    />
                    @error('assign_server_id')
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
            aria-labelledby="proxmox-delete-title"
            @click.stop
        >
            @php $deleting = $this->deletingProxmox(); @endphp
            <div class="flex items-start justify-between">
                <div class="flex items-start gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-negative-50 text-negative-600">
                        <i class="ti ti-alert-triangle text-lg"></i>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-content-primary" id="proxmox-delete-title">Hapus Proxmox</h2>
                        <p class="mt-1 text-sm text-content-secondary">
                            Proxmox <span class="font-medium text-content-primary">{{ $deleting?->name ?? '-' }}</span> akan dihapus
                            @if ($deleting?->server !== null)
                                dari server <span class="font-medium text-content-primary">{{ $deleting?->server->server_name }}</span>.
                            @else
                                dari daftar.
                            @endif
                            Tindakan ini tidak bisa dibatalkan.
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
