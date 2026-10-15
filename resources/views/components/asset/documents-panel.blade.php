{{-- Panel tab Dokumentasi pada halaman detail entitas --}}
@props([
    'documents',
    'name' => 'entitas',
    'addHref' => null,
])

<div class="ina-card ina-card--stack p-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-base font-semibold text-content-primary">Dokumentasi</h2>
            <p class="mt-0.5 text-sm text-content-secondary">Dokumen yang terkait {{ $name }}.</p>
        </div>
        @if ($addHref)
            <a href="{{ $addHref }}" wire:navigate class="ina-button ina-button--primary ina-button--sm">
                <i class="ti ti-upload text-base" aria-hidden="true"></i>
                Unggah
            </a>
        @endif
    </div>

    @if ($documents->isNotEmpty())
        <div class="overflow-x-auto">
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
                        <tr class="border-b border-stroke-primary transition-colors duration-150 h-14 last:border-b-0 hover:bg-neutral-50" wire:key="doc-{{ $document->id }}">
                            <td class="h-14 max-w-xs truncate px-4 font-medium text-content-primary" title="{{ $document->name }}">{{ $document->name }}</td>
                            <td class="h-14 max-w-xs truncate px-4 text-content-secondary">{{ $document->addedBy?->name ?? '-' }}</td>
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
    @else
        {{-- Empty state --}}
        <div class="flex flex-col items-center px-6 py-10 text-center">
            <x-asset.illustration name="nodata" size="sm" />
            <p class="mt-4 text-sm font-medium text-content-primary">Belum ada dokumen terkait</p>
            <p class="mt-2 max-w-sm text-sm text-content-secondary">Dokumen terkait {{ $name }} akan tampil di sini. Unggah lewat tombol di atas atau melalui menu Dokumentasi.</p>
        </div>
    @endif
</div>
