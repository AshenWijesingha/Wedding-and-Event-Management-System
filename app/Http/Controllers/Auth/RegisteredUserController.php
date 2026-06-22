<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use App\Settings\PlatformSettings;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    public function store(Request $request, PlatformSettings $settings): RedirectResponse
    {
        abort_unless($settings->signups_enabled, 403, 'Public sign-ups are currently disabled.');

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        // A public signup must belong to a tenant. Prefer the tenant resolved from the
        // request (domain), falling back to the sole tenant in single-tenant mode.
        $tenant = Tenant::current() ?? Tenant::query()->orderBy('id')->first();
        abort_if($tenant === null, 503, 'Registration is unavailable: no tenant is configured.');

        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'client',
        ]);

        $user->assignRole('client');

        // A client account is useless in the portal without a linked Client profile —
        // the portal looks bookings/quotations/payments up by client_id. Create one now.
        [$firstName, $lastName] = $this->splitName($request->name);
        $user->client()->create([
            'tenant_id' => $tenant->id,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $user->email,
        ]);

        event(new Registered($user));
        Auth::login($user);

        return redirect(route('portal.dashboard'));
    }

    /**
     * Split a single display name into first/last for the Client profile.
     *
     * @return array{0:string,1:string}
     */
    private function splitName(string $name): array
    {
        $parts = preg_split('/\s+/', trim($name), 2);

        return [$parts[0] ?? $name, $parts[1] ?? ''];
    }
}
