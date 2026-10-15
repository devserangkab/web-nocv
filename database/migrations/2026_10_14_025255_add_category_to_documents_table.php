<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kategori dokumen sebagai label tampilan (nilai dari App\Enums\DocumentCategory),
     * nullable karena kategori bersifat opsional.
     */
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->string('category', 60)->nullable()->after('name')->comment('Kategori dokumen (App\Enums\DocumentCategory)');
            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropIndex(['category']);
            $table->dropColumn('category');
        });
    }
};
