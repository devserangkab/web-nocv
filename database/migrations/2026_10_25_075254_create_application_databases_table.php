<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_databases', function (Blueprint $table) {
            $table->id();
            $table->string('application_id', 9)->comment('Relasi ke applications.application_id');
            $table->string('db_id', 8)->comment('Relasi ke databases.db_id');
            $table->string('username')->nullable()->comment('User DB khusus aplikasi, terenkripsi');
            $table->text('password')->nullable()->comment('Password user DB aplikasi, terenkripsi');
            $table->text('note')->nullable()->comment('Keterangan pemakaian, mis. read-write / readonly');
            $table->timestamps();

            $table->foreign('application_id')
                ->references('application_id')
                ->on('applications')
                ->cascadeOnDelete();

            $table->foreign('db_id')
                ->references('db_id')
                ->on('databases')
                ->cascadeOnDelete();

            $table->unique(['application_id', 'db_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_databases');
    }
};
