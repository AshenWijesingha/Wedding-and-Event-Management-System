<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Hotel;
use App\Models\Package;
use App\Models\User;
use App\Models\Venue;
use App\Notifications\ApprovalReviewed;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ApprovalController extends Controller
{
    private const TYPES = ['hotel' => Hotel::class, 'venue' => Venue::class, 'package' => Package::class];

    public function index(): Response
    {
        $this->authorizePlatform('approvals.review');

        $items = collect(self::TYPES)->flatMap(function ($class, $type) {
            return $class::withoutGlobalScopes()->pendingReview()
                ->with('submitter')->get()
                ->map(fn ($m) => [
                    'type' => $type,
                    'id' => $m->id,
                    'name' => $m->name,
                    'tenant_id' => $m->tenant_id,
                    'approval_status' => $m->approval_status,
                    'changes_pending_review' => $m->changes_pending_review,
                    'submitted_by' => $m->submitter?->name,
                    'submitted_at' => $m->submitted_at,
                ]);
        })->sortByDesc('submitted_at')->values();

        return Inertia::render('Admin/Approvals/Index', ['items' => $items]);
    }

    public function approve(string $type, int $id): RedirectResponse
    {
        $model = $this->find($type, $id);
        $model->approve(request()->user());
        $this->notifySubmitter($model, 'approved', null);

        return back()->with('success', ucfirst($type).' approved.');
    }

    public function reject(Request $request, string $type, int $id): RedirectResponse
    {
        $model = $this->find($type, $id);
        $notes = $request->validate(['notes' => 'required|string|max:2000'])['notes'];
        $model->reject(request()->user(), $notes);
        $this->notifySubmitter($model, 'rejected', $notes);

        return back()->with('success', ucfirst($type).' rejected.');
    }

    private function find(string $type, int $id)
    {
        $this->authorizePlatform('approvals.review');
        abort_unless(isset(self::TYPES[$type]), 404);

        return self::TYPES[$type]::withoutGlobalScopes()->findOrFail($id);
    }

    private function authorizePlatform(string $permission): void
    {
        abort_unless(request()->user()?->can($permission), 403);
    }

    private function notifySubmitter($model, string $decision, ?string $notes): void
    {
        if ($model->submitted_by && $user = User::find($model->submitted_by)) {
            $user->notify(new ApprovalReviewed(class_basename($model), $model->name, $decision, $notes));
        }
    }
}
