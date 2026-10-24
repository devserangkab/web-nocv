<?php

use App\Actions\Documents\DeleteDocument;
use App\Actions\Documents\StoreDocument;
use App\Models\Application;
use App\Models\Document;
use App\Models\Location;
use App\Models\Nas;
use App\Models\Panel;
use App\Models\Waf;
use App\Models\Proxmox;
use App\Models\Rack;
use App\Models\Server;
use App\Models\Vms;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

new class extends Component
{
    use \App\Livewire\Concerns\Notifies;

    use WithFileUploads;
    use WithPagination;

    #[Layout('layouts::admin')]
    #[Title('Dokumentasi')]

    public string $search = '';

    public string $entityFilter = 'ALL';

    public string $categoryFilter = 'ALL';

    public string $dateFrom = '';

    public string $dateTo = '';

    public bool $showUploadModal = false;

    public bool $showDeleteModal = false;

    public ?int $deletingId = null;

    public string $name = '';

    public string $category = '';

    public $file = null; // @var UploadedFile|null

    public string $documentable = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedEntityFilter(): void
    {
        $this->resetPage();
    }

    public function updatedCategoryFilter(): void
    {
        $this->resetPage();
    }

    public function mount(): void
    {
        // Tab detail entitas mengarah ke sini dengan entitas terpilih
        $type = request()->query('type');
        $id = request()->query('id');

        if ($type !== null && $id !== null && in_array($type, StoreDocument::allowedTypes(), true)) {
            $this->documentable = $type.'|'.$id;
            $this->showUploadModal = true;
        }
    }

    /**
     * Pilihan entitas untuk form/upload, tergrup per jenis.
     *
     * @return list<array{label: string|null, options: list<array{value: string, label: string}>}>
     */
    public function entityOptions(): array
    {
        $options = fn (string $class, string $nameColumn, iterable $models, string $label) => array_values(
            collect($models)
                ->map(fn ($model) => [
                    'value' => $class.'|'.$model->id,
                    'label' => $model->{$nameColumn},
                ])
                ->all()
        );

        return [
            [
                'label' => 'Application',
                'options' => $options(Application::class, 'name', Application::query()->orderBy('name')->get(), 'Application'),
            ],
            [
                'label' => 'Server Room',
                'options' => $options(Location::class, 'name', Location::query()->orderBy('location_id')->get(), 'Server Room'),
            ],
            [
                'label' => 'Rack',
                'options' => $options(Rack::class, 'name', Rack::query()->orderBy('rack_id')->get(), 'Rack'),
            ],
            [
                'label' => 'Server',
                'options' => $options(Server::class, 'server_name', Server::query()->orderBy('server_id')->get(), 'Server'),
            ],
            [
                'label' => 'Proxmox',
                'options' => $options(Proxmox::class, 'name', Proxmox::query()->orderBy('proxmox_id')->get(), 'Proxmox'),
            ],
            [
                'label' => 'Virtual Machine',
                'options' => $options(Vms::class, 'name', Vms::query()->orderBy('vm_id')->get(), 'Virtual Machine'),
            ],
            [
                'label' => 'NAS',
                'options' => $options(\App\Models\Nas::class, 'name', \App\Models\Nas::query()->orderBy('nas_id')->get(), 'NAS'),
            ],
            [
                'label' => 'Panel',
                'options' => $options(Panel::class, 'name', Panel::query()->orderBy('panel_id')->get(), 'Panel'),
            ],
            [
                'label' => 'WAF',
                'options' => $options(Waf::class, 'name', Waf::query()->orderBy('waf_id')->get(), 'WAF'),
            ],
        ];
    }

    public function saveUpload(): void
    {
        $documentable = $this->resolveDocumentable($this->documentable);

        try {
            app(StoreDocument::class)($documentable, [
                'name' => $this->name,
                'category' => $this->category,
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

        $this->notify('Dokumen berhasil diunggah.');
        $this->showUploadModal = false;
        $this->resetForm();
    }

    private function resolveDocumentable(string $value): ?object
    {
        if ($value === '') {
            return null;
        }

        [$type, $id] = array_pad(explode('|', $value, 2), 2, null);

        if (! in_array($type, StoreDocument::allowedTypes(), true) || ! is_numeric($id)) {
            return null;
        }

        return $type::query()->find((int) $id);
    }

    public function askDelete(int $id): void
    {
        $this->deletingId = $id;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        if ($this->deletingId !== null) {
            app(DeleteDocument::class)(Document::findOrFail($this->deletingId));
        }

        $this->notify('Dokumen berhasil dihapus.');
        $this->showDeleteModal = false;
        $this->deletingId = null;
    }

    public function closeUploadModal(): void
    {
        $this->showUploadModal = false;
        $this->resetForm();
    }

    public function resetForm(): void
    {
        $this->resetValidation();
        $this->name = '';
        $this->category = '';
        $this->file = null;
        $this->documentable = '';
    }

    /**
     * Opsi kategori dokumen (daftar dari enum).
     *
     * @return array<int, array{value: string, label: string}>
     */
    public function categoryOptions(): array
    {
        return collect([
            ['value' => '', 'label' => 'Tanpa kategori'],
            ...collect(\App\Enums\DocumentCategory::cases())
                ->map(fn (\App\Enums\DocumentCategory $category) => [
                    'value' => $category->value,
                    'label' => $category->value,
                ])
                ->all(),
        ])->all();
    }

    public function documents(): LengthAwarePaginator
    {
        return Document::query()
            ->with(['addedBy', 'documentable'])
            ->when($this->entityFilter !== 'ALL', fn ($query) => $query->where('documentable_type', $this->entityFilter))
            ->when($this->categoryFilter !== 'ALL', fn ($query) => $query->where('category', $this->categoryFilter))
            ->when($this->dateFrom !== '', fn ($query) => $query->whereDate('created_at', '>=', $this->dateFrom))
            ->when($this->dateTo !== '', fn ($query) => $query->whereDate('created_at', '<=', $this->dateTo))
            ->when($this->search !== '', fn ($query) => $query->where(function ($query) {
                $query->where('name', 'like', "%{$this->search}%")
                    ->orWhere('document_id', 'like', "%{$this->search}%")
                    ->orWhereHas('addedBy', fn ($user) => $user->where('name', 'like', "%{$this->search}%"))
                    ->orWhereHas('documentable', function ($morph) {
                        // Pencarian nama entitas di semua jenis documentable
                        $morph
                            ->orWhereHasMorph('documentable', Application::class, fn ($app) => $app->where('name', 'like', "%{$this->search}%"))
                            ->orWhereHasMorph('documentable', [Location::class, Rack::class, Proxmox::class, Vms::class, Panel::class, Waf::class], fn ($asset) => $asset->where('name', 'like', "%{$this->search}%"))
                            ->orWhereHasMorph('documentable', Server::class, fn ($server) => $server->where('server_name', 'like', "%{$this->search}%"));
                    });
            }))
            ->orderByDesc('created_at')
            ->paginate(10);
    }

    public function deletingDocument(): ?Document
    {
        return $this->deletingId !== null ? Document::with(['addedBy', 'documentable'])->find($this->deletingId) : null;
    }

    /**
     * Opsi filter entitas berdasarkan jenis documentable.
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
            [
                'value' => Waf::class,
                'label' => 'WAF',
            ],
        ];
    }
};
?>

<div>
    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-semibold text-content-primary">Dokumentasi</h1>
            <p class="mt-1 text-sm text-content-secondary">Pusat pengelolaan seluruh dokumen infrastruktur dan aplikasi.</p>
        </div>
        @can("documents.create")
        <button type="button" wire:click="$set('showUploadModal', true)" class="ina-button ina-button--primary ina-button--md">
            <i class="ti ti-upload text-lg" aria-hidden="true"></i>
            Unggah Dokumen
        </button>
        @endcan
    </div>

    {{-- Filter + search --}}
    <div class="mt-6 flex flex-col gap-3 xl:flex-row xl:items-start xl:justify-between">
        <div class="flex flex-col gap-3 sm:flex-row">
            <div class="w-44">
                <x-idds.select
                    wire-prop="entityFilter"
                    :value="$entityFilter"
                    :options="$this->entityFilterOptions()"
                    placeholder="Semua Entitas"
                    aria-label="Filter jenis entitas"
                />
            </div>
            <div class="w-44">
                <x-idds.select
                    wire-prop="categoryFilter"
                    :value="$categoryFilter"
                    :options="collect([['value' => 'ALL', 'label' => 'Semua Kategori']])->merge(collect(\App\Enums\DocumentCategory::cases())->map(fn (\App\Enums\DocumentCategory $c) => ['value' => $c->value, 'label' => $c->value]))->all()"
                    placeholder="Semua Kategori"
                    aria-label="Filter kategori dokumen"
                />
            </div>
            <div class="w-36">
                <label for="date-from" class="sr-only">Dari tanggal</label>
                <div class="ina-text-field__wrapper ina-text-field__wrapper--size-md">
                    <input type="date" id="date-from" wire:model.live="dateFrom" class="ina-text-field__input" title="Dari tanggal">
                </div>
            </div>
            <div class="w-36">
                <label for="date-to" class="sr-only">Sampai tanggal</label>
                <div class="ina-text-field__wrapper ina-text-field__wrapper--size-md">
                    <input type="date" id="date-to" wire:model.live="dateTo" class="ina-text-field__input" title="Sampai tanggal">
                </div>
            </div>
        </div>

        <div class="w-full max-w-sm">
            <div class="ina-input-search ina-input-search--size-md">
                <div class="ina-input-search__wrapper">
                    <i class="ti ti-search ina-input-search__prefix-icon" aria-hidden="true"></i>
                    <input
                        type="search"
                        class="ina-input-search__input"
                        placeholder="Cari nama, kode, entitas, atau uploader..."
                        wire:model.live.debounce.300ms="search"
                        aria-label="Cari dokumen"
                    >
                </div>
            </div>
        </div>
    </div>

    {{-- Table --}}
    @php $documents = $this->documents(); @endphp
    @if ($documents->count() > 0)
        <div class="mt-4 overflow-hidden rounded-lg border border-stroke-primary bg-background-primary">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-stroke-primary bg-background-secondary text-left text-[12px] font-medium text-content-secondary">
                            <th scope="col" class="h-10 px-4 font-medium">Judul</th>
                            <th scope="col" class="h-10 px-4 font-medium">Kategori</th>
                            <th scope="col" class="h-10 px-4 font-medium">Entitas Terkait</th>
                            <th scope="col" class="h-10 px-4 font-medium">Diunggah Oleh</th>
                            <th scope="col" class="h-10 px-4 font-medium">Tanggal</th>
                            <th scope="col" class="h-10 px-4 font-medium">File</th>
                            <th scope="col" class="h-10 px-4 font-medium text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($documents as $document)
                            <tr class="border-b border-stroke-primary transition-colors duration-150 h-14 last:border-b-0 hover:bg-neutral-50" wire:key="doc-{{ $document->id }}">
                                <td class="h-14 max-w-xs truncate px-4 font-medium text-content-primary" title="{{ $document->name }}">{{ $document->name }}</td>
                                <td class="h-14 px-4">
                                    @if ($document->category !== null)
                                        <span class="ina-badge ina-badge--soft ina-badge--info ina-badge--sm">{{ $document->category->value }}</span>
                                    @else
                                        <span class="text-content-tertiary">-</span>
                                    @endif
                                </td>
                                <td class="h-14 px-4 text-content-secondary">
                                    @if ($document->documentable !== null)
                                        @php
                                            $entityUrl = match ($document->documentable_type) {
                                                Application::class => route('admin.applications.show', $document->documentable->application_id),
                                                Location::class => route('admin.server-rooms.show', $document->documentable->location_id),
                                                Rack::class => route('admin.assets.racks.show', $document->documentable->rack_id),
                                                Server::class => route('admin.assets.servers.show', $document->documentable->server_id),
                                                Proxmox::class => route('admin.assets.proxmox.show', $document->documentable->proxmox_id),
                                                Vms::class => route('admin.assets.vms.show', $document->documentable->vm_id),
                                                Panel::class => route('admin.assets.panels.show', $document->documentable->panel_id),
                                                Waf::class => route('admin.assets.wafs.show', $document->documentable->waf_id),
                                                default => null,
                                            };
                                        @endphp
                                        <span class="block text-[12px] leading-snug">
                                            <span class="ina-badge ina-badge--soft ina-badge--neutral ina-badge--sm">{{ class_basename($document->documentable_type) }}</span>
                                            @if ($entityUrl)
                                                <a href="{{ $entityUrl }}" wire:navigate class="ml-1.5 font-medium text-content-primary hover:text-guide-600">
                                                    {{ $document->documentable->name ?? '-' }}
                                                </a>
                                            @else
                                                <span class="ml-1.5 font-medium text-content-primary">{{ $document->documentable->name ?? '-' }}</span>
                                            @endif
                                        </span>
                                    @elseif ($document->application !== null)
                                        <a href="{{ route('admin.applications.show', $document->application->application_id) }}" wire:navigate class="font-medium text-content-primary hover:text-guide-600">
                                            {{ $document->application->name }}
                                        </a>
                                    @else
                                        <span class="text-content-tertiary">-</span>
                                    @endif
                                </td>
                                <td class="h-14 max-w-[10rem] truncate px-4 text-content-secondary">{{ $document->addedBy?->name ?? '-' }}</td>
                                <td class="h-14 px-4 text-content-secondary">{{ $document->created_at->format('d M Y') }}</td>
                                <td class="h-14 px-4 text-content-secondary">
                                    @if (filled($document->document))
                                        <span class="text-[12px]">{{ strtoupper(pathinfo((string) $document->document, PATHINFO_EXTENSION)) }}</span>
                                    @else
                                        <span class="text-content-tertiary">-</span>
                                    @endif
                                </td>
                                <td class="h-14 px-4">
                                    @php
                                        $ext = strtolower(pathinfo((string) $document->document, PATHINFO_EXTENSION));
                                        $previewable = in_array($ext, ['pdf', 'png', 'jpg', 'jpeg', 'webp', 'txt', 'csv', 'md'], true);
                                    @endphp
                                    <div class="flex items-center justify-end gap-1">
                                        @if (filled($document->document) && $previewable)
                                            <a
                                                href="{{ route('admin.documents.download', ['documentId' => $document->document_id, 'preview' => 1]) }}"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                class="flex h-8 w-8 items-center justify-center rounded-md text-content-tertiary transition-colors duration-150 hover:bg-background-tertiary hover:text-content-primary"
                                                aria-label="Preview {{ $document->name }}"
                                                title="Preview"
                                            >
                                                <i class="ti ti-maximize text-base"></i>
                                            </a>
                                            <a
                                                href="{{ route('admin.documents.download', $document->document_id) }}"
                                                class="flex h-8 w-8 items-center justify-center rounded-md text-content-tertiary transition-colors duration-150 hover:bg-background-tertiary hover:text-content-primary"
                                                aria-label="Unduh {{ $document->name }}"
                                                title="Unduh"
                                            >
                                                <i class="ti ti-download text-base"></i>
                                            </a>
                                        @elseif (filled($document->document))
                                            <span class="flex items-center gap-1 pr-1 text-[11px] text-content-tertiary" title="Format ini tidak dapat dipreview, silakan unduh.">
                                                <i class="ti ti-file text-base"></i>
                                            </span>
                                            <a
                                                href="{{ route('admin.documents.download', $document->document_id) }}"
                                                class="flex h-8 w-8 items-center justify-center rounded-md text-content-tertiary transition-colors duration-150 hover:bg-background-tertiary hover:text-content-primary"
                                                aria-label="Unduh {{ $document->name }}"
                                                title="Unduh"
                                            >
                                                <i class="ti ti-download text-base"></i>
                                            </a>
                                        @else
                                            <span class="px-2">-</span>
                                        @endif
                                        @if (auth()->user()?->can('documents.delete') || (auth()->user()?->can('documents.delete-own') && $document->added_by === auth()->id()))
                                        <button
                                            type="button"
                                            wire:click="askDelete({{ $document->id }})"
                                            class="flex h-8 w-8 items-center justify-center rounded-md text-content-tertiary transition-colors duration-150 hover:bg-negative-50 hover:text-negative-600"
                                            aria-label="Hapus {{ $document->name }}"
                                            title="Hapus"
                                        >
                                            <i class="ti ti-trash text-base"></i>
                                        </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-6">{{ $documents->links('vendor.pagination.tailwind') }}</div>
    @else
        {{-- Empty state (IDDS) --}}
        <div class="ina-card ina-card--stack mt-4 px-6 py-12 text-center">
            <x-asset.illustration name="{{ $search !== '' ? 'noresult' : 'nodata' }}" size="sm" />
            <h2 class="mt-4 text-base font-semibold text-content-primary">
                {{ $search !== '' || $entityFilter !== 'ALL' || $categoryFilter !== 'ALL' || $dateFrom !== '' || $dateTo !== '' ? 'Tidak ada dokumen untuk filter ini' : 'Belum ada dokumen' }}
            </h2>
            <p class="mt-2 mx-auto max-w-sm text-sm text-content-secondary">
                {{ $search !== '' || $entityFilter !== 'ALL' || $categoryFilter !== 'ALL' || $dateFrom !== '' || $dateTo !== '' ? 'Ubah kata kunci atau bersihkan filter.' : 'Dokumen yang diunggah akan muncul di sini dan terkait ke entitas yang dipilih.' }}
            </p>
        </div>
    @endif

    {{-- Modal Upload (IDDS Task Modal) --}}
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
            aria-labelledby="doc-upload-title"
            @click.stop
        >
            <div class="flex items-start justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-content-primary" id="doc-upload-title">Unggah Dokumen</h2>
                    <p class="mt-1 text-sm text-content-secondary">Format didukung: pdf, doc(x), xls(x), ppt(x), md, txt, csv, dan gambar — maksimal 20 MB.</p>
                </div>
                <button type="button" class="text-content-tertiary hover:text-content-primary" wire:click="closeUploadModal" aria-label="Tutup">
                    <i class="ti ti-x text-lg"></i>
                </button>
            </div>

            <form class="mt-6 space-y-5" wire:submit="saveUpload">
                <div class="ina-text-field">
                    <label for="doc-name" class="ina-text-field__label">
                        Judul Dokumen
                        <span class="ina-text-field__required">*</span>
                    </label>
                    <div class="ina-text-field__wrapper ina-text-field__wrapper--size-md {{ $errors->has('name') ? 'ina-text-field__wrapper--status-error' : '' }}">
                        <input
                            type="text"
                            id="doc-name"
                            wire:model="name"
                            class="ina-text-field__input"
                            placeholder="Dokumen pengadaan server HPE"
                            required
                            autofocus
                        >
                    </div>
                    @error('name')
                        <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="ina-text-field">
                    <label for="doc-file" class="ina-text-field__label">
                        File
                        <span class="ina-text-field__required">*</span>
                    </label>
                    <div class="ina-text-field__wrapper ina-text-field__wrapper--size-md {{ $errors->has('file') ? 'ina-text-field__wrapper--status-error' : '' }}">
                        <input
                            type="file"
                            id="doc-file"
                            wire:model="file"
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

                <div class="grid grid-cols-2 gap-4">
                    <div class="ina-text-field">
                        <label for="doc-category" class="ina-text-field__label">
                            Kategori
                        </label>
                        <x-idds.select
                            wire-prop="category"
                            :value="$category"
                            :options="$this->categoryOptions()"
                            placeholder="Tanpa kategori..."
                            aria-label="Pilih kategori dokumen"
                        />
                        @error('category')
                            <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="ina-text-field">
                        <label for="doc-entity" class="ina-text-field__label">
                            Entitas Terkait
                        </label>
                        <x-idds.select
                            wire-prop="documentable"
                            :value="$documentable"
                            :groups="$this->entityOptions()"
                            placeholder="Tanpa entitas..."
                            aria-label="Pilih entitas terkait"
                        />
                        <p class="mt-1.5 text-xs text-content-tertiary">Dokumen dapat disertai dengan satu entitas terkait; pilih "Tanpa ..." bila berdiri sendiri.</p>
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" class="ina-button ina-button--secondary ina-button--md" wire:click="closeUploadModal">
                        Batal
                    </button>
                    <button type="submit" class="ina-button ina-button--primary ina-button--md" wire:loading.attr="disabled">
                        Unggah
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
            aria-labelledby="doc-delete-title"
            @click.stop
        >
            @php $deleting = $this->deletingDocument(); @endphp
            <div class="flex items-start justify-between">
                <div class="flex items-start gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-negative-50 text-negative-600">
                        <i class="ti ti-alert-triangle text-lg"></i>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-content-primary" id="doc-delete-title">Hapus Dokumen</h2>
                        <p class="mt-1 text-sm text-content-secondary">
                            Dokumen <span class="font-medium text-content-primary">{{ $deleting?->name ?? '-' }}</span> beserta file-nya akan dihapus. Tindakan ini tidak bisa dibatalkan.
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
