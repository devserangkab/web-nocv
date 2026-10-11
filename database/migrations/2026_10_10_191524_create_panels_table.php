<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('panels', function (Blueprint $table) {
            $table->id();
            $table->string('panel_id', 9)->unique()->comment('Kode panel, mis. PNL-00001');
            $table->string('vm_id', 9)->nullable()->comment('Sasaran install di VM (optional)');
            $table->string('server_id', 9)->nullable()->comment('Sasaran install di server langsung (optional)');
            $table->string('name');
            $table->text('internet_address')->nullable()->comment('URL panel eksternal, terenkripsi');
            $table->text('internal_address')->nullable()->comment('URL panel internal, terenkripsi');
            $table->string('username')->nullable()->comment('Username kredensial panel, terenkripsi');
            $table->text('password')->nullable()->comment('Password kredensial panel, terenkripsi');
            $table->string('type')->comment('Jenis panel: aaPanel / cPanel (PanelType)');
            $table->string('version')->nullable()->comment('Contoh: 7.0.11');
            $table->timestamps();

            $table->foreign('vm_id')
                ->references('vm_id')
                ->on('vms')
                ->nullOnDelete();

            $table->foreign('server_id')
                ->references('server_id')
                ->on('servers')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('panels');
    }
};
