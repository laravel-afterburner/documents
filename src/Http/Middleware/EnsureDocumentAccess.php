<?php

namespace Afterburner\Documents\Http\Middleware;

use Afterburner\Documents\Models\Document;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureDocumentAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $documentId = $request->route('document');

        if ($documentId) {
            $document = Document::findOrFail($documentId);

            // Check if user has access to the document
            if (!auth()->check() || !$document->canView(auth()->user())) {
                abort(403, 'You do not have permission to access this document.');
            }
        }

        return $next($request);
    }
}

