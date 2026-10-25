<?php

use App\Enums\ApplicationStatus;
use App\Enums\HostingKind;
use App\Models\Activity;
use App\Models\Application;
use App\Models\Document;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    #[Layout('layouts::admin')]
    #[Title('Detail Application')]

    public Application $application;

    public function mount(string $applicationId): void
    {
        $this->application = Application::with(['developers.user', 'hostings.hostable'])
            ->where('application_id', $applicationId)
            ->firstOrFail();
    }

    public function documents(): LengthAwarePaginator
    {
        return Document::query()
            ->with(['addedBy', 'application'])
            ->where('documentable_type', Application::class)
            ->where('documentable_id', $this->application->id)
            ->orderBy('document_id')
            ->paginate(10, pageName: 'docs_page');
    }

    public function activities(): LengthAwarePaginator
    {
        return Activity::query()
            ->with('user')
            ->where('subject_type', Application::class)
            ->where('subject_id', $this->application->id)
            ->orderByDesc('id')
            ->paginate(10, pageName: 'acts_page');
    }
};
?>
<div>
    @php $documents = $this->documents(); @endphp
    @php $activities = $this->activities(); @endphp
    {{-- Header --}}
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div class="flex items-start gap-3">
            <a
                href="{{ route('admin.applications.index') }}"
                wire:navigate
                class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-md text-content-tertiary transition-colors duration-150 hover:bg-background-tertiary hover:text-content-primary"
                aria-label="Kembali ke daftar application"
                title="Kembali"
            >
                <i class="ti ti-arrow-left text-base"></i>
            </a>
            <div>
                <h1 class="flex items-center gap-3 text-xl font-semibold text-content-primary">
                    {{ $application->name }}
                    <span class="ina-badge ina-badge--soft ina-badge--neutral ina-badge--sm">{{ $application->application_id }}</span>
                </h1>
                <p class="mt-1 text-sm text-content-secondary">
                    Detail application beserta informasi terkait.
                </p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            @if ($application->ownership !== null)
                <span class="ina-badge ina-badge--soft {{ $application->ownership === \App\Enums\ApplicationOwnership::InHouse ? 'ina-badge--success' : ($application->ownership === \App\Enums\ApplicationOwnership::OpenSource ? 'ina-badge--info' : 'ina-badge--warning') }} ina-badge--md" title="{{ $application->vendor_name !== null ? 'Vendor: '.$application->vendor_name : '' }}">
                    {{ $application->ownership->value }}
                </span>
            @endif
            <span class="ina-badge ina-badge--soft {{ $application->status === ApplicationStatus::Production ? 'ina-badge--success' : ($application->status === ApplicationStatus::Development ? 'ina-badge--info' : ($application->status === ApplicationStatus::Maintenance ? 'ina-badge--warning' : 'ina-badge--neutral')) }} ina-badge--md">
                {{ $application->status->value }}
            </span>
            <span class="ina-badge ina-badge--soft ina-badge--brand ina-badge--md">{{ $application->type->value }}</span>
        </div>
    </div>

    {{-- Card informasi --}}
    <div class="mt-6 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-6">
        {{-- Informasi umum --}}
        <div class="ina-card ina-card--stack p-6 md:col-span-1 xl:col-span-2">
            <div class="flex flex-col">
                <h2 class="text-base font-semibold text-content-primary">Informasi Umum</h2>
                <p class="mt-0.5 text-sm text-content-secondary">Informasi dasar mengenai application.</p>
            </div>

            <div class="border-stroke-primary pt-5">
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between gap-3">
                        <dt class="text-content-tertiary">Name</dt>
                        <dd class="text-right font-medium text-content-primary" title="{{ $application->name }}">{{ $application->name }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-content-tertiary">Singkatan</dt>
                        <dd class="font-medium text-content-primary text-right">{{ $application->abbreviation ?? '-' }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-content-tertiary">Jenis Application</dt>
                        <dd class="font-medium text-content-primary text-right">{{ $application->type->value }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-content-tertiary">Request</dt>
                        <dd class="text-right font-medium text-content-primary" title="{{ $application->requestedBy?->name ?? '' }}">
                            {{ $application->requestedBy !== null ? ($application->requestedBy->abbreviation.' ('.$application->requestedBy->institution_id.')') : '-' }}
                        </dd>
                    </div>
                </dl>
            </div>
        </div>

        {{-- Teknologi & akses --}}
        <div class="ina-card ina-card--stack p-6 md:col-span-1 xl:col-span-2">
            <div class="flex flex-col">
                <h2 class="text-base font-semibold text-content-primary">Teknologi &amp; Akses</h2>
                <p class="mt-0.5 text-sm text-content-secondary">Stack yang dipakai dan akses aplikasi.</p>
            </div>

            <div class="border-stroke-primary pt-5">
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between gap-3">
                        <dt class="text-content-tertiary">Framework</dt>
                        <dd class="font-medium text-content-primary text-right">
                            {{ $application->framework !== null ? trim($application->framework->value.' '.($application->framework_version ?? '')) : '-' }}
                        </dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-content-tertiary">Language</dt>
                        <dd class="font-medium text-content-primary text-right">
                            {{ $application->language !== null ? trim($application->language->value.' '.($application->language_version ?? '')) : '-' }}
                        </dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-content-tertiary">Database</dt>
                        <dd class="font-medium text-content-primary text-right">
                            {{ $application->database !== null ? trim($application->database->value.' '.($application->database_version ?? '')) : '-' }}
                        </dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-content-tertiary">Domain</dt>
                        <dd class="max-w-[60%] truncate font-medium text-content-primary" title="{{ $application->domain }}">{{ $application->domain ?? '-' }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="shrink-0 text-content-tertiary">Repo URL</dt>
                        <dd class="max-w-[60%] truncate font-medium">
                            @if ($application->repo_url !== null)
                                <a
                                    href="{{ $application->repo_url }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="text-guide-600 underline-offset-2 hover:underline"
                                    title="{{ $application->repo_url }}"
                                >{{ $application->repo_url }}</a>
                            @else
                                <span class="text-content-primary">-</span>
                            @endif
                        </dd>
                    </div>
                </dl>
            </div>
        </div>

        {{-- Developers --}}
        <div class="ina-card ina-card--stack p-6 md:col-span-1 xl:col-span-2">
            <div class="flex flex-col">
                <h2 class="text-base font-semibold text-content-primary">Developers</h2>
                <p class="mt-0.5 text-sm text-content-secondary">Pengembang yang terlibat dalam application.</p>
            </div>

            <div class="border-stroke-primary pt-5">
                @if ($application->developers->isNotEmpty())
                    <ul class="space-y-3">
                        @foreach ($application->developers as $developer)
                            <li class="flex items-center gap-3">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-full bg-guide-50 text-xs font-medium text-guide-600">
                                    @if ($developer->user?->avatar !== null)
                                        <img src="{{ asset('storage/'.$developer->user->avatar) }}" alt="{{ $developer->user?->name ?? '-' }}" class="h-9 w-9 object-cover">
                                    @else
                                        {{ collect(str_split($developer->user?->name ?? '?', 1))->take(2)->map(fn ($c) => strtoupper($c))->implode('') }}
                                    @endif
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-sm font-medium text-content-primary" title="{{ $developer->user?->name ?? '-' }}">
                                        {{ $developer->user?->name ?? '-' }}
                                    </span>
                                </span>
                                <span class="ina-badge ina-badge--soft ina-badge--neutral ina-badge--sm shrink-0">{{ $developer->type->value }}</span>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-sm text-content-tertiary">Belum ada developer.</p>
                @endif
            </div>
        </div>

        {{-- Description --}}
        <div class="ina-card ina-card--stack p-6 md:col-span-1 xl:col-span-2">
            <div class="flex flex-col">
                <h2 class="text-base font-semibold text-content-primary">Description</h2>
                <p class="mt-0.5 text-sm text-content-secondary">Deskripsi mengenai aplikasi.</p>
            </div>

            <div class="border-stroke-primary pt-5">
                @if ($application->description !== null)
                    <p class="whitespace-pre-line text-sm text-content-primary">{{ $application->description }}</p>
                @else
                    <p class="text-sm text-content-tertiary">Belum ada description.</p>
                @endif
            </div>
        </div>

        {{-- Card hosting --}}
        <div class="ina-card ina-card--stack p-6 md:col-span-2 xl:col-span-4">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="flex flex-col">
                    <h2 class="text-base font-semibold text-content-primary">Hosting</h2>
                    <p class="mt-0.5 text-sm text-content-secondary">Lokasi infrastruktur tempat application di-host.</p>
                </div>
                <span class="ina-badge ina-badge--soft ina-badge--neutral ina-badge--sm">{{ $application->hostings->count() }} host</span>
            </div>

            <div class="border-stroke-primary pt-5">
                @if ($application->hostings->isNotEmpty())
                    <div class="overflow-hidden rounded-lg border border-stroke-primary bg-background-primary">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-stroke-primary bg-background-secondary text-left text-[12px] font-medium text-content-secondary">
                                    <th scope="col" class="h-10 px-4 font-medium">Jenis</th>
                                    <th scope="col" class="h-10 px-4 font-medium">Host</th>
                                    <th scope="col" class="h-10 px-4 font-medium">IP</th>
                                    <th scope="col" class="h-10 px-4 font-medium">OS</th>
                                    <th scope="col" class="h-10 px-4 font-medium">Keterangan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($application->hostings as $hosting)
                                    @php
                                        $host = $hosting->hostable;
                                        $kind = $hosting->kind();
                                        $hostName = $host !== null
                                            ? ($kind === HostingKind::Server ? $host->server_name : $host->name) ?? $hosting->name
                                            : $hosting->name;
                                        $hostUrl = $host !== null && $kind !== null ? match ($kind) {
                                            HostingKind::Server => route('admin.assets.servers.show', $host->server_id),
                                            HostingKind::Panel => route('admin.assets.panels.show', $host->panel_id),
                                            HostingKind::Vm => route('admin.assets.vms.show', $host->vm_id),
                                            default => null,
                                        } : null;
                                        $ip = $kind === HostingKind::Vm ? $host->local_ip
                                            : ($kind === HostingKind::Server ? $host->static_ip : null);
                                    @endphp
                                    <tr class="border-b border-stroke-primary transition-colors duration-150 h-14 last:border-b-0 hover:bg-neutral-50">
                                        <td class="h-14 px-4">
                                            <span class="ina-badge ina-badge--soft ina-badge--sm {{ $kind === HostingKind::Vm ? 'ina-badge--info' : ($kind === HostingKind::Server ? 'ina-badge--neutral' : ($kind === HostingKind::Panel ? 'ina-badge--brand' : 'ina-badge--warning')) }}">
                                                {{ $kind?->label() ?? 'Eksternal' }}
                                            </span>
                                        </td>
                                        <td class="h-14 max-w-xs truncate px-4 font-medium text-content-primary">
                                            @if ($hostUrl !== null)
                                                <a href="{{ $hostUrl }}" wire:navigate class="block truncate text-content-primary hover:text-guide-600" title="{{ $hostName ?? '-' }}">{{ $hostName ?? '-' }}</a>
                                            @else
                                                <span title="{{ $hostName ?? '-' }}">{{ $hostName ?? '-' }}</span>
                                            @endif
                                        </td>
                                        <td class="h-14 px-4 text-content-secondary">{{ $ip ?? '-' }}</td>
                                        <td class="h-14 max-w-[160px] truncate px-4 text-content-secondary" title="{{ $host?->os ?? '' }}">{{ $host?->os ?? '-' }}</td>
                                        <td class="h-14 max-w-xs truncate px-4 text-content-secondary" title="{{ $hosting->note ?? '-' }}">{{ $hosting->note ?? '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="flex items-center gap-3 rounded-lg border border-stroke-primary bg-background-secondary px-4 py-6">
                        <i class="ti ti-server text-xl text-content-tertiary" aria-hidden="true"></i>
                        <div>
                            <p class="text-sm font-medium text-content-primary">Belum ada info hosting</p>
                            <p class="text-xs text-content-tertiary">Lokasi infrastruktur application ini akan tampil di sini.</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Card activities (timeline) --}}
        <div class="ina-card ina-card--stack p-6 md:col-span-2 xl:col-span-3 h-fit">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="flex flex-col">
                    <h2 class="text-base font-semibold text-content-primary">Activities</h2>
                    <p class="mt-0.5 text-sm text-content-secondary">Riwayat aktivitas pada application ini.</p>
                </div>
                <span class="ina-badge ina-badge--soft ina-badge--neutral ina-badge--sm">{{ $activities->total() }} aktivitas</span>
            </div>

            <div class="border-stroke-primary pt-5">
                @if ($activities->count() > 0)
                    <ol class="relative ml-2 space-y-6 border-l-2 border-stroke-primary sm:ml-3">
                        @foreach ($activities as $activity)
                            <li class="relative pl-6 sm:pl-7">
                                <span class="absolute -left-[10px] top-0.5 flex h-4 w-4 items-center justify-center rounded-full border-2 border-background-primary {{ $activity->finished_at !== null ? 'bg-guide-600' : 'bg-neutral-400' }}"></span>
                                <div class="flex items-center gap-3">
                                    <span class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-full bg-guide-50 text-xs font-medium text-guide-600">
                                        @if ($activity->user?->avatar !== null)
                                            <img src="{{ asset('storage/'.$activity->user->avatar) }}" alt="{{ $activity->user?->name ?? '-' }}" class="h-9 w-9 object-cover">
                                        @else
                                            {{ collect(str_split($activity->user?->name ?? '?', 1))->take(2)->map(fn ($c) => strtoupper($c))->implode('') }}
                                        @endif
                                    </span>
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-sm font-medium text-content-primary" title="{{ $activity->user?->name ?? '-' }}">{{ $activity->user?->name ?? '-' }}</p>
                                        <p class="text-xs text-content-tertiary">{{ $activity->finished_at?->format('d M Y') ?? 'Masih berjalan' }}</p>
                                    </div>
                                    <span class="ina-badge ina-badge--soft ina-badge--sm shrink-0 {{ $activity->finished_at !== null ? 'ina-badge--success' : 'ina-badge--warning' }}">
                                        {{ $activity->finished_at !== null ? 'Selesai' : 'Berjalan' }}
                                    </span>
                                </div>
                                <p class="mt-1.5 text-sm font-semibold text-content-primary">{{ $activity->activity }}</p>

                                @if ($activity->description !== null)
                                    <p class="mt-1 text-sm text-content-secondary">{{ $activity->description }}</p>
                                @endif
                            </li>
                        @endforeach
                    </ol>

                    <div class="mt-6">{{ $activities->links('vendor.pagination.tailwind') }}</div>
                @else
                    <div class="flex items-center gap-3 rounded-lg border border-stroke-primary bg-background-secondary px-4 py-6">
                        <i class="ti ti-list-check text-xl text-content-tertiary" aria-hidden="true"></i>
                        <div>
                            <p class="text-sm font-medium text-content-primary">Belum ada aktivitas</p>
                            <p class="text-xs text-content-tertiary">Riwayat aktivitas application ini akan tampil di sini.</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Card dokumen --}}
        <div class="ina-card ina-card--stack p-6 md:col-span-2 xl:col-span-3 h-fit">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="flex flex-col">
                    <h2 class="text-base font-semibold text-content-primary">Documents</h2>
                    <p class="mt-0.5 text-sm text-content-secondary">Dokumen terkait application.</p>
                </div>
                <span class="ina-badge ina-badge--soft ina-badge--neutral ina-badge--sm">{{ $documents->total() }} dokumen</span>
            </div>

            <div class="border-stroke-primary pt-5">
                @if ($documents->count() > 0)
                    <div class="overflow-hidden rounded-lg border border-stroke-primary bg-background-primary">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-stroke-primary bg-background-secondary text-left text-[12px] font-medium text-content-secondary">
                                    <th scope="col" class="h-10 px-4 font-medium">Nama</th>
                                    <th scope="col" class="h-10 px-4 font-medium">Diunggah Oleh</th>
                                    <th scope="col" class="h-10 px-4 font-medium">Tanggal</th>
                                    <th scope="col" class="h-10 px-4 font-medium text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($documents as $document)
                                    <tr class="border-b border-stroke-primary transition-colors duration-150 h-14 last:border-b-0 hover:bg-neutral-50">
                                        <td class="h-14 max-w-xs truncate px-4 font-medium text-content-primary" title="{{ $document->name }}">{{ $document->name }}</td>
                                        <td class="h-14 max-w-xs truncate px-4 text-content-secondary" title="{{ $document->addedBy?->email ?? '-' }}">{{ $document->addedBy?->name ?? '-' }}</td>
                                        <td class="h-14 px-4 text-content-secondary">{{ $document->created_at->format('d M Y') }}</td>
                                        <td class="h-14 px-4">
                                            <div class="flex items-center justify-end">
                                                @if ($document->document !== null)
                                                    <a
                                                        href="{{ route('admin.documents.download', $document->document_id) }}"
                                                        class="ina-button ina-button--secondary ina-button--sm"
                                                    >
                                                        <i class="ti ti-download text-base" aria-hidden="true"></i>
                                                        Unduh
                                                    </a>
                                                @else
                                                    <span class="text-content-tertiary">-</span>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-6">{{ $documents->links('vendor.pagination.tailwind') }}</div>
                @else
                    <div class="flex items-center gap-3 rounded-lg border border-stroke-primary bg-background-secondary px-4 py-6">
                        <i class="ti ti-folder-open text-xl text-content-tertiary" aria-hidden="true"></i>
                        <div>
                            <p class="text-sm font-medium text-content-primary">Belum ada dokumen</p>
                            <p class="text-xs text-content-tertiary">File terkait application ini akan tampil di sini setelah diunggah.</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
