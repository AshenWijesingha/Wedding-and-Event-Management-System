<?php

namespace App\Policies;

use App\Models\Hotel;
use App\Models\User;

class HotelPolicy
{
    public function viewAny(User $user): bool { return $user->can('hotels.view'); }
    public function view(User $user, Hotel $hotel): bool { return $user->can('hotels.view'); }
    public function create(User $user): bool { return $user->can('hotels.create'); }
    public function update(User $user, Hotel $hotel): bool { return $user->can('hotels.edit'); }
    public function delete(User $user, Hotel $hotel): bool { return $user->can('hotels.delete'); }
    public function submit(User $user, Hotel $hotel): bool { return $user->can('hotels.submit'); }
}
