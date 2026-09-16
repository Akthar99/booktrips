<?php

namespace App\Http\Controllers;

use App\Services\GeoSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GeoController extends Controller
{
    public function __construct(private readonly GeoSearchService $geo) {}

    /**
     * Place search proxy (used by the location picker).
     */
    public function search(Request $request): JsonResponse
    {
        $query = (string) $request->query('q', '');

        return response()->json([
            'results' => $this->geo->search($query),
        ]);
    }
}
