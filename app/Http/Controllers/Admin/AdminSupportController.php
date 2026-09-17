<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TicketStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Support\ReplyToTicketRequest;
use App\Models\SupportTicket;
use App\Services\SupportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AdminSupportController extends Controller
{
    public function __construct(private readonly SupportService $support) {}

    /**
     * Every support thread, newest activity first.
     */
    public function index(Request $request): Response
    {
        $status = (string) $request->query('status', 'open');
        $q = trim((string) $request->query('q', ''));

        $tickets = SupportTicket::query()
            ->with(['user', 'messages.author'])
            ->when($status === 'open', fn ($query) => $query->where('status', '!=', TicketStatus::Resolved))
            ->when($status === 'resolved', fn ($query) => $query->where('status', TicketStatus::Resolved))
            ->when($status === 'awaiting_admin', fn ($query) => $query->where('status', TicketStatus::AwaitingAdmin))
            ->when($q !== '', function ($query) use ($q): void {
                $like = '%'.$q.'%';
                $query->where(function ($inner) use ($like): void {
                    $inner->where('subject', 'like', $like)
                        ->orWhereHas('user', fn ($user) => $user->where('name', 'like', $like)->orWhere('email', 'like', $like));
                });
            })
            ->latest('last_message_at')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (SupportTicket $ticket): array => $this->support->summary($ticket, false));

        $selected = null;
        $ticketId = $request->integer('ticket');

        if ($ticketId > 0) {
            $ticket = SupportTicket::query()
                ->with(['messages.author', 'user'])
                ->whereKey($ticketId)
                ->first();

            if ($ticket) {
                $selected = [
                    ...$this->support->summary($ticket),
                    'booking_id' => $ticket->booking_id,
                ];
            }
        }

        return Inertia::render('admin/support', [
            'tickets' => $tickets,
            'selected' => $selected,
            'filters' => ['status' => $status, 'q' => $q],
            'counts' => [
                'open' => SupportTicket::query()->where('status', '!=', TicketStatus::Resolved)->count(),
                'awaiting_admin' => SupportTicket::query()->where('status', TicketStatus::AwaitingAdmin)->count(),
            ],
        ]);
    }

    public function reply(ReplyToTicketRequest $request, SupportTicket $ticket): RedirectResponse
    {
        $this->support->reply($ticket, $request->user(), $request->string('body')->value(), true);

        return back()->with('success', 'Reply sent to the user.');
    }

    public function status(Request $request, SupportTicket $ticket): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::enum(TicketStatus::class)],
        ]);

        $this->support->setStatus($ticket, TicketStatus::from($data['status']));

        return back()->with('success', 'Ticket updated.');
    }
}
