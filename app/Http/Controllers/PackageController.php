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

        // Everything below is filtered, sorted and paginated in the database. The old
        // version pulled every active package into memory and sliced it in PHP, which
        // got slower with every listing added.
        $dates = $this->priceDates();

        $packages = Package::query()
            ->active()
            ->select([
                'id', 'business_id', 'title', 'slug', 'category', 'highlight', 'location', 'district',
                'images', 'price_lkr', 'price_type', 'duration_days', 'duration_nights', 'rating',
                'review_count', 'featured', 'active', 'discount_enabled', 'discount_type',
                'discount_value', 'discount_start', 'discount_end',
            ])
            ->with('business:id,name,type,city,cover_image')
            ->when($query !== '', function ($builder) use ($query): void {
                $like = '%'.$query.'%';
                $builder->where(function ($inner) use ($like): void {
                    $inner->where('title', 'like', $like)
                        ->orWhere('location', 'like', $like)
                        ->orWhere('district', 'like', $like)
                        ->orWhere('category', 'like', $like)
                        ->orWhere('description', 'like', $like);
                });
            })
            ->when($category !== '' && $category !== 'all', fn ($builder) => $builder->whereIn('category', array_map('trim', explode(',', $category))))
            ->when($district !== '', fn ($builder) => $builder->where('district', $district))
            ->when($location !== '', function ($builder) use ($location): void {
                $like = '%'.$location.'%';
                $builder->where(function ($inner) use ($like): void {
                    $inner->where('location', 'like', $like)->orWhere('district', 'like', $like);
                });
            })
            ->when($featured === '1', fn ($builder) => $builder->where('featured', true))
            ->when($guests !== null && $guests !== '', fn ($builder) => $builder->where('max_guests', '>=', (int) $guests))
            ->when($minPrice !== null && $minPrice !== '', fn ($builder) => $builder->whereRaw(self::PRICE_SQL.' >= ?', [...$dates, (int) $minPrice]))
            ->when($maxPrice !== null && $maxPrice !== '', fn ($builder) => $builder->whereRaw(self::PRICE_SQL.' <= ?', [...$dates, (int) $maxPrice]))
            ->when(true, function ($builder) use ($sort, $dates): void {
                match ($sort) {
                    'price_asc' => $builder->orderByRaw(self::PRICE_SQL.' asc', $dates),
                    'price_desc' => $builder->orderByRaw(self::PRICE_SQL.' desc', $dates),
                    'rating' => $builder->orderByRaw('rating desc'),
                    default => $builder->orderByRaw('featured desc, rating desc'),
                };
            })
            ->orderByDesc('id')
            ->paginate(12)
            ->withQueryString();

        $cards = $packages->getCollection()
            ->map(fn (Package $package): array => $this->presenter->packageCard($package));

        return Inertia::render('search', [
            'packages' => $cards->values()->all(),
            'total' => $packages->total(),
            'pagination' => [
                'page' => $packages->currentPage(),
                'last_page' => $packages->lastPage(),
                'per_page' => $packages->perPage(),
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
            'seo' => $this->presenter->packageSeo($model),
        ]);
    }

    /**
     * SQL twin of PricingService::pricingFor()'s unit price, so the catalogue can filter
     * and sort on the discounted price instead of loading every listing into PHP.
     *
     * The four date placeholders take today's date, in the order the branches read them.
     */
    private const string PRICE_SQL = "case
            when discount_enabled = 1 and discount_value > 0
                and (discount_start is null or discount_start <= ?)
                and (discount_end is null or discount_end >= ?)
                and discount_type = 'percentage'
                then price_lkr - round(price_lkr * 1.0 * (case when discount_value > 100 then 100 else discount_value end) / 100.0)
            when discount_enabled = 1 and discount_value > 0
                and (discount_start is null or discount_start <= ?)
                and (discount_end is null or discount_end >= ?)
                and discount_type = 'fixed'
                then case when round(discount_value) >= price_lkr then 0 else price_lkr - round(discount_value) end
            else price_lkr
        end";

    /**
     * @return array<int, string>
     */
    private function priceDates(): array
    {
        $today = now()->toDateString();

        return [$today, $today, $today, $today];
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
            ->select(['id', 'title', 'location', 'lat', 'lng', 'category', 'price_lkr'])
            ->get()
            ->map(fn (Package $package): array => [
                'id' => $package->id,
                'title' => $package->title,
                'location' => $package->location,
                'category' => $package->category,
                'lat' => $package->lat,
                'lng' => $package->lng,
                'price_lkr' => $package->price_lkr,
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
