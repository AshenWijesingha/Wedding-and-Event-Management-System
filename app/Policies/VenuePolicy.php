<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Venue;

class VenuePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('venues.view');
    }

    public function view(User $user, Venue $venue): bool
    {
        return $user->can('venues.view');
    }

    public function create(User $user): bool
    {
        return $user->can('venues.create');
    }

    public function update(User $user, Venue $venue): bool
    {
        return $user->can('venues.edit');
    }

    public function delete(User $user, Venue $venue): bool
    {
        return $user->can('venues.delete');
    }
}
