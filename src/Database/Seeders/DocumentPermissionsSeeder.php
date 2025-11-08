<?php

namespace Afterburner\Documents\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DocumentPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * This seeder adds document-related permissions to the permissions table.
     * These permissions can then be assigned to roles via the role_permission pivot table.
     *
     * Note: This uses insertOrIgnore to avoid duplicates if run multiple times.
     */
    public function run(): void
    {
        // Check if permissions table exists
        if (!DB::getSchemaBuilder()->hasTable('permissions')) {
            if (isset($this->command)) {
                $this->command->error('Permissions table does not exist. Please ensure your database migrations are up to date.');
            }
            return;
        }

        $now = Carbon::now();

        $permissions = [
            [
                'name' => 'View Documents',
                'slug' => 'view_documents',
                'description' => 'View team documents',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Create Documents',
                'slug' => 'create_documents',
                'description' => 'Create new documents',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Edit Documents',
                'slug' => 'edit_documents',
                'description' => 'Edit existing documents',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Delete Documents',
                'slug' => 'delete_documents',
                'description' => 'Delete documents',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Download Documents',
                'slug' => 'download_documents',
                'description' => 'Download documents',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Share Documents',
                'slug' => 'share_documents',
                'description' => 'Share documents with other users',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Manage Document Permissions',
                'slug' => 'manage_document_permissions',
                'description' => 'Manage permissions for documents',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'View Document Versions',
                'slug' => 'view_document_versions',
                'description' => 'View document version history',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Restore Document Versions',
                'slug' => 'restore_document_versions',
                'description' => 'Restore previous document versions',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Manage Folders',
                'slug' => 'manage_folders',
                'description' => 'Create, edit, and delete document folders',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Manage Folder Permissions',
                'slug' => 'manage_folder_permissions',
                'description' => 'Manage permissions for folders',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Manage Retention Tags',
                'slug' => 'manage_retention_tags',
                'description' => 'Manage document retention tags',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        // Insert permissions using insertOrIgnore to avoid duplicates
        $insertedPermissionIds = [];
        foreach ($permissions as $permission) {
            $result = DB::table('permissions')->insertOrIgnore($permission);
            // Get the ID of the inserted permission (or existing one)
            $permissionRecord = DB::table('permissions')
                ->where('slug', $permission['slug'])
                ->first();
            if ($permissionRecord) {
                $insertedPermissionIds[] = $permissionRecord->id;
            }
        }

        // Assign all permissions to team owners' highest hierarchy role
        if (!empty($insertedPermissionIds) && DB::getSchemaBuilder()->hasTable('role_permission')) {
            $assignedCount = $this->assignPermissionsToTeamOwners($insertedPermissionIds, $permissions, $now);
            
            if (isset($this->command) && $assignedCount > 0) {
                $this->command->info("✓ Permissions assigned to {$assignedCount} team owner role(s)");
            } elseif (isset($this->command)) {
                $this->command->warn('  ⚠ Could not assign permissions to team owners. Check that teams and roles tables exist.');
            }
        } elseif (isset($this->command) && empty($insertedPermissionIds)) {
            $this->command->warn('  ⚠ No permissions were inserted. They may already exist.');
        } elseif (isset($this->command) && !DB::getSchemaBuilder()->hasTable('role_permission')) {
            $this->command->warn('  ⚠ role_permission table does not exist. Permissions were created but not assigned to roles.');
        }

        if (isset($this->command)) {
            $this->command->info('✓ Document permissions seeded successfully!');
            $this->command->line('');
            
            $this->command->comment('Available permissions:');
            foreach ($permissions as $permission) {
                $this->command->line("  • {$permission['name']} ({$permission['slug']})");
            }
        }
    }

    /**
     * Assign permissions to team owners' highest hierarchy role.
     *
     * @param array $insertedPermissionIds
     * @param array $permissions
     * @param \Carbon\Carbon $now
     * @return int Number of roles that received permissions
     */
    protected function assignPermissionsToTeamOwners(array $insertedPermissionIds, array $permissions, $now): int
    {
        // Check if teams table exists
        if (!DB::getSchemaBuilder()->hasTable('teams')) {
            if (isset($this->command)) {
                $this->command->warn('  ⚠ Teams table does not exist. Skipping team owner permission assignment.');
            }
            return 0;
        }

        // Check if roles table exists
        if (!DB::getSchemaBuilder()->hasTable('roles')) {
            if (isset($this->command)) {
                $this->command->warn('  ⚠ Roles table does not exist. Skipping team owner permission assignment.');
            }
            return 0;
        }

        // Check for user_role pivot table (common patterns: user_role, role_user, user_roles)
        $userRoleTable = null;
        $possibleTables = ['user_role', 'role_user', 'user_roles', 'role_users'];
        foreach ($possibleTables as $table) {
            if (DB::getSchemaBuilder()->hasTable($table)) {
                $userRoleTable = $table;
                break;
            }
        }

        if (!$userRoleTable) {
            if (isset($this->command)) {
                $this->command->warn('  ⚠ User-role pivot table not found. Expected one of: ' . implode(', ', $possibleTables));
            }
            return 0;
        }

        // Get role_permission table structure
        $rolePermissionColumns = DB::getSchemaBuilder()->getColumnListing('role_permission');
        $userRoleColumns = DB::getSchemaBuilder()->getColumnListing($userRoleTable);
        $rolesColumns = DB::getSchemaBuilder()->getColumnListing('roles');

        // Determine hierarchy field name (common patterns: hierarchy, hierarchy_number, level, order)
        $hierarchyField = null;
        $possibleHierarchyFields = ['hierarchy', 'hierarchy_number', 'level', 'order', 'hierarchy_level'];
        foreach ($possibleHierarchyFields as $field) {
            if (in_array($field, $rolesColumns)) {
                $hierarchyField = $field;
                break;
            }
        }

        if (!$hierarchyField) {
            if (isset($this->command)) {
                $this->command->warn('  ⚠ No hierarchy field found in roles table. Expected one of: ' . implode(', ', $possibleHierarchyFields));
            }
            return 0;
        }

        // Get all teams with their owners
        $teams = DB::table('teams')
            ->whereNotNull('user_id')
            ->select('id', 'user_id')
            ->get();

        if ($teams->isEmpty()) {
            if (isset($this->command)) {
                $this->command->warn('  ⚠ No teams with owners found.');
            }
            return 0;
        }

        $assignedCount = 0;

        foreach ($teams as $team) {
            // Get all roles for the team owner for this team
            $ownerRolesQuery = DB::table($userRoleTable)
                ->join('roles', function ($join) use ($userRoleTable, $userRoleColumns) {
                    // Determine the join key based on table structure
                    if (in_array('role_id', $userRoleColumns)) {
                        $join->on("{$userRoleTable}.role_id", '=', 'roles.id');
                    } elseif (in_array('role_slug', $userRoleColumns)) {
                        $join->on("{$userRoleTable}.role_slug", '=', 'roles.slug');
                    }
                })
                ->where("{$userRoleTable}.user_id", $team->user_id);

            // Add team_id filter if it exists in the pivot table
            if (in_array('team_id', $userRoleColumns)) {
                $ownerRolesQuery->where("{$userRoleTable}.team_id", $team->id);
            }

            $ownerRoles = $ownerRolesQuery
                ->select('roles.*')
                ->orderByDesc("roles.{$hierarchyField}")
                ->get();

            if ($ownerRoles->isEmpty()) {
                continue;
            }

            // Get the role with the highest hierarchy number
            $highestRole = $ownerRoles->first();

            // Check if timestamp columns exist
            $hasTimestamps = in_array('created_at', $rolePermissionColumns) && in_array('updated_at', $rolePermissionColumns);

            // Assign permissions to this role
            if (in_array('role_slug', $rolePermissionColumns) && in_array('permission_id', $rolePermissionColumns)) {
                // Pattern: role_slug + permission_id
                foreach ($insertedPermissionIds as $permissionId) {
                    $data = [
                        'role_slug' => $highestRole->slug,
                        'permission_id' => $permissionId,
                    ];
                    if ($hasTimestamps) {
                        $data['created_at'] = $now;
                        $data['updated_at'] = $now;
                    }
                    DB::table('role_permission')->insertOrIgnore($data);
                }
                $assignedCount++;
            } elseif (in_array('role_slug', $rolePermissionColumns) && in_array('permission_slug', $rolePermissionColumns)) {
                // Pattern: role_slug + permission_slug
                foreach ($permissions as $permission) {
                    $data = [
                        'role_slug' => $highestRole->slug,
                        'permission_slug' => $permission['slug'],
                    ];
                    if ($hasTimestamps) {
                        $data['created_at'] = $now;
                        $data['updated_at'] = $now;
                    }
                    DB::table('role_permission')->insertOrIgnore($data);
                }
                $assignedCount++;
            } elseif (in_array('role_id', $rolePermissionColumns) && in_array('permission_id', $rolePermissionColumns)) {
                // Pattern: role_id + permission_id
                foreach ($insertedPermissionIds as $permissionId) {
                    $data = [
                        'role_id' => $highestRole->id,
                        'permission_id' => $permissionId,
                    ];
                    if ($hasTimestamps) {
                        $data['created_at'] = $now;
                        $data['updated_at'] = $now;
                    }
                    DB::table('role_permission')->insertOrIgnore($data);
                }
                $assignedCount++;
            }
        }

        return $assignedCount;
    }
}

