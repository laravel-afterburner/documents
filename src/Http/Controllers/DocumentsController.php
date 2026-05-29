<?php

namespace Afterburner\Documents\Http\Controllers;

use App\Models\Team;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class DocumentsController
{
    /**
     * Display the documents index page.
     */
    public function index(Team $team): View
    {
        Gate::authorize('documents.access-team', $team);

        return view('afterburner-documents::documents.show', [
            'team' => $team,
        ]);
    }

    /**
     * Display documents for a specific folder.
     */
    public function folder(Team $team, string $folder_slug): View
    {
        Gate::authorize('documents.access-team', $team);

        return view('afterburner-documents::documents.show', [
            'team' => $team,
            'folder_slug' => $folder_slug,
        ]);
    }
}
