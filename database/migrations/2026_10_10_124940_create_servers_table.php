<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('servers', function (Blueprint $table) {
            $table->id();
            $table->string('server_id', 9)->unique()->comment('Kode server, mis. SVR-00001');
            $table->string('server_name');
            $table->string('rack_id', 9)->nullable();
            $table->unsignedInteger('position')->nullable();
            $table->string('brand')->nullable();
            $table->unsignedBigInteger('storage_size')->nullable()->comment('Kapasitas penyimpanan dalam megabyte (MB)');
            $table->string('storage')->nullable()->comment('Spesifikasi disk, mis. HPE 3.8TB 15K RPM HDD');
            $table->unsignedBigInteger('memory_size')->nullable()->comment('Kapasitas memori dalam megabyte (MB)');
            $table->string('memory')->nullable()->comment('Spesifikasi memori, mis. HPE 64GB (4x16GB) DDR4 2666MT/s');
            $table->string('cpu')->nullable();
            $table->text('static_ip')->nullable()->comment('Static IP server, terenkripsi');
            $table->string('username')->nullable()->comment('Username kredensial server, terenkripsi');
            $table->text('password')->nullable()->comment('Password kredensial server, terenkripsi');
            $table->string('os')->nullable();
            $table->string('status', 10)->default('OFF')->comment('ON/OFF (App\Enums\ServerStatus)');
            $table->string('location_id', 9)->nullable()->comment('Lokasi langsung untuk server tanpa rack');
            $table->timestamps();

            $table->foreign('rack_id')
                ->references('rack_id')
                ->on('racks')
                ->nullOnDelete();

            $table->foreign('location_id')
                ->references('location_id')
                ->on('locations')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('servers');
    }
};
