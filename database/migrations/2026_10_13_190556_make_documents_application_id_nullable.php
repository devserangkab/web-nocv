<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Dokumen infrastruktur tidak selalu ber-relasi application — application_id
     * menjadi nullable. FK tetap dipertahankan.
     */
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropForeign(['application_id']);
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->string('application_id', 9)->nullable()->change();

            $table->foreign('application_id')
                ->references('application_id')
                ->on('applications')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropForeign(['application_id']);
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->string('application_id', 9)->change();

            $table->foreign('application_id')
                ->references('application_id')
                ->on('applications')
                ->cascadeOnDelete();
        });
    }
};
