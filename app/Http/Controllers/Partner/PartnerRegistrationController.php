<?php

namespace App\Http\Controllers\Partner;

use App\Enums\BusinessType;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Partner\RegisterPartnerRequest;
use App\Models\Business;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\PhoneVerificationService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class PartnerRegistrationController extends Controller
{
    public function __construct(
        private readonly NotificationService $notifications,
        private readonly PhoneVerificationService $phones,
    ) {}

    /**
     * Show the partner application page (guests sign up, travellers upgrade).
     */
    public function create(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        if ($user instanceof User && $redirect = $this->redirectExistingUser($user)) {
            return $redirect;
        }

        return Inertia::render('partners/apply', [
            'mode' => $user instanceof User ? 'upgrade' : 'register',
            'account' => $user instanceof User ? [
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone ?? '',
            ] : null,
            'verifiedPhone' => $this->phones->sessionVerifiedPhone(),
            'businessTypes' => array_map(
                fn ($case): array => ['slug' => $case->value, 'name' => $case->label()],
                BusinessType::cases(),
            ),
        ]);
    }

    /**
     * Waiting room shown while a super admin reviews the request.
     */
    public function pending(Request $request): Response|RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->isAdmin()) {
            return to_route('admin.overview');
        }

        if (! $user->isPartner()) {
            return to_route('partner.apply');
        }

        $business = $user->business;

        if ($business?->approved) {
            return to_route('partner.dashboard');
        }

        return Inertia::render('partners/pending', [
            'emailVerified' => $user->hasVerifiedEmail(),
            'business' => $business ? [
                'name' => $business->name,
                'city' => $business->city,
                'type' => $business->type->value,
                'approved' => $business->approved,
            ] : null,
        ]);
    }

    /**
     * Create the owner account and the pending business, or upgrade a signed-in traveller.
     */
    public function store(RegisterPartnerRequest $request): RedirectResponse
    {
        $user = $request->user();

        if ($user instanceof User) {
            return $this->upgrade($request, $user);
        }

        [$user, $business] = DB::transaction(function () use ($request): array {
            $user = User::create([
                'name' => $request->string('name')->value(),
                'email' => $request->string('email')->value(),
                'phone' => $request->filled('phone') ? $request->string('phone')->value() : null,
                'password' => $request->string('password')->value(),
            ]);

            $user->forceFill(['role' => UserRole::Business])->save();

            return [$user, $this->openBusiness($request, $user)];
        });

        event(new Registered($user));

        Auth::login($user);

        $request->session()->regenerate();

        $this->announce($business);

        return to_route('partner.pending')
            ->with('success', 'Request received. A super admin will review it. You get the partner panel after approval.');
    }

    /**
     * Turn a signed-in traveller into a pending partner on the same account.
     */
    private function upgrade(RegisterPartnerRequest $request, User $user): RedirectResponse
    {
        if ($user->isAdmin()) {
            return to_route('admin.overview');
        }

        if ($user->business) {
            return to_route('partner.pending');
        }

        $business = DB::transaction(function () use ($request, $user): Business {
            $user->forceFill([
                'name' => $request->string('name')->value(),
                'phone' => $request->filled('phone') ? $request->string('phone')->value() : $user->phone,
                'role' => UserRole::Business,
            ])->save();

            return $this->openBusiness($request, $user);
        });

        $this->announce($business);

        return to_route('partner.pending')
            ->with('success', 'Request received. A super admin will review it. Your account is now a partner account.');
    }

    /**
     * Where an already signed-in user belongs when they open the application page.
     */
    private function redirectExistingUser(User $user): ?RedirectResponse
    {
        if ($user->isAdmin()) {
            return to_route('admin.overview');
        }

        if (! $user->isPartner()) {
            return null;
        }

        $business = $user->business;

        if ($business?->approved) {
            return to_route('partner.dashboard');
        }

        return $business ? to_route('partner.pending') : null;
    }

    /**
     * Create the pending business. Owner and approval state are set directly
     * because neither may be mass assigned from request input.
     */
    private function openBusiness(RegisterPartnerRequest $request, User $owner): Business
    {
        $business = new Business($this->businessAttributes($request, $owner));

        $business->user_id = $owner->id;
        $business->approved = false;
        $business->phone_verified_at = now();
        $business->save();

        return $business;
    }

    /**
     * @return array<string, mixed>
     */
    private function businessAttributes(RegisterPartnerRequest $request, User $owner): array
    {
        return [
            'name' => $request->string('business_name')->value(),
            'type' => $request->string('type')->value(),
            'description' => $request->input('description'),
            'address' => $request->input('address'),
            'city' => $request->string('city')->value(),
            'district' => $request->input('district'),
            'phone' => $request->input('phone') ?? $owner->phone,
            'email' => $owner->email,
            'website' => $request->input('website'),
            'cover_image' => $request->input('cover_image'),
            'instagram' => $request->input('instagram'),
            'facebook' => $request->input('facebook'),
            'tiktok' => $request->input('tiktok'),
            'whatsapp' => $request->input('whatsapp'),
        ];
    }

    /**
     * Tell every super admin a request needs their eyes.
     */
    private function announce(Business $business): void
    {
        $this->notifications->notifyAdmins(
            'partner_application',
            "New partner request: {$business->name}",
            "{$business->city} · waiting for review.",
            null,
            '/admin/partners',
        );
    }
}
