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
        Schema::table('documents', function (Blueprint $table) {
            $table->foreignId('retention_tag_id')->nullable()->after('uploaded_by')->constrained('retention_tags')->onDelete('set null');
            $table->timestamp('retention_expires_at')->nullable()->after('retention_tag_id');

            // Index for efficient queries
            $table->index('retention_tag_id');
            $table->index('retention_expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropForeign(['retention_tag_id']);
            $table->dropIndex(['retention_tag_id']);
            $table->dropIndex(['retention_expires_at']);
            $table->dropColumn(['retention_tag_id', 'retention_expires_at']);
        });
    }
};

