<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Inquiry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InquiryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $inquiries = Inquiry::paginate($request->per_page ?? 15);
        return response()->json($inquiries);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:50',
            'message' => 'required|string',
            'event_type' => 'nullable|string',
            'event_date' => 'nullable|date',
        ]);

        $inquiry = Inquiry::create($validated);
        return response()->json($inquiry, 201);
    }

    public function show(Inquiry $inquiry): JsonResponse
    {
        return response()->json($inquiry);
    }

    public function update(Request $request, Inquiry $inquiry): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'sometimes|string|in:new,contacted,converted,lost',
            'notes' => 'nullable|string',
        ]);

        $inquiry->update($validated);
        return response()->json($inquiry);
    }

    public function destroy(Inquiry $inquiry): JsonResponse
    {
        $inquiry->delete();
        return response()->json(null, 204);
    }
}
