<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vms', function (Blueprint $table) {
            $table->id();
            $table->string('vm_id', 9)->unique()->comment('Kode vm, mis. VMS-00001');
            $table->string('proxmox_id', 9);
            $table->string('proxmox_number')->nullable()->comment('vmid di Proxmox, mis. 101');
            $table->string('name');
            $table->text('local_ip')->nullable()->comment('IP lokal VM, terenkripsi');
            $table->text('public_ip')->nullable()->comment('IP publik VM, terenkripsi');
            $table->string('username')->nullable()->comment('Username kredensial VM, terenkripsi');
            $table->text('password')->nullable()->comment('Password kredensial VM, terenkripsi');
            $table->unsignedBigInteger('cpu')->nullable()->comment('Jumlah core');
            $table->unsignedBigInteger('ram')->nullable()->comment('Kapasitas dalam megabyte (MB)');
            $table->unsignedBigInteger('storage')->nullable()->comment('Kapasitas dalam megabyte (MB)');
            $table->string('os')->nullable();
            $table->string('status', 10)->default('OFF')->comment('ON/OFF/DESTROYED (App\Enums\VmsStatus)');
            $table->text('note')->nullable();
            $table->timestamps();

            $table->foreign('proxmox_id')
                ->references('proxmox_id')
                ->on('proxmoxs')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vms');
    }
};
