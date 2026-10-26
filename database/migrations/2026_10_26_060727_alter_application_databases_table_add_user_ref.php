<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('application_databases', function (Blueprint $table) {
            $table->string('db_user_id', 9)->nullable()->comment('User database yang dipakai aplikasi; null = kredensial root/admin instance');
            $table->foreign('db_user_id')
                ->references('dbu_id')
                ->on('database_users')
                ->nullOnDelete();
        });

        Schema::table('application_databases', function (Blueprint $table) {
            $table->dropColumn(['username', 'password']);
        });
    }

    public function down(): void
    {
        Schema::table('application_databases', function (Blueprint $table) {
            $table->string('username')->nullable()->comment('User DB khusus aplikasi, terenkripsi');
            $table->text('password')->nullable()->comment('Password user DB aplikasi, terenkripsi');
        });

        Schema::table('application_databases', function (Blueprint $table) {
            $table->dropForeign(['db_user_id']);
        });

        Schema::table('application_databases', function (Blueprint $table) {
            $table->dropColumn('db_user_id');
        });
    }
};
