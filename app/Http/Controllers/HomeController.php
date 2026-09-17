<?php

namespace App\Http\Controllers;

use App\Models\Package;
use App\Presenters\CatalogPresenter;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __construct(private readonly CatalogPresenter $presenter) {}

    /**
     * Marketing home page with featured packages.
     */
    public function index(): Response
    {
        $featured = Package::query()
            ->active()
            ->featured()
            ->with('business:id,name,type,city,cover_image')
            ->orderByDesc('rating')
            ->take(8)
            ->get()
            ->map(fn (Package $package): array => $this->presenter->packageCard($package))
            ->all();

        // Pins only carry what MapPin renders — no image JSON, no price.
        $pins = Package::query()
            ->active()
            ->whereNotNull('lat')
            ->whereNotNull('lng')
            ->select(['id', 'title', 'location', 'lat', 'lng'])
            ->get()
            ->map(fn (Package $package): array => [
                'id' => $package->id,
                'title' => $package->title,
                'location' => $package->location,
                'lat' => $package->lat,
                'lng' => $package->lng,
            ])
            ->values()
            ->all();

        return Inertia::render('home', [
            'featured' => $featured,
            'pins' => $pins,
            'categories' => config('booktrips.categories'),
            'destinations' => config('booktrips.destinations'),
            'packageCount' => Package::query()->active()->count(),
        ]);
    }
}
