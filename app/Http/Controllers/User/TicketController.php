<?php

namespace App\Http\Controllers\User;

use App\Enums\TicketCategory;
use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\TransactionResult;
use App\Support\TicketNumber;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TicketController extends Controller
{
    public function index(): View
    {
        $tickets = auth()->user()->tickets()
            ->latest('id')
            ->paginate(10);

        return view('user.tickets.index', compact('tickets'));
    }

    public function create(): View
    {
        return view('user.tickets.create', [
            'category' => TicketCategory::tryFrom((string) request('kategori')) ?? TicketCategory::Complaint,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'category' => ['required', Rule::enum(TicketCategory::class)],
            'subject' => ['required', 'string', 'max:150'],
            'description' => ['required', 'string', 'min:10', 'max:3000'],
            'company_name' => ['required_if:category,supplier', 'nullable', 'string', 'max:150'],
            'company_phone' => ['required_if:category,supplier', 'nullable', 'string', 'max:30'],
        ], [
            'description.min' => 'Ceritakan kendala/masukan minimal 10 karakter.',
            'company_name.required_if' => 'Nama perusahaan wajib diisi untuk pengajuan supplier.',
            'company_phone.required_if' => 'Nomor perusahaan wajib diisi untuk pengajuan supplier.',
        ]);

        $ticket = auth()->user()->tickets()->create([
            'code' => TicketNumber::generate(),
            'category' => $validated['category'],
            'subject' => $validated['subject'],
            'description' => $validated['description'],
            'company_name' => $validated['company_name'] ?? null,
            'company_phone' => $validated['company_phone'] ?? null,
        ]);

        User::permission('tickets.view')->get()->each(
            fn (User $admin) => $admin->notify(new TransactionResult(
                title: 'Tiket baru dari '.auth()->user()->name,
                body: $ticket->code.' · '.$ticket->category->label().' · '.$ticket->subject,
                level: 'info',
                icon: $ticket->category->icon(),
                url: route('admin.tickets.show', $ticket),
            )),
        );

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('status', 'Tiket '.$ticket->code.' berhasil dibuat. Tim kami akan segera menindaklanjuti.');
    }

    public function show(Ticket $ticket): View
    {
        abort_unless($ticket->user_id === auth()->id(), 404);

        $ticket->load(['replies.user']);

        return view('user.tickets.show', compact('ticket'));
    }

    public function reply(Request $request, Ticket $ticket): RedirectResponse
    {
        abort_unless($ticket->user_id === auth()->id(), 404);

        if ($ticket->status->isFinal()) {
            return back()->with('error', 'Tiket ini sudah selesai dan tidak dapat dibalas lagi.');
        }

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:3000'],
        ]);

        $ticket->replies()->create([
            'user_id' => auth()->id(),
            'body' => $validated['body'],
            'is_staff' => false,
        ]);

        return back()->with('status', 'Balasan terkirim.');
    }
}
