<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $clients = Client::paginate($request->per_page ?? 15);
        return response()->json($clients);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:clients',
            'phone' => 'nullable|string|max:50',
        ]);

        $client = Client::create($validated);
        return response()->json($client, 201);
    }

    public function show(Client $client): JsonResponse
    {
        return response()->json($client);
    }

    public function update(Request $request, Client $client): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|max:255|unique:clients,email,' . $client->id,
            'phone' => 'nullable|string|max:50',
        ]);

        $client->update($validated);
        return response()->json($client);
    }

    public function destroy(Client $client): JsonResponse
    {
        $client->delete();
        return response()->json(null, 204);
    }
}
