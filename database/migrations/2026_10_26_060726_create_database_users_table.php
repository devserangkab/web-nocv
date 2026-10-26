<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('database_users', function (Blueprint $table) {
            $table->id();
            $table->string('dbu_id', 9)->unique()->comment('Kode user database, mis. DBU-00001');
            $table->string('dbi_id', 9)->comment('Instance pemilik user');
            $table->string('username');
            $table->text('password')->nullable()->comment('Password user database, terenkripsi');
            $table->text('note')->nullable()->comment('Keterangan: kegunaan, aplikasi pemakai, dsb.');
            $table->timestamps();

            $table->foreign('dbi_id')
                ->references('dbi_id')
                ->on('database_instances')
                ->cascadeOnDelete();

            $table->unique(['dbi_id', 'username']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('database_users');
    }
};
