<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * This migration ensures that all existing documents and folders have
     * full permissions granted to the 'owner' role. This is necessary because
     * team owners should have complete document management permissions.
     */
    public function up(): void
    {
        // Get all documents that don't have owner permissions
        $documentsWithoutOwnerPermissions = DB::table('documents')
            ->leftJoin('document_permissions', function ($join) {
                $join->on('documents.id', '=', 'document_permissions.document_id')
                     ->where('document_permissions.role_slug', '=', 'owner');
            })
            ->whereNull('document_permissions.id')
            ->pluck('documents.id');

        // Insert owner permissions for documents that don't have them
        $documentPermissions = [];
        foreach ($documentsWithoutOwnerPermissions as $documentId) {
            $documentPermissions[] = [
                'document_id' => $documentId,
                'role_slug' => 'owner',
                'can_view' => true,
                'can_edit' => true,
                'can_delete' => true,
                'can_share' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (!empty($documentPermissions)) {
            // Insert in chunks to avoid memory issues with large datasets
            foreach (array_chunk($documentPermissions, 500) as $chunk) {
                DB::table('document_permissions')->insert($chunk);
            }
        }

        // Get all folders that don't have owner permissions
        $foldersWithoutOwnerPermissions = DB::table('folders')
            ->leftJoin('folder_permissions', function ($join) {
                $join->on('folders.id', '=', 'folder_permissions.folder_id')
                     ->where('folder_permissions.role_slug', '=', 'owner');
            })
            ->whereNull('folder_permissions.id')
            ->pluck('folders.id');

        // Insert owner permissions for folders that don't have them
        $folderPermissions = [];
        foreach ($foldersWithoutOwnerPermissions as $folderId) {
            $folderPermissions[] = [
                'folder_id' => $folderId,
                'role_slug' => 'owner',
                'can_view' => true,
                'can_create' => true,
                'can_edit' => true,
                'can_delete' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (!empty($folderPermissions)) {
            // Insert in chunks to avoid memory issues with large datasets
            foreach (array_chunk($folderPermissions, 500) as $chunk) {
                DB::table('folder_permissions')->insert($chunk);
            }
        }
    }

    /**
     * Reverse the migrations.
     * 
     * Note: This will remove ALL owner permissions, not just the ones
     * added by this migration. Use with caution.
     */
    public function down(): void
    {
        // Remove owner permissions from documents
        DB::table('document_permissions')
            ->where('role_slug', 'owner')
            ->delete();

        // Remove owner permissions from folders
        DB::table('folder_permissions')
            ->where('role_slug', 'owner')
            ->delete();
    }
};

