<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * api_key panel, opsional — mengikuti pola kredensial terenkripsi.
     * hanya penambahan kolom, tidak menyentuh kolom lama.
     */
    public function up(): void
    {
        Schema::table('panels', function (Blueprint $table) {
            $table->text('api_key')->nullable()->after('password')->comment('API key panel, terenkripsi');
        });
    }

    public function down(): void
    {
        Schema::table('panels', function (Blueprint $table) {
            $table->dropColumn('api_key');
        });
    }
};
