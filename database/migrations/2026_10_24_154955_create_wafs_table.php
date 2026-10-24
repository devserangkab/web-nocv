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
        Schema::create('wafs', function (Blueprint $table) {
            $table->id();
            $table->string('waf_id', 9)->unique()->comment('Kode WAF, mis. WAF-00001');
            $table->string('vm_id', 9)->nullable()->comment('Sasaran install di VM (optional)');
            $table->string('server_id', 9)->nullable()->comment('Sasaran install di server langsung (optional)');
            $table->string('name');
            $table->text('internet_address')->nullable()->comment('URL WAF eksternal, terenkripsi');
            $table->text('internal_address')->nullable()->comment('URL WAF internal, terenkripsi');
            $table->string('username')->nullable()->comment('Username kredensial WAF, terenkripsi');
            $table->text('password')->nullable()->comment('Password kredensial WAF, terenkripsi');
            $table->string('tech')->comment('Teknologi WAF: ModSecurity / SafeLine (WafTech)');
            $table->string('version')->nullable()->comment('Contoh: 2.9.7');
            $table->timestamps();

            $table->foreign('vm_id')
                ->references('vm_id')
                ->on('vms')
                ->nullOnDelete();

            $table->foreign('server_id')
                ->references('server_id')
                ->on('servers')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wafs');
    }
};
