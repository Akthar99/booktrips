<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Services\AnalyticsService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PartnerAnalyticsController extends Controller
{
    public function __construct(private readonly AnalyticsService $analytics) {}

    /**
     * Partner analytics with an optional created-date range.
     */
    public function index(Request $request): Response
    {
        $from = (string) $request->query('from', '');
        $to = (string) $request->query('to', '');

        return Inertia::render('partner/analytics', [
            'analytics' => $this->analytics->forBusiness(
                $request->user()->business,
                $from !== '' ? $from : null,
                $to !== '' ? $to : null,
            ),
        ]);
    }
}
