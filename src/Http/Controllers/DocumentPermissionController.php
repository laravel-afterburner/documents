<?php

namespace Afterburner\Documents\Http\Controllers;

use Afterburner\Documents\Models\Document;
use Afterburner\Documents\Models\DocumentPermission;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Routing\Controller;

class DocumentPermissionController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of document permissions.
     */
    public function index(Request $request, Document $document)
    {
        Gate::authorize('view', $document);

        $permissions = $document->permissions;

        return response()->json($permissions);
    }

    /**
     * Update document permissions for a role.
     */
    public function update(Request $request, Document $document, DocumentPermission $permission)
    {
        Gate::authorize('update', $document);

        // Verify permission belongs to document
        if ($permission->document_id !== $document->id) {
            abort(404);
        }

        $request->validate([
            'can_view' => 'sometimes|boolean',
            'can_edit' => 'sometimes|boolean',
            'can_delete' => 'sometimes|boolean',
            'can_share' => 'sometimes|boolean',
        ]);

        $permission->update($request->only([
            'can_view',
            'can_edit',
            'can_delete',
            'can_share',
        ]));

        // Log audit trail
        \Afterburner\Documents\Models\DocumentAudit::logAction(
            $document,
            $request->user(),
            'permission_changed',
            [
                'role_slug' => $permission->role_slug,
                'permissions' => $permission->only(['can_view', 'can_edit', 'can_delete', 'can_share']),
            ]
        );

        return response()->json($permission);
    }

    /**
     * Store a new permission for a document.
     */
    public function store(Request $request, Document $document)
    {
        Gate::authorize('update', $document);

        $request->validate([
            'role_slug' => 'required|string|max:255',
            'can_view' => 'sometimes|boolean',
            'can_edit' => 'sometimes|boolean',
            'can_delete' => 'sometimes|boolean',
            'can_share' => 'sometimes|boolean',
        ]);

        // Check if permission already exists
        $existing = DocumentPermission::where('document_id', $document->id)
            ->where('role_slug', $request->role_slug)
            ->first();

        if ($existing) {
            return response()->json(['error' => 'Permission already exists for this role'], 422);
        }

        $permission = DocumentPermission::create([
            'document_id' => $document->id,
            'role_slug' => $request->role_slug,
            'can_view' => $request->boolean('can_view', false),
            'can_edit' => $request->boolean('can_edit', false),
            'can_delete' => $request->boolean('can_delete', false),
            'can_share' => $request->boolean('can_share', false),
        ]);

        // Log audit trail
        \Afterburner\Documents\Models\DocumentAudit::logAction(
            $document,
            $request->user(),
            'permission_changed',
            [
                'action' => 'created',
                'role_slug' => $permission->role_slug,
                'permissions' => $permission->only(['can_view', 'can_edit', 'can_delete', 'can_share']),
            ]
        );

        return response()->json($permission, 201);
    }

    /**
     * Remove a permission from a document.
     */
    public function destroy(Request $request, Document $document, DocumentPermission $permission)
    {
        Gate::authorize('update', $document);

        // Verify permission belongs to document
        if ($permission->document_id !== $document->id) {
            abort(404);
        }

        $roleSlug = $permission->role_slug;
        $permission->delete();

        // Log audit trail
        \Afterburner\Documents\Models\DocumentAudit::logAction(
            $document,
            $request->user(),
            'permission_changed',
            [
                'action' => 'deleted',
                'role_slug' => $roleSlug,
            ]
        );

        return response()->json(['message' => 'Permission removed successfully'], 200);
    }
}

