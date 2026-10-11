<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('developers', function (Blueprint $table) {
            $table->id();
            $table->string('application_id', 9)->comment('Relasi ke applications.application_id');
            $table->foreignId('developer_id')->comment('User developer (users.id)');
            $table->string('type')->comment('DeveloperType: Programmer / Designer / Fullstack / System Administrator');
            $table->timestamps();

            $table->foreign('application_id')
                ->references('application_id')
                ->on('applications')
                ->cascadeOnDelete();

            $table->foreign('developer_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();

            $table->unique(['application_id', 'developer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('developers');
    }
};
