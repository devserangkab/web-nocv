<?php

use App\Actions\Media\DeleteMedia;
use App\Actions\Media\StoreMedia;
use App\Enums\MediaType;
use App\Models\Application;
use App\Models\Location;
use App\Models\Media;
use App\Models\Panel;
use App\Models\Proxmox;
use App\Models\Rack;
use App\Models\Server;
use App\Models\Vms;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

new class extends Component
{
    use WithFileUploads;
    use WithPagination;

    #[Layout('layouts::admin')]
    #[Title('Gallery')]

    public string $search = '';

    public string $typeFilter = 'ALL';

    public string $entityFilter = 'ALL';

    public bool $showUploadModal = false;

    public bool $showDeleteModal = false;

    public bool $showPreview = false;

    public ?int $previewingId = null;

    public ?int $deletingId = null;

    public string $title = '';

    public string $description = '';

    public $file = null; // @var UploadedFile|null

    public string $mediable = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedEntityFilter(): void
    {
        $this->resetPage();
    }

    public function updatedTypeFilter(): void
    {
        $this->resetPage();
    }

    public function mount(): void
    {
        // Tab Gallery detail entitas mengarah ke sini dengan entitas terpilih
        $type = request()->query('type');
        $id = request()->query('id');

        if ($type !== null && $id !== null && in_array($type, static::allowedTypes(), true)) {
            $this->mediable = $type.'|'.$id;
            $this->showUploadModal = true;
        }
    }

    /**
     * Daftar tipe entitas yang valid sebagai pemilik media.
     */
    public static function allowedTypes(): array
    {
        return [
            Application::class,
            Location::class,
            Rack::class,
            Server::class,
            Proxmox::class,
            Vms::class,
            Panel::class,
        ];
    }

    /**
     * Pilihan entitas untuk form, tergrup per jenis.
     *
     * @return list<array{label: string|null, options: list<array{value: string, label: string}>}>
     */
    public function entityOptions(): array
    {
        $options = fn (string $class, string $codeColumn, string $nameColumn, iterable $models) => array_values(
            collect($models)
                ->map(fn ($model) => [
                    'value' => $class.'|'.$model->id,
                    'label' => $model->{$nameColumn}.(' ('.($model->{$codeColumn} ?? '').')'),
                ])
                ->all()
        );

        return [
            [
                'label' => 'Application',
                'options' => $options(Application::class, 'application_id', 'name', Application::query()->orderBy('name')->get()),
            ],
            [
                'label' => 'Server Room',
                'options' => $options(Location::class, 'location_id', 'name', Location::query()->orderBy('location_id')->get()),
            ],
            [
                'label' => 'Rack',
                'options' => $options(Rack::class, 'rack_id', 'name', Rack::query()->orderBy('rack_id')->get()),
            ],
            [
                'label' => 'Server',
                'options' => $options(Server::class, 'server_id', 'server_name', Server::query()->orderBy('server_id')->get()),
            ],
            [
                'label' => 'Proxmox',
                'options' => $options(Proxmox::class, 'proxmox_id', 'name', Proxmox::query()->orderBy('proxmox_id')->get()),
            ],
            [
                'label' => 'Virtual Machine',
                'options' => $options(Vms::class, 'vm_id', 'name', Vms::query()->orderBy('vm_id')->get()),
            ],
            [
                'label' => 'NAS',
                'options' => $options(\App\Models\Nas::class, 'nas_id', 'name', \App\Models\Nas::query()->orderBy('nas_id')->get()),
            ],
            [
                'label' => 'Panel',
                'options' => $options(Panel::class, 'panel_id', 'name', Panel::query()->orderBy('panel_id')->get()),
            ],
        ];
    }

    public function saveUpload(): void
    {
        [$type, $id] = array_pad(explode('|', $this->mediable, 2), 2, null);
        $isAllowed = in_array($type, static::allowedTypes(), true) && is_numeric($id);

        if ($this->mediable !== '' && ! $isAllowed) {
            $this->addError('mediable', 'Entitas tidak valid');

            return;
        }

        if (! $isAllowed) {
            $this->addError('mediable', 'Pilih entitas terkait terlebih dahulu.');

            return;
        }

        /** @var object|null $mediable */
        $mediable = $type::query()->find((int) $id);

        if ($mediable === null) {
            $this->addError('mediable', 'Entitas tidak ditemukan.');

            return;
        }

        try {
            app(StoreMedia::class)($mediable, [
                'title' => $this->title,
                'description' => $this->description,
                'file' => $this->file,
            ], auth()->id());
        } catch (ValidationException $e) {
            foreach ($e->errors() as $attribute => $errors) {
                foreach ($errors as $message) {
                    $this->addError($attribute, $message);
                }
            }

            return;
        }

        $this->showUploadModal = false;
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
            app(DeleteMedia::class)(Media::findOrFail($this->deletingId));
        }

        $this->showDeleteModal = false;
        $this->deletingId = null;
    }

    public function openPreview(int $id): void
    {
        $this->previewingId = $id;
        $this->showPreview = true;
    }

    public function previewing(): ?Media
    {
        return $this->previewingId !== null
            ? Media::query()->with('mediable')->find($this->previewingId)
            : null;
    }

    public function closeUploadModal(): void
    {
        $this->showUploadModal = false;
        $this->resetForm();
    }

    public function resetForm(): void
    {
        $this->resetValidation();
        $this->title = '';
        $this->description = '';
        $this->file = null;
        $this->mediable = '';
    }

    public function medias(): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return Media::query()
            ->with(['mediable', 'addedBy'])
            ->when($this->typeFilter !== 'ALL', fn ($query) => $query->where('type', $this->typeFilter))
            ->when($this->entityFilter !== 'ALL', fn ($query) => $query->where('mediable_type', $this->entityFilter))
            ->when($this->search !== '', fn ($query) => $query->where(function ($query) {
                $query->where('title', 'like', "%{$this->search}%")
                    ->orWhere('description', 'like', "%{$this->search}%")
                    ->orWhere('media_id', 'like', "%{$this->search}%")
                    ->orWhereHas('addedBy', fn ($user) => $user->where('name', 'like', "%{$this->search}%"));
            }))
            ->orderByDesc('created_at')
            ->paginate(24);
    }

    public function deletingMedia(): ?Media
    {
        return $this->deletingId !== null ? Media::query()->find($this->deletingId) : null;
    }

    /**
     * Opsi filter jenis bukti.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public function typeFilterOptions(): array
    {
        return [
            [
                'value' => 'ALL',
                'label' => 'Semua Type',
            ],
            [
                'value' => MediaType::Image->value,
                'label' => 'Gambar',
            ],
            [
                'value' => MediaType::Video->value,
                'label' => 'Video',
            ],
        ];
    }

    /**
     * Opsi filter entitas berdasarkan jenis mediable.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public function entityFilterOptions(): array
    {
        return [
            [
                'value' => 'ALL',
                'label' => 'Semua Entitas',
            ],
            [
                'value' => Application::class,
                'label' => 'Application',
            ],
            [
                'value' => Location::class,
                'label' => 'Server Room',
            ],
            [
                'value' => Rack::class,
                'label' => 'Rack',
            ],
            [
                'value' => Server::class,
                'label' => 'Server',
            ],
            [
                'value' => Proxmox::class,
                'label' => 'Proxmox',
            ],
            [
                'value' => Vms::class,
                'label' => 'Virtual Machine',
            ],
            [
                'value' => \App\Models\Nas::class,
                'label' => 'NAS',
            ],
            [
                'value' => Panel::class,
                'label' => 'Panel',
            ],
        ];
    }
};
?>

<div>
    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-semibold text-content-primary">Gallery</h1>
            <p class="mt-1 text-sm text-content-secondary">Pusat pengelolaan foto dan bukti visual infrastruktur.</p>
        </div>
        <button type="button" wire:click="$set('showUploadModal', true)" class="ina-button ina-button--primary ina-button--md">
            <i class="ti ti-photo-up text-lg" aria-hidden="true"></i>
            Tambah Media
        </button>
    </div>

    {{-- Filter + search --}}
    <div class="mt-6 flex flex-col gap-3 xl:flex-row xl:items-start xl:justify-between">
        <div class="flex flex-col gap-3 sm:flex-row">
            <div class="w-40">
                <x-idds.select
                    wire-prop="typeFilter"
                    :value="$typeFilter"
                    :options="$this->typeFilterOptions()"
                    placeholder="Semua Type"
                    aria-label="Filter jenis media"
                />
            </div>
            <div class="w-44">
                <x-idds.select
                    wire-prop="entityFilter"
                    :value="$entityFilter"
                    :options="$this->entityFilterOptions()"
                    placeholder="Semua Entitas"
                    aria-label="Filter jenis entitas"
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
                        placeholder="Cari judul, deskripsi, kode media, uploader..."
                        wire:model.live.debounce.300ms="search"
                        aria-label="Cari media"
                    >
                </div>
            </div>
        </div>
    </div>

    {{-- Grid --}}
    @php $medias = $this->medias(); @endphp
    @if ($medias->count() > 0)
        <div class="mt-4 grid grid-cols-2 gap-4 sm:grid-cols-3 xl:grid-cols-5">
            @foreach ($medias as $media)
                <figure class="ina-card ina-card--stack overflow-hidden !gap-0 !p-0" wire:key="media-{{ $media->id }}">
                    @if ($media->isVideo())
                        <video src="{{ route('admin.media.show', $media->media_id) }}" controls preload="metadata" class="h-32 w-full bg-black object-contain"></video>
                    @else
                        <button type="button" wire:click="openPreview({{ $media->id }})" class="block h-32 w-full" aria-label="Preview {{ $media->title ?? $media->media_id }}">
                            <img
                                src="{{ route('admin.media.show', ['mediaId' => $media->media_id, 't' => 1]) }}"
                                alt="{{ $media->title ?? 'Media' }}"
                                loading="lazy"
                                class="h-32 w-full object-cover"
                            >
                        </button>
                    @endif

                    <figcaption class="space-y-0.5 px-3 py-2.5">
                        <p class="truncate text-xs font-medium text-content-primary" title="{{ $media->title ?? $media->media_id }}">{{ $media->title ?? $media->media_id }}</p>
                        @if ($media->mediable !== null)
                            @php
                                $entityUrl = match ($media->mediable_type) {
                                    Application::class => route('admin.applications.show', $media->mediable->application_id),
                                    Location::class => route('admin.server-rooms.show', $media->mediable->location_id),
                                    Rack::class => route('admin.assets.racks.show', $media->mediable->rack_id),
                                    Server::class => route('admin.assets.servers.show', $media->mediable->server_id),
                                    Proxmox::class => route('admin.assets.proxmox.show', $media->mediable->proxmox_id),
                                    Vms::class => route('admin.assets.vms.show', $media->mediable->vm_id),
                                    Panel::class => route('admin.assets.panels.show', $media->mediable->panel_id),
                                    default => null,
                                };
                            @endphp
                            <p class="truncate text-[11px] text-content-tertiary">
                                <span class="ina-badge ina-badge--soft ina-badge--neutral ina-badge--sm p-0">{{ class_basename($media->mediable_type) }}</span>
                                @if ($entityUrl)
                                    <a href="{{ $entityUrl }}" wire:navigate class="ml-1 hover:text-guide-600">{{ $media->mediable->name ?? '-' }}</a>
                                @else
                                    <span class="ml-1">{{ $media->mediable->name ?? '-' }}</span>
                                @endif
                            </p>
                        @endif
                        <p class="text-[11px] text-content-tertiary">{{ $media->addedBy?->name ?? 'Sistem' }} · {{ $media->created_at->format('d M Y') }}</p>
                    </figcaption>

                    <div class="flex items-center justify-end gap-1 border-t border-stroke-primary px-2 py-1.5">
                        <button
                            type="button"
                            wire:click="askDelete({{ $media->id }})"
                            class="flex h-7 w-7 items-center justify-center rounded-md text-content-tertiary transition-colors duration-150 hover:bg-negative-50 hover:text-negative-600"
                            aria-label="Hapus {{ $media->title ?? $media->media_id }}"
                            title="Hapus"
                        >
                            <i class="ti ti-trash text-base"></i>
                        </button>
                    </div>
                </figure>
            @endforeach
        </div>

        <div class="mt-6">{{ $medias->links('vendor.pagination.tailwind') }}</div>
    @else
        {{-- Empty state (IDDS) --}}
        <div class="ina-card ina-card--stack mt-4 items-center px-6 py-12 text-center">
            <x-asset.illustration name="{{ $search !== '' ? 'noresult' : 'nodata' }}" size="sm" />
            <h2 class="mt-4 text-base font-semibold text-content-primary">
                {{ $search !== '' || $typeFilter !== 'ALL' || $entityFilter !== 'ALL' ? 'Tidak ada media untuk filter ini' : 'Belum ada media' }}
            </h2>
            <p class="mt-2 max-w-sm text-sm text-content-secondary">
                {{ $search !== '' || $typeFilter !== 'ALL' || $entityFilter !== 'ALL' ? 'Ubah kata kunci atau bersihkan filter.' : 'Foto server room, rack, label server, instalasi, dan bukti pemeliharaan tertayang di sini.' }}
            </p>
            @if ($search === '' && $typeFilter === 'ALL' && $entityFilter === 'ALL')
                <button type="button" wire:click="$set('showUploadModal', true)" class="ina-button ina-button--primary ina-button--md mt-6">
                    <i class="ti ti-photo-up text-lg" aria-hidden="true"></i>
                    Tambah Media
                </button>
            @else
                <button type="button" wire:click="$set('search', ''); $set('typeFilter', 'ALL'); $set('entityFilter', 'ALL')" class="ina-button ina-button--secondary ina-button--md mt-6">
                    Bersihkan Filter
                </button>
            @endif
        </div>
    @endif

    {{-- Modal Upload --}}
    <div
        x-data="{ open: $wire.entangle('showUploadModal') }"
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
            aria-labelledby="media-upload-title"
            @click.stop
        >
            <div class="flex items-start justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-content-primary" id="media-upload-title">Tambah Media</h2>
                    <p class="mt-1 text-sm text-content-secondary">Format gambar (jpg, png, webp, gif) atau video (mp4, webm) — maksimal 50 MB.</p>
                </div>
                <button type="button" class="text-content-tertiary hover:text-content-primary" wire:click="closeUploadModal" aria-label="Tutup">
                    <i class="ti ti-x text-lg"></i>
                </button>
            </div>

            <form class="mt-6 space-y-5" wire:submit="saveUpload">
                <div class="ina-text-field">
                    <label for="media-entity" class="ina-text-field__label">
                        Entitas Terkait
                        <span class="ina-text-field__required">*</span>
                    </label>
                    <x-idds.select
                        wire-prop="mediable"
                        :value="$mediable"
                        :groups="$this->entityOptions()"
                        placeholder="Pilih entitas..."
                        aria-label="Pilih entitas pemilik media"
                        :error="$errors->has('mediable')"
                    />
                    @error('mediable')
                        <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="ina-text-field">
                    <label for="media-title" class="ina-text-field__label">
                        Judul
                    </label>
                    <div class="ina-text-field__wrapper ina-text-field__wrapper--size-md {{ $errors->has('title') ? 'ina-text-field__wrapper--status-error' : '' }}">
                        <input
                            type="text"
                            id="media-title"
                            wire:model="title"
                            class="ina-text-field__input"
                            placeholder="Kondisi existing rak NOC"
                            autofocus
                        >
                    </div>
                    @error('title')
                        <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="ina-text-area">
                    <label for="media-description" class="ina-text-area__label">
                        Deskripsi
                    </label>
                    <div class="ina-text-area__wrapper ina-text-area__wrapper--size-sm {{ $errors->has('description') ? 'ina-text-area__wrapper--status-error' : '' }}">
                        <textarea
                            id="media-description"
                            wire:model="description"
                            class="ina-text-area__input"
                            placeholder="Keterangan gambar/video, mis. kondisi existing, hasil pemeliharaan..."
                        ></textarea>
                    </div>
                    @error('description')
                        <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="ina-text-field">
                    <label for="media-file" class="ina-text-field__label">
                        File
                        <span class="ina-text-field__required">*</span>
                    </label>
                    <div class="ina-text-field__wrapper ina-text-field__wrapper--size-md {{ $errors->has('file') ? 'ina-text-field__wrapper--status-error' : '' }}">
                        <input
                            type="file"
                            id="media-file"
                            wire:model="file"
                            accept="image/jpeg,image/png,image/webp,image/gif,video/mp4,video/webm"
                            class="ina-text-field__input file:mr-3 file:cursor-pointer file:rounded-md file:border-0 file:bg-background-secondary file:px-3 file:py-1 file:text-xs file:text-content-secondary"
                            required
                        >
                    </div>
                    <p class="mt-1.5 flex items-center gap-1.5 text-xs text-content-tertiary" wire:loading wire:target="file">
                        <i class="ti ti-loader-2 animate-spin"></i>
                        Mengunggah file...
                    </p>
                    @error('file')
                        <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" class="ina-button ina-button--secondary ina-button--md" wire:click="closeUploadModal">
                        Batal
                    </button>
                    <button type="submit" class="ina-button ina-button--primary ina-button--md" wire:loading.attr="disabled">
                        Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal Preview (lightbox) --}}
    <div
        x-data="{ open: $wire.entangle('showPreview') }"
        x-on:keydown.escape.window="if (open) open = false"
        x-cloak
    >
        <div class="fixed inset-0 z-50 bg-black/80" x-show="open" x-transition.opacity @click="open = false" aria-hidden="true"></div>

        <div
            class="fixed left-1/2 top-1/2 z-50 w-full max-w-3xl max-h-[calc(100vh-32px)] -translate-x-1/2 -translate-y-1/2 rounded-xl border border-stroke-primary bg-background-primary p-4 shadow-lg"
            x-show="open"
            x-transition.opacity.duration.150ms
            role="dialog"
            aria-modal="true"
            aria-label="Preview media"
            @click.stop
        >
            @php $preview = $this->previewing(); @endphp
            @if ($preview !== null)
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="truncate text-sm font-semibold text-content-primary">{{ $preview->title ?? $preview->media_id }}</p>
                        @if ($preview->description !== null)
                            <p class="mt-0.5 line-clamp-2 text-xs text-content-tertiary">{{ $preview->description }}</p>
                        @endif
                    </div>
                    <button type="button" class="text-content-tertiary hover:text-content-primary" wire:click="$set('showPreview', false)" aria-label="Tutup">
                        <i class="ti ti-x text-lg"></i>
                    </button>
                </div>

                <div class="mt-3 flex justify-center">
                    @if ($preview->isVideo())
                        <video src="{{ route('admin.media.show', $preview->media_id) }}" controls autoplay class="max-h-[65vh] w-auto rounded-lg bg-black"></video>
                    @else
                        <img
                            src="{{ route('admin.media.show', $preview->media_id) }}"
                            alt="{{ $preview->title ?? 'Media' }}"
                            class="max-h-[65vh] w-auto rounded-lg"
                        >
                    @endif
                </div>

                <p class="mt-3 text-[11px] text-content-tertiary">
                    @if ($preview->mediable !== null)
                        {{ class_basename($preview->mediable_type) }} — {{ $preview->mediable->name ?? '-' }} ·
                    @endif
                    {{ $preview->addedBy?->name ?? 'Sistem' }} · {{ $preview->created_at->format('d M Y H:i') }}
                </p>
                <a
                    href="{{ route('admin.media.show', $preview->media_id) }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="mt-2 inline-flex items-center gap-1 text-xs font-medium text-guide-600 hover:underline"
                >
                    <i class="ti ti-external-link text-xs"></i>
                    Buka file asli
                </a>
            @endif
        </div>
    </div>

    {{-- Modal Konfirmasi Hapus --}}
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
            aria-labelledby="media-delete-title"
            @click.stop
        >
            <div class="flex items-start justify-between">
                <div class="flex items-start gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-negative-50 text-negative-600">
                        <i class="ti ti-alert-triangle text-lg"></i>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-content-primary" id="media-delete-title">Hapus Media</h2>
                        <p class="mt-1 text-sm text-content-secondary">
                            Media <span class="font-medium text-content-primary">{{ $this->deletingMedia()?->title ?? $this->deletingMedia()?->media_id ?? '-' }}</span> beserta filenya akan dihapus. Tindakan ini tidak bisa dibatalkan.
                        </p>
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
