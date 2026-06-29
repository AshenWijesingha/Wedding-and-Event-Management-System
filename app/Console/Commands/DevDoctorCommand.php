<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;

/**
 * `php artisan dev:doctor`
 *
 * Offline, git-free health check for a live evaluation. Reports:
 *   1. SOURCE INTEGRITY  - exactly which files/lines changed vs the baseline
 *                          (proves all functions/CRUD code is "as before").
 *   2. OFFLINE READINESS  - no re-introduced internet dependency, built assets present.
 *   3. FUNCTIONAL SUITE   - runs the test suite so every CRUD flow is exercised.
 */
class DevDoctorCommand extends Command
{
    protected $signature = 'dev:doctor
        {--no-tests : Skip the functional test suite (integrity + offline checks only, fast)}
        {--filter= : Only run tests matching this filter (passed to artisan test)}';

    protected $description = 'Offline health check: find changed/deleted code (no git) and verify CRUD flows still work';

    public function handle(): int
    {
        require_once base_path('tools/demo/lib/Integrity.php');
        require_once base_path('tools/demo/lib/report.php');

        $this->line('');
        $this->line('  ===================================================');
        $this->line('   EventPro dev:doctor  -  offline health check');
        $this->line('  ===================================================');

        $problems = 0;

        // --- 1. Source integrity (git-free) --------------------------------
        $engine = new \DemoIntegrity(base_path());
        if (! $engine->baselineExists()) {
            $this->warn("  No baseline found. Run `php artisan dev:baseline` on a known-good checkout first.");
            $this->line('  (Integrity comparison skipped.)');
        } else {
            $report = $engine->check();
            $code = render_integrity_report($report);
            if ($code !== 0) {
                $problems++;
            }
        }

        // --- 2. Offline readiness ------------------------------------------
        $this->line('  -- Offline readiness ------------------------------');
        $problems += $this->offlineChecks();
        $this->line('');

        // --- 3. Functional suite (CRUD + flows) ----------------------------
        if ($this->option('no-tests')) {
            $this->line('  -- Functional suite: SKIPPED (--no-tests) ---------');
            $this->line('');
        } else {
            $problems += $this->functionalSuite();
        }

        // --- Verdict --------------------------------------------------------
        $this->line('  ===================================================');
        if ($problems === 0) {
            $this->info('   RESULT: HEALTHY - code matches baseline and all flows pass.');
            $this->line('  ===================================================');
            $this->line('');

            return self::SUCCESS;
        }

        $this->error('   RESULT: PROBLEMS FOUND - see the sections above.');
        $this->line('   Recover:  php artisan dev:restore <path>   (one file)');
        $this->line('             php artisan dev:restore --all     (everything changed)');
        $this->line('  ===================================================');
        $this->line('');

        return self::FAILURE;
    }

    private function offlineChecks(): int
    {
        $problems = 0;

        // No external font/CDN link re-introduced into the blade shells.
        $leak = false;
        foreach (glob(base_path('resources/views').'/{,**/}*.blade.php', GLOB_BRACE) ?: [] as $blade) {
            $c = (string) file_get_contents($blade);
            if (str_contains($c, 'fonts.bunny.net') || str_contains($c, 'fonts.googleapis.com')) {
                $leak = true;
                break;
            }
        }
        $this->status(! $leak, 'no external font/CDN link in blade views (offline-safe)',
            'an external font/CDN <link> is back in a blade view (breaks offline)');
        $problems += $leak ? 1 : 0;

        // Built front-end assets present.
        $built = is_file(base_path('public/build/manifest.json'));
        $this->status($built, 'front-end assets built (public/build/manifest.json)',
            'public/build/manifest.json missing - run `npm run build` (needs internet once)');
        $problems += $built ? 0 : 1;

        // Offline-friendly .env drivers.
        $env = @file_get_contents(base_path('.env')) ?: '';
        $expect = [
            'DB_CONNECTION=sqlite' => 'database',
            'QUEUE_CONNECTION=sync' => 'queue',
            'MAIL_MAILER=log' => 'mail',
        ];
        foreach ($expect as $needle => $label) {
            $ok = str_contains($env, $needle);
            $this->status($ok, "{$label} driver is offline ({$needle})",
                "{$label} driver is NOT {$needle} - may try the network");
            $problems += $ok ? 0 : 1;
        }

        // Stale Vite dev-server marker.
        $hot = is_file(base_path('public/hot'));
        $this->status(! $hot, 'no public/hot (production asset mode)',
            'public/hot exists - delete it, it forces the Vite dev server');
        $problems += $hot ? 1 : 0;

        return $problems;
    }

    private function functionalSuite(): int
    {
        $this->line('  -- Functional suite (CRUD + every flow) -----------');
        $this->line('     running the test suite, please wait...');

        $cmd = '"'.PHP_BINARY.'" artisan test --compact';
        if ($filter = $this->option('filter')) {
            $cmd .= ' --filter='.escapeshellarg($filter);
        }

        // This command is already a booted Laravel process, which has leaked its
        // dev environment (APP_ENV=local, the file DB, etc.) into the OS env via
        // putenv. Pass the testing environment explicitly so the child test run is
        // identical to a clean top-level `php artisan test`: APP_ENV=testing makes
        // runningUnitTests() true (so CSRF is skipped, otherwise POSTs return 419),
        // and the in-memory DB keeps the demo database untouched.
        $result = Process::path(base_path())
            ->env([
                'APP_ENV' => 'testing',
                'DB_CONNECTION' => 'sqlite',
                'DB_DATABASE' => ':memory:',
                'SESSION_DRIVER' => 'array',
                'CACHE_STORE' => 'array',
                'CACHE_DRIVER' => 'array',
                'QUEUE_CONNECTION' => 'sync',
                'MAIL_MAILER' => 'array',
                'BCRYPT_ROUNDS' => '4',
            ])
            ->timeout(900)
            ->run($cmd);
        $out = trim($result->output()."\n".$result->errorOutput());

        // Show the last few lines (the PHPUnit summary).
        $tail = array_slice(preg_split('/\r\n|\r|\n/', $out), -6);
        foreach ($tail as $l) {
            if (trim($l) !== '') {
                $this->line('     '.$l);
            }
        }

        if ($result->successful()) {
            $this->status(true, 'all functional/CRUD tests pass', '');
            $this->line('');

            return 0;
        }

        $this->status(false, '', 'functional tests FAILED - a flow is broken (see summary above)');
        $this->line('');

        return 1;
    }

    private function status(bool $ok, string $okMsg, string $failMsg): void
    {
        if ($ok) {
            $this->line('   <fg=green>[OK]</>      '.$okMsg);
        } else {
            $this->line('   <fg=red>[FAIL]</>    '.$failMsg);
        }
    }
}
