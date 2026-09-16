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
            ->with('business')
            ->orderByDesc('rating')
            ->take(8)
            ->get()
            ->map(fn (Package $package): array => $this->presenter->packageCard($package))
            ->all();

        $pins = Package::query()
            ->active()
            ->whereNotNull('lat')
            ->whereNotNull('lng')
            ->get()
            ->map(fn (Package $package): array => [
                'id' => $package->id,
                'title' => $package->title,
                'category' => $package->category,
                'location' => $package->location,
                'lat' => $package->lat,
                'lng' => $package->lng,
                'price_lkr' => $package->price_lkr,
                'images' => $package->images ?? [],
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
