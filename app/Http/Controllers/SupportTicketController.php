<?php

namespace App\Http\Controllers;

use App\Enums\TicketCategory;
use App\Enums\TicketStatus;
use App\Http\Requests\Support\ReplyToTicketRequest;
use App\Http\Requests\Support\StoreTicketRequest;
use App\Models\SupportTicket;
use App\Services\SupportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Help & support: users see their own threads with the BookTrips team.
 */
class SupportTicketController extends Controller
{
    public function __construct(private readonly SupportService $support) {}

    public function index(Request $request): Response
    {
        $user = $request->user();

        $selected = null;
        $ticketId = $request->integer('ticket');

        if ($ticketId > 0) {
            $ticket = SupportTicket::query()
                ->with(['messages.author', 'user'])
                ->where('user_id', $user->id)
                ->whereKey($ticketId)
                ->first();

            $selected = $ticket ? $this->support->summary($ticket) : null;
        }

        return Inertia::render('support/index', [
            'tickets' => SupportTicket::query()
                ->with('user')
                ->where('user_id', $user->id)
                ->latest('last_message_at')
                ->take(30)
                ->get()
                ->map(fn (SupportTicket $ticket): array => $this->support->summary($ticket, false))
                ->all(),
            'selected' => $selected,
            'categories' => array_map(fn (TicketCategory $category): array => [
                'value' => $category->value,
                'label' => $category->label(),
            ], TicketCategory::cases()),
        ]);
    }

    public function store(StoreTicketRequest $request): RedirectResponse
    {
        $ticket = $this->support->open($request->user(), [
            'subject' => $request->string('subject')->value(),
            'category' => $request->string('category')->value(),
            'body' => $request->string('body')->value(),
            'booking_id' => $request->filled('booking_id') ? $request->integer('booking_id') : null,
        ]);

        return to_route('support.index', ['ticket' => $ticket->id])
            ->with('success', 'Request sent. Our team replies here and by email.');
    }

    public function reply(ReplyToTicketRequest $request, SupportTicket $ticket): RedirectResponse
    {
        abort_unless($ticket->user_id === $request->user()->id, 404);

        $this->support->reply($ticket, $request->user(), $request->string('body')->value(), false);

        return to_route('support.index', ['ticket' => $ticket->id])->with('success', 'Message sent.');
    }

    public function status(Request $request, SupportTicket $ticket): RedirectResponse
    {
        abort_unless($ticket->user_id === $request->user()->id, 404);

        $data = $request->validate([
            'status' => ['required', Rule::in(['open', 'resolved'])],
        ]);

        $this->support->setStatus($ticket, TicketStatus::from($data['status']));

        return to_route('support.index', ['ticket' => $ticket->id])->with('success', 'Request updated.');
    }
}
