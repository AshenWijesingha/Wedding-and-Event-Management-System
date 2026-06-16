<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ClientResource;
use App\Models\Client;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ClientController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Client::class, 'client');
    }

    public function index(Request $request): Response
    {
        $clients = Client::withCount(['bookings', 'inquiries'])
            ->when($request->search, fn ($q, $search) =>
                $q->where(fn ($sub) =>
                    $sub->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                )
            )
            ->orderBy('first_name')
            ->paginate(15)
            ->withQueryString()
            ->through(fn ($c) => [
                'id'              => $c->id,
                'name'           => trim("{$c->first_name} {$c->last_name}"),
                'email'          => $c->email,
                'phone'          => $c->phone,
                'bookings_count' => $c->bookings_count,
                'inquiries_count' => $c->inquiries_count,
                'created_at'     => $c->created_at?->toDateString(),
            ]);

        return Inertia::render('Clients/Index', [
            'clients' => $clients,
            'filters' => $request->only(['search']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Clients/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateClient($request);

        Client::create($data);

        return redirect('/admin/clients')->with('success', 'Client created successfully.');
    }

    public function show(Client $client): Response
    {
        $bookings = $client->bookings()->with('venue:id,name')->latest('event_date')->get()
            ->map(fn ($b) => [
                'id'         => $b->id,
                'event_type' => $b->event_type,
                'event_date' => $b->event_date?->toDateString(),
                'venue'      => $b->venue?->name,
                'status'     => $b->status,
                'total'      => (float) $b->total_amount,
                'balance'    => (float) $b->balance_amount,
            ]);

        $inquiries = $client->inquiries()->latest()->get()
            ->map(fn ($i) => [
                'id'         => $i->id,
                'event_type' => $i->event_type,
                'status'     => $i->status,
                'created_at' => $i->created_at?->toDateString(),
            ]);

        $quotations = $client->quotations()->latest()->get()
            ->map(fn ($q) => [
                'id'         => $q->id,
                'number'     => $q->quotation_number,
                'status'     => $q->status,
                'total'      => (float) $q->total_amount,
                'created_at' => $q->created_at?->toDateString(),
            ]);

        $payments = $client->payments()->latest('payment_date')->get()
            ->map(fn ($p) => [
                'id'           => $p->id,
                'number'       => $p->payment_number,
                'amount'       => (float) $p->amount,
                'status'       => $p->status,
                'payment_date' => $p->payment_date?->toDateString(),
            ]);

        $stats = [
            'lifetime_value' => (float) $client->payments()->where('status', 'completed')->sum('amount'),
            'open_balance'   => (float) $client->bookings()->whereNotIn('status', ['cancelled'])->sum('balance_amount'),
            'bookings_count' => $bookings->count(),
        ];

        return Inertia::render('Clients/Show', [
            'client'     => new ClientResource($client),
            'bookings'   => $bookings,
            'inquiries'  => $inquiries,
            'quotations' => $quotations,
            'payments'   => $payments,
            'stats'      => $stats,
        ]);
    }

    public function edit(Client $client): Response
    {
        return Inertia::render('Clients/Edit', [
            'client' => new ClientResource($client),
        ]);
    }

    public function update(Request $request, Client $client): RedirectResponse
    {
        $data = $this->validateClient($request, $client);

        $client->update($data);

        return redirect("/admin/clients/{$client->id}")->with('success', 'Client updated.');
    }

    public function destroy(Client $client): RedirectResponse
    {
        if ($client->bookings()->exists() || $client->quotations()->exists()) {
            return back()->with('error', 'Cannot delete a client with bookings or quotations on record.');
        }

        $client->delete();

        return redirect('/admin/clients')->with('success', 'Client deleted.');
    }

    /**
     * Shared validation for store/update. Email is unique per tenant, ignoring the
     * client being updated.
     */
    private function validateClient(Request $request, ?Client $client = null): array
    {
        return $request->validate([
            'first_name'      => 'required|string|max:255',
            'last_name'       => 'required|string|max:255',
            'email'           => [
                'nullable', 'email', 'max:255',
                Rule::unique('clients', 'email')
                    ->where('tenant_id', $request->user()->tenant_id)
                    ->ignore($client?->id),
            ],
            'phone'           => 'nullable|string|max:50',
            'secondary_phone' => 'nullable|string|max:50',
            'address'         => 'nullable|string|max:255',
            'city'            => 'nullable|string|max:120',
            'state'           => 'nullable|string|max:120',
            'postal_code'     => 'nullable|string|max:30',
            'country'         => 'nullable|string|max:120',
            'company_name'    => 'nullable|string|max:255',
            'notes'           => 'nullable|string',
            'tags'            => 'nullable|array',
            'tags.*'          => 'string|max:50',
        ]);
    }
}
