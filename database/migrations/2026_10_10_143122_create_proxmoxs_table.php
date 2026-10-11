<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proxmoxs', function (Blueprint $table) {
            $table->id();
            $table->string('proxmox_id', 9)->unique()->comment('Kode proxmox, mis. PMX-00001');
            $table->string('name');
            $table->text('url')->nullable()->comment('URL host Proxmox, terenkripsi');
            $table->string('username')->nullable()->comment('Username kredensial Proxmox, terenkripsi');
            $table->text('password')->nullable()->comment('Password kredensial Proxmox, terenkripsi');
            $table->string('version')->nullable()->comment('Contoh: 8.2.4');
            $table->string('server_id', 9)->nullable()->comment('Relasi optional, satu server maksimal satu proxmox');
            $table->timestamps();

            $table->foreign('server_id')
                ->references('server_id')
                ->on('servers')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proxmoxs');
    }
};
