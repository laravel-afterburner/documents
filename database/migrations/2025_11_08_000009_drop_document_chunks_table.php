<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::dropIfExists('document_chunks');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // The original document_chunks migration can be re-run if needed.
    }
};
