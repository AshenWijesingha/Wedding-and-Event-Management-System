<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Activitylog\Models\Activity;

/**
 * Cross-tenant platform audit log. Super-admin only (enforced by the route group).
 */
class AuditLogController extends Controller
{
    public function index(Request $request): Response
    {
        $activities = Activity::query()
            ->with(['causer', 'subject'])
            ->when($request->subject_type, fn ($q, $type) => $q->where('subject_type', $type))
            ->when($request->event, fn ($q, $event) => $q->where('event', $event))
            ->latest()
            ->paginate(25)
            ->withQueryString()
            ->through(fn (Activity $a) => [
                'id' => $a->id,
                'description' => $a->description,
                'event' => $a->event,
                'log_name' => $a->log_name,
                'subject_type' => class_basename($a->subject_type ?? ''),
                'subject_id' => $a->subject_id,
                'causer' => $a->causer?->name ?? 'System',
                'changes' => $a->changes()->toArray(),
                'created_at' => $a->created_at?->toDateTimeString(),
            ]);

        $subjectTypes = Activity::query()
            ->whereNotNull('subject_type')
            ->distinct()
            ->pluck('subject_type')
            ->map(fn ($t) => ['value' => $t, 'label' => class_basename($t)])
            ->values();

        return Inertia::render('Platform/AuditLog', [
            'activities' => $activities,
            'subjectTypes' => $subjectTypes,
            'filters' => $request->only(['subject_type', 'event']),
        ]);
    }
}
