<?php

namespace App\Http\Middleware;

use App\Models\Business;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => fn () => $this->authUser($request),
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'status' => fn () => $request->session()->get('status'),
            ],
            'notifications' => fn () => $this->notifications($request),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function authUser(Request $request): ?array
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return null;
        }

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone ?? '',
            'role' => $user->role->value,
            'email_verified' => $user->hasVerifiedEmail(),
            'pending_email' => $user->pending_email,
            'business' => $user->business ? $this->business($user->business) : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function business(Business $business): array
    {
        return [
            'id' => $business->id,
            'name' => $business->name,
            'approved' => $business->approved,
            'type' => $business->type->value,
            'city' => $business->city,
            'cover_image' => $business->cover_image,
        ];
    }

    /**
     * @return array{items: array<int, array<string, mixed>>, unread: int}
     */
    private function notifications(Request $request): array
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return ['items' => [], 'unread' => 0];
        }

        return [
            'items' => $user->notifications()
                ->latest()
                ->take(20)
                ->get()
                ->map(fn ($notification): array => [
                    'id' => $notification->id,
                    'type' => $notification->data['type'] ?? 'info',
                    'title' => $notification->data['title'] ?? '',
                    'body' => $notification->data['body'] ?? '',
                    'booking_id' => $notification->data['booking_id'] ?? null,
                    'link' => $notification->data['link'] ?? null,
                    'read' => $notification->read_at !== null,
                    'created_at' => $notification->created_at?->diffForHumans(),
                ])
                ->values()
                ->all(),
            'unread' => $user->unreadNotifications()->count(),
        ];
    }
}
