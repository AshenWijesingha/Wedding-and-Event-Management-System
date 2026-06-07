<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClientController extends Controller
{
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
}
