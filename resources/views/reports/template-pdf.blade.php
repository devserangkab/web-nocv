<?php
/**
 * Template PDF Laporan Bulanan (dompdf):
 * Cover → Daftar Isi → Kata Pengantar → Uraian Kerja → Kesimpulan + TTD.
 * Footer halaman ditaruh via Canvas::page_script di controller (di-skip utk cover).
 * Catatan struktur: URL gambar sudah diganti data URI di controller (file privat).
 *
 * @var \App\Models\MonthlyReport $report
 * @var \Illuminate\Support\Collection $items
 */
$period = $report->periodeText();
$creator = $report->creator?->name ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $report->title }}</title>
    <style>
        @page { margin: 2.2cm 2.2cm 2.8cm; }

        /* Font core dompdf: family "times" (Times-Roman/Times-Bold) */
        body {
            margin: 0;
            padding: 0;
            font-family: 'Times New Roman', 'times', serif;
            font-size: 12pt;
            line-height: 1.5;
            color: #000000;
        }

        .page-break { page-break-after: always; }

        {{-- COVER --}}
        .cover { text-align: center; padding-top: 260px; }
        .cover-brand { font-size: 15pt; font-weight: bold; margin-bottom: 30px; }
        .cover-title { font-size: 20pt; font-weight: bold; margin: 0; }
        .cover-subtitle { font-size: 14pt; margin-top: 10px; }
        .cover-meta { font-size: 12pt; margin-top: 50px; }

        {{-- ISIAN --}}
        .section-title { font-size: 13pt; font-weight: bold; margin: 0 0 12px; }
        .toc-row { margin: 6px 0; }
        .toc-nested { margin-left: 22px; }
        .generated-lists { margin: 6px 0 6px 22px; padding: 0; }

        .item-title { font-size: 12pt; font-weight: bold; margin: 18px 0 6px; }

        .report-body p { text-align: justify; margin: 0 0 10px; }
        .report-body img { max-width: 100%; height: auto; margin: 12px 0; }
        .report-body table { width: 100%; border-collapse: collapse; margin: 12px 0; font-size: 12pt; }
        .report-body table th, .report-body table td { border: 1px solid #000000; padding: 6px 10px; vertical-align: top; }
        .report-body table th { font-weight: bold; }

        .signature { margin-top: 64px; text-align: right; }
        .signature .city { margin-bottom: 2px; }
        .signature .spare { height: 60px; }
        .signature .name { font-weight: bold; text-decoration: underline; display: inline-block; padding-top: 2px; min-width: 220px; }
    </style>
</head>
<body>

{{-- COVER --}}
<div class="cover page-break">
    <div class="cover-brand">{{ config('app.name', 'Laravel') }}</div>

    <h1 class="cover-title">{{ $report->title }}</h1>
    <p class="cover-subtitle">Periode: {{ $period }}</p>

    <p class="cover-meta">
        Disusun oleh <strong>{{ $creator }}</strong><br>
        {{ now()->translatedFormat('d F Y') }}
    </p>
</div>

{{-- Daftar Isi --}}
<div class="page-break">
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

{{-- Kata Pengantar --}}
<div>
    <h1 class="section-title">Kata Pengantar</h1>
    <div class="report-body">
        {!! $report->introduction ?? '<p>Belum ada kata pengantar.</p>' !!}
    </div>
    <div class="page-break"></div>
</div>

{{-- Uraian Kerja --}}
@if ($items->count() > 0)
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

        <div class="page-break"></div>
    </div>
@endif

{{-- Kesimpulan + TTD --}}
<div>
    <h1 class="section-title">Kesimpulan</h1>
    <div class="report-body">
        {!! $report->conclusion ?? '<p>Belum ada kesimpulan.</p>' !!}
    </div>

    {{-- Tanda tangan oleh pembuat laporan saja --}}
    <div class="signature">
        <p class="city">{{ config('app.report_city', 'Serang') }}, {{ now()->translatedFormat('d F Y') }}</p>
        <p style="margin: 0;">Pembuat Laporan,</p>
        <div class="spare"></div>
        <span class="name">{{ $creator }}</span>
    </div>
</div>

</body>
</html>
