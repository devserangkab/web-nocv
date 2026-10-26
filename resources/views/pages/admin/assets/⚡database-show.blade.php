<?php

use App\Models\Activity;
use App\Models\Database;
use App\Models\DatabaseInstance;
use App\Models\Document;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new class extends Component
{
    use \App\Livewire\Concerns\InteractsWithDatabaseInstanceForm;

    use \App\Livewire\Concerns\InteractsWithDatabaseSchemaForm;

    use \App\Livewire\Concerns\InteractsWithDatabaseUserForm;

    #[Layout('layouts::admin')]
    #[Title('Detail Database')]

    public DatabaseInstance $instance;

    public string $tab = 'overview';

    public function mount(string $dbiId): void
    {
        $this->instance = DatabaseInstance::query()
            ->with(['server', 'vm.panel.server', 'panel.server', 'schemas'])
            ->where('dbi_id', $dbiId)
            ->firstOrFail();

        $this->tab = request()->query('tab', 'overview');
    }

    public function tabs(): array
    {
        return [
            ['key' => 'overview', 'label' => 'Overview', 'icon' => 'ti-info-circle'],
            ['key' => 'relations', 'label' => 'Relasi', 'icon' => 'ti-topology-star'],
            ['key' => 'documents', 'label' => 'Dokumentasi', 'icon' => 'ti-folder', 'count' => Document::where('documentable_type', DatabaseInstance::class)->where('documentable_id', $this->instance->id)->count()],
            ['key' => 'activities', 'label' => 'Aktivitas', 'icon' => 'ti-activity', 'count' => Activity::where('subject_type', DatabaseInstance::class)->where('subject_id', $this->instance->id)->count()],
            ['key' => 'gallery', 'label' => 'Gallery', 'icon' => 'ti-photo', 'count' => $this->instance->medias()->count()],
        ];
    }
};
?>

