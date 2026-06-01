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
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->onDelete('cascade');
            $table->foreignId('folder_id')->nullable()->constrained('folders')->onDelete('set null');
            $table->string('name');
            $table->text('notes')->nullable();
            $table->string('filename');
            $table->string('mime_type');
            $table->unsignedBigInteger('size');
            $table->string('storage_path');
            $table->enum('upload_status', ['pending', 'uploading', 'processing', 'completed', 'failed'])->default('pending');
            $table->unsignedTinyInteger('upload_progress')->default(0);
            $table->foreignId('uploaded_by')->constrained('users')->onDelete('cascade');
            $table->unsignedBigInteger('retention_tag_id')->nullable();
            $table->timestamp('retention_expires_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('team_id');
            $table->index('folder_id');
            $table->index('upload_status');
            $table->index('retention_tag_id');
            $table->index('retention_expires_at');
            $table->unique(['team_id', 'folder_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
