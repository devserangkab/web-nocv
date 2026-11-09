<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Riwayat sinkronisasi, satu baris per panel per crawl.
     */
    public function up(): void
    {
        Schema::create('sync_runs', function (Blueprint $table) {
            $table->id();
            $table->string('run_id', 9)->unique()->comment('Kode run, mis. SYR-00001');
            $table->string('run_group', 36)->index()->comment('Grup eksekusi satu crawl (semua panel same batch)');
            $table->string('source', 40)->index()->comment('Tipe sinkronisasi (SyncSource)');
            $table->string('panel_id', 9)->nullable()->index()->comment('Panel sumber (no FK, references panels.panel_id)');
            $table->string('status', 20)->comment('succeeded / failed / skipped');
            $table->unsignedInteger('total_sites')->default(0);
            $table->unsignedInteger('total_new')->default(0);
            $table->unsignedInteger('total_changed')->default(0);
            $table->unsignedInteger('total_unchanged')->default(0);
            $table->text('message')->nullable()->comment('Pesan gagal atau alasan dilewati');
            $table->unsignedBigInteger('triggered_by')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->foreign('triggered_by')
                ->references('id')->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_runs');
    }
};