<div>
    <x-asset.detail-header
        :back-route="route('admin.assets.databases.index')"
        :title="$instance->name"
        subtitle="Detail database server beserta host dan database di dalamnya."
    >
        @slot('right')
            @can('infrastructure.manage')
            <button
                type="button"
                wire:click="edit({{ $instance->id }})"
                class="ina-button ina-button--secondary ina-button--md"
            >
                <i class="ti ti-pencil text-base" aria-hidden="true"></i>
                Edit Database Server
            </button>
            @endcan
        @endslot
    </x-asset.detail-header>

    <x-asset.tab-nav
        :url="route('admin.assets.databases.show', ['dbiId' => $instance->dbi_id])"
        :current="$tab"
        :tabs="$this->tabs()"
    />

    {{-- Tab: Overview --}}
    @if ($tab === 'overview')
        <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
            <div class="ina-card ina-card--stack p-6">
                <h2 class="text-base font-semibold text-content-primary">Informasi Umum</h2>
                <p class="mt-0.5 text-sm text-content-secondary">Informasi dasar database server.</p>

                <div class="pt-5">
                    <dl class="space-y-2 text-sm">
                        <div class="flex justify-between gap-3">
                            <dt class="text-content-tertiary">Nama</dt>
                            <dd class="font-medium text-content-primary text-right">{{ $instance->name }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-content-tertiary">Teknologi</dt>
                            <dd class="font-medium text-content-primary text-right">{{ $instance->tech->value }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-content-tertiary">Versi</dt>
                            <dd class="font-medium text-content-primary text-right">{{ $instance->version ?? '-' }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <div class="ina-card ina-card--stack p-6">
                <h2 class="text-base font-semibold text-content-primary">Akses</h2>
                <p class="mt-0.5 text-sm text-content-secondary">Alamat dan kredensial root/admin instance.</p>

                <div class="pt-5">
                    <dl class="space-y-2 text-sm">
                        <div class="flex justify-between gap-3">
                            <dt class="text-content-tertiary">Internal Address</dt>
                            <dd class="font-medium text-content-primary text-right">{{ $instance->internal_address ?? '-' }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-content-tertiary">Username</dt>
                            <dd class="text-right font-medium text-content-primary">
                                @if (filled($instance->username))
                                    <span class="inline-flex items-center gap-1.5" x-data="{ copied: false }">
                                        <span>{{ $instance->username }}</span>
                                        <button
                                            type="button"
                                            class="text-content-tertiary transition-colors duration-150 hover:text-content-primary"
                                            @click="navigator.clipboard.writeText($refs.value.innerText.trim()); copied = true; setTimeout(() => copied = false, 1500)"
                                            aria-label="Salin username"
                                            title="Salin username"
                                        >
                                            <i class="ti text-base" :class="copied ? 'ti-check text-guide-600' : 'ti-copy'"></i>
                                        </button>
                                        <span x-ref="value" hidden>{{ $instance->username }}</span>
                                    </span>
                                @else
                                    -
                                @endif
                            </dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-content-tertiary">Password</dt>
                            <dd class="text-right font-medium text-content-primary">
                                @if (filled($instance->password))
                                    <span class="inline-flex items-center gap-1.5" x-data="{ show: false, copied: false }">
                                        <span x-show="!show">••••••••</span>
                                        <span x-show="show" x-cloak>{{ $instance->password }}</span>
                                        <button
                                            type="button"
                                            class="text-content-tertiary transition-colors duration-150 hover:text-content-primary"
                                            @click="show = !show"
                                            :aria-label="show ? 'Sembunyikan password' : 'Tampilkan password'"
                                        >
                                            <i class="ti text-base" :class="show ? 'ti-eye-off' : 'ti-eye'"></i>
                                        </button>
                                        <button
                                            type="button"
                                            class="text-content-tertiary transition-colors duration-150 hover:text-content-primary"
                                            @click="navigator.clipboard.writeText($refs.value.innerText.trim()); copied = true; setTimeout(() => copied = false, 1500)"
                                            aria-label="Salin password"
                                            title="Salin password"
                                        >
                                            <i class="ti text-base" :class="copied ? 'ti-check text-guide-600' : 'ti-copy'"></i>
                                        </button>
                                        <span x-ref="value" hidden>{{ $instance->password }}</span>
                                    </span>
                                @else
                                    -
                                @endif
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>

            <div class="ina-card ina-card--stack p-6">
                <h2 class="text-base font-semibold text-content-primary">Host</h2>
                <p class="mt-0.5 text-sm text-content-secondary">Tempat database server ini ter-install.</p>

                <div class="pt-5">
                    @if ($instance->panel !== null)
                        <div class="flex items-center gap-3 rounded-lg border border-stroke-primary p-4">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-guide-50 text-guide-600">
                                <i class="ti ti-app-window text-base"></i>
                            </span>
                            <div class="min-w-0 flex-1">
                                <a href="{{ route('admin.assets.panels.show', $instance->panel->panel_id) }}" wire:navigate class="block truncate text-sm font-medium text-content-primary hover:text-guide-600">
                                    {{ $instance->panel->name }}
                                </a>
                                <p class="truncate text-xs text-content-tertiary">Panel · {{ $instance->panel->panel_id }}</p>
                            </div>
                        </div>
                    @elseif ($instance->vm !== null)
                        <div class="flex items-center gap-3 rounded-lg border border-stroke-primary p-4">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-guide-50 text-guide-600">
                                <i class="ti ti-box-multiple-3 text-base"></i>
                            </span>
                            <div class="min-w-0 flex-1">
                                <a href="{{ route('admin.assets.vms.show', $instance->vm->vm_id) }}" wire:navigate class="block truncate text-sm font-medium text-content-primary hover:text-guide-600">
                                    {{ $instance->vm->name }}
                                </a>
                                <p class="truncate text-xs text-content-tertiary">VM · {{ $instance->vm->vm_id }}</p>
                            </div>
                        </div>
                    @elseif ($instance->server !== null)
                        <div class="flex items-center gap-3 rounded-lg border border-stroke-primary p-4">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-guide-50 text-guide-600">
                                <i class="ti ti-server text-base"></i>
                            </span>
                            <div class="min-w-0 flex-1">
                                <a href="{{ route('admin.assets.servers.show', $instance->server->server_id) }}" wire:navigate class="block truncate text-sm font-medium text-content-primary hover:text-guide-600">
                                    {{ $instance->server->server_name }}
                                </a>
                                <p class="truncate text-xs text-content-tertiary">Server · {{ $instance->server->server_id }}</p>
                            </div>
                        </div>
                    @else
                        <p class="py-4 text-center text-sm text-content-tertiary">Instance ini belum ditautkan ke host mana pun.</p>
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- Tab: Relasi --}}
    @if ($tab === 'relations')
        <div class="mt-4 grid grid-cols-1 gap-4 xl:grid-cols-2">
            <div class="ina-card ina-card--stack p-6">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="flex flex-col">
                        <h2 class="text-base font-semibold text-content-primary">Database</h2>
                        <p class="mt-0.5 text-sm text-content-secondary">Basis data yang berada di instance ini.</p>
                    </div>
                    @can('infrastructure.manage')
                    <button type="button" wire:click="openSchemaCreate" class="ina-button ina-button--secondary ina-button--sm">
                        <i class="ti ti-plus text-base" aria-hidden="true"></i>
                        Tambah Database
                    </button>
                    @endcan
                </div>

                <div class="pt-5">
                    @php $schemas = $this->instance->schemas()->orderBy('name')->get(); @endphp
                    @if ($schemas->isNotEmpty())
                        <ul class="divide-y divide-stroke-primary">
                            @foreach ($schemas as $database)
                                <li class="flex items-center gap-3 py-3" wire:key="db-{{ $database->id }}">
                                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-background-tertiary text-content-secondary">
                                        <i class="ti ti-database text-base"></i>
                                    </span>
                                    <div class="min-w-0 flex-1">
                                        <span class="block truncate text-sm font-medium text-content-primary" title="{{ $database->name }}">{{ $database->name }}</span>
                                        <span class="block truncate text-xs text-content-tertiary" title="{{ $database->note ?? '' }}">{{ $database->note ?? $database->db_id }}</span>
                                    </div>
                                    @can('infrastructure.manage')
                                    <button
                                        type="button"
                                        wire:click="editSchema({{ $database->id }})"
                                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-md text-content-tertiary transition-colors duration-150 hover:bg-background-tertiary hover:text-content-primary"
                                        aria-label="Ubah {{ $database->name }}"
                                        title="Ubah"
                                    >
                                        <i class="ti ti-pencil text-base"></i>
                                    </button>
                                    <button
                                        type="button"
                                        wire:click="askSchemaDelete({{ $database->id }})"
                                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-md text-content-tertiary transition-colors duration-150 hover:bg-negative-50 hover:text-negative-600"
                                        aria-label="Hapus {{ $database->name }}"
                                        title="Hapus"
                                    >
                                        <i class="ti ti-trash text-base"></i>
                                    </button>
                                    @endcan
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="py-6 text-center text-sm text-content-tertiary">Belum ada database di instance ini.</p>
                    @endif
                </div>
            </div>

            <div class="ina-card ina-card--stack p-6">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="flex flex-col">
                        <h2 class="text-base font-semibold text-content-primary">User Database</h2>
                        <p class="mt-0.5 text-sm text-content-secondary">Kredensial user di instance ini — aplikasi menautkannya lewat relasi.</p>
                    </div>
                    @can('infrastructure.manage')
                    <button type="button" wire:click="openUserCreate" class="ina-button ina-button--secondary ina-button--sm">
                        <i class="ti ti-plus text-base" aria-hidden="true"></i>
                        Tambah User
                    </button>
                    @endcan
                </div>

                <div class="pt-5">
                    @php $dbUsers = $this->instance->users()->orderBy('username')->get(); @endphp
                    @if ($dbUsers->isNotEmpty())
                        <ul class="divide-y divide-stroke-primary">
                            @foreach ($dbUsers as $dbUser)
                                @php
                                    $usageCount = \App\Models\ApplicationDatabase::query()->where('db_user_id', $dbUser->dbu_id)->count();
                                @endphp
                                <li class="flex items-center gap-3 py-3" wire:key="dbu-{{ $dbUser->id }}">
                                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-background-tertiary text-content-secondary">
                                        <i class="ti ti-user text-base"></i>
                                    </span>
                                    <div class="min-w-0 flex-1">
                                        <span class="block truncate text-sm font-medium text-content-primary" title="{{ $dbUser->username }}">{{ $dbUser->username }}</span>
                                        <span class="block truncate text-xs text-content-tertiary">
                                            {{ $dbUser->note ?? $dbUser->dbu_id }}{{ $usageCount > 0 ? ' · dipakai '.$usageCount.' application' : '' }}
                                        </span>
                                    </div>
                                    @can('infrastructure.manage')
                                    <button
                                        type="button"
                                        wire:click="editUser({{ $dbUser->id }})"
                                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-md text-content-tertiary transition-colors duration-150 hover:bg-background-tertiary hover:text-content-primary"
                                        aria-label="Ubah {{ $dbUser->username }}"
                                        title="Ubah"
                                    >
                                        <i class="ti ti-pencil text-base"></i>
                                    </button>
                                    <button
                                        type="button"
                                        wire:click="askUserDelete({{ $dbUser->id }})"
                                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-md text-content-tertiary transition-colors duration-150 hover:bg-negative-50 hover:text-negative-600"
                                        aria-label="Hapus {{ $dbUser->username }}"
                                        title="Hapus"
                                    >
                                        <i class="ti ti-trash text-base"></i>
                                    </button>
                                    @endcan
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="py-6 text-center text-sm text-content-tertiary">Belum ada user database di instance ini.</p>
                    @endif
                </div>
            </div>

            <div class="ina-card ina-card--stack p-6 xl:col-span-2">
                <h2 class="text-base font-semibold text-content-primary">Aplikasi Pemakai</h2>
                <p class="mt-0.5 text-sm text-content-secondary">Aplikasi yang memakai database di instance ini.</p>

                <div class="pt-5">
                    @php
                        $appUsages = Database::query()
                            ->with('applications')
                            ->where('dbi_id', $this->instance->dbi_id)
                            ->get()
                            ->flatMap(fn (Database $database) => $database->applications->map(fn ($application) => [
                                'application' => $application,
                                'database' => $database,
                            ]));
                    @endphp
                    @if ($appUsages->isNotEmpty())
                        <ul class="divide-y divide-stroke-primary">
                            @foreach ($appUsages as $usage)
                                <li class="flex items-center gap-3 py-3" wire:key="usage-{{ $usage['application']->id }}-{{ $usage['database']->id }}">
                                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-background-tertiary text-content-secondary">
                                        <i class="ti ti-apps text-base"></i>
                                    </span>
                                    <div class="min-w-0 flex-1">
                                        <a href="{{ route('admin.applications.show', $usage['application']->application_id) }}" wire:navigate class="block truncate text-sm font-medium text-content-primary hover:text-guide-600">
                                            {{ $usage['application']->name }}
                                        </a>
                                        <span class="block truncate text-xs text-content-tertiary">
                                            {{ $usage['database']->name }}
                                            {{ filled($usage['application']->pivot->dbUser?->username) ? ' · sebagai '.$usage['application']->pivot->dbUser->username : ' · kredensial instance (root)' }}
                                        </span>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="py-6 text-center text-sm text-content-tertiary">Belum ada application yang memakai database di instance ini.</p>
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- Tab: Dokumentasi --}}
    @if ($tab === 'documents')
        @php $documents = Document::query()->with('addedBy')->where('documentable_type', DatabaseInstance::class)->where('documentable_id', $this->instance->id)->orderBy('document_id')->get(); @endphp
        <div class="mt-4">
            <x-asset.documents-panel :documents="$documents" name="{{ $instance->name }}" :add-href="route('admin.documents.index', ['type' => DatabaseInstance::class, 'id' => $instance->id])" />
        </div>
    @endif

    {{-- Tab: Aktivitas --}}
    @if ($tab === 'activities')
        @php $activities = Activity::query()->with('user')->where('subject_type', DatabaseInstance::class)->where('subject_id', $this->instance->id)->orderByDesc('id')->get(); @endphp
        <div class="mt-4">
            <x-asset.activities-panel :activities="$activities" name="{{ $instance->name }}" />
        </div>
    @endif

    {{-- Tab: Gallery --}}
    @if ($tab === 'gallery')
        @php $medias = $this->instance->medias()->orderByDesc('id')->get(); @endphp
        <div class="mt-4">
            <x-asset.gallery-panel :medias="$medias" name="{{ $instance->name }}" :add-href="route('admin.galleries.index', ['type' => DatabaseInstance::class, 'id' => $instance->id])" />
        </div>
    @endif

    @include('pages.admin.assets.partials.database-instance-form-modal')
    @include('pages.admin.assets.partials.database-schema-form-modal')
    @include('pages.admin.assets.partials.database-user-form-modal')

    {{-- Modal Konfirmasi Hapus Schema --}}
    <div
        x-data="{ open: $wire.entangle('showSchemaDeleteModal') }"
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
            aria-labelledby="db-schema-delete-title"
            @click.stop
        >
            @php $deletingSchema = $this->deletingSchema(); @endphp
            <div class="flex items-start justify-between">
                <div class="flex items-start gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-negative-50 text-negative-600">
                        <i class="ti ti-alert-triangle text-lg"></i>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-content-primary" id="db-schema-delete-title">Hapus Database</h2>
                        <p class="mt-1 text-sm text-content-secondary">
                            Database <span class="font-medium text-content-primary">{{ $deletingSchema?->name ?? '-' }}</span>
                            akan dihapus dari instance ini, beserta kredensial aplikasi yang memakainya. Tindakan ini tidak bisa dibatalkan.
                        </p>
                        @error('deletingSchemaId')
                            <p class="mt-1.5 text-sm text-negative-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
                <button type="button" class="text-content-tertiary hover:text-content-primary" wire:click="$set('showSchemaDeleteModal', false)" aria-label="Tutup">
                    <i class="ti ti-x text-lg"></i>
                </button>
            </div>

            <div class="mt-6 flex justify-end gap-2">
                <button type="button" class="ina-button ina-button--secondary ina-button--md" wire:click="$set('showSchemaDeleteModal', false)">
                    Batal
                </button>
                <button
                    type="button"
                    class="ina-button ina-button--secondary ina-button--md bg-negative-600! text-white! hover:bg-negative-700!"
                    wire:loading.attr="disabled"
                    wire:click="deleteSchema"
                >
                    Hapus
                </button>
            </div>
        </div>
    </div>
</div>
