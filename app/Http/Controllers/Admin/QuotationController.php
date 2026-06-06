<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\QuotationResource;
use App\Models\Quotation;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class QuotationController extends Controller
{
    public function index(Request $request): Response
    {
        $quotations = Quotation::with(['client', 'venue'])
            ->when($request->search, fn ($q, $search) =>
                $q->where('quotation_number', 'like', "%{$search}%")
                  ->orWhereHas('client', fn ($cq) =>
                      $cq->where('first_name', 'like', "%{$search}%")
                         ->orWhere('last_name', 'like', "%{$search}%")
                  )
            )
            ->when($request->status, fn ($q, $status) =>
                $q->where('status', $status)
            )
            ->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Quotations/Index', [
            'quotations' => QuotationResource::collection($quotations),
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    public function show(Quotation $quotation): Response
    {
        return Inertia::render('Quotations/Show', [
            'quotation' => new QuotationResource($quotation->load(['client', 'venue'])),
        ]);
    }
}
