<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `databases` adalah reserved word MySQL — rename ke `database_schemas`
     * agar UniqueIdGenerator (raw SQL tanpa backtick) tidak meledak.
     */
    public function up(): void
    {
        Schema::rename('databases', 'database_schemas');
    }

    public function down(): void
    {
        Schema::rename('database_schemas', 'databases');
    }
};
