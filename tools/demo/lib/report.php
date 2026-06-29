<?php

/**
 * Plain-text renderer for a DemoIntegrity::check() report. No Laravel deps so the
 * standalone CLI can use it. Returns an exit code: 0 = clean, 1 = changes found.
 */
function render_integrity_report(array $report): int
{
    $missing = $report['missing'];
    $modified = $report['modified'];
    $added = $report['added'];

    echo "\n=====================================================\n";
    echo "  Source integrity vs baseline (git-free)\n";
    echo "=====================================================\n";
    echo "  unchanged: {$report['ok']} / {$report['total']} tracked files\n\n";

    if (! $missing && ! $modified && ! $added) {
        echo "  [OK] No source changes. All functions/CRUD code is identical to baseline.\n\n";

        return 0;
    }

    if ($missing) {
        echo '  DELETED ('.count($missing)." file(s)) - re-create these:\n";
        foreach ($missing as $m) {
            $lines = $m['lines'] ? " (~{$m['lines']} lines in baseline)" : '';
            echo "    - {$m['path']}{$lines}\n";
        }
        echo "\n";
    }

    if ($modified) {
        echo '  MODIFIED ('.count($modified)." file(s)) - exact line changes:\n";
        foreach ($modified as $mod) {
            echo "    ~ {$mod['path']}\n";
            foreach ($mod['hunks'] as $h) {
                if (($h['op'] ?? '') === 'note') {
                    echo "        {$h['text']}\n";

                    continue;
                }
                $no = str_pad((string) $h['line'], 5, ' ', STR_PAD_LEFT);
                $text = rtrim($h['text']);
                if (strlen($text) > 120) {
                    $text = substr($text, 0, 117).'...';
                }
                echo "        {$h['op']} {$no}: {$text}\n";
            }
            echo "\n";
        }
    }

    if ($added) {
        echo '  ADDED ('.count($added)." file(s)) - not in baseline (new or moved):\n";
        foreach ($added as $a) {
            echo "    + {$a}\n";
        }
        echo "\n";
    }

    echo "  Legend:  '- 123:' baseline line removed/changed   '+ 123:' current line\n";
    echo "  Fix by hand, or restore:  php artisan dev:restore <path>\n\n";

    return 1;
}
