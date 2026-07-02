<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\CustomField;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomFieldController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $customFields = CustomField::query()
            ->when($request->entity_type, fn ($query, $type) => $query->where('entity_type', $type)
            )
            ->paginate($request->per_page ?? 15);

        return response()->json($customFields);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'label' => 'required|string|max:255',
            'entity_type' => 'required|string|in:booking,client,venue,inquiry',
            'field_type' => 'required|string|in:text,number,select,date,textarea',
            'options' => 'nullable|array',
            'required' => 'boolean',
        ]);

        $customField = CustomField::create($validated);

        return response()->json($customField, 201);
    }

    public function show(CustomField $customField): JsonResponse
    {
        return response()->json($customField);
    }

    public function update(Request $request, CustomField $customField): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'label' => 'sometimes|string|max:255',
            'options' => 'nullable|array',
            'required' => 'boolean',
        ]);

        $customField->update($validated);

        return response()->json($customField);
    }

    public function destroy(CustomField $customField): JsonResponse
    {
        $customField->delete();

        return response()->json(null, 204);
    }
}
