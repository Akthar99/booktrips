<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DisputeResolution;
use App\Enums\DisputeStatus;
use App\Enums\DisputeType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ResolveDisputeRequest;
use App\Models\Dispute;
use App\Services\DisputeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminDisputeController extends Controller
{
    public function __construct(private readonly DisputeService $disputes) {}

    /**
     * Every report between a traveller and a partner, newest first.
     */
    public function index(Request $request): Response
    {
        $status = (string) $request->query('status', 'open');
        $type = (string) $request->query('type', '');
        $q = trim((string) $request->query('q', ''));

        $disputes = Dispute::query()
            ->with(['booking.user', 'business', 'raisedBy', 'against'])
            ->when($status === 'open', fn ($query) => $query->open())
            ->when($status === 'resolved', fn ($query) => $query->where('status', DisputeStatus::Resolved))
            ->when($type !== '', fn ($query) => $query->where('type', $type))
            ->when($q !== '', function ($query) use ($q): void {
                $like = '%'.$q.'%';
                $query->where(function ($inner) use ($like): void {
                    $inner->whereHas('booking', fn ($booking) => $booking->where('booking_code', 'like', $like))
                        ->orWhereHas('business', fn ($business) => $business->where('name', 'like', $like))
                        ->orWhere('summary', 'like', $like);
                });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Dispute $dispute): array => [
                ...$this->disputes->summary($dispute),
                'booking' => $dispute->booking ? [
                    'id' => $dispute->booking->id,
                    'booking_code' => $dispute->booking->booking_code,
                    'check_in' => $dispute->booking->check_in->toDateString(),
                    'total_lkr' => $dispute->booking->total_lkr,
                    'status' => $dispute->booking->status->value,
                    'traveller' => [
                        'id' => $dispute->booking->user?->id,
                        'name' => $dispute->booking->user?->name,
                        'strikes' => $dispute->booking->user?->strikes,
                    ],
                ] : null,
                'business' => $dispute->business ? [
                    'id' => $dispute->business->id,
                    'name' => $dispute->business->name,
                    'strikes' => $dispute->business->strikes,
                ] : null,
                'raised_by' => $dispute->raisedBy ? [
                    'id' => $dispute->raisedBy->id,
                    'name' => $dispute->raisedBy->name,
                    'role' => $dispute->raisedBy->role->value,
                ] : null,
                'against' => $dispute->against ? [
                    'id' => $dispute->against->id,
                    'name' => $dispute->against->name,
                    'strikes' => $dispute->against->strikes,
                ] : null,
            ]);

        return Inertia::render('admin/disputes', [
            'disputes' => $disputes,
            'filters' => ['status' => $status, 'type' => $type, 'q' => $q],
            'types' => array_map(fn (DisputeType $type): array => [
                'value' => $type->value,
                'label' => $type->label(),
            ], DisputeType::cases()),
            'resolutions' => array_map(fn (DisputeResolution $resolution): array => [
                'value' => $resolution->value,
                'label' => $resolution->label(),
            ], DisputeResolution::cases()),
            'counts' => [
                'open' => Dispute::query()->open()->count(),
                'awaiting' => Dispute::query()->where('status', DisputeStatus::AwaitingResponse)->count(),
                'ready' => Dispute::query()->open()->whereNotNull('responded_at')->count(),
            ],
        ]);
    }

    /**
     * Hand down the verdict (strikes, suspension or a commission penalty).
     */
    public function resolve(ResolveDisputeRequest $request, Dispute $dispute): RedirectResponse
    {
        $this->authorize('resolve', $dispute);

        $this->disputes->resolve($dispute, $request->user(), [
            'resolution' => $request->string('resolution')->value(),
            'penalty' => $request->input('penalty', 'none'),
            'penalty_amount_lkr' => $request->integer('penalty_amount_lkr'),
            'resolution_note' => $request->input('resolution_note'),
        ]);

        return back()->with('success', 'Report resolved and both sides notified.');
    }
}
