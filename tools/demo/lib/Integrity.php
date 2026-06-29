<?php

/**
 * DemoIntegrity — git-free source integrity engine.
 *
 * Builds a baseline (sha256 hash + a verbatim copy of every source file) and later
 * compares the working tree against it to report EXACTLY which files were deleted,
 * modified (with the changed line numbers), or added — without using git.
 *
 * Plain PHP, no Laravel/Composer dependencies, so it still runs when the app itself
 * has been broken by a deletion. Used by tools/demo/integrity.php (standalone CLI)
 * and by the `dev:doctor` / `dev:baseline` / `dev:restore` artisan commands.
 */
class DemoIntegrity
{
    /** Directories whose text files are tracked (evaluator-editable source). */
    public array $includeDirs = [
        'app',
        'routes',
        'config',
        'bootstrap',
        'database/migrations',
        'database/seeders',
        'database/factories',
        'resources/js',
        'resources/views',
        'resources/css',
        'tests',
    ];

    /** Individual root files that are part of the contract. */
    public array $includeFiles = [
        'composer.json',
        'package.json',
        'vite.config.js',
        'tailwind.config.js',
        'postcss.config.js',
        'artisan',
    ];

    /** Only these extensions are tracked (text source). */
    public array $extensions = ['php', 'js', 'jsx', 'mjs', 'cjs', 'ts', 'vue', 'css', 'scss', 'json'];

    /** Path fragments that are never tracked. */
    public array $excludeFragments = [
        '/vendor/', '/node_modules/', '/public/build/', '/.git/', '/storage/',
        '/tools/demo/baseline/', '/bootstrap/cache/',
    ];

    public string $root;

    public string $baselineDir;

    public function __construct(string $root, ?string $baselineDir = null)
    {
        $this->root = rtrim(str_replace('\\', '/', $root), '/');
        $this->baselineDir = $baselineDir
            ? rtrim(str_replace('\\', '/', $baselineDir), '/')
            : $this->root . '/tools/demo/baseline';
    }

    // ---- collection -------------------------------------------------------

    /** @return array<string,string> relative path => absolute path */
    public function collect(): array
    {
        $out = [];

        foreach ($this->includeFiles as $rel) {
            $abs = $this->root . '/' . $rel;
            if (is_file($abs)) {
                $out[$rel] = $abs;
            }
        }

        foreach ($this->includeDirs as $dir) {
            $base = $this->root . '/' . $dir;
            if (! is_dir($base)) {
                continue;
            }
            $it = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::LEAVES_ONLY
            );
            foreach ($it as $file) {
                if (! $file->isFile()) {
                    continue;
                }
                $abs = str_replace('\\', '/', $file->getPathname());
                if ($this->excluded($abs)) {
                    continue;
                }
                if (! $this->trackedExtension($abs)) {
                    continue;
                }
                $rel = ltrim(substr($abs, strlen($this->root)), '/');
                $out[$rel] = $abs;
            }
        }

        ksort($out);

