<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Laporan Bulanan: satu laporan per (bulan, tahun)
        Schema::create('monthly_reports', function (Blueprint $table) {
            $table->id();
            $table->string('report_id', 9)->unique()->comment('Kode report, mis. RPT-00001');
            $table->string('title')->comment('Judul laporan, mis. Laporan Aktivitas Oktober 2026');
            $table->unsignedTinyInteger('report_month')->comment('1-12');
            $table->unsignedSmallInteger('report_year');
            $table->longText('introduction')->nullable()->comment('PENDAHULUAN — tiptap HTML');
            $table->longText('conclusion')->nullable()->comment('KESIMPULAN — tiptap HTML');
            $table->foreignId('created_by')->nullable()->comment('Penyusun laporan (users.id)');
            $table->timestamps();

            $table->unique(['report_month', 'report_year']);
            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });

        // Item uraian kerja laporan: hasil seleksi aktivitas (Sistem/Manual)
        // + snapshot agar isi resmi tidak berubah bila data master berubah.
        Schema::create('monthly_report_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('monthly_report_id')->constrained('monthly_reports')->cascadeOnDelete();
            $table->foreignId('activity_id')->nullable()->constrained('activities')->nullOnDelete()->comment('Referensi aktivitas asal (bisa hilang, snapshot tetap utuh)');
            $table->unsignedTinyInteger('sequence')->comment('Urutan tampil di laporan');
            $table->longText('uraian')->nullable()->comment('Deskripsi kerja final dari editor (tiptap HTML)');
            $table->string('title_snapshot')->comment('Judul aktivitas saat dimasukkan');
            $table->string('subject_name_snapshot', 150)->nullable()->comment('Nama entitas terkait');
            $table->string('performer_name_snapshot', 100)->nullable()->comment('Nama pelaksana');
            $table->string('type_snapshot', 10)->comment('AUDIT/WORK');
            $table->string('category_snapshot', 60)->nullable()->comment('Kategori saat dimasukkan');
            $table->timestamps();

            $table->index(['monthly_report_id', 'sequence']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monthly_report_items');
        Schema::dropIfExists('monthly_reports');
    }
};
