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
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->string('media_id', 9)->unique()->comment('Kode media, mis. MED-00001');
            $table->string('mediable_type', 60)->comment('Jenis entitas pemilik media (Location/Rack/Server/Proxmox/Vms/Panel/Application)');
            $table->unsignedBigInteger('mediable_id')->comment('ID entitas pemilik media');
            $table->string('type', 10)->default('IMAGE')->comment('IMAGE/VIDEO (App\Enums\MediaType)');
            $table->string('title')->nullable()->comment('Label singkat media');
            $table->text('description')->nullable()->comment('Penjelasan bebas: kondisi existing, evidence aktivitas, diagram, dll');
            $table->text('file')->comment('Path file di disk public (media/...)');
            $table->string('thumbnail')->nullable()->comment('Path thumbnail 150x150 di disk public (media/thumbs/...), null bila tidak perlu');
            $table->foreignId('added_by')->nullable()->comment('User pengunggah (users.id)');
            $table->timestamps();

            $table->index(['mediable_type', 'mediable_id']);
            $table->foreign('added_by')
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
        Schema::dropIfExists('media');
    }
};
