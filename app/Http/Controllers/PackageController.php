<?php

namespace App\Http\Controllers;

use App\Http\Requests\Booking\QuoteRequest;
use App\Models\Package;
use App\Models\Review;
use App\Presenters\CatalogPresenter;
use App\Services\PricingService;
use App\Services\ScheduleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PackageController extends Controller
{
    public function __construct(
        private readonly CatalogPresenter $presenter,
        private readonly ScheduleService $schedule,
        private readonly PricingService $pricing,
    ) {}

    /**
     * Public listing with the Agoda-style filters.
     */
    public function index(Request $request): Response
    {
        $query = trim((string) $request->query('q', ''));
        $category = (string) $request->query('category', '');
        $district = trim((string) $request->query('district', ''));
        $location = trim((string) $request->query('location', ''));
        $minPrice = $request->query('minPrice');
        $maxPrice = $request->query('maxPrice');
        $guests = $request->query('guests');
        $featured = $request->query('featured');
        $sort = (string) $request->query('sort', 'featured');

        $packages = Package::query()
            ->active()
            ->with('business')
            ->get();

        if ($query !== '') {
            $needle = mb_strtolower($query);
            $packages = $packages->filter(fn (Package $package): bool => str_contains(mb_strtolower($package->title), $needle)
                || str_contains(mb_strtolower($package->location), $needle)
                || str_contains(mb_strtolower((string) $package->district), $needle)
                || str_contains(mb_strtolower($package->category), $needle)
                || str_contains(mb_strtolower($package->description), $needle));
        }

        if ($category !== '' && $category !== 'all') {
            $categories = array_map('trim', explode(',', $category));
            $packages = $packages->whereIn('category', $categories);
        }

        if ($district !== '') {
            $needle = mb_strtolower($district);
            $packages = $packages->filter(fn (Package $package): bool => mb_strtolower((string) $package->district) === $needle);
        }

        if ($location !== '') {
            $needle = mb_strtolower($location);
            $packages = $packages->filter(fn (Package $package): bool => str_contains(mb_strtolower($package->location), $needle)
                || str_contains(mb_strtolower((string) $package->district), $needle));
        }

        if ($featured === '1') {
            $packages = $packages->where('featured', true);
        }

        if ($guests !== null && $guests !== '') {
            $wanted = (int) $guests;
            $packages = $packages->filter(fn (Package $package): bool => $package->max_guests >= $wanted);
        }

        $cards = $packages->map(fn (Package $package): array => $this->presenter->packageCard($package));

        if ($minPrice !== null && $minPrice !== '') {
            $cards = $cards->filter(fn (array $card): bool => $card['display_price_lkr'] >= (int) $minPrice);
        }

        if ($maxPrice !== null && $maxPrice !== '') {
            $cards = $cards->filter(fn (array $card): bool => $card['display_price_lkr'] <= (int) $maxPrice);
        }

        $cards = match ($sort) {
            'price_asc' => $cards->sortBy('display_price_lkr')->values(),
            'price_desc' => $cards->sortByDesc('display_price_lkr')->values(),
            'rating' => $cards->sortByDesc('rating')->values(),
            default => $cards->sortByDesc(fn (array $card): string => sprintf('%d-%08d', $card['featured'] ? 1 : 0, (int) round($card['rating'] * 1000)))->values(),
        };

        $total = $cards->count();
        $perPage = 12;
        $page = max((int) $request->query('page', 1), 1);
        $lastPage = max((int) ceil($total / $perPage), 1);

        return Inertia::render('search', [
            'packages' => $cards->slice(($page - 1) * $perPage, $perPage)->values()->all(),
            'total' => $total,
            'pagination' => [
                'page' => $page,
                'last_page' => $lastPage,
                'per_page' => $perPage,
            ],
            'categories' => config('booktrips.categories'),
            'destinations' => config('booktrips.destinations'),
            'filters' => [
                'q' => $query,
                'category' => $category,
                'district' => $district,
                'location' => $location,
                'check_in' => (string) $request->query('check_in', ''),
                'check_out' => (string) $request->query('check_out', ''),
                'minPrice' => $minPrice,
                'maxPrice' => $maxPrice,
                'guests' => $guests,
                'featured' => $featured,
                'sort' => $sort,
            ],
        ]);
    }

    /**
     * Public package detail, reachable by id or slug.
     */
    public function show(string $package): Response
    {
        $model = $this->resolvePackage($package);

        if (! $model || ! $model->active) {
            abort(404);
        }

        $model->load('business');

        $reviews = Review::query()
            ->where('package_id', $model->id)
            ->with('user')
            ->latest()
            ->get()
            ->map(fn (Review $review): array => [
                'id' => $review->id,
                'rating' => $review->rating,
                'title' => $review->title,
                'comment' => $review->comment,
                'user_name' => $review->user->name ?? 'Guest',
                'created_at' => $review->created_at?->toISOString(),
            ])
            ->all();

        return Inertia::render('packages/show', [
            'package' => $this->presenter->packageDetail($model),
            'reviews' => $reviews,
        ]);
    }

    /**
     * Map pins for the explore page.
     */
    public function map(): Response
    {
        $pins = Package::query()
            ->active()
            ->whereNotNull('lat')
            ->whereNotNull('lng')
            ->get()
            ->map(fn (Package $package): array => [
                'id' => $package->id,
                'title' => $package->title,
                'slug' => $package->slug,
                'category' => $package->category,
                'location' => $package->location,
                'lat' => $package->lat,
                'lng' => $package->lng,
                'price_lkr' => $package->price_lkr,
                'images' => $package->images ?? [],
            ])
            ->values()
            ->all();

        return Inertia::render('map', [
            'packages' => $pins,
            'categories' => config('booktrips.categories'),
        ]);
    }

    /**
     * Server-side price quote for a stay (never trust client-side totals).
     */
    public function quote(QuoteRequest $request, Package $package): JsonResponse
    {
        if (! $package->active) {
            abort(404);
        }

        $checkIn = $request->string('check_in')->value();
        $checkOut = $request->string('check_out')->value();
        $guests = $request->integer('guests');

        if (! $this->schedule->allowsStay($package, $checkIn, $checkOut)) {
            throw ValidationException::withMessages([
                'check_in' => 'Those dates are outside this package’s running days.',
            ]);
        }

        if ($guests < $package->min_guests || $guests > $package->max_guests) {
            throw ValidationException::withMessages([
                'guests' => "This package is for {$package->min_guests}–{$package->max_guests} guests.",
            ]);
        }

        $pricing = $this->pricing->calculateTotal($package, $guests, $checkIn, $checkOut);

        return response()->json([
            'nights' => $pricing['nights'],
            'units' => $pricing['units'],
            'guests' => $pricing['guests'],
            'price_lkr' => $package->price_lkr,
            'price_type' => $package->price_type->value,
            'base_total_lkr' => $pricing['base_total_lkr'],
            'discount_lkr' => $pricing['discount_lkr'],
            'discount_active' => $pricing['discount_active'],
            'discount_label' => $pricing['discount_label'],
            'total_lkr' => $pricing['total_lkr'],
            'payment_method' => 'pay_at_destination',
        ]);
    }

    private function resolvePackage(string $value): ?Package
    {
        $query = Package::query();

        return ctype_digit($value)
            ? $query->whereKey((int) $value)->first()
            : $query->where('slug', $value)->first();
    }
}
