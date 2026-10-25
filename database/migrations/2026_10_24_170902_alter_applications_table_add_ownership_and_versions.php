<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->string('ownership')->nullable()->comment('Asal & kelola: Internal NOC / Serah Terima Vendor / Dikelola Vendor / Open Source (ApplicationOwnership)');
            $table->string('vendor_name')->nullable()->comment('Nama vendor (wajib untuk ownership vendor)');

            $table->string('framework_version', 20)->nullable()->comment('Versi framework, mis. 10.48.4');
            $table->string('language_version', 20)->nullable()->comment('Versi bahasa, mis. 8.2');
            $table->string('database_version', 20)->nullable()->comment('Versi database, mis. 8.0');
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropColumn(['ownership', 'vendor_name', 'framework_version', 'language_version', 'database_version']);
        });
    }
};
