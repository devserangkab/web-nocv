<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * NAS = perangkat hardware mandiri (Synology/QNAP/TrueNAS, dll).
     * Letak: di dalam rack (rack_id) atau langsung di server room (location_id)
     * — satu wajib, mengikuti pola servers. Relasi Panel/Application
     * sengaja tidak dibuat (NAS perangkat, software tercatat sebagai aplikasi).
     */
    public function up(): void
    {
        Schema::create('nas', function (Blueprint $table) {
            $table->id();
            $table->string('nas_id', 9)->unique()->comment('Kode nas, mis. NAS-00001');
            $table->string('rack_id', 9)->nullable();
            $table->string('location_id', 9)->nullable()->comment('Lokasi langsung untuk NAS tanpa rack');
            $table->string('name');
            $table->string('brand', 100)->nullable();
            $table->unsignedBigInteger('storage_size')->nullable()->comment('Kapasitas total dalam megabyte (MB)');
            $table->unsignedTinyInteger('bays')->nullable()->comment('Jumlah slot disk');
            $table->string('raid_level', 40)->nullable()->comment('Contoh: SHR-2, RAID-6');
            $table->string('os', 100)->nullable()->comment('Contoh: DSM 7.2, TrueNAS SCALE 24.x');
            $table->text('static_ip')->nullable()->comment('IP NAS, terenkripsi');
            $table->string('username', 255)->nullable()->comment('Username kredensial NAS, terenkripsi');
            $table->text('password')->nullable()->comment('Password kredensial NAS, terenkripsi');
            $table->string('status', 10)->default('OFF')->comment('ON/OFF (App\Enums\NasStatus)');
            $table->timestamps();

            $table->foreign('rack_id')
                ->references('rack_id')
                ->on('racks')
                ->nullOnDelete();

            $table->foreign('location_id')
                ->references('location_id')
                ->on('locations')
                ->nullOnDelete();

            $table->index('rack_id');
            $table->index('location_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nas');
    }
};
