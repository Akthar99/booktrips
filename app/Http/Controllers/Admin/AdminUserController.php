<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AdminUserController extends Controller
{
    /**
     * Searchable user list.
     */
    public function index(Request $request): Response
    {
        $q = trim((string) $request->query('q', ''));
        $role = (string) $request->query('role', 'all');

        $users = User::query()
            ->with('business')
            ->when($role !== '' && $role !== 'all', fn ($query) => $query->where('role', $role))
            ->when($q !== '', function ($query) use ($q): void {
                $like = '%'.$q.'%';
                $query->where(function ($inner) use ($like): void {
                    $inner->where('name', 'like', $like)
                        ->orWhere('email', 'like', $like)
                        ->orWhere('phone', 'like', $like)
                        ->orWhereHas('business', fn ($business) => $business->where('name', 'like', $like));
                });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString()
            ->through(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone ?? '',
                'role' => $user->role->value,
                'email_verified' => $user->hasVerifiedEmail(),
                'active' => $user->active,
                'created_at' => $user->created_at?->toISOString(),
                'booking_count' => $user->bookings()->count(),
                'business_name' => $user->business?->name,
                'business_approved' => $user->business?->approved,
            ]);

        return Inertia::render('admin/users', [
            'users' => $users,
            'filters' => ['q' => $q, 'role' => $role],
        ]);
    }

    /**
     * Verify or suspend an account. Admin accounts are protected from other admins.
     */
    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $actor = $request->user();

        if ($user->id === $actor->id) {
            throw ValidationException::withMessages([
                'user' => 'You cannot change your own admin account here.',
            ]);
        }

        if ($user->role === UserRole::Admin && $request->has('active')) {
            throw ValidationException::withMessages([
                'user' => 'Admin accounts can only be changed by the account owner.',
            ]);
        }

        if ($request->has('email_verified')) {
            $user->forceFill([
                'email_verified_at' => $request->boolean('email_verified') ? now() : null,
            ]);
        }

        if ($request->has('active')) {
            $user->forceFill(['active' => $request->boolean('active')]);
        }

        $user->save();

        return back()->with('success', 'User updated.');
    }
}
