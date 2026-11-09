<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * IP publik untuk server & NAS — mengikuti kolom vms.public_ip.
     * hanya penambahan kolom, tidak menyentuh kolom lama.
     */
    public function up(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->text('public_ip')->nullable()->after('static_ip')->comment('IP publik server, terenkripsi');
        });

        Schema::table('nas', function (Blueprint $table) {
            $table->text('public_ip')->nullable()->after('static_ip')->comment('IP publik NAS, terenkripsi');
        });
    }

    public function down(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->dropColumn('public_ip');
        });

        Schema::table('nas', function (Blueprint $table) {
            $table->dropColumn('public_ip');
        });
    }
};
