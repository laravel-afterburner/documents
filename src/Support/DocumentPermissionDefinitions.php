<?php

namespace Afterburner\Documents\Support;

class DocumentPermissionDefinitions
{
    /**
     * @return array<int, array{name: string, slug: string, description: string}>
     */
    public static function all(): array
    {
        return [
            [
                'name' => 'View Documents',
                'slug' => 'view_documents',
                'description' => 'View team documents',
            ],
            [
                'name' => 'Create Documents',
                'slug' => 'create_documents',
                'description' => 'Create new documents',
            ],
            [
                'name' => 'Edit Documents',
                'slug' => 'edit_documents',
                'description' => 'Edit existing documents',
            ],
            [
                'name' => 'Delete Documents',
                'slug' => 'delete_documents',
                'description' => 'Delete documents',
            ],
            [
                'name' => 'Download Documents',
                'slug' => 'download_documents',
                'description' => 'Download documents',
            ],
            [
                'name' => 'Share Documents',
                'slug' => 'share_documents',
                'description' => 'Share documents with other users',
            ],
            [
                'name' => 'Manage Document Permissions',
                'slug' => 'manage_document_permissions',
                'description' => 'Manage permissions for documents',
            ],
            [
                'name' => 'View Document Versions',
                'slug' => 'view_document_versions',
                'description' => 'View document version history',
            ],
            [
                'name' => 'Restore Document Versions',
                'slug' => 'restore_document_versions',
                'description' => 'Restore previous document versions',
            ],
            [
                'name' => 'Manage Folders',
                'slug' => 'manage_folders',
                'description' => 'Create, edit, and delete document folders',
            ],
            [
                'name' => 'Manage Folder Permissions',
                'slug' => 'manage_folder_permissions',
                'description' => 'Manage permissions for folders',
            ],
            [
                'name' => 'Manage Retention Tags',
                'slug' => 'manage_retention_tags',
                'description' => 'Manage document retention tags',
            ],
        ];
    }
}
