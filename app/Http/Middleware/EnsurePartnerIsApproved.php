<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePartnerIsApproved
{
    /**
     * Partner areas are locked until a super admin approves the business.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var User|null $user */
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        // Admins work from their own console; these pages expect a business.
        if ($user->isAdmin()) {
            return to_route('admin.overview');
        }

        if (! $user->isPartner()) {
            return to_route('partner.apply')
                ->with('error', 'Create a partner account to open the host tools.');
        }

        $business = $user->business;

        if (! $business) {
            return to_route('partner.apply')
                ->with('error', 'Tell us about your business to open the host tools.');
        }

        if (! $business->approved) {
            return to_route('partner.pending')->with('error', 'Your partner request is still waiting for approval.');
        }

        return $next($request);
    }
}
