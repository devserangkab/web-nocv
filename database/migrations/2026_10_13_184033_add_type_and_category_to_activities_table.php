<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Membedakan dua jenis aktivitas:
     * - AUDIT : catatan otomatis operasi aplikasi (create/update/delete relasi).
     * - WORK  : catatan pekerjaan teknis manual (pemeliharaan, deployment, dll).
     * Kategori dibuat nullable & bebas teks agar tidak butuh tabel referensi.
     */
    public function up(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->string('type', 10)->default('WORK')->after('description')->comment('AUDIT/WORK (App\Enums\ActivityType)');
            $table->string('category', 60)->nullable()->after('type')->comment('Kategori pekerjaan: Pemeliharaan/Deployment/Gangguan/dll');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->dropIndex(['type']);
            $table->dropColumn(['type', 'category']);
        });
    }
};
