<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Models\Client;
use App\Models\CustomField;
use App\Models\Inquiry;
use App\Models\Package;
use App\Models\Payment;
use App\Models\Quotation;
use App\Models\Staff;
use App\Models\Task;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Venue;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Deletes expired public-demo tenants and all of their tenant-scoped data.
 * Scheduled every 15 minutes (see routes/console.php).
 */
class ReapDemoTenants extends Command
{
    protected $signature = 'demo:reap';

    protected $description = 'Delete expired public demo tenants and all their data.';

    /** Child-before-parent delete order to satisfy foreign keys. */
    private const MODELS = [
        Task::class, Payment::class, Quotation::class, Inquiry::class,
        Booking::class, Vendor::class, Staff::class, Package::class,
        Venue::class, Client::class, CustomField::class, User::class,
    ];

    public function handle(): int
    {
        $expired = Tenant::where('is_demo', true)
            ->where('demo_expires_at', '<', now())
            ->get();

        $count = 0;
        foreach ($expired as $tenant) {
            DB::transaction(function () use ($tenant) {
                foreach (self::MODELS as $model) {
                    $model::withoutGlobalScopes()->where('tenant_id', $tenant->id)->delete();
                }
                $tenant->delete();
            });
            $count++;
        }

        $this->info("Reaped {$count} expired demo tenant(s).");

        return self::SUCCESS;
    }
}
