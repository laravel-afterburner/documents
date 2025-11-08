<?php

namespace Afterburner\Documents\Http\Controllers;

use App\Models\Team;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DocumentsController
{
    /**
     * Display the documents index page.
     */
    public function index(Team $team): View
    {
        // Ensure user belongs to team
        if (!Auth::user()->belongsToTeam($team)) {
            abort(403, 'Access denied.');
        }

        return view('afterburner-documents::documents.show', [
            'team' => $team,
        ]);
    }

    /**
     * Display documents for a specific folder.
     */
    public function folder(Team $team, string $folder_slug): View
    {
        // Ensure user belongs to team
        if (!Auth::user()->belongsToTeam($team)) {
            abort(403, 'Access denied.');
        }

        return view('afterburner-documents::documents.show', [
            'team' => $team,
            'folder_slug' => $folder_slug,
        ]);
    }
}

