<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\StaffResource;
use App\Http\Resources\TaskResource;
use App\Http\Traits\ApiResponse;
use App\Models\Staff;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StaffController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $staff = Staff::query()
            ->when($request->search, fn ($q, $s) => $q->where('first_name', 'like', "%{$s}%")
                ->orWhere('last_name', 'like', "%{$s}%")
                ->orWhere('email', 'like', "%{$s}%")
            )
            ->when($request->role, fn ($q, $role) => $q->where('role', $role))
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->orderBy('first_name')
            ->paginate($request->integer('per_page', 15))
            ->withQueryString();

        return $this->success(StaffResource::collection($staff));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'role' => 'nullable|string|max:50',
            'status' => 'nullable|in:active,inactive',
            'notes' => 'nullable|string',
        ]);

        $staff = Staff::create($data);

        return $this->success(new StaffResource($staff), 'Staff member created.', 201);
    }

    public function show(Staff $staff): JsonResponse
    {
        return $this->success(new StaffResource($staff));
    }

    public function update(Request $request, Staff $staff): JsonResponse
    {
        $data = $request->validate([
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'role' => 'nullable|string|max:50',
            'status' => 'nullable|in:active,inactive',
            'notes' => 'nullable|string',
        ]);

        $staff->update($data);

        return $this->success(new StaffResource($staff), 'Staff member updated.');
    }

    public function destroy(Staff $staff): JsonResponse
    {
        $staff->delete();

        return $this->success(null, 'Staff member deleted.');
    }

    public function schedule(Request $request, Staff $staff): JsonResponse
    {
        $tasks = $staff->tasks()
            ->with('booking')
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->when($request->from, fn ($q, $from) => $q->where('due_date', '>=', $from))
            ->when($request->to, fn ($q, $to) => $q->where('due_date', '<=', $to))
            ->orderBy('due_date')
            ->get();

        return $this->success(TaskResource::collection($tasks));
    }
}
