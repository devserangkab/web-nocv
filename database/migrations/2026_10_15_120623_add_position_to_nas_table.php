<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Posisi U di dalam rack untuk NAS — mengikuti pola servers.position.
     */
    public function up(): void
    {
        Schema::table('nas', function (Blueprint $table) {
            $table->unsignedSmallInteger('position')->nullable()->after('bays')->comment('Posisi U di dalam rack');
            $table->index('position');
        });
    }

    public function down(): void
    {
        Schema::table('nas', function (Blueprint $table) {
            $table->dropIndex(['position']);
            $table->dropColumn('position');
        });
    }
};
