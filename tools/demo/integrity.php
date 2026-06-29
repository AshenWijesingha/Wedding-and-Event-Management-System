<?php

/**
 * Standalone, git-free integrity CLI. Works even when the Laravel app cannot boot
 * (e.g. a deletion broke autoloading) because it does NOT load the framework.
 *
 *   php tools/demo/integrity.php --snapshot     # capture the current good state
 *   php tools/demo/integrity.php --check        # list deleted/modified/added files (+ line numbers)
 *   php tools/demo/integrity.php --restore PATH # restore one file from the baseline
 *   php tools/demo/integrity.php --restore-all  # restore every changed/missing file
 *
 * The `dev:doctor` / `dev:baseline` / `dev:restore` artisan commands wrap the same
 * engine for the richer (app-booted) experience.
 */

require __DIR__ . '/lib/Integrity.php';
require __DIR__ . '/lib/report.php';

$root = dirname(__DIR__, 2);
$engine = new DemoIntegrity($root);

$arg = $argv[1] ?? '--check';

try {
    switch ($arg) {
        case '--snapshot':
            $meta = $engine->snapshot();
            echo "Baseline captured: {$meta['count']} files\n";
            echo '  -> ' . $engine->baselineDir . "\n";
            exit(0);

        case '--check':
            $report = $engine->check();
            $code = render_integrity_report($report);
            exit($code);

        case '--restore':
            $path = $argv[2] ?? null;
            if (! $path) {
                fwrite(STDERR, "Usage: --restore <relative/path>\n");
                exit(2);
            }
            $done = $engine->restore($path);
            echo 'Restored: ' . implode(', ', $done) . "\n";
            exit(0);

        case '--restore-all':
            $done = $engine->restore(null);
            echo $done ? ('Restored ' . count($done) . " file(s):\n  " . implode("\n  ", $done) . "\n") : "Nothing to restore.\n";
            exit(0);

        default:
            fwrite(STDERR, "Unknown option: {$arg}\n");
            fwrite(STDERR, "Use --snapshot | --check | --restore PATH | --restore-all\n");
            exit(2);
    }
} catch (Throwable $e) {
    fwrite(STDERR, 'ERROR: ' . $e->getMessage() . "\n");
    exit(2);
}
