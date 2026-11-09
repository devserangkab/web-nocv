<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Proposal perubahan hasil crawl yang menunggu persetujuan superadmin.
     */
    public function up(): void
    {
        Schema::create('sync_proposals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sync_run_id')->constrained('sync_runs')->cascadeOnDelete();
            $table->string('source', 40)->index()->comment('Tipe sinkronisasi (SyncSource)');
            $table->string('panel_id', 9)->nullable()->comment('Panel sumber (no FK, references panels.panel_id)');
            $table->string('application_id', 9)->nullable()->comment('Application yang terkait kolom datanya, null utk create');
            $table->string('site_name', 255)->comment('Nama situs pada panel (kunci pencocokan)');
            $table->string('action', 20)->comment('create / update');
            $table->json('panel_data')->comment('Snapshot data situs dari panel');
            $table->json('changes')->comment('Daftar diff [{field, old, new}]');
            $table->string('status', 20)->index()->comment('pending / applied / rejected');
            $table->unsignedBigInteger('applied_by')->nullable();
            $table->timestamp('applied_at')->nullable();
            $table->timestamps();

            $table->foreign('application_id')
                ->references('application_id')->on('applications')
                ->nullOnDelete();

            $table->foreign('applied_by')
                ->references('id')->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_proposals');
    }
};
