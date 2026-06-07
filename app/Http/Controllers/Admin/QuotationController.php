<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\QuotationResource;
use App\Models\Client;
use App\Models\Package;
use App\Models\Quotation;
use App\Models\Venue;
use App\Services\BrandingService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class QuotationController extends Controller
{
    public function __construct(private BrandingService $brandingService) {}

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

    public function create(): Response
    {
        return Inertia::render('Quotations/Create', [
            'venues'   => Venue::where('status', 'active')->orderBy('name')->get(['id', 'name']),
            'packages' => Package::where('status', 'active')->orderBy('name')->get(['id', 'name', 'base_price']),
            'clients'  => Client::orderBy('first_name')->get()->map(fn ($c) => [
                'id'   => $c->id,
                'name' => trim("{$c->first_name} {$c->last_name}"),
            ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->hasAnyRole(['admin', 'manager']), 403);

        $validated = $request->validate([
            'client_id'            => 'required|exists:clients,id',
            'venue_id'             => 'nullable|exists:venues,id',
            'package_id'           => 'nullable|exists:packages,id',
            'event_date'           => 'nullable|date',
            'guest_count'          => 'nullable|integer|min:1',
            'items'                => 'required|array|min:1',
            'items.*.name'         => 'required|string|max:255',
            'items.*.quantity'     => 'required|numeric|min:0',
            'items.*.unit_price'   => 'required|numeric|min:0',
            'discount_amount'      => 'nullable|numeric|min:0',
            'tax_rate'             => 'nullable|numeric|min:0|max:100',
            'valid_until'          => 'nullable|date|after_or_equal:today',
            'notes'                => 'nullable|string',
            'terms_and_conditions' => 'nullable|string',
        ]);

        $items = array_map(function ($item) {
            $item['total'] = round((float) $item['quantity'] * (float) $item['unit_price'], 2);
            return $item;
        }, $validated['items']);

        $subtotal = array_sum(array_column($items, 'total'));
        $discount = (float) ($validated['discount_amount'] ?? 0);
        $tax      = round(($subtotal - $discount) * ((float) ($validated['tax_rate'] ?? 0) / 100), 2);
        $total    = $subtotal - $discount + $tax;

        Quotation::create([
            'quotation_number'     => Quotation::generateQuotationNumber(),
            'client_id'            => $validated['client_id'],
            'venue_id'             => $validated['venue_id'] ?? null,
            'package_id'           => $validated['package_id'] ?? null,
            'event_date'           => $validated['event_date'] ?? null,
            'guest_count'          => $validated['guest_count'] ?? null,
            'items'                => $items,
            'subtotal'             => $subtotal,
            'discount_amount'      => $discount,
            'tax_amount'           => $tax,
            'total_amount'         => $total,
            'valid_until'          => $validated['valid_until'] ?? now()->addDays(21)->toDateString(),
            'status'               => 'draft',
            'notes'                => $validated['notes'] ?? null,
            'terms_and_conditions' => $validated['terms_and_conditions'] ?? null,
            'prepared_by'          => $request->user()->id,
        ]);

        return redirect()->route('admin.quotations.index')->with('success', 'Quotation created successfully.');
    }

    public function show(Quotation $quotation): Response
    {
        return Inertia::render('Quotations/Show', [
            'quotation' => new QuotationResource($quotation->load(['client', 'venue', 'package'])),
        ]);
    }

    public function downloadPdf(Quotation $quotation): SymfonyResponse
    {
        try {
            $quotation->load(['client', 'venue', 'package']);

            $pdf = Pdf::loadView('pdf.quotation', [
                'quotation' => $quotation,
                'branding'  => $this->brandingService->getBranding(),
            ])->setPaper('a4', 'portrait');

            $safeNumber = preg_replace('/[^a-zA-Z0-9_\-]/', '', $quotation->quotation_number);
            return $pdf->download("quotation_{$safeNumber}.pdf");
        } catch (\Throwable $e) {
            logger()->error('Quotation PDF generation failed', ['quotation_id' => $quotation->id, 'error' => $e->getMessage()]);
            return back()->with('error', 'PDF generation failed. Please try again.');
        }
    }
}
