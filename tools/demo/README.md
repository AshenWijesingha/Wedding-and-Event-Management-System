# EventPro demo recovery kit (`tools/demo/`)

A **git-free** way to make the live evaluation safe. It captures a known-good
baseline (sha256 hashes + verbatim copies of every source file), then on demand
tells you **exactly which files and lines changed or were deleted**, confirms every
CRUD flow still works, and restores from the baseline — all **offline, without git**.

Engine: `lib/Integrity.php` (plain PHP, no framework — runs even if the app is too
broken to boot). Driven by the `dev:*` artisan commands and the root `dev.cmd`.

## The one command you need: `dev doctor`

From the project root (or double-click `tools\demo\doctor.cmd`):

```cmd
dev doctor          :: find changed/deleted code (with line numbers) + run all CRUD tests
dev run doctor      :: identical ("run" is optional)
dev doctor --no-tests   :: fast - integrity + offline checks only, skips the test suite
```

`dev doctor` reports three sections:

1. **Source integrity vs baseline** — `DELETED`, `MODIFIED` (with the exact
   `- line` / `+ line` changes), and `ADDED` files. This is the git-free "where did
   the code change" detector, and it proves the rest of the code is byte-identical
   to before.
2. **Offline readiness** — no external font/CDN link crept back, assets built,
   `.env` on offline drivers, no stale `public/hot`.
3. **Functional suite** — runs the test suite so every CRUD flow is exercised.

## Commands

| Command | Double-click | Purpose |
| --- | --- | --- |
| `dev baseline` | `tools\demo\baseline.cmd` | Capture the current source as the known-good baseline. **Run once before the evaluation** on a healthy checkout. |
| `dev doctor` | `tools\demo\doctor.cmd` | Full health check (integrity + offline + CRUD tests). |
| `dev doctor --no-tests` | `tools\demo\diff.cmd` | Fast: just show what changed/was deleted. |
| `dev restore <path>` | `tools\demo\restore.cmd <path>` | Restore one file from the baseline. |
| `dev restore --all` | `tools\demo\restore.cmd --all` | Restore every changed/missing file. |
| — | `tools\demo\backup.cmd` | Zip the whole project (incl. git-ignored `vendor/`, `node_modules/`, `public/build/`, `.env`) as an extra safety net. |

The same actions are available as artisan commands: `php artisan dev:baseline`,
`php artisan dev:doctor`, `php artisan dev:restore`. If the app is so broken it
won't boot, `dev doctor` automatically falls back to the standalone checker
(`php tools/demo/integrity.php --check`) so you can still see what was deleted.

## The live-evaluation loop

1. **Before** the evaluation (healthy code): `dev baseline`.
2. Evaluator deletes/edits some code.
3. `dev doctor` → see the exact files/lines that changed, and which CRUD flow broke.
4. Fix it **by hand** using the listed line changes as the answer key, **or**
   `dev restore <path>` to recover it.
5. `dev doctor` again → `HEALTHY`.

The baseline lives in `tools/demo/baseline/` (git-ignored; recreate any time with
`dev baseline`). Full playbook + architecture map:
[`docs/EVALUATION_RECOVERY.md`](../../docs/EVALUATION_RECOVERY.md).
