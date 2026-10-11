<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->string('document_id', 9)->unique()->comment('Kode document, mis. DOC-00001');
            $table->string('application_id', 9)->comment('Relasi ke applications.application_id');
            $table->foreignId('added_by')->nullable()->comment('User pengunggah (users.id)');
            $table->string('name')->comment('Judul/nama dokumen, mis. SRS Sistem');
            $table->text('document')->nullable()->comment('Path file dokumen di disk public');
            $table->timestamps();

            $table->foreign('application_id')
                ->references('application_id')
                ->on('applications')
                ->cascadeOnDelete();

            $table->foreign('added_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
