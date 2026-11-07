<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TicketCategory;
use App\Enums\TicketStatus;
use App\Http\Controllers\Admin\Concerns\HasPerPage;
use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\TicketReply;
use App\Notifications\TransactionResult;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TicketController extends Controller
{
    use HasPerPage;

    public function index(Request $request): View|JsonResponse
    {
        $status = TicketStatus::tryFrom((string) $request->query('status'));
        $category = TicketCategory::tryFrom((string) $request->query('category'));
        $search = trim((string) $request->query('q'));

        $tickets = Ticket::query()
            ->with('user')
            ->withCount('replies')
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($category, fn ($q) => $q->where('category', $category))
            ->when($search !== '', fn ($q) => $q->where(function ($q) use ($search): void {
                $q->where('code', 'like', '%'.$search.'%')
                    ->orWhere('subject', 'like', '%'.$search.'%')
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', '%'.$search.'%')->orWhere('email', 'like', '%'.$search.'%'));
            }))
            ->latest('id')
            ->paginate($this->perPage($request))->withQueryString()
            ->withQueryString();

        $data = [
            'tickets' => $tickets,
            'listRoute' => route('admin.tickets.index'),
            'status' => $status,
            'category' => $category,
            'search' => $search,
        ];

        if ($request->ajax()) {
            return response()->json([
                'rows' => view('admin.tickets._rows', $data)->render(),
                'footer' => view('admin.tickets._footer', $data)->render(),
            ]);
        }

        return view('admin.tickets.index', $data);
    }

    public function show(Ticket $ticket): View
    {
        $ticket->load(['user', 'replies.user']);

        return view('admin.tickets.show', [
            'ticket' => $ticket,
            'statuses' => TicketStatus::options(),
        ]);
    }

    public function reply(Request $request, Ticket $ticket): RedirectResponse
    {
        $validated = $request->validate([
            'body' => ['required', 'string', 'max:3000'],
        ]);

        TicketReply::create([
            'ticket_id' => $ticket->getKey(),
            'user_id' => auth()->id(),
            'body' => $validated['body'],
            'is_staff' => true,
        ]);

        if ($ticket->status === TicketStatus::Open) {
            $ticket->update(['status' => TicketStatus::InProgress]);
        }

        $ticket->user?->notify(new TransactionResult(
            title: 'Balasan tiket '.$ticket->code,
            body: 'Tim Atlantic Pedia membalas tiket Anda.',
            level: 'info',
            icon: 'support_agent',
            url: route('tickets.show', $ticket),
        ));

        return back()->with('status', 'Balasan terkirim.');
    }

    public function updateStatus(Request $request, Ticket $ticket): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::enum(TicketStatus::class)],
        ]);

        $status = TicketStatus::from($validated['status']);

        $ticket->update([
            'status' => $status,
            'closed_at' => $status->isFinal() ? now() : null,
        ]);

        $ticket->user?->notify(new TransactionResult(
            title: 'Status tiket '.$ticket->code,
            body: 'Tiket Anda kini berstatus '.$status->label().'.',
            level: $status === TicketStatus::Resolved ? 'success' : 'info',
            icon: 'confirmation_number',
            url: route('tickets.show', $ticket),
        ));

        return back()->with('status', 'Status tiket diperbarui.');
    }
}
