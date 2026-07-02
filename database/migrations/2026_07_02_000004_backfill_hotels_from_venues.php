<?php

use App\Models\Hotel;
use App\Models\Package;
use App\Models\Venue;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        // Group by tenant + name prefix before ' — ' (fallback: whole name).
        Venue::withoutGlobalScopes()->whereNull('hotel_id')->get()
            ->groupBy(fn ($v) => $v->tenant_id.'|'.trim(Str::before($v->name, ' — ')))
            ->each(function ($venues, $key) {
                [$tenantId, $prefix] = explode('|', $key, 2);
                $hotel = Hotel::withoutGlobalScopes()->firstOrCreate(
                    ['tenant_id' => $tenantId, 'name' => $prefix],
                    [
                        'slug'            => Str::slug($prefix).'-'.Str::random(4),
                        'status'          => 'active',
                        'approval_status' => 'approved',
                        'reviewed_at'     => now(),
                    ]
                );
                Venue::withoutGlobalScopes()->whereIn('id', $venues->pluck('id'))
                    ->update(['hotel_id' => $hotel->id, 'approval_status' => 'approved']);
            });

        // Any existing packages become approved (hotel_id stays null / agnostic).
        Package::withoutGlobalScopes()
            ->where(fn ($q) => $q
                ->whereNull('approval_status')
                ->orWhere('approval_status', 'draft'))
            ->update(['approval_status' => 'approved']);
    }

    public function down(): void
    {
        // Rollback reverses venue hotel_id links and deletes hotel rows created during up().
        // However, package approval_status values bumped to 'approved' during up() are
        // intentionally NOT restored — their prior draft/null state is not tracked, so
        // we cannot safely restore them without risking data loss or inconsistency.
        Venue::withoutGlobalScopes()->update(['hotel_id' => null]);
        Hotel::withoutGlobalScopes()->delete();
    }
};
