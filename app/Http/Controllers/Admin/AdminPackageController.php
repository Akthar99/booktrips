<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdatePackageRequest;
use App\Models\Package;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminPackageController extends Controller
{
    /**
     * Searchable listing overview.
     */
    public function index(Request $request): Response
    {
        $q = trim((string) $request->query('q', ''));

        $packages = Package::query()
            ->with('business')
            ->when($q !== '', function ($query) use ($q): void {
                $like = '%'.$q.'%';
                $query->where(function ($inner) use ($like): void {
                    $inner->where('title', 'like', $like)
                        ->orWhere('category', 'like', $like)
                        ->orWhere('location', 'like', $like)
                        ->orWhereHas('business', fn ($business) => $business->where('name', 'like', $like));
                });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Package $package): array => [
                'id' => $package->id,
                'title' => $package->title,
                'slug' => $package->slug,
                'category' => $package->category,
                'location' => $package->location,
                'price_lkr' => $package->price_lkr,
                'active' => $package->active,
                'featured' => $package->featured,
                'rating' => $package->rating,
                'business_name' => $package->business->name ?? '',
            ]);

        return Inertia::render('admin/listings', [
            'packages' => $packages,
            'filters' => ['q' => $q],
        ]);
    }

    /**
     * List, unlist or feature a package.
     */
    public function update(UpdatePackageRequest $request, Package $package): RedirectResponse
    {
        if ($request->has('active')) {
            $package->forceFill(['active' => $request->boolean('active')]);
        }

        if ($request->has('featured')) {
            $package->forceFill(['featured' => $request->boolean('featured')]);
        }

        $package->save();

        return back()->with('success', 'Listing updated.');
    }
}
