<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\QuotationResource;
use App\Models\Quotation;
use App\Services\BrandingService;
use Barryvdh\DomPDF\Facade\Pdf;
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
