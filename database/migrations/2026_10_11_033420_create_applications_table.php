<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->string('application_id', 9)->unique()->comment('Kode application, mis. APP-00001');
            $table->string('name')->unique();
            $table->string('abbreviation')->nullable();
            $table->text('description')->nullable();
            $table->string('status')->comment('AppStatus: Development / Production / Maintenance / Retired / Archived');
            $table->foreignId('requested_by')->nullable()->comment('OPD pengusul (institutions.id)');
            $table->string('domain')->nullable();
            $table->string('repo_url')->nullable();
            $table->string('icon')->nullable();
            $table->string('framework')->nullable();
            $table->string('language')->nullable();
            $table->string('database')->nullable()->comment('Database yang dipakai, mis. MySQL, PostgreSQL');
            $table->string('type')->comment('ApplicationType: Website / Mobile / Desktop / API');
            $table->timestamps();

            $table->foreign('requested_by')
                ->references('id')
                ->on('institutions')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};
