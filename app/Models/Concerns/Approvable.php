<?php

namespace App\Models\Concerns;

use App\Models\User;
use App\Observers\ApprovableObserver;
use Illuminate\Database\Eloquent\Builder;

trait Approvable
{
    public static function bootApprovable(): void
    {
        static::observe(ApprovableObserver::class);
    }

    public function scopeApproved(Builder $q): Builder
    {
        return $q->where($this->getTable().'.approval_status', 'approved');
    }

    public function scopePendingReview(Builder $q): Builder
    {
        return $q->where(fn ($w) => $w
            ->where($this->getTable().'.approval_status', 'pending')
            ->orWhere($this->getTable().'.changes_pending_review', true));
    }

    public function submit(User $user): void
    {
        $this->forceFill([
            'approval_status' => 'pending',
            'submitted_at' => now(),
            'submitted_by' => $user->id,
            'review_notes' => null,
        ])->saveQuietly();
    }

    public function approve(User $user): void
    {
        $this->forceFill([
            'approval_status' => 'approved',
            'reviewed_at' => now(),
            'reviewed_by' => $user->id,
            'changes_pending_review' => false,
        ])->saveQuietly();
    }

    public function reject(User $user, string $notes): void
    {
        $this->forceFill([
            'approval_status' => 'rejected',
            'reviewed_at' => now(),
            'reviewed_by' => $user->id,
            'review_notes' => $notes,
            'changes_pending_review' => false,
        ])->saveQuietly();
    }

    public function isApproved(): bool
    {
        return $this->approval_status === 'approved';
    }

    public function isPending(): bool
    {
        return $this->approval_status === 'pending';
    }

    public function isRejected(): bool
    {
        return $this->approval_status === 'rejected';
    }
}
