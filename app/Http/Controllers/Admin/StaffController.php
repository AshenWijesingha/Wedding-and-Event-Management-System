<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\StaffResource;
use App\Http\Resources\TaskResource;
use App\Models\Staff;
use App\Models\Task;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StaffController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(\App\Models\Staff::class, 'staff');
    }

    public function index(Request $request): Response
    {
        $staff = Staff::withCount('tasks')
            ->when($request->search, fn ($q, $s) =>
                $q->where('first_name', 'like', "%{$s}%")
                  ->orWhere('last_name', 'like', "%{$s}%")
            )
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->orderBy('first_name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Staff/Index', [
            'staff'   => StaffResource::collection($staff),
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Staff/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name'  => 'required|string|max:255',
            'email'      => 'nullable|email|max:255',
            'phone'      => 'nullable|string|max:50',
            'role'       => 'nullable|string|max:50',
            'status'     => 'nullable|in:active,inactive',
            'notes'      => 'nullable|string',
        ]);

        Staff::create($data);

        return redirect('/admin/staff')->with('success', 'Staff member created.');
    }

    public function show(Staff $staff): Response
    {
        $tasks = Task::with('booking')
            ->where('assigned_to', $staff->id)
            ->orderBy('due_date')
            ->get();

        return Inertia::render('Staff/Show', [
            'staff' => new StaffResource($staff),
            'tasks' => TaskResource::collection($tasks),
        ]);
    }

    public function edit(Staff $staff): Response
    {
        return Inertia::render('Staff/Edit', [
            'staff' => new StaffResource($staff),
        ]);
    }

    public function update(Request $request, Staff $staff): RedirectResponse
    {
        $data = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name'  => 'required|string|max:255',
            'email'      => 'nullable|email|max:255',
            'phone'      => 'nullable|string|max:50',
            'role'       => 'nullable|string|max:50',
            'status'     => 'nullable|in:active,inactive',
            'notes'      => 'nullable|string',
        ]);

        $staff->update($data);

        return redirect('/admin/staff')->with('success', 'Staff member updated.');
    }

    public function destroy(Staff $staff): RedirectResponse
    {
        $staff->delete();
        return redirect('/admin/staff')->with('success', 'Staff member deleted.');
    }
}