        return $out;
    }

    private function excluded(string $abs): bool
    {
        foreach ($this->excludeFragments as $frag) {
            if (strpos($abs, $frag) !== false) {
                return true;
            }
        }

        return false;
    }

    private function trackedExtension(string $abs): bool
    {
        $ext = strtolower(pathinfo($abs, PATHINFO_EXTENSION));

        return in_array($ext, $this->extensions, true);
    }

    // ---- baseline ---------------------------------------------------------

    /** Build/refresh the baseline manifest + verbatim file copies. */
    public function snapshot(): array
    {
        $files = $this->collect();
        $snapDir = $this->baselineDir . '/snapshot';

        $this->rrmdir($snapDir);
        @mkdir($snapDir, 0777, true);

        $manifest = [];
        foreach ($files as $rel => $abs) {
            $hash = hash_file('sha256', $abs);
            $manifest[$rel] = [
                'sha256' => $hash,
                'bytes' => filesize($abs),
                'lines' => $this->lineCount($abs),
            ];
            $dest = $snapDir . '/' . $rel;
            @mkdir(dirname($dest), 0777, true);
            copy($abs, $dest);
        }

        $meta = [
            'generated_at' => date('c'),
            'root' => $this->root,
            'count' => count($manifest),
            'files' => $manifest,
        ];
        @mkdir($this->baselineDir, 0777, true);
        file_put_contents(
            $this->baselineDir . '/manifest.json',
            json_encode($meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );

        return $meta;
    }

    public function baselineExists(): bool
    {
        return is_file($this->baselineDir . '/manifest.json');
    }

    public function loadManifest(): ?array
    {
        $path = $this->baselineDir . '/manifest.json';
        if (! is_file($path)) {
            return null;
        }

        return json_decode((string) file_get_contents($path), true);
    }

    // ---- comparison -------------------------------------------------------

    /**
     * Compare the working tree against the baseline.
     *
     * @return array{missing:list<array>,modified:list<array>,added:list<string>,ok:int,total:int}
     */
    public function check(): array
    {
        $manifest = $this->loadManifest();
        if ($manifest === null) {
            throw new RuntimeException('No baseline. Run `php artisan dev:baseline` (or php tools/demo/integrity.php --snapshot) first.');
        }

        $base = $manifest['files'];
        $current = $this->collect();

        $missing = [];
        $modified = [];
        $ok = 0;

        foreach ($base as $rel => $info) {
            $abs = $this->root . '/' . $rel;
            if (! is_file($abs)) {
                $missing[] = ['path' => $rel, 'lines' => $info['lines'] ?? null];

                continue;
            }
            if (hash_file('sha256', $abs) === $info['sha256']) {
                $ok++;

                continue;
            }
            $modified[] = [
                'path' => $rel,
                'hunks' => $this->fileDiff($rel),
            ];
        }

        $added = [];
        foreach ($current as $rel => $abs) {
            if (! isset($base[$rel])) {
                $added[] = $rel;
            }
        }

        sort($added);

        return [
            'missing' => $missing,
            'modified' => $modified,
            'added' => $added,
            'ok' => $ok,
            'total' => count($base),
        ];
    }

    /** Per-file line diff: baseline copy vs current. Returns formatted change lines. */
    private function fileDiff(string $rel): array
    {
        $baseFile = $this->baselineDir . '/snapshot/' . $rel;
        $curFile = $this->root . '/' . $rel;
        if (! is_file($baseFile) || ! is_file($curFile)) {
            return [];
        }

        $a = preg_split('/\r\n|\r|\n/', (string) file_get_contents($baseFile));
        $b = preg_split('/\r\n|\r|\n/', (string) file_get_contents($curFile));

        // Guard against pathological memory use on huge files.
        if (count($a) > 4000 || count($b) > 4000) {
            return [['op' => 'note', 'text' => 'large file changed (' . count($a) . ' -> ' . count($b) . ' lines); run a full diff manually']];
        }

        $ops = $this->lcsDiff($a, $b);

        $changes = [];
        foreach ($ops as $op) {
            if ($op[0] === '=') {
                continue;
            }
            if ($op[0] === '-') {
                $changes[] = ['op' => '-', 'line' => $op[1] + 1, 'text' => $a[$op[1]]];
            } else {
                $changes[] = ['op' => '+', 'line' => $op[2] + 1, 'text' => $b[$op[2]]];
            }
        }

        if (count($changes) > 80) {
            $head = array_slice($changes, 0, 80);
            $head[] = ['op' => 'note', 'text' => '... (' . (count($changes) - 80) . ' more changed lines)'];

            return $head;
        }

        return $changes;
    }

    /** Classic LCS diff over line arrays. @return list<array{0:string,1:int,2:int}> */
    private function lcsDiff(array $a, array $b): array
    {
        $n = count($a);
        $m = count($b);
        $dp = array_fill(0, $n + 1, array_fill(0, $m + 1, 0));
        for ($i = $n - 1; $i >= 0; $i--) {
            for ($j = $m - 1; $j >= 0; $j--) {
                $dp[$i][$j] = ($a[$i] === $b[$j])
                    ? $dp[$i + 1][$j + 1] + 1
                    : max($dp[$i + 1][$j], $dp[$i][$j + 1]);
            }
        }

        $ops = [];
        $i = 0;
        $j = 0;
        while ($i < $n && $j < $m) {
            if ($a[$i] === $b[$j]) {
                $ops[] = ['=', $i, $j];
                $i++;
                $j++;
            } elseif ($dp[$i + 1][$j] >= $dp[$i][$j + 1]) {
                $ops[] = ['-', $i, $j];
                $i++;
            } else {
                $ops[] = ['+', $i, $j];
                $j++;
            }
        }
        while ($i < $n) {
            $ops[] = ['-', $i, $j];
            $i++;
        }
        while ($j < $m) {
            $ops[] = ['+', $i, $j];
            $j++;
        }

        return $ops;
    }

    // ---- restore ----------------------------------------------------------

    /**
     * Restore files from the baseline snapshot copies (git-free).
     *
     * @param  string|null  $relPath  one path, or null to restore everything that is missing/modified
     * @return list<string> restored relative paths
     */
    public function restore(?string $relPath = null): array
    {
        if (! $this->baselineExists()) {
            throw new RuntimeException('No baseline to restore from.');
        }

        $restored = [];

        if ($relPath !== null) {
            $rel = ltrim(str_replace('\\', '/', $relPath), '/');
            $src = $this->baselineDir . '/snapshot/' . $rel;
            if (! is_file($src)) {
                throw new RuntimeException("Not in baseline: {$rel}");
            }
            $dest = $this->root . '/' . $rel;
            @mkdir(dirname($dest), 0777, true);
            copy($src, $dest);
            $restored[] = $rel;

            return $restored;
        }

        $report = $this->check();
        foreach (array_merge(
            array_map(fn ($m) => $m['path'], $report['missing']),
            array_map(fn ($m) => $m['path'], $report['modified'])
        ) as $rel) {
            $src = $this->baselineDir . '/snapshot/' . $rel;
            if (! is_file($src)) {
                continue;
            }
            $dest = $this->root . '/' . $rel;
            @mkdir(dirname($dest), 0777, true);
            copy($src, $dest);
            $restored[] = $rel;
        }

        return $restored;
    }

    // ---- helpers ----------------------------------------------------------

    private function lineCount(string $abs): int
    {
        $c = 0;
        $fh = fopen($abs, 'r');
        if (! $fh) {
            return 0;
        }
        while (! feof($fh)) {
            $c += substr_count((string) fread($fh, 1 << 16), "\n");
        }
        fclose($fh);

        return $c + 1;
    }

    private function rrmdir(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($it as $f) {
            $f->isDir() ? @rmdir($f->getPathname()) : @unlink($f->getPathname());
        }
        @rmdir($dir);
    }
}
