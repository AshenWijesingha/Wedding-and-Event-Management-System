<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * `php artisan dev:restore [path]`
 *
 * Restores source files from the baseline snapshot copies (git-free).
 *   dev:restore app/Http/Controllers/Admin/VenueController.php   # one file
 *   dev:restore --all                                            # every changed/missing file
 */
class DevRestoreCommand extends Command
{
    protected $signature = 'dev:restore
        {path? : Relative path of the file to restore from baseline}
        {--all : Restore every file that is currently missing or modified}';

    protected $description = 'Restore source from the git-free baseline snapshot (use after dev:doctor finds changes)';

    public function handle(): int
    {
        require_once base_path('tools/demo/lib/Integrity.php');

        $engine = new \DemoIntegrity(base_path());

        if (! $engine->baselineExists()) {
            $this->error('No baseline. Run `php artisan dev:baseline` first.');

            return self::FAILURE;
        }

        $path = $this->argument('path');

        if (! $path && ! $this->option('all')) {
            $this->error('Give a path, or use --all to restore everything changed.');
            $this->line('  e.g. php artisan dev:restore app/Http/Controllers/Admin/VenueController.php');

            return self::FAILURE;
        }

        try {
            if ($this->option('all')) {
                if (! $this->confirm('Restore ALL missing/modified files to baseline? Current edits will be overwritten.', true)) {
                    $this->line('Aborted.');

                    return self::SUCCESS;
                }
                $done = $engine->restore(null);
            } else {
                $done = $engine->restore($path);
            }
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        if (! $done) {
            $this->info('Nothing needed restoring - already matches baseline.');

            return self::SUCCESS;
        }

        $this->info('Restored ' . count($done) . ' file(s):');
        foreach ($done as $f) {
            $this->line('   ' . $f);
        }
        $this->line('');
        $this->line('Run `php artisan dev:doctor` to confirm HEALTHY.');

        return self::SUCCESS;
    }
}
