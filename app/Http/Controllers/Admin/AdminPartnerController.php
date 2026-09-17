<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\PartnerApprovedMail;
use App\Models\Business;
use App\Services\MailService;
use App\Services\NotificationService;
use App\Services\PhoneVerificationService;
use App\Services\SmsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminPartnerController extends Controller
{
    public function __construct(
        private readonly NotificationService $notifications,
        private readonly MailService $mail,
        private readonly SmsService $sms,
        private readonly PhoneVerificationService $phones,
    ) {}

    /**
     * Pending and approved partner requests, with everything needed to decide.
     */
    public function index(): Response
    {
        return Inertia::render('admin/partners', [
            'businesses' => Business::query()
                ->with('user')
                ->orderBy('approved')
                ->latest()
                ->paginate(20)
                ->through(fn (Business $business): array => [
                    'id' => $business->id,
                    'name' => $business->name,
                    'type' => $business->type->value,
                    'type_label' => $business->type->label(),
                    'description' => $business->description,
                    'address' => $business->address,
                    'city' => $business->city,
                    'district' => $business->district,
                    'phone' => $business->phone,
                    'email' => $business->email,
                    'website' => $business->website,
                    'cover_image' => $business->cover_image,
                    'instagram' => $business->instagram,
                    'facebook' => $business->facebook,
                    'tiktok' => $business->tiktok,
                    'whatsapp' => $business->whatsapp,
                    'approved' => $business->approved,
                    'phone_verified_at' => $business->phone_verified_at?->toISOString(),
                    'created_at' => $business->created_at?->toISOString(),
                    'owner' => $business->user ? [
                        'id' => $business->user->id,
                        'name' => $business->user->name,
                        'email' => $business->user->email,
                        'phone' => $business->user->phone,
                        'email_verified' => $business->user->hasVerifiedEmail(),
                        'joined_at' => $business->user->created_at?->toISOString(),
                    ] : null,
                ]),
        ]);
    }

    /**
     * Approve or revoke a partner.
     */
    public function approve(Request $request, Business $business): RedirectResponse
    {
        $validated = $request->validate([
            'approved' => ['required', 'boolean'],
        ]);

        $this->authorize('approve', $business);

        $business->loadMissing('user');

        $approved = (bool) $validated['approved'];
        $wasApproved = $business->approved;

        $business->forceFill(['approved' => $approved])->save();

        if ($business->user) {
            $this->notifications->notify(
                $business->user,
                'partner',
                $approved ? 'Partner account approved' : 'Partner access revoked',
                $approved
                    ? 'You can now publish packages and manage reservations.'
                    : 'Your partner tools are on hold. Contact BookTrips for details.',
                null,
                $approved ? '/partners/dashboard' : null,
            );
        }

        // Congratulate first-time approvals only, never re-sends of the same state.
        if ($approved && ! $wasApproved) {
            $this->mail->quietSend($business->email ?? $business->user?->email, new PartnerApprovedMail($business));

            $phone = $this->phones->normalise($business->phone);

            if ($phone !== null) {
                $this->sms->send($phone, 'Great news — BookTrips approved your partner application. Sign in and start publishing your packages.');
            }
        }

        return back()->with('success', $approved ? 'Partner approved.' : 'Partner access revoked.');
    }
}
