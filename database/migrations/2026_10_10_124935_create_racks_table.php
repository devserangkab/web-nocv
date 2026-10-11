<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('racks', function (Blueprint $table) {
            $table->id();
            $table->string('rack_id', 9)->unique()->comment('Kode rak, mis. RAK-00001');
            $table->string('location_id', 9)->nullable();
            $table->string('name');
            $table->string('brand')->nullable();
            $table->string('color', 7)->nullable()->comment('Hex untuk warna badge, mis. #1B604E');
            $table->unsignedSmallInteger('units')->nullable()->comment('Jumlah unit (U) rak');
            $table->timestamps();

            $table->foreign('location_id')
                ->references('location_id')
                ->on('locations')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('racks');
    }
};
