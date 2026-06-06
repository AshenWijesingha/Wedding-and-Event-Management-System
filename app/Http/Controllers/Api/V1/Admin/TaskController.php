<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\TaskResource;
use App\Http\Traits\ApiResponse;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $tasks = Task::with(['assignee', 'booking'])
            ->when($request->booking_id, fn ($q, $id) => $q->where('booking_id', $id))
            ->when($request->assigned_to, fn ($q, $id) => $q->where('assigned_to', $id))
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->when($request->priority, fn ($q, $priority) => $q->where('priority', $priority))
            ->when($request->overdue, fn ($q) => $q->overdue())
            ->orderBy('due_date')
            ->paginate($request->integer('per_page', 15))
            ->withQueryString();

        return $this->success(TaskResource::collection($tasks));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'priority'    => 'nullable|in:low,medium,high,urgent',
            'status'      => 'nullable|in:pending,in_progress,completed,cancelled',
            'due_date'    => 'nullable|date',
            'booking_id'  => 'nullable|exists:bookings,id',
            'assigned_to' => 'nullable|exists:staff,id',
        ]);

        $task = Task::create($data);

        return $this->success(new TaskResource($task->load(['assignee', 'booking'])), 'Task created.', 201);
    }

    public function show(Task $task): JsonResponse
    {
        return $this->success(new TaskResource($task->load(['assignee', 'booking'])));
    }

    public function update(Request $request, Task $task): JsonResponse
    {
        $data = $request->validate([
            'title'       => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'priority'    => 'nullable|in:low,medium,high,urgent',
            'status'      => 'nullable|in:pending,in_progress,completed,cancelled',
            'due_date'    => 'nullable|date',
            'assigned_to' => 'nullable|exists:staff,id',
        ]);

        if (isset($data['status']) && $data['status'] === 'completed' && $task->status !== 'completed') {
            $data['completed_at'] = now();
        }

        $task->update($data);

        return $this->success(new TaskResource($task->load(['assignee', 'booking'])), 'Task updated.');
    }

    public function destroy(Task $task): JsonResponse
    {
        $task->delete();
        return $this->success(null, 'Task deleted.');
    }
}
