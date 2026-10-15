<?php

use App\Models\Application;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Dokumen kini dapat dikaitkan dengan entitas infrastruktur apa pun
     * (Location/Rack/Server/Proxmox/Vms/Panel/Application) melalui relasi
     * polymorphic. application_id tetap dipertahankan & di-backfill untuk
     * data lama agar relasi historis tidak hilang.
     */
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->string('documentable_type', 60)->nullable()->after('document_id');
            $table->unsignedBigInteger('documentable_id')->nullable()->after('documentable_type');
            $table->index(['documentable_type', 'documentable_id']);
        });

        // Backfill data lama: dokumen ber-relasi application
        $applications = Application::query()->pluck('id', 'application_id');

        DB::table('documents')
            ->whereNull('documentable_type')
            ->whereIn('application_id', $applications->keys())
            ->get(['id', 'application_id'])
            ->each(function ($document) use ($applications) {
                DB::table('documents')
                    ->where('id', $document->id)
                    ->update([
                        'documentable_type' => Application::class,
                        'documentable_id' => $applications->get($document->application_id),
                    ]);
            });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropIndex(['documentable_type', 'documentable_id']);
            $table->dropColumn(['documentable_type', 'documentable_id']);
        });
    }
};
