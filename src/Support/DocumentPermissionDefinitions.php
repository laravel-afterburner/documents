<?php

namespace Afterburner\Documents\Support;

class DocumentPermissionDefinitions
{
    /**
     * @return list<string>
     */
    public static function slugs(): array
    {
        return [
            'manage_documents',
            'view_documents',
            'create_documents',
            'edit_documents',
            'delete_documents',
            'download_documents',
            'share_documents',
            'manage_document_permissions',
            'view_document_versions',
            'restore_document_versions',
            'manage_folders',
            'manage_folder_permissions',
            'manage_retention_tags',
        ];
    }

    /**
     * @return array<int, array{name: string, slug: string, description: string}>
     */
    public static function all(): array
    {
        if (class_exists(\App\Support\PermissionCatalog::class)) {
            return collect(\App\Support\PermissionCatalog::definitions())
                ->filter(fn (array $permission) => in_array($permission['slug'], self::slugs(), true))
                ->values()
                ->all();
        }

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
