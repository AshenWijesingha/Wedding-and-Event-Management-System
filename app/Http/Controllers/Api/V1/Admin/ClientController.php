<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ClientResource;
use App\Http\Traits\ApiResponse;
use App\Models\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ClientController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $clients = Client::query()
            ->when($request->search, fn ($q, $search) => $q->search($search))
            ->when($request->tag, fn ($q, $tag) =>
                $q->whereJsonContains('tags', $tag)
            )
            ->withCount('bookings')
            ->orderBy($request->sort_by ?? 'first_name', $request->sort_dir ?? 'asc')
            ->paginate($request->per_page ?? 15);

        return $this->success(ClientResource::collection($clients));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('clients')->where('tenant_id', $request->user()->tenant_id),
            ],
            'phone' => 'required|string|max:50',
            'secondary_phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'company_name' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'tags' => 'nullable|array',
            'tags.*' => 'string',
        ]);

        $client = Client::create($validated);

        return $this->created(new ClientResource($client));
    }

    public function show(Client $client): JsonResponse
    {
        return $this->success(new ClientResource($client->loadCount('bookings')));
    }

    public function update(Request $request, Client $client): JsonResponse
    {
        $validated = $request->validate([
            'first_name' => 'sometimes|string|max:100',
            'last_name' => 'sometimes|string|max:100',
            'email' => ['sometimes', 'email', 'max:255', Rule::unique('clients')->where('tenant_id', $request->user()->tenant_id)->ignore($client->id)],
            'phone' => 'sometimes|string|max:50',
            'secondary_phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'company_name' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'tags' => 'nullable|array',
            'tags.*' => 'string',
        ]);

        $client->update($validated);

        return $this->success(new ClientResource($client->fresh()));
    }

    public function destroy(Client $client): JsonResponse
    {
        if ($client->bookings()->whereNotIn('status', ['cancelled'])->exists()) {
            return $this->error('Cannot delete client with active bookings.', 422);
        }

        $client->delete();

        return $this->noContent();
    }
}
