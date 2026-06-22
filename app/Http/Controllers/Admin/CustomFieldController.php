<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CustomField;
use App\Services\CustomFieldService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CustomFieldController extends Controller
{
    public function __construct(private CustomFieldService $service) {}

    public function index(Request $request): Response
    {
        $fields = CustomField::query()
            ->when($request->entity_type, fn ($q, $type) => $q->where('entity_type', $type))
            ->orderBy('entity_type')
            ->orderBy('sort_order')
            ->get();

        return Inertia::render('CustomFields/Index', [
            'fields' => $fields,
            'filters' => $request->only(['entity_type']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->hasAnyRole(['super_admin', 'tenant_owner', 'admin', 'manager']), 403);
        $data = $request->validate([
            'entity_type' => 'required|in:booking,client,venue,inquiry,quotation',
            'name' => 'required|string|max:255',
            'type' => 'required|in:text,textarea,number,date,email,select,multiselect,checkbox,radio',
            'options' => 'nullable|array',
            'options.*' => 'string|max:100',
            'placeholder' => 'nullable|string|max:255',
            'help_text' => 'nullable|string|max:500',
            'is_required' => 'boolean',
            'is_active' => 'boolean',
            'show_on_list' => 'boolean',
        ]);

        $data['slug'] = Str::slug($data['name']);
        $data['sort_order'] = CustomField::where('entity_type', $data['entity_type'])->count();

        $this->service->createField($data);

        return back()->with('success', 'Custom field created.');
    }

    public function update(Request $request, CustomField $customField): RedirectResponse
    {
        abort_unless($request->user()->hasAnyRole(['super_admin', 'tenant_owner', 'admin', 'manager']), 403);
        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'type' => 'sometimes|in:text,textarea,number,date,email,select,multiselect,checkbox,radio',
            'options' => 'nullable|array',
            'options.*' => 'string|max:100',
            'placeholder' => 'nullable|string|max:255',
            'help_text' => 'nullable|string|max:500',
            'is_required' => 'boolean',
            'is_active' => 'boolean',
            'show_on_list' => 'boolean',
            'sort_order' => 'nullable|integer',
        ]);

        $this->service->updateField($customField, $data);

        return back()->with('success', 'Custom field updated.');
    }

    public function destroy(CustomField $customField): RedirectResponse
    {
        abort_unless(request()->user()->hasAnyRole(['super_admin', 'tenant_owner', 'admin', 'manager']), 403);
        $this->service->deleteField($customField);

        return back()->with('success', 'Custom field deleted.');
    }
}
