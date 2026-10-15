<?php

use App\Actions\Applications\DeleteApplication;
use App\Actions\Applications\StoreApplication;
use App\Enums\ApplicationStatus;
use App\Enums\ApplicationType;
use App\Enums\DeveloperType;
use App\Models\Application;
use App\Models\Developer;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

new class extends Component
{
    use WithFileUploads;
    use WithPagination;

    #[Layout('layouts::admin')]
    #[Title('Applications')]

    public string $search = '';

    public string $statusFilter = 'ALL';

    public string $typeFilter = 'ALL';

    public bool $showModal = false;

    public bool $showDeleteModal = false;

    public ?int $editingId = null;

    public ?int $deletingId = null;

    public string $name = '';

    public string $abbreviation = '';

    public string $description = '';

    public string $status = '';

    public string $type = '';

    public string $requested_by = '';

    public string $domain = '';

    public string $repo_url = '';

    public $icon = null; // @var TemporaryUploadedFile|null|min:0

    public string $icon_path = '';

    public string $framework = '';

    public string $language = '';

    public string $database = '';

    /**
     * Baris repeater developer di modal: [['user_id' => '1', 'type' => 'Programmer'], ...]
     *
     * @var array<int, array{user_id: string, type: string}>
     */
    public array $developerRows = [];

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedTypeFilter(): void
    {
        $this->resetPage();
    }

    public function addDeveloperRow(): void
    {
        $this->developerRows[] = ['user_id' => '', 'type' => ''];
    }

    public function removeDeveloperRow(int $index): void
    {
        unset($this->developerRows[$index]);
        $this->developerRows = array_values($this->developerRows);
    }

    public function removeIcon(): void
    {
        $this->icon = null;
        $this->icon_path = '';
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $application = Application::with('developers')->findOrFail($id);

        $this->editingId = $application->id;
        $this->name = (string) $application->name;
        $this->abbreviation = (string) $application->abbreviation;
        $this->description = (string) $application->description;
        $this->status = $application->status->value;
        $this->type = $application->type->value;
        $this->requested_by = (string) ($application->requested_by ?? '');
        $this->domain = (string) $application->domain;
        $this->repo_url = (string) $application->repo_url;
        $this->icon = null;
        $this->icon_path = (string) $application->icon;
        $this->framework = (string) $application->framework;
        $this->language = (string) $application->language;
        $this->database = (string) $application->database;
        $this->developerRows = $application->developers
            ->map(fn (Developer $row) => [
                'user_id' => (string) ($row->user->id ?? ''),
                'type' => $row->type->value,
            ])
            ->all();

        $this->showModal = true;
    }

    public function save(): void
    {
        // Validasi dinormalisasi di action; baris developer & icon ikut di-manage action.
        $data = [
            'name' => $this->name,
            'abbreviation' => $this->abbreviation === '' ? null : $this->abbreviation,
            'description' => $this->description === '' ? null : $this->description,
            'status' => $this->status,
            'type' => $this->type,
            'requested_by' => $this->requested_by === '' ? null : (int) $this->requested_by,
            'domain' => $this->domain === '' ? null : $this->domain,
            'repo_url' => $this->repo_url === '' ? null : $this->repo_url,
            'framework' => $this->framework === '' ? null : $this->framework,
            'language' => $this->language === '' ? null : $this->language,
            'database' => $this->database === '' ? null : $this->database,
            'icon' => $this->icon,
            'existing_icon_path' => $this->icon_path,
            'developers' => $this->developerRows,
        ];

        if ($this->editingId !== null) {
            app(StoreApplication::class)(Application::findOrFail($this->editingId), $data);
        } else {
            app(StoreApplication::class)(new Application, $data);
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
            $application = Application::findOrFail($this->deletingId);

            app(DeleteApplication::class)($application);
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
        $this->abbreviation = '';
        $this->description = '';
        $this->status = '';
        $this->type = '';
        $this->requested_by = '';
        $this->domain = '';
        $this->repo_url = '';
        $this->icon = null;
        $this->icon_path = '';
        $this->framework = '';
        $this->language = '';
        $this->database = '';
        $this->developerRows = [];
    }

    /**
     * Opsi status untuk filter: "Semua Status" + daftar enum.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public function statusFilterOptions(): array
    {
        return [
            [
                'value' => 'ALL',
                'label' => 'Semua Status',
            ],
            ...$this->statusOptions(),
        ];
    }

    /**
     * Opsi status untuk form.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public function statusOptions(): array
    {
        return collect(ApplicationStatus::cases())
            ->map(fn (ApplicationStatus $status) => [
                'value' => $status->value,
                'label' => $status->value,
            ])
            ->all();
    }

    /**
     * Opsi jenis untuk filter: "Semua Jenis" + daftar enum.
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
     * Opsi jenis untuk form.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public function typeOptions(): array
    {
        return collect(ApplicationType::cases())
            ->map(fn (ApplicationType $type) => [
                'value' => $type->value,
                'label' => $type->value,
            ])
            ->all();
    }

    /**
     * Opsi OPD pengusul: "Tanpa OPD" + institution.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public function requestedByOptions(): array
    {
        return [
            [
                'value' => '',
                'label' => 'Tanpa OPD',
            ],
            ...Institution::query()
                ->orderBy('abbreviation')
                ->get()
                ->map(fn (Institution $institution) => [
                    'value' => (string) $institution->id,
                    'label' => "{$institution->abbreviation} ({$institution->institution_id})",
                ])
                ->all(),
        ];
    }

    /**
     * Opsi developer: user bеrole "developer" saja.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public function developerUserOptions(): array
    {
        return User::query()
            ->role('developer')
            ->orderBy('name')
            ->get()
            ->map(fn (User $user) => [
                'value' => (string) $user->id,
                'label' => "{$user->name} ({$user->email})",
            ])
            ->all();
    }

    /**
     * Opsi jenis developer untuk repeater.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public function developerTypeOptions(): array
    {
        return collect(DeveloperType::cases())
            ->map(fn (DeveloperType $type) => [
                'value' => $type->value,
                'label' => $type->value,
            ])
            ->all();
    }

    public function applications(): LengthAwarePaginator
    {
        return Application::query()
            ->with(['requestedBy', 'developers', 'hostings.hostable'])
            ->when($this->statusFilter !== 'ALL', fn ($query) => $query->where('status', $this->statusFilter))
            ->when($this->typeFilter !== 'ALL', fn ($query) => $query->where('type', $this->typeFilter))
            ->when($this->search !== '', fn ($query) => $query->where(function ($query) {
                $query->where('name', 'like', "%{$this->search}%")
                    ->orWhere('abbreviation', 'like', "%{$this->search}%")
                    ->orWhere('application_id', 'like', "%{$this->search}%")
                    ->orWhere('domain', 'like', "%{$this->search}%")
                    ->orWhere('framework', 'like', "%{$this->search}%")
                    ->orWhere('language', 'like', "%{$this->search}%")
                    ->orWhere('database', 'like', "%{$this->search}%")
                    ->orWhereHas('requestedBy', fn ($institution) => $institution
                        ->where('abbreviation', 'like', "%{$this->search}%")
                        ->orWhere('name', 'like', "%{$this->search}%"));
            }))
            ->orderBy('application_id')
            ->paginate(10);
    }

    public function deletingApplication(): ?Application
    {
        return $this->deletingId !== null ? Application::withCount('developers')->find($this->deletingId) : null;
    }
};
?>

<div>
    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-semibold text-content-primary">Applications</h1>
            <p class="mt-1 text-sm text-content-secondary">Daftar application berikut tim developer-nya.</p>
        </div>
        <button type="button" wire:click="openCreate" class="ina-button ina-button--primary ina-button--md">
            <i class="ti ti-plus text-lg" aria-hidden="true"></i>
            Tambah Application
        </button>
    </div>

    {{-- Filter kiri + Search kanan --}}
    <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div class="flex gap-3">
            <div class="w-44">
                <x-idds.select
                    wire-prop="statusFilter"
                    :value="$statusFilter"
                    :options="$this->statusFilterOptions()"
                    placeholder="Semua Status"
                    aria-label="Filter status application"
                />
            </div>

            <div class="w-44">
                <x-idds.select
                    wire-prop="typeFilter"
                    :value="$typeFilter"
                    :options="$this->typeFilterOptions()"
                    placeholder="Semua Jenis"
                    aria-label="Filter jenis application"
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
                        placeholder="Cari name, kode, domain, atau teknologi..."
                        wire:model.live.debounce.300ms="search"
                        aria-label="Cari application"
                    >
                </div>
            </div>
        </div>
    </div>

    {{-- Table --}}
    @php $applications = $this->applications(); @endphp
    @if ($applications->count() > 0)
        <div class="mt-4 overflow-hidden rounded-lg border border-stroke-primary bg-background-primary">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-stroke-primary bg-background-secondary text-left text-[12px] font-medium text-content-secondary">
                            <th scope="col" class="h-10 px-4 font-medium">Kode</th>
                            <th scope="col" class="h-10 px-4 font-medium">Nama</th>
                            <th scope="col" class="h-10 px-4 font-medium">Status</th>
                            <th scope="col" class="h-10 px-4 font-medium">Jenis</th>
                            <th scope="col" class="h-10 px-4 font-medium">OPD Pengusul</th>
                            <th scope="col" class="h-10 px-4 font-medium">Dev</th>
                            <th scope="col" class="h-10 px-4 font-medium">Deployment</th>
                            <th scope="col" class="h-10 px-4 font-medium">Domain</th>
                            <th scope="col" class="h-10 px-4 font-medium text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($applications as $application)
                            <tr class="border-b border-stroke-primary transition-colors duration-150 h-14 last:border-b-0 hover:bg-neutral-50">
                                <td class="h-14 px-4 text-[13px] text-content-primary">{{ $application->application_id }}</td>
                                <td class="h-14 px-4 font-medium text-content-primary">
                                    <span class="flex items-center gap-2">
                                        @if ($application->icon !== null)
                                            <img src="{{ asset('storage/'.$application->icon) }}" alt="Icon {{ $application->name }}" class="h-6 w-6 shrink-0 rounded bg-background-secondary object-contain">
                                        @endif
                                        <span class="truncate">
                                            {{ $application->name }}
                                            <span class="block text-[11px] font-normal text-content-tertiary">{{ $application->abbreviation ?? '-' }}</span>
                                        </span>
                                    </span>
                                </td>
                                <td class="h-14 px-4">
                                    @php
                                        $statusColor = match ($application->status) {
                                            ApplicationStatus::Production => 'ina-badge--positive',
                                            ApplicationStatus::Development => 'ina-badge--info',
                                            ApplicationStatus::Maintenance => 'ina-badge--warning',
                                            default => 'ina-badge--neutral',
                                        };
                                    @endphp
                                    <span class="ina-badge ina-badge--soft {{ $statusColor }} ina-badge--sm">{{ $application->status->value }}</span>
                                </td>
                                <td class="h-14 px-4 text-content-secondary">{{ $application->type->value }}</td>
                                <td class="h-14 px-4 text-content-secondary">{{ $application->requestedBy?->abbreviation ?? '-' }}</td>
                                <td class="h-14 px-4">
                                    <span class="ina-badge ina-badge--soft ina-badge--neutral ina-badge--sm">{{ $application->developers->count() }} dev</span>
                                </td>
                                <td class="h-14 max-w-[12rem] px-4 text-content-secondary">
                                    @if ($application->hostings->isNotEmpty())
                                        @foreach ($application->hostings as $hosting)
                                            <span class="block truncate text-[12px]" title="{{ $hosting->hostable !== null ? ($hosting->hostable->name ?? $hosting->hostable->server_name ?? '?') : $hosting->name }}">
                                                <i class="ti {{ match ($hosting->kind()) { 'vm' => 'ti-box-multiple-3', 'server' => 'ti-server', default => 'ti-cloud' } }} text-sm" aria-hidden="true"></i>
                                                {{ $hosting->hostable !== null ? ($hosting->hostable->name ?? $hosting->hostable->server_name ?? '-') : $hosting->name }}
                                            </span>
                                        @endforeach
                                    @else
                                        <span class="text-content-tertiary">-</span>
                                    @endif
                                </td>
                                <td class="h-14 max-w-xs truncate px-4 text-content-secondary" title="{{ $application->domain }}">{{ $application->domain ?? '-' }}</td>
                                <td class="h-14 px-4">
                                    <div class="flex items-center justify-end gap-1">
                                        <a
                                            href="{{ route('admin.applications.show', $application->application_id) }}"
                                            wire:navigate
                                            class="flex h-8 w-8 items-center justify-center rounded-md text-content-tertiary transition-colors duration-150 hover:bg-background-tertiary hover:text-content-primary"
                                            aria-label="Detail {{ $application->name }}"
                                            title="Detail"
                                        >
                                            <i class="ti ti-eye text-base"></i>
                                        </a>
                                        <button
                                            type="button"
                                            wire:click="edit({{ $application->id }})"
                                            class="flex h-8 w-8 items-center justify-center rounded-md text-content-tertiary transition-colors duration-150 hover:bg-background-tertiary hover:text-content-primary"
                                            aria-label="Ubah {{ $application->name }}"
                                            title="Ubah"
                                        >
                                            <i class="ti ti-pencil text-base"></i>
                                        </button>
                                        <button
                                            type="button"
                                            wire:click="askDelete({{ $application->id }})"
                                            class="flex h-8 w-8 items-center justify-center rounded-md text-content-tertiary transition-colors duration-150 hover:bg-negative-50 hover:text-negative-600"
                                            aria-label="Hapus {{ $application->name }}"
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

        <div class="mt-6">{{ $applications->links('vendor.pagination.tailwind') }}</div>
    @else
        {{-- Empty state (IDDS) --}}
        <div class="ina-card ina-card--stack mt-4 px-6 py-12 text-center">
            <x-asset.illustration name="{{ $search !== '' || $statusFilter !== 'ALL' || $typeFilter !== 'ALL' ? 'noresult' : 'nodata' }}" size="sm" />
            <h2 class="mt-4 text-base font-semibold text-content-primary">
                {{ $search !== '' || $statusFilter !== 'ALL' || $typeFilter !== 'ALL' ? 'Tidak ada application untuk filter ini' : 'Belum ada application' }}
            </h2>
            <p class="mt-2 mx-auto max-w-sm text-sm text-content-secondary">
                {{ $search !== '' || $statusFilter !== 'ALL' || $typeFilter !== 'ALL' ? 'Ubah kata kunci atau filter.' : 'Application yang ditambahkan akan tampil di sini berserta tim developernya.' }}
            </p>
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
            class="fixed left-1/2 top-1/2 z-50 w-full max-w-2xl max-h-[calc(100vh-32px)] overflow-y-auto -translate-x-1/2 -translate-y-1/2 rounded-xl border border-stroke-primary bg-background-primary p-6 shadow-lg"
            x-show="open"
            x-transition.opacity.duration.150ms
            role="dialog"
            aria-modal="true"
            aria-labelledby="applications-modal-title"
            @click.stop
        >
            <div class="flex items-start justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-content-primary" id="applications-modal-title">
                        {{ $editingId !== null ? 'Ubah Application' : 'Tambah Application' }}
                    </h2>
                    <p class="mt-1 text-sm text-content-secondary">
                        {{ $editingId !== null ? 'Perbarui data application dan tim developernya.' : 'Kode akan terisi otomatis setelah disimpan.' }}
                    </p>
                </div>
                <button type="button" class="text-content-tertiary hover:text-content-primary" wire:click="closeModal" aria-label="Tutup">
                    <i class="ti ti-x text-lg"></i>
                </button>
            </div>

            <form class="mt-6 space-y-5" wire:submit="save">
                <div class="ina-text-field">
                    <label for="application-name" class="ina-text-field__label">
                        Name
                        <span class="ina-text-field__required">*</span>
                    </label>
                    <div class="ina-text-field__wrapper ina-text-field__wrapper--size-md {{ $errors->has('name') ? 'ina-text-field__wrapper--status-error' : '' }}">
                        <input
                            type="text"
                            id="application-name"
                            wire:model="name"
                            class="ina-text-field__input"
                            placeholder="SIMDATA Pendapatan Daerah"
                            required
                        >
                    </div>
                    @error('name')
                        <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="ina-text-field">
                        <label for="application-abbreviation" class="ina-text-field__label">
                            Singkatan
                        </label>
                        <div class="ina-text-field__wrapper ina-text-field__wrapper--size-md {{ $errors->has('abbreviation') ? 'ina-text-field__wrapper--status-error' : '' }}">
                            <input
                                type="text"
                                id="application-abbreviation"
                                wire:model="abbreviation"
                                class="ina-text-field__input"
                                placeholder="SIMPELDA"
                            >
                        </div>
                        @error('abbreviation')
                            <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="ina-text-field">
                        <label for="application-requested" class="ina-text-field__label">
                            Requested By
                        </label>
                        <x-idds.select
                            wire-prop="requested_by"
                            :value="$requested_by"
                            :options="$this->requestedByOptions()"
                            placeholder="Pilih OPD..."
                            aria-label="Pilih OPD pengusul"
                            :error="$errors->has('requested_by')"
                        />
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="ina-text-field">
                        <label for="application-status" class="ina-text-field__label">
                            Status
                            <span class="ina-text-field__required">*</span>
                        </label>
                        <x-idds.select
                            wire-prop="status"
                            :value="$status"
                            :options="$this->statusOptions()"
                            placeholder="Pilih status..."
                            aria-label="Pilih status application"
                            :error="$errors->has('status')"
                        />
                        @error('status')
                            <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="ina-text-field">
                        <label for="application-type" class="ina-text-field__label">
                            Jenis Application
                            <span class="ina-text-field__required">*</span>
                        </label>
                        <x-idds.select
                            wire-prop="type"
                            :value="$type"
                            :options="$this->typeOptions()"
                            placeholder="Pilih jenis..."
                            aria-label="Pilih jenis application"
                            :error="$errors->has('type')"
                        />
                        @error('type')
                            <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div class="ina-text-field">
                        <label for="application-domain" class="ina-text-field__label">
                            Domain
                        </label>
                        <div class="ina-text-field__wrapper ina-text-field__wrapper--size-md {{ $errors->has('domain') ? 'ina-text-field__wrapper--status-error' : '' }}">
                            <input
                                type="text"
                                id="application-domain"
                                wire:model="domain"
                                class="ina-text-field__input"
                                placeholder="app.serangkab.go.id"
                            >
                        </div>
                        @error('domain')
                            <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="ina-text-field">
                        <label for="application-repo" class="ina-text-field__label">
                            Repo URL
                        </label>
                        <div class="ina-text-field__wrapper ina-text-field__wrapper--size-md {{ $errors->has('repo_url') ? 'ina-text-field__wrapper--status-error' : '' }}">
                            <input
                                type="text"
                                id="application-repo"
                                wire:model="repo_url"
                                class="ina-text-field__input"
                                placeholder="https://github.com/contoh/repo"
                            >
                        </div>
                        @error('repo_url')
                            <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="ina-text-field">
                        <label for="application-icon" class="ina-text-field__label">
                            Icon
                        </label>
                        <div class="ina-text-field__wrapper ina-text-field__wrapper--size-md {{ $errors->has('icon') ? 'ina-text-field__wrapper--status-error' : '' }}">
                            <input
                                type="file"
                                id="application-icon"
                                wire:model="icon"
                                accept="image/*"
                                class="ina-text-field__input file:mr-3 file:cursor-pointer file:rounded-md file:border-0 file:bg-background-secondary file:px-3 file:py-1 file:text-xs file:text-content-secondary"
                            >
                        </div>
                        <p class="mt-1.5 text-xs text-content-tertiary">
                            @if ($this->icon_path !== '' || $this->icon instanceof TemporaryUploadedFile)
                                Icon saat ini tersimpan — unggah baru untuk mengganti.
                            @endif
                        </p>
                        <button
                            type="button"
                            wire:click="removeIcon"
                            class="mt-1.5 text-xs font-medium text-negative-600 hover:underline"
                        >
                            Hapus icon
                        </button>
                        @error('icon')
                            <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div class="ina-text-field">
                        <label for="application-framework" class="ina-text-field__label">
                            Framework
                        </label>
                        <div class="ina-text-field__wrapper ina-text-field__wrapper--size-md {{ $errors->has('framework') ? 'ina-text-field__wrapper--status-error' : '' }}">
                            <input
                                type="text"
                                id="application-framework"
                                wire:model="framework"
                                class="ina-text-field__input"
                                placeholder="Laravel"
                            >
                        </div>
                        @error('framework')
                            <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="ina-text-field">
                        <label for="application-language" class="ina-text-field__label">
                            Language
                        </label>
                        <div class="ina-text-field__wrapper ina-text-field__wrapper--size-md {{ $errors->has('language') ? 'ina-text-field__wrapper--status-error' : '' }}">
                            <input
                                type="text"
                                id="application-language"
                                wire:model="language"
                                class="ina-text-field__input"
                                placeholder="PHP"
                            >
                        </div>
                        @error('language')
                            <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="ina-text-field">
                        <label for="application-database" class="ina-text-field__label">
                            Database
                        </label>
                        <div class="ina-text-field__wrapper ina-text-field__wrapper--size-md {{ $errors->has('database') ? 'ina-text-field__wrapper--status-error' : '' }}">
                            <input
                                type="text"
                                id="application-database"
                                wire:model="database"
                                class="ina-text-field__input"
                                placeholder="MySQL"
                            >
                        </div>
                        @error('database')
                            <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="ina-text-area">
                    <label for="application-description" class="ina-text-area__label">
                        Description
                    </label>
                    <div class="ina-text-area__wrapper ina-text-area__wrapper--size-sm {{ $errors->has('description') ? 'ina-text-area__wrapper--status-error' : '' }}">
                        <textarea
                            id="application-description"
                            wire:model="description"
                            class="ina-text-area__input"
                            placeholder="Deskripsi singkat application..."
                        ></textarea>
                    </div>
                    @error('description')
                        <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Repeater: Developers --}}
                <div>
                    <p class="text-sm font-medium text-content-primary">Developers</p>
                    <div class="mt-2 space-y-3">
                        @foreach ($developerRows as $index => $row)
                            <div class="flex items-start gap-2">
                                <div class="flex-1">
                                    <x-idds.select
                                        wire-prop="developerRows.{{ $index }}.user_id"
                                        :value="$row['user_id']"
                                        :options="$this->developerUserOptions()"
                                        placeholder="Pilih user..."
                                        aria-label="Pilih developer"
                                    />
                                </div>
                                <div class="w-44 shrink-0">
                                    <x-idds.select
                                        wire-prop="developerRows.{{ $index }}.type"
                                        :value="$row['type']"
                                        :options="$this->developerTypeOptions()"
                                        placeholder="Jenis..."
                                        aria-label="Pilih jenis developer"
                                    />
                                </div>
                                <button
                                    type="button"
                                    wire:click="removeDeveloperRow({{ $index }})"
                                    class="mt-1 flex h-8 w-8 shrink-0 items-center justify-center rounded-md text-content-tertiary transition-colors duration-150 hover:bg-negative-50 hover:text-negative-600"
                                    aria-label="Hapus baris developer"
                                    title="Hapus"
                                >
                                    <i class="ti ti-trash text-base"></i>
                                </button>
                            </div>
                        @endforeach
                    </div>

                    <button
                        type="button"
                        wire:click="addDeveloperRow"
                        class="ina-button ina-button--secondary ina-button--sm mt-3"
                    >
                        <i class="ti ti-plus text-base" aria-hidden="true"></i>
                        Tambah Developer
                    </button>
                    @error('developers')
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
            aria-labelledby="applications-delete-title"
            @click.stop
        >
            @php $deleting = $this->deletingApplication(); @endphp
            <div class="flex items-start justify-between">
                <div class="flex items-start gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-negative-50 text-negative-600">
                        <i class="ti ti-alert-triangle text-lg"></i>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-content-primary" id="applications-delete-title">Hapus Application</h2>
                        <p class="mt-1 text-sm text-content-secondary">
                            Application <span class="font-medium text-content-primary">{{ $deleting?->name ?? '-' }}</span>
                            dengan {{ $deleting?->developers_count ?? 0 }} developer terdaftar akan dihapus
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