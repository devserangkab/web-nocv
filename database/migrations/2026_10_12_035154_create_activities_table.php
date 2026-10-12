<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->string('activity_id', 9)->unique()->comment('Kode activity, mis. ACT-00001');
            $table->string('subject_type', 60)->comment('Jenis subjek (Application/Server/Proxmox/Vms/Panel)');
            $table->unsignedBigInteger('subject_id')->comment('ID subjek terkait');
            $table->foreignId('user_id')->nullable()->comment('User pelaksana (users.id)');
            $table->string('activity')->comment('Judul aktivitas, mis. Menambahkan fitur rekap');
            $table->text('description')->nullable()->comment('Rincian aktivitas');
            $table->timestamp('finished_at')->nullable()->comment('Waktu selesai; null berarti masih berjalan');
            $table->timestamps();

            $table->index(['subject_type', 'subject_id']);
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
