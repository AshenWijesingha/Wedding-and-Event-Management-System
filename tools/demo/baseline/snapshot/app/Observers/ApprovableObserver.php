<?php

namespace App\Observers;

use Illuminate\Database\Eloquent\Model;

class ApprovableObserver
{
    /**
     * When an already-approved record is edited through normal update(),
     * flag it for re-review but keep it approved (stays live). Transition
     * helpers use saveQuietly(), so they do not trigger this.
     */
    public function updating(Model $model): void
    {
        if (
            $model->approval_status === 'approved'
            && ! $model->isDirty('changes_pending_review')
            && ! $model->isDirty('approval_status')
            && $model->isDirty()
        ) {
            $model->changes_pending_review = true;
        }
    }
}
