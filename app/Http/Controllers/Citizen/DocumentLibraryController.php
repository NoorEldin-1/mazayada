<?php

namespace App\Http\Controllers\Citizen;

use App\Http\Controllers\Controller;
use App\Services\DocumentLibraryService;
use App\Support\DocumentFilters;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Personal Document Library (الوثائق) for the citizen dashboard — a unified,
 * searchable archive of every generated PDF tied to the auctions the signed-in
 * user has engaged with (condition books, award / receipt / delivery documents).
 *
 * Strictly self-scoped like the rest of the citizen dashboard: the service only
 * ever returns documents this user could download, so no extra gate is needed.
 * The binary is still served through the policy-gated documents.download route.
 */
class DocumentLibraryController extends Controller
{
    public function index(Request $request, DocumentLibraryService $service): View
    {
        $user = $request->user();
        $filters = DocumentFilters::fromRequest($request);

        $documents = $service->query($user, $filters)
            ->paginate(24)
            ->withQueryString();

        // Only two renderings; anything else falls back to the grouped default.
        $view = $request->query('view') === 'flat' ? 'flat' : 'grouped';

        $grouped = $view === 'grouped'
            ? $service->groupByAuction($documents->getCollection())
            : collect();

        $options = $service->filterOptions($user);

        return view('citizen.documents', [
            'documents' => $documents,
            'grouped' => $grouped,
            'stats' => $service->stats($user),
            'filters' => $filters,
            'view' => $view,
            'categories' => $options['categories'],
            'wilayas' => $options['wilayas'],
            'entities' => $options['entities'],
        ]);
    }
}
