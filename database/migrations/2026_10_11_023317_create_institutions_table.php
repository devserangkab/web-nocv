<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('institutions', function (Blueprint $table) {
            $table->id();
            $table->string('institution_id', 9)->unique()->comment('Kode institution, mis. INS-00001');
            $table->string('name')->comment('Nama lengkap OPD, mis. Badan Kepegawaian dan Pengembangan Sumber Daya Manusia');
            $table->string('abbreviation', 50)->comment('Singkatan OPD, mis. BKPSDM, SET DPRD');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('institutions');
    }
};
