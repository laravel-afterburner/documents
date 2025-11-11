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
        Schema::create('document_chunks', function (Blueprint $table) {
            $table->id();
            $table->string('chunk_id')->unique(); // Unique identifier for the chunk (UUID or similar)
            $table->foreignId('document_id')->nullable()->constrained('documents')->onDelete('cascade');
            $table->unsignedInteger('chunk_index')->nullable(); // Order of chunk in the file
            $table->string('storage_path'); // Path in R2 where chunk is stored
            $table->unsignedBigInteger('size'); // Size of chunk in bytes
            $table->timestamp('expires_at')->nullable(); // For cleanup of abandoned chunks
            $table->timestamps();

            // Indexes
            $table->index('chunk_id');
            $table->index('document_id');
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_chunks');
    }
};

