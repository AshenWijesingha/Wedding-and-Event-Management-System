<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * `php artisan dev:baseline`
 *
 * Captures the CURRENT source tree as the known-good baseline (sha256 manifest +
 * verbatim file copies) under tools/demo/baseline/. Run this once on a healthy
 * checkout BEFORE an evaluation. `dev:doctor` then compares against it, and
 * `dev:restore` recovers from it - all without git.
 */
class DevBaselineCommand extends Command
{
    protected $signature = 'dev:baseline {--force : Overwrite an existing baseline without confirmation}';

    protected $description = 'Capture the current source as the git-free known-good baseline (for dev:doctor / dev:restore)';

    public function handle(): int
    {
        require_once base_path('tools/demo/lib/Integrity.php');

        $engine = new \DemoIntegrity(base_path());

        if ($engine->baselineExists() && ! $this->option('force')) {
            if (! $this->confirm('A baseline already exists. Overwrite it with the current source?', true)) {
                $this->line('Aborted.');

                return self::SUCCESS;
            }
        }

        $this->line('Capturing baseline (hashing + copying source files)...');
        $meta = $engine->snapshot();

        $this->info("Baseline captured: {$meta['count']} files.");
        $this->line('  Location: '.$engine->baselineDir);
        $this->line('  Generated: '.$meta['generated_at']);
        $this->line('');
        $this->line('Now run `php artisan dev:doctor` any time to detect changes against this.');

        return self::SUCCESS;
    }
}
