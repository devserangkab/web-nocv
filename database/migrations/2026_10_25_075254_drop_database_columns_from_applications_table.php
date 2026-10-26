<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropColumn(['database', 'database_version']);
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->string('database')->nullable()->comment('Database yang dipakai, mis. MySQL, PostgreSQL');
            $table->string('database_version', 20)->nullable()->comment('Versi database, mis. 8.0');
        });
    }
};
