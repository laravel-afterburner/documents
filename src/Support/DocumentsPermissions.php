<?php

namespace Afterburner\Documents\Support;

use App\Models\Team;
use App\Models\User;
use App\Support\TeamPermissionGate;

/**
 * Documents UI areas mapped to permission slugs.
 */
final class DocumentsPermissions
{
    public const SECTION_LIBRARY = 'library';

    public const SECTION_FOLDERS = 'folders';

    public const SECTION_RETENTION = 'retention';

    /**
     * @return array<string, string>
     */
    public static function sectionPermissionMap(): array
    {
        return [
            self::SECTION_LIBRARY => 'view_documents',
            self::SECTION_FOLDERS => 'manage_folders',
            self::SECTION_RETENTION => 'manage_retention_tags',
        ];
    }

    /**
     * @return list<string>
     */
    public static function sectionDisplayOrder(): array
    {
        return [
            self::SECTION_LIBRARY,
            self::SECTION_FOLDERS,
            self::SECTION_RETENTION,
        ];
    }

    /**
     * @return list<string>
     */
    public static function moduleAccessSlugs(): array
    {
        return [
            'view_documents',
            'manage_documents',
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

    public static function canAccessModule(User $user, Team $team): bool
    {
        return TeamPermissionGate::allowsAny($user, $team->id, self::moduleAccessSlugs());
    }

    public static function canViewSection(User $user, Team $team, string $section): bool
    {
        $slug = self::sectionPermissionMap()[$section] ?? null;

        if ($slug === null) {
            return false;
        }

        if ($section === self::SECTION_LIBRARY) {
            return TeamPermissionGate::allowsAny($user, $team->id, [
                'view_documents',
                'manage_documents',
                'create_documents',
            ]);
        }

        return TeamPermissionGate::allows($user, $team->id, $slug)
            || TeamPermissionGate::allows($user, $team->id, 'manage_documents');
    }

    /**
     * @return list<string>
     */
    public static function visibleSections(User $user, Team $team): array
    {
        $visible = [];

        foreach (self::sectionDisplayOrder() as $section) {
            if (self::canViewSection($user, $team, $section)) {
                $visible[] = $section;
            }
        }

        return $visible;
    }
}
