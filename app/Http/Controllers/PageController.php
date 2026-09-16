<?php

namespace App\Http\Controllers;

use App\Enums\BusinessType;
use Inertia\Inertia;
use Inertia\Response;

class PageController extends Controller
{
    /**
     * About BookTrips.
     */
    public function about(): Response
    {
        return Inertia::render('about');
    }

    /**
     * Partner landing page.
     */
    public function partners(): Response
    {
        return Inertia::render('partners/index', [
            'businessTypes' => array_map(
                fn ($case): array => ['slug' => $case->value, 'name' => $case->label()],
                BusinessType::cases(),
            ),
        ]);
    }
}
