<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('database_instances', function (Blueprint $table) {
            $table->id();
            $table->string('dbi_id', 9)->unique()->comment('Kode instance, mis. DBS-00001');
            $table->string('server_id', 9)->nullable()->comment('Host server fisik (optional)');
            $table->string('vm_id', 9)->nullable()->comment('Host VM (optional)');
            $table->string('panel_id', 9)->nullable()->comment('Host panel (optional)');
            $table->string('name');
            $table->string('tech')->comment('Teknologi RDBMS: MySQL / PostgreSQL (DatabaseTech)');
            $table->string('version', 20)->nullable()->comment('Versi instance, mis. 8.0');
            $table->text('internal_address')->nullable()->comment('Alamat + port instance, terenkripsi');
            $table->string('username')->nullable()->comment('Kredensial root/admin, terenkripsi');
            $table->text('password')->nullable()->comment('Kredensial root/admin, terenkripsi');
            $table->timestamps();

            $table->foreign('server_id')
                ->references('server_id')
                ->on('servers')
                ->nullOnDelete();

            $table->foreign('vm_id')
                ->references('vm_id')
                ->on('vms')
                ->nullOnDelete();

            $table->foreign('panel_id')
                ->references('panel_id')
                ->on('panels')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('database_instances');
    }
};
