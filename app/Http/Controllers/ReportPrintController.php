<?php

namespace App\Http\Controllers;

use App\Models\MonthlyReport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ReportPrintController extends Controller
{
    /**
     * Versi PDF laporan bulanan (dompdf):
     * Cover → Daftar Isi → Kata Pengantar → Uraian Kerja → Kesimpulan → TTD.
     * Footer tiap lembar (kecuali cover): "{instansi} · Halaman N" via Canvas::page_script.
     */
    public function __invoke(string $reportId, Request $request)
    {
        $report = MonthlyReport::query()
            ->with(['items', 'creator'])
            ->where('report_id', $reportId)
            ->firstOrFail();

        $items = $report->items()->orderBy('sequence')->get();

        // Auto-generate referensi Gambar & Tabel dari konten HTML user
        $figures = [];
        $tables = [];

        foreach ($items as $item) {
            $html = strtolower((string) ($item->uraian ?? ''));
            $imgCount = substr_count($html, '<img');
            $tblCount = substr_count($html, '<table');

            for ($i = 0; $i < $imgCount; $i++) {
                $figures[] = ['title' => $item->title_snapshot];
            }

            for ($i = 0; $i < $tblCount; $i++) {
                $tables[] = ['title' => $item->title_snapshot];
            }
        }

        $html = view('reports.template-pdf', [
            'report' => $report,
            'items' => $items,
            'figures' => $figures,
            'tables' => $tables,
        ])->render();

        // dompdf tidak dapat memuat URL privat (butuh session) —
        // gambar report-assets di-inline sebagai data URI.
        $html = $this->inlineReportAssets($html);

        $pdf = Pdf::loadHTML($html)->setPaper('a4');

        // Render dulu: Canvas::page_script mengeksekusi callback langsung
        // atas halaman-halaman yang SUDAH ada — jika dipanggil sebelum render,
        // canvas masih kosong dan footer tidak digambar.
        $pdf->render();

        $this->drawFooter($pdf);

        $filename = $report->report_id.'.pdf';

        // ?download=1 → file unduhan; default → stream inline (preview modal)
        return $request->boolean('download')
            ? $pdf->download($filename)
            : $pdf->stream($filename);
    }

    /**
     * Ganti semua src report-assets menjadi data URI base64 dari disk private.
     */
    private function inlineReportAssets(string $html): string
    {
        $disk = Storage::disk('private');

        return (string) preg_replace_callback(
            '/src="[^"]*report-assets\/([^"?]+)"/',
            function (array $matches) use ($disk): string {
                $filename = basename($matches[1]);
                $path = 'report-assets/'.$filename;

                if (! $disk->exists($path)) {
                    return $matches[0];
                }

                $binary = $disk->get($path);
                $mime = str_contains($filename, '.png') ? 'image/png'
                    : (str_contains($filename, '.webp') ? 'image/webp' : 'image/jpeg');

                return sprintf('src="data:%s;base64,%s"', $mime, base64_encode($binary));
            },
            $html
        );
    }

    /**
     * Footer tiap lembar (kecuali cover/Halaman 1):
     * "{instansi} · Halaman N" — center di bawah kertas.
     */
    private function drawFooter($pdf): void
    {
        $dompdf = $pdf->getDomPDF();
        $canvas = $dompdf->get_canvas();
        $font = $dompdf->getFontMetrics()->getFont('times', 'normal');

        $canvas->page_script(function (int $PAGE_NUM, int $PAGE_COUNT, $canvas, $fontMetrics) use ($font): void {
            if ($PAGE_NUM > 1) {
                $text = 'Halaman '.$PAGE_NUM - 1;
                $width = $fontMetrics->getTextWidth($text, $font, 10);

                $canvas->text(
                    ($canvas->get_width() - $width) / 2,
                    $canvas->get_height() - 30,
                    $text,
                    $font,
                    10,
                    [0, 0, 0],
                );
            }
        });
    }
}
