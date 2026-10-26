<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('databases', function (Blueprint $table) {
            $table->id();
            $table->string('db_id', 8)->unique()->comment('Kode database, mis. DB-00001');
            $table->string('dbi_id', 9)->comment('Instance pemilik database');
            $table->string('name');
            $table->text('note')->nullable()->comment('Keterangan: charset, kegunaan, dsb.');
            $table->timestamps();

            $table->foreign('dbi_id')
                ->references('dbi_id')
                ->on('database_instances')
                ->cascadeOnDelete();

            $table->unique(['dbi_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('databases');
    }
};
