<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Services\SettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function __construct(
        protected SettingsService $settingsService
    ) {}

    public function index(): JsonResponse
    {
        $settings = $this->settingsService->all();
        return response()->json($settings);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'settings' => 'required|array',
        ]);

        $this->settingsService->update($validated['settings']);
        
        return response()->json([
            'message' => 'Settings updated successfully',
            'settings' => $this->settingsService->all(),
        ]);
    }
}
