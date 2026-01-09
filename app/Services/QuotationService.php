<?php

namespace App\Services;

use App\Models\Inquiry;
use App\Models\Quotation;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class QuotationService
{
    private PricingService $pricingService;
    private BrandingService $brandingService;
    private SettingsService $settingsService;

    public function __construct(
        PricingService $pricingService, 
        BrandingService $brandingService,
        SettingsService $settingsService
    ) {
        $this->pricingService = $pricingService;
        $this->brandingService = $brandingService;
        $this->settingsService = $settingsService;
    }

    /**
     * Generate quotation from an inquiry.
     */
    public function generateFromInquiry(Inquiry $inquiry, array $services = []): Quotation
    {
        $pricing = $this->pricingService->calculateEventPrice([
            'venue_id' => $inquiry->venue_id,
            'package_id' => $inquiry->package_id,
            'guest_count' => $inquiry->guest_count,
            'event_date' => $inquiry->preferred_date,
            'services' => $services,
        ]);

        $validityDays = $this->settingsService->get('quotation.validity_days', 14);
        $prefix = $this->settingsService->get('quotation.number_prefix', 'QT');

        $quotation = Quotation::create([
            'quotation_number' => Quotation::generateQuotationNumber($prefix),
            'inquiry_id' => $inquiry->id,
            'client_id' => $inquiry->client_id,
            'venue_id' => $inquiry->venue_id,
            'package_id' => $inquiry->package_id,
            'event_date' => $inquiry->preferred_date,
            'guest_count' => $inquiry->guest_count,
            'items' => $this->buildItems($pricing),
            'subtotal' => $pricing['subtotal'],
            'discount_amount' => $pricing['discount'],
            'tax_amount' => $pricing['tax'],
            'total_amount' => $pricing['total'],
            'valid_until' => now()->addDays($validityDays),
            'status' => 'draft',
            'prepared_by' => auth()->id(),
        ]);

        return $quotation;
    }

    /**
     * Build items array from pricing breakdown.
     */
    private function buildItems(array $pricing): array
    {
        $items = [];

        if ($pricing['venue'] > 0) {
            $items[] = [
                'type' => 'venue',
                'description' => 'Venue Rental',
                'amount' => $pricing['venue'],
            ];
        }

        if ($pricing['package'] > 0) {
            $items[] = [
                'type' => 'package',
                'description' => 'Event Package',
                'amount' => $pricing['package'],
            ];
        }

        foreach ($pricing['services'] as $service) {
            $items[] = [
                'type' => 'service',
                'description' => $service['name'],
                'quantity' => $service['quantity'],
                'unit_price' => $service['unit_price'],
                'amount' => $service['total'],
            ];
        }

        foreach ($pricing['surcharges'] as $surcharge) {
            $items[] = [
                'type' => 'surcharge',
                'description' => $surcharge['name'],
                'rate' => $surcharge['rate'],
                'amount' => $surcharge['amount'],
            ];
        }

        return $items;
    }

    /**
     * Generate PDF for quotation.
     */
    public function generatePdf(Quotation $quotation): string
    {
        $quotation->load(['inquiry', 'client', 'venue', 'package']);

        $pdf = Pdf::loadView('pdf.quotation', [
            'quotation' => $quotation,
            'branding' => $this->brandingService->getBranding(),
            'settings' => $this->settingsService,
        ]);

        $filename = "quotation_{$quotation->quotation_number}.pdf";
        $path = "quotations/{$filename}";

        Storage::disk('public')->put($path, $pdf->output());

        return $path;
    }

    /**
     * Mark quotation as sent.
     */
    public function markAsSent(Quotation $quotation): Quotation
    {
        $quotation->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        return $quotation;
    }

    /**
     * Mark quotation as viewed.
     */
    public function markAsViewed(Quotation $quotation): Quotation
    {
        if (!$quotation->viewed_at) {
            $quotation->update([
                'status' => 'viewed',
                'viewed_at' => now(),
            ]);
        }

        return $quotation;
    }

    /**
     * Accept quotation.
     */
    public function accept(Quotation $quotation): Quotation
    {
        if (!$quotation->canBeAccepted()) {
            throw new \Exception('Quotation cannot be accepted.');
        }

        $quotation->update([
            'status' => 'accepted',
            'accepted_at' => now(),
        ]);

        return $quotation;
    }
}
