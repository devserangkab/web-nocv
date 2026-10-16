<?php

use App\Actions\Activities\CreateWorkLog;
use App\Enums\ActivityType;
use App\Models\Activity;
use App\Models\Application;
use App\Models\Location;
use App\Models\Media;
use App\Models\Panel;
use App\Models\Proxmox;
use App\Models\Rack;
use App\Models\Server;
use App\Models\User;
use App\Models\Vms;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    #[Layout('layouts::admin')]
    #[Title('Semua Aktivitas')]

    public string $search = '';

    public string $typeFilter = 'ALL';

    public string $categoryFilter = 'ALL';

    public string $userFilter = 'ALL';

    public string $entityFilter = 'ALL';

    public string $dateFrom = '';

    public string $dateTo = '';

    public bool $showWorkLogModal = false;

    public string $work_activity = '';

    public string $work_description = '';

    public string $work_category = '';

    public string $work_subject = '';

    public string $work_finished_at = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedTypeFilter(): void
    {
        $this->resetPage();
    }

    public function updatedCategoryFilter(): void
    {
        $this->resetPage();
    }

    public function updatedUserFilter(): void
    {
        $this->resetPage();
    }

    public function updatedEntityFilter(): void
    {
        $this->resetPage();
    }

    public function updatedDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatedDateTo(): void
    {
        $this->resetPage();
    }

    public function saveWorkLog(): void
    {
        [$type, $id] = array_pad(explode('|', $this->work_subject, 2), 2, null);
        $isAllowed = in_array($type, CreateWorkLog::allowedTypes(), true) && is_numeric($id);

        if ($this->work_subject === '') {
            $subject = null;
        } elseif ($isAllowed) {
            /** @var object|null $subject */
            $subject = $type::query()->find((int) $id);

            if ($subject === null) {
                $this->addError('work_subject', 'Entitas tidak ditemukan.');

                return;
            }
        } else {
            $this->addError('work_subject', 'Entitas tidak valid.');

            return;
        }

        try {
            app(CreateWorkLog::class)($subject, [
                'activity' => $this->work_activity,
                'description' => $this->work_description,
                'category' => $this->work_category,
                'finished_at' => $this->work_finished_at,
            ], auth()->id());
        } catch (\Illuminate\Validation\ValidationException $e) {
            foreach ($e->errors() as $attribute => $errors) {
                foreach ($errors as $message) {
                    $this->addError('work_'.$attribute, $message);
                }
            }

            return;
        }

        $this->showWorkLogModal = false;
        $this->resetWorkLogForm();
    }

    public function resetWorkLogForm(): void
    {
        $this->resetValidation();
        $this->work_activity = '';
        $this->work_description = '';
        $this->work_category = '';
        $this->work_subject = '';
        $this->work_finished_at = '';
    }

    public function closeWorkLogModal(): void
    {
        $this->showWorkLogModal = false;
        $this->resetWorkLogForm();
    }

    /**
     * Opsi kategori untuk filter & work log form (daftar dari enum).
     *
     * @return array<int, array{value: string, label: string}>
     */
    public function categoryFilterOptions(): array
    {
        return [
            [
                'value' => 'ALL',
                'label' => 'Semua Kategori',
            ],
            ...collect(\App\Enums\ActivityCategory::cases())
                ->map(fn (\App\Enums\ActivityCategory $category) => [
                    'value' => $category->value,
                    'label' => $category->value,
                ])
                ->all(),
        ];
    }

    /**
     * Opsi kategori untuk work log form.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public function categoryOptions(): array
    {
        return collect($this->categoryFilterOptions())
            ->reject(fn ($option) => $option['value'] === 'ALL')
            ->prepend(['value' => '', 'label' => 'Tanpa kategori'])
            ->values()
            ->all();
    }

    /**
     * Opsi pelaksana yang pernah tercatat pada aktivitas.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public function userFilterOptions(): array
    {
        $options = [
            [
                'value' => 'ALL',
                'label' => 'Semua Pelaksana',
            ],
        ];

        User::query()
            ->whereHas('activities')
            ->orderBy('name')
            ->get()
            ->each(fn (User $user) => $options[] = [
                'value' => (string) $user->id,
                'label' => $user->name,
            ]);

        return $options;
    }

    /**
     * Opsi filter entitas berdasarkan jenis subject.
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

    /**
     * Pilihan entitas untuk work log, tergrup per jenis.
     *
     * @return list<array{label: string|null, options: list<array{value: string, label: string}>}>
     */
    public function subjectOptions(): array
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

    public function activities(): LengthAwarePaginator
    {
        return Activity::query()
            ->with(['user', 'subject'])
            ->when($this->typeFilter !== 'ALL', fn ($query) => $query->where('type', $this->typeFilter))
            ->when($this->categoryFilter !== 'ALL', fn ($query) => $query->where('category', $this->categoryFilter))
            ->when($this->userFilter !== 'ALL', fn ($query) => $query->where('user_id', $this->userFilter))
            ->when($this->entityFilter !== 'ALL', fn ($query) => $query->where('subject_type', $this->entityFilter))
            ->when($this->dateFrom !== '', fn ($query) => $query->whereDate('created_at', '>=', $this->dateFrom))
            ->when($this->dateTo !== '', fn ($query) => $query->whereDate('created_at', '<=', $this->dateTo))
            ->when($this->search !== '', fn ($query) => $query->where(function ($query) {
                $query->where('activity', 'like', "%{$this->search}%")
                    ->orWhere('description', 'like', "%{$this->search}%")
                    ->orWhere('category', 'like', "%{$this->search}%")
                    ->orWhere('activity_id', 'like', "%{$this->search}%")
                    ->orWhereHas('user', fn ($user) => $user->where('name', 'like', "%{$this->search}%"))
                    ->orWhereHas('subject', function ($morph) {
                        // Pencarian nama entitas di semua jenis subject
                        $morph
                            ->orWhereHasMorph('subject', Application::class, fn ($app) => $app->where('name', 'like', "%{$this->search}%"))
                            ->orWhereHasMorph('subject', [Location::class, Rack::class, Proxmox::class, Vms::class, \App\Models\Nas::class, Panel::class], fn ($asset) => $asset->where('name', 'like', "%{$this->search}%"))
                            ->orWhereHasMorph('subject', Server::class, fn ($server) => $server->where('server_name', 'like', "%{$this->search}%"));
                    });
            }))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(15);
    }

    /**
     * URL detail entitas terkait aktivitas (morph), null bila tak tersedia.
     */
    public function subjectUrl(Activity $activity): ?string
    {
        $subject = $activity->subject;

        if ($subject === null) {
            return null;
        }

        return match ($activity->subject_type) {
            Application::class => route('admin.applications.show', $subject->application_id),
            Location::class => route('admin.server-rooms.show', $subject->location_id),
            Rack::class => route('admin.assets.racks.show', $subject->rack_id),
            Server::class => route('admin.assets.servers.show', $subject->server_id),
            Proxmox::class => route('admin.assets.proxmox.show', $subject->proxmox_id),
            Vms::class => route('admin.assets.vms.show', $subject->vm_id),
            \App\Models\Nas::class => route('admin.assets.nas.show', $subject->nas_id),
            Panel::class => route('admin.assets.panels.show', $subject->panel_id),
            default => null,
        };
    }
};
?>

