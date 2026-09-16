<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Inertia\Inertia;
use Inertia\Response;

class AdminReviewController extends Controller
{
    /**
     * Every review, newest first.
     */
    public function index(): Response
    {
        return Inertia::render('admin/reviews', [
            'reviews' => Review::query()
                ->with(['package', 'user'])
                ->latest()
                ->paginate(20)
                ->through(fn (Review $review): array => [
                    'id' => $review->id,
                    'rating' => $review->rating,
                    'title' => $review->title,
                    'comment' => $review->comment,
                    'created_at' => $review->created_at?->toISOString(),
                    'package_title' => $review->package->title ?? '',
                    'package_id' => $review->package_id,
                    'user_name' => $review->user->name ?? 'Guest',
                ]),
        ]);
    }
}
