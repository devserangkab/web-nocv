<?php
/** @var \App\Models\MonthlyReport $report */
/** @var \Illuminate\Support\Collection $items */
/** @var array $figures */
/** @var array $tables */
$period = $report->periodeText();
$now = now();
$creator = $report->creator?->name ?? 'Admin';
$footerText = config('app.report_footer', 'Dinas Komunikasi, Informatika, Statistik dan Persandian Kabupaten Serang');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $report->title }}</title>
    <style>
        /*
         * Kertas A4 dengan halaman bernama:
         *  - @page (isian umum): footer margin-box "instansi · Halaman N"
         *  - @page cover: tanpa footer
         * Margins: @page tetap; @media print memakai padding fisik sebagai
         * jaminan saat browser mengabaikan @page (khususnya pencetakan iframe).
         */
        @page {
            size: A4;
            margin: 2.2cm 2.2cm 2.6cm;

            @bottom-center {
                content: "{{ $footerText }}  ·  Halaman " counter(page);
                font-family: 'Times New Roman', 'Liberation Serif', serif;
                font-size: 10pt;
                color: #000000;
            }
        }

        @page cover {
            margin: 2.2cm;

            @bottom-center { content: none; }
        }

        /* Tipografi formal — seluruhnya hitam */
        html, body {
            margin: 0;
            padding: 0;
            font-family: 'Times New Roman', 'Liberation Serif', serif;
            font-size: 12pt;
            line-height: 1.5;
            color: #000000;
            background: #ffffff;
        }

        body * { color: #000000; }

        .page {
            padding: 2.2cm 2.2cm 2.8cm;
            page-break-after: always;
        }
        .page:last-child { page-break-after: auto; }

        .page--cover { page: cover; }

        {{-- ARTF: cover behavior without running footers --}}
        .cover {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            min-height: calc(100vh - 4cm);
        }
        .cover-brand { display: flex; align-items: center; gap: 10px; margin-bottom: 28px; }
        .cover-title { font-size: 20pt; font-weight: 700; margin: 0; }
        .cover-subtitle { margin-top: 10px; font-size: 14pt; }
        .cover-meta { margin-top: 48px; font-size: 12pt; }

        .section-title { font-size: 13pt; font-weight: 700; margin: 0 0 12px; }
        .toc-row { margin: 6px 0; }
        .toc-nested { margin-left: 22px; }
        .generated-lists { margin: 6px 0 6px 22px; padding: 0; }

        .item-title { font-size: 12pt; font-weight: 700; margin: 18px 0 6px; }

        .report-body p { text-align: justify; margin: 0 0 10px; }
        .report-body img { max-width: 100%; height: auto; margin: 12px 0; }
        .report-body table { width: 100%; border-collapse: collapse; margin: 12px 0; font-size: 12pt; }
        .report-body table th, .report-body table td { border: 1px solid #000000; padding: 6px 10px; vertical-align: top; }
        .report-body table th { font-weight: 700; }

        .signature { margin-top: 64px; text-align: right; }
        .signature .city { margin-bottom: 2px; }
        .signature .spare { height: 64px; }
        .signature .name { font-weight: 700; text-decoration: underline; display: inline-block; padding-top: 2px; min-width: 220px; }

        @media print {
            /* Margin fisik pada konten — jaminan saat @page diabaikan browser */
            .page { padding: 2.2cm 2.2cm 3.2cm; min-height: auto; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

{{-- Toolbar cetak (tidak ikut tercetak) --}}
<nav class="no-print" style="position: sticky; top: 0; z-index: 10; background:#f9fafb; border-bottom: 1px solid #d1d5db; padding: 10px 24px; display: flex; align-items: center; justify-content: space-between;">
    <span style="font-size: 13px; color: #6b7280; font-family: system-ui, sans-serif;">{{ $report->report_id }} · {{ $report->title }} · A4</span>
    <button onclick="window.print()" style="font-family: system-ui, sans-serif; font-size: 13px; padding: 8px 14px; background:#1c6053; color:#fff; border:0; border-radius:6px; cursor:pointer;">Cetak / Simpan PDF</button>
</nav>

{{-- COVER — tanpa footer/nomor halaman --}}
<section class="page page--cover">
    <div class="cover">
        <div class="cover-brand">
            <i class="ti ti-sitemap" style="font-size: 34px;"></i>
            <span style="font-size: 14pt; font-weight: 700;">{{ config('app.name', 'Laravel') }}</span>
        </div>

        <h1 class="cover-title">{{ $report->title }}</h1>
        <p class="cover-subtitle">Periode: {{ $period }}</p>

        <p class="cover-meta">
            Disusun oleh <strong>{{ $creator }}</strong><br>
            {{ $now->translatedFormat('d F Y') }}
        </p>
    </div>
</section>

{{-- Daftar Isi --}}
<section class="page">
    <div>
        <h1 class="section-title">Daftar Isi</h1>
        <p class="toc-row">Kata Pengantar</p>

        @if ($items->count() > 0)
            <p class="toc-row">Uraian Kerja</p>
            <div class="toc-nested">
                @foreach ($items as $item)
                    <p class="toc-row">{{ $item->sequence }}. {{ $item->title_snapshot }}</p>
                @endforeach
            </div>
        @endif

        <p class="toc-row">Kesimpulan</p>

        @if (count($figures) > 0)
            <h1 class="section-title" style="margin-top: 32px;">Daftar Gambar</h1>
            <ol class="generated-lists">
                @foreach ($figures as $fig)
                    <li>Gambar {{ $loop->iteration }} — {{ $fig['title'] }}</li>
                @endforeach
            </ol>
        @endif

        @if (count($tables) > 0)
            <h1 class="section-title" style="margin-top: 32px;">Daftar Tabel</h1>
            <ol class="generated-lists">
                @foreach ($tables as $tbl)
                    <li>Tabel {{ $loop->iteration }} — {{ $tbl['title'] }}</li>
                @endforeach
            </ol>
        @endif
    </div>
</section>

{{-- Kata Pengantar --}}
<section class="page">
    <div>
        <h1 class="section-title">Kata Pengantar</h1>
        <div class="report-body">
            {!! $report->introduction ?? '<p>Belum ada kata pengantar.</p>' !!}
        </div>
    </div>
</section>

{{-- Uraian Kerja --}}
@if ($items->count() > 0)
    <section class="page">
        <div>
            <h1 class="section-title">Uraian Kerja</h1>

            @foreach ($items as $item)
                <div>
                    <p class="item-title">{{ $item->sequence }}. {{ $item->title_snapshot }}</p>
                    <div class="report-body">
                        {!! $item->uraian ?? '<p>-</p>' !!}
                    </div>
                </div>
            @endforeach
        </div>
    </section>
@endif

{{-- Kesimpulan + TTD --}}
<section class="page">
    <div>
        <h1 class="section-title">Kesimpulan</h1>
        <div class="report-body">
            {!! $report->conclusion ?? '<p>Belum ada kesimpulan.</p>' !!}
        </div>
    </div>

    {{-- Tanda tangan oleh pembuat laporan saja --}}
    <div class="signature">
        <p class="city">{{ config('app.report_city', 'Serang') }}, {{ $now->translatedFormat('d F Y') }}</p>
        <p style="margin: 0;">Pembuat Laporan,</p>
        <div class="spare"></div>
        <span class="name">{{ $creator }}</span>
    </div>
</section>

</body>
</html>