<div>
    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-semibold text-content-primary">Semua Aktivitas</h1>
            <p class="mt-1 text-sm text-content-secondary">
                Audit log operasi aplikasi dan work log pekerjaan teknis, terpusat dalam satu daftar.
            </p>
        </div>
        @can('activities.create')
        <button type="button" wire:click="$set('showWorkLogModal', true)" class="ina-button ina-button--primary ina-button--md">
            <i class="ti ti-clipboard-text text-lg" aria-hidden="true"></i>
            Catat Work Log
        </button>
        @endcan
    </div>

    {{-- Filter baris 1: jenis & kategori; baris 2: pelaksana & entitas; search & tanggal --}}
    <div class="mt-6 flex flex-col gap-3 xl:flex-row xl:items-start xl:justify-between">
        <div class="flex flex-col gap-3 lg:flex-row">
            <div class="w-36">
                <x-idds.select
                    wire-prop="typeFilter"
                    :value="$typeFilter"
                    :options="collect([
                        ['value' => 'ALL', 'label' => 'Semua Jenis'],
                        ['value' => '{{ App\Enums\ActivityType::Audit->value }}', 'label' => '{{ App\Enums\ActivityType::Audit->label() }}'],
                        ['value' => '{{ App\Enums\ActivityType::Work->value }}', 'label' => '{{ App\Enums\ActivityType::Work->label() }}'],
                    ])->all()"
                    placeholder="Semua Jenis"
                    aria-label="Filter jenis aktivitas"
                />
            </div>
            <div class="w-48">
                <x-idds.select
                    wire-prop="categoryFilter"
                    :value="$categoryFilter"
                    :options="$this->categoryFilterOptions()"
                    placeholder="Semua Kategori"
                    aria-label="Filter kategori"
                />
            </div>
            <div class="w-44">
                <x-idds.select
                    wire-prop="userFilter"
                    :value="$userFilter"
                    :options="$this->userFilterOptions()"
                    placeholder="Semua Pelaksana"
                    aria-label="Filter pelaksana"
                />
            </div>
            <div class="w-44">
                <x-idds.select
                    wire-prop="entityFilter"
                    :value="$entityFilter"
                    :options="$this->entityFilterOptions()"
                    placeholder="Semua Entitas"
                    aria-label="Filter entitas"
                />
            </div>
        </div>

        <div class="flex flex-col gap-3 sm:flex-row">
            <div class="w-36">
                <label for="act-date-from" class="sr-only">Dari tanggal</label>
                <div class="ina-text-field__wrapper ina-text-field__wrapper--size-md">
                    <input type="date" id="act-date-from" wire:model.live="dateFrom" class="ina-text-field__input" title="Dari tanggal">
                </div>
            </div>
            <div class="w-36">
                <label for="act-date-to" class="sr-only">Sampai tanggal</label>
                <div class="ina-text-field__wrapper ina-text-field__wrapper--size-md">
                    <input type="date" id="act-date-to" wire:model.live="dateTo" class="ina-text-field__input" title="Sampai tanggal">
                </div>
            </div>
            <div class="w-full max-w-xs">
                <div class="ina-input-search ina-input-search--size-md">
                    <div class="ina-input-search__wrapper">
                        <i class="ti ti-search ina-input-search__prefix-icon" aria-hidden="true"></i>
                        <input
                            type="search"
                            class="ina-input-search__input"
                            placeholder="Cari aktivitas, entitas, pelaksana..."
                            wire:model.live.debounce.300ms="search"
                            aria-label="Cari aktivitas"
                        >
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Table --}}
    @php $activities = $this->activities(); @endphp
    @if ($activities->count() > 0)
        <div class="mt-4 overflow-hidden rounded-lg border border-stroke-primary bg-background-primary">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-stroke-primary bg-background-secondary text-left text-[12px] font-medium text-content-secondary">
                            <th scope="col" class="h-10 px-4 font-medium">Waktu</th>
                            <th scope="col" class="h-10 px-4 font-medium">Ringkasan</th>
                            <th scope="col" class="h-10 px-4 font-medium">Entitas</th>
                            <th scope="col" class="h-10 px-4 font-medium">Pelaksana</th>
                            <th scope="col" class="h-10 px-4 font-medium">Jenis</th>
                            <th scope="col" class="h-10 px-4 font-medium">Kategori</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($activities as $activity)
                            <tr class="border-b border-stroke-primary transition-colors duration-150 h-14 last:border-b-0 hover:bg-neutral-50" wire:key="act-{{ $activity->id }}">
                                <td class="h-14 px-4 text-content-secondary">
                                    <span class="block text-[12px] font-medium text-content-primary">{{ $activity->created_at->format('d M Y H:i') }}</span>
                                    <span class="block text-[11px] text-content-tertiary">{{ $activity->created_at->diffForHumans() }}</span>
                                </td>
                                <td class="h-14 max-w-[24rem] px-4">
                                    <span class="block truncate font-medium text-content-primary" title="{{ $activity->activity }}">{{ $activity->activity }}</span>
                                    @if ($activity->description !== null)
                                        <span class="block truncate text-[11px] text-content-tertiary" title="{{ $activity->description }}">{{ $activity->description }}</span>
                                    @endif
                                </td>
                                <td class="h-14 px-4 text-content-secondary">
                                    @php $subjectUrl = $this->subjectUrl($activity); @endphp
                                    @if ($activity->subject !== null)
                                        <span class="block text-[11px] text-content-tertiary">{{ class_basename($activity->subject_type) }}</span>
                                        @if ($subjectUrl !== null)
                                            <a href="{{ $subjectUrl }}" wire:navigate class="max-w-[10rem] block truncate font-medium text-content-primary hover:text-guide-600" title="{{ $activity->subject->name ?? '' }}">{{ $activity->subject->name ?? '-' }}</a>
                                        @else
                                            <span class="max-w-[10rem] block truncate font-medium text-content-primary">{{ $activity->subject->name ?? '-' }}</span>
                                        @endif
                                    @else
                                        <span class="text-content-tertiary">-</span>
                                    @endif
                                </td>
                                <td class="h-14 px-4 text-content-secondary">
                                    {{ $activity->user?->name ?? 'Sistem' }}
                                </td>
                                <td class="h-14 px-4">
                                    <span class="ina-badge ina-badge--soft ina-badge--sm {{ $activity->type === App\Enums\ActivityType::Audit ? 'ina-badge--info' : 'ina-badge--brand' }}">
                                        {{ $activity->type->label() }}
                                    </span>
                                </td>
                                <td class="h-14 px-4 text-content-secondary">
                                    {{ $activity->category ?? '-' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-6">{{ $activities->links('vendor.pagination.tailwind') }}</div>
    @else
        {{-- Empty state (IDDS) --}}
        <div class="ina-card ina-card--stack mt-4 px-6 py-12 text-center">
            <x-asset.illustration name="{{ $search !== '' ? 'noresult' : 'nodata' }}" size="sm" />
            <h2 class="mt-4 text-base font-semibold text-content-primary">
                {{ $search !== '' || $typeFilter !== 'ALL' || $categoryFilter !== 'ALL' || $userFilter !== 'ALL' || $entityFilter !== 'ALL' || $dateFrom !== '' || $dateTo !== '' ? 'Tidak ada aktivitas untuk filter ini' : 'Belum ada aktivitas' }}
            </h2>
            <p class="mt-2 mx-auto max-w-sm text-sm text-content-secondary">
                {{ $search !== '' || $typeFilter !== 'ALL' || $categoryFilter !== 'ALL' || $userFilter !== 'ALL' || $entityFilter !== 'ALL' || $dateFrom !== '' || $dateTo !== '' ? 'Ubah kata kunci atau bersihkan filter.' : 'Audit log operasi CRUD dan work log pekerjaan teknis akan tampil di sini.' }}
            </p>
        </div>
    @endif

    {{-- Modal Work Log (IDDS Task Modal) --}}
    <div
        x-data="{ open: $wire.entangle('showWorkLogModal') }"
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
            aria-labelledby="worklog-modal-title"
            @click.stop
        >
            <div class="flex items-start justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-content-primary" id="worklog-modal-title">Catat Work Log</h2>
                    <p class="mt-1 text-sm text-content-secondary">Catatan pekerjaan teknis: pemeliharaan, deployment, gangguan, pemeriksaan, dan lainnya.</p>
                </div>
                <button type="button" class="text-content-tertiary hover:text-content-primary" wire:click="closeWorkLogModal" aria-label="Tutup">
                    <i class="ti ti-x text-lg"></i>
                </button>
            </div>

            <form class="mt-6 space-y-5" wire:submit="saveWorkLog">
                <div class="ina-text-field">
                    <label for="work-activity" class="ina-text-field__label">
                        Judul Aktivitas
                        <span class="ina-text-field__required">*</span>
                    </label>
                    <div class="ina-text-field__wrapper ina-text-field__wrapper--size-md {{ $errors->has('work_activity') ? 'ina-text-field__wrapper--status-error' : '' }}">
                        <input
                            type="text"
                            id="work-activity"
                            wire:model="work_activity"
                            class="ina-text-field__input"
                            placeholder="Pemeliharaan rutin server aplikasi"
                            required
                            autofocus
                        >
                    </div>
                    @error('work_activity')
                        <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="ina-text-field">
                        <label for="work-category" class="ina-text-field__label">
                            Kategori
                        </label>
                        <x-idds.select
                            wire-prop="work_category"
                            :value="$work_category"
                            :options="$this->categoryOptions()"
                            placeholder="Tanpa kategori..."
                            aria-label="Pilih kategori pekerjaan"
                            :error="$errors->has('work_category')"
                        />
                        @error('work_category')
                            <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="ina-text-field">
                        <label for="work-finished" class="ina-text-field__label">
                            Waktu Selesai
                        </label>
                        <div class="ina-text-field__wrapper ina-text-field__wrapper--size-md {{ $errors->has('work_finished_at') ? 'ina-text-field__wrapper--status-error' : '' }}">
                            <input
                                type="datetime-local"
                                id="work-finished"
                                wire:model="work_finished_at"
                                class="ina-text-field__input"
                            >
                        </div>
                        <p class="mt-1.5 text-xs text-content-tertiary">Kosongkan bila pekerjaan masih berjalan.</p>
                        @error('work_finished_at')
                            <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="ina-text-field">
                    <label for="work-subject" class="ina-text-field__label">
                        Entitas Terkait
                    </label>
                    <x-idds.select
                        wire-prop="work_subject"
                        :value="$work_subject"
                        :groups="$this->subjectOptions()"
                        placeholder="Tidak terkait entitas..."
                        aria-label="Pilih entitas terkait"
                        :error="$errors->has('work_subject')"
                    />
                    @error('work_subject')
                        <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="ina-text-area">
                    <label for="work-description" class="ina-text-area__label">
                        Deskripsi
                    </label>
                    <div class="ina-text-area__wrapper ina-text-area__wrapper--size-sm {{ $errors->has('work_description') ? 'ina-text-area__wrapper--status-error' : '' }}">
                        <textarea
                            id="work-description"
                            wire:model="work_description"
                            class="ina-text-area__input"
                            placeholder="Rincian pekerjaan: langkah, hasil, catatan..."
                        ></textarea>
                    </div>
                    @error('work_description')
                        <p class="mt-1.5 text-sm text-negative-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" class="ina-button ina-button--secondary ina-button--md" wire:click="closeWorkLogModal">
                        Batal
                    </button>
                    <button type="submit" class="ina-button ina-button--primary ina-button--md" wire:loading.attr="disabled">
                        Simpan Work Log
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
