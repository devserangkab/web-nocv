<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('hostings', function (Blueprint $table) {
            $table->id();
            $table->string('application_id', 9)->comment('Relasi ke applications.application_id');
            $table->string('hostable_type')->nullable()->comment('Jenis host (Vms/Server), null bila eksternal');
            $table->unsignedBigInteger('hostable_id')->nullable()->comment('ID host terkait, null bila eksternal');
            $table->string('name')->nullable()->comment('Nama host eksternal (di luar master data)');
            $table->text('note')->nullable()->comment('Keterangan tambahan hosting');
            $table->timestamps();

            $table->index(['hostable_type', 'hostable_id']);
            $table->foreign('application_id')
                ->references('application_id')
                ->on('applications')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hostings');
    }
};
