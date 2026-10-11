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
            $table->unsignedBigInteger('storage')->nullable()->comment('Kapasitas dalam megabyte (MB)');
            $table->unsignedBigInteger('ram')->nullable()->comment('Kapasitas dalam megabyte (MB)');
            $table->string('cpu')->nullable();
            $table->text('static_ip')->nullable()->comment('Static IP server, terenkripsi');
            $table->string('username')->nullable()->comment('Username kredensial server, terenkripsi');
            $table->text('password')->nullable()->comment('Password kredensial server, terenkripsi');
            $table->string('os')->nullable();
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
