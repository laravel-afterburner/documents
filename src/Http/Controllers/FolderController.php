<?php

namespace Afterburner\Documents\Http\Controllers;

use Afterburner\Documents\Models\Folder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Routing\Controller;

class FolderController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of folders.
     */
    public function index(Request $request)
    {
        $team = $request->user()->currentTeam;

        $query = Folder::forTeam($team->id)
            ->with(['parent', 'creator', 'children', 'documents']);

        // Filter by parent folder
        if ($request->has('parent_id')) {
            if ($request->parent_id === 'null' || $request->parent_id === null) {
                $query->root();
            } else {
                $query->inFolder($request->parent_id);
            }
        } else {
            // Default to root folders
            $query->root();
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Sort
        $sortBy = $request->get('sort_by', 'name');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        $folders = $query->get();

        return response()->json($folders);
    }

    /**
     * Store a newly created folder.
     */
    public function store(Request $request)
    {
        $request->validate([
            'team_id' => 'required|exists:teams,id',
            'parent_id' => 'nullable|exists:folders,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        $team = $request->user()->currentTeam;
        
        if ($team->id != $request->team_id) {
            abort(403, 'You can only create folders for your current team.');
        }

        // Check parent folder access if provided
        if ($request->parent_id) {
            $parentFolder = Folder::findOrFail($request->parent_id);
            Gate::authorize('view', $parentFolder);
        }

        $slug = \Illuminate\Support\Str::slug($request->name);

        // Ensure unique slug within parent folder
        $existingFolder = Folder::where('team_id', $team->id)
            ->where('parent_id', $request->parent_id)
            ->where('slug', $slug)
            ->first();

        if ($existingFolder) {
            $slug = $slug.'-'.time();
        }

        $folder = Folder::create([
            'team_id' => $request->team_id,
            'parent_id' => $request->parent_id,
            'name' => $request->name,
            'slug' => $slug,
            'description' => $request->description,
            'created_by' => $request->user()->id,
        ]);

        return response()->json($folder->load(['parent', 'creator']), 201);
    }

    /**
     * Display the specified folder.
     */
    public function show(Request $request, Folder $folder)
    {
        Gate::authorize('view', $folder);

        $folder->load(['parent', 'creator', 'children', 'documents', 'permissions']);

        return response()->json($folder);
    }

    /**
     * Update the specified folder.
     */
    public function update(Request $request, Folder $folder)
    {
        Gate::authorize('update', $folder);

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string|max:1000',
            'parent_id' => 'nullable|exists:folders,id|different:id',
        ]);

        $data = $request->only(['name', 'description', 'parent_id']);

        // Handle slug update if name changed
        if (isset($data['name']) && $data['name'] !== $folder->name) {
            $slug = \Illuminate\Support\Str::slug($data['name']);

            // Ensure unique slug within parent folder
            $parentId = $data['parent_id'] ?? $folder->parent_id;
            $existingFolder = Folder::where('team_id', $folder->team_id)
                ->where('parent_id', $parentId)
                ->where('slug', $slug)
                ->where('id', '!=', $folder->id)
                ->first();

            if ($existingFolder) {
                $slug = $slug.'-'.time();
            }

            $data['slug'] = $slug;
        }

        // Prevent moving folder into itself or its descendants
        if (isset($data['parent_id']) && $data['parent_id']) {
            $newParent = Folder::findOrFail($data['parent_id']);
            $descendants = $folder->getDescendants();
            
            if ($descendants->contains('id', $data['parent_id']) || $folder->id == $data['parent_id']) {
                abort(422, 'Cannot move folder into itself or its descendants');
            }
        }

        $folder->update($data);

        return response()->json($folder->load(['parent', 'creator', 'children']));
    }

    /**
     * Remove the specified folder.
     */
    public function destroy(Request $request, Folder $folder)
    {
        Gate::authorize('delete', $folder);

        if (!$folder->canBeDeleted()) {
            abort(422, 'Folder contains documents or subfolders and cannot be deleted');
        }

        $folder->delete();

        return response()->json(['message' => 'Folder deleted successfully'], 200);
    }
}

