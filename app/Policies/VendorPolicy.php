<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Vendor;

class VendorPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('vendors.view');
    }

    public function view(User $user, Vendor $vendor): bool
    {
        return $user->can('vendors.view');
    }

    public function create(User $user): bool
    {
        return $user->can('vendors.create');
    }

    public function update(User $user, Vendor $vendor): bool
    {
        return $user->can('vendors.edit');
    }

    public function delete(User $user, Vendor $vendor): bool
    {
        return $user->can('vendors.delete');
    }
}
