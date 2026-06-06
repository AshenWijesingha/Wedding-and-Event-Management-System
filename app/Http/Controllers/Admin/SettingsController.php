<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    private function getTenant(): ?Tenant
    {
        return Tenant::first();
    }

    public function index(): Response
    {
        $tenant   = $this->getTenant();
        $settings = $tenant?->settings ?? [];

        return Inertia::render('Settings/Index', [
            'tenant'   => $tenant ? [
                'id'            => $tenant->id,
                'name'          => $tenant->name,
                'email'         => $tenant->email,
                'phone'         => $tenant->phone,
                'primary_color' => $tenant->primary_color,
                'logo'          => $tenant->logo,
            ] : null,
            'settings' => $settings,
        ]);
    }

    public function updateGeneral(Request $request): RedirectResponse
    {
        $request->validate([
            'name'         => 'required|string|max:255',
            'email'        => 'nullable|email|max:255',
            'phone'        => 'nullable|string|max:50',
            'primary_color'=> 'nullable|string|max:20',
        ]);

        $tenant = $this->getTenant();
        if ($tenant) {
            $tenant->update($request->only(['name', 'email', 'phone', 'primary_color']));
        }

        return back()->with('success', 'General settings saved.');
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $request->validate([
            'key'   => 'required|string|max:255',
            'value' => 'present',
        ]);

        $tenant = $this->getTenant();
        if ($tenant) {
            $tenant->setSetting($request->key, $request->value);
        }

        return back()->with('success', 'Setting saved.');
    }

    public function updateBranding(Request $request): RedirectResponse
    {
        $request->validate([
            'company_name'  => 'nullable|string|max:255',
            'tagline'       => 'nullable|string|max:500',
            'primary_color' => 'nullable|string|max:20',
            'font_family'   => 'nullable|string|max:100',
            'logo_url'      => 'nullable|url|max:500',
        ]);

        $tenant = $this->getTenant();
        if ($tenant) {
            $tenant->setSetting('branding', $request->only(['company_name', 'tagline', 'primary_color', 'font_family', 'logo_url']));
        }

        return back()->with('success', 'Branding settings saved.');
    }

    public function updateDocumentTemplates(Request $request): RedirectResponse
    {
        $request->validate([
            'quotation_footer' => 'nullable|string',
            'quotation_terms'  => 'nullable|string',
            'invoice_footer'   => 'nullable|string',
            'contract_header'  => 'nullable|string',
        ]);

        $tenant = $this->getTenant();
        if ($tenant) {
            $tenant->setSetting('document_templates', $request->only(['quotation_footer', 'quotation_terms', 'invoice_footer', 'contract_header']));
        }

        return back()->with('success', 'Document templates saved.');
    }

    public function updateEmailTemplates(Request $request): RedirectResponse
    {
        $request->validate([
            'templates'        => 'nullable|array',
            'templates.*.key'  => 'required|string|max:100',
            'templates.*.subject' => 'required|string|max:255',
            'templates.*.body' => 'required|string',
        ]);

        $tenant = $this->getTenant();
        if ($tenant) {
            $indexed = collect($request->templates ?? [])
                ->keyBy('key')
                ->map(fn ($t) => ['subject' => $t['subject'], 'body' => $t['body']])
                ->toArray();
            $tenant->setSetting('email_templates', $indexed);
        }

        return back()->with('success', 'Email templates saved.');
    }
}
