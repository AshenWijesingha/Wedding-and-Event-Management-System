# EventPro — Offline Demo & Evaluation Recovery

One-stop guide for running EventPro **with no internet** and surviving an evaluation
that **deletes/edits code and asks you to fix it live** — all without git.

| I want to… | Do this |
| --- | --- |
| Run the app offline | Turn off Wi-Fi, double-click **`Start-EventPro.exe`** → <http://127.0.0.1:8000> |
| Prepare for the evaluation (once) | `dev baseline`  +  `tools\demo\backup.cmd` |
| See what code an examiner changed/deleted | `dev doctor` |
| Recover a deleted/changed file | `dev restore <path>`  or  `dev restore --all` |

Deep docs: [Offline run guide](docs/OFFLINE_DEMO.md) · [Recovery playbook + architecture map](docs/EVALUATION_RECOVERY.md) · [Tooling reference](tools/demo/README.md) · [Demo script](docs/DEMONSTRATION_GUIDE.md)

---

## 1. Run completely offline

Everything needed at runtime is local — there is **no internet dependency**:

- Fonts (Inter + Fraunces) are **self-hosted** (`resources/fonts/`, bundled into `public/build`). The old `fonts.bunny.net` link is gone.
- `.env` uses offline drivers: sqlite DB, file cache, sync queue, log mail, no Meilisearch/Redis.
- `vendor/`, `node_modules/`, and the built assets in `public/build/` are already in place.

**Start:** double-click `Start-EventPro.exe` (project root). It finds PHP (PATH → Laravel
Herd), skips all install steps, serves on `127.0.0.1:8000`, opens the browser, and prints
the logins. Manual alternative:

```cmd
"%USERPROFILE%\.config\herd-lite\bin\php.exe" artisan serve --host=127.0.0.1 --port=8000
```

**Logins** (password `password`): super admin `admin@eventpro.io` · tenant admin
`nuwan@mangala.lk` · staff `sanduni@mangala.lk`. Client portal = register at `/register`.

**Reset demo data:** `php artisan migrate:fresh --seed`.

Full details + a "verify there's no internet" checklist: [docs/OFFLINE_DEMO.md](docs/OFFLINE_DEMO.md).

---

## 2. The evaluation recovery tool (git-free)

A baseline = sha256 hash + a verbatim copy of every source file. The tool compares the
working tree against it by hashing, so it pinpoints **exactly which files and lines changed
or were deleted** — no git, no internet. It even runs when the app is too broken to boot
(standalone `tools/demo/integrity.php`).

### Commands (`dev` from the project root)

```cmd
dev baseline            :: capture the current source as the known-good baseline (run once, on healthy code)
dev doctor              :: report changed/deleted files (+ exact lines), offline readiness, and run all CRUD tests
dev run doctor          :: identical ("run" is optional)
dev doctor --no-tests   :: fast — integrity + offline checks only, skip the test suite
dev restore <path>      :: restore one file from the baseline
dev restore --all       :: restore every changed/missing file
```

Artisan equivalents: `php artisan dev:baseline | dev:doctor | dev:restore`.
Double-click wrappers: `tools\demo\{baseline,doctor,diff,restore}.cmd`.

> **Shell note.** `dev doctor` works as-is in **cmd.exe**. In **PowerShell**, run it as
> **`.\dev doctor`** (PowerShell won't run a program from the current folder without a path
> prefix). The **`php artisan dev:doctor`** form works in *any* shell (PowerShell, cmd, bash)
> and is the safest to use. To get a bare `dev` everywhere in PowerShell, add a function to
> your `$PROFILE`:
> ```powershell
> function dev { & "C:\path\to\project\dev.cmd" @args }
> ```

### The live-evaluation loop

1. **Before** the evaluation, on healthy code: `dev baseline`.
2. Examiner deletes/edits some code.
3. `dev doctor` → shows the exact files/lines that changed and which CRUD flow broke.
4. Fix it **by hand** (the diff is your answer key), **or** `dev restore <path>`.
5. `dev doctor` → confirm `HEALTHY`.

`dev doctor` reports three sections: **source integrity** (deleted/modified/added with line
numbers), **offline readiness** (no CDN leaked back, assets built, offline drivers), and the
**functional suite** (every CRUD flow). Architecture map for hand-fixing:
[docs/EVALUATION_RECOVERY.md](docs/EVALUATION_RECOVERY.md).

> The baseline lives in `tools/demo/baseline/` and is **git-ignored / per-machine**. On any
> fresh clone, run `dev baseline` once before `dev doctor`.

---

## 3. Full backup (the ultimate safety net)

`dev baseline` covers source. The git-ignored artifacts (`vendor/`, `node_modules/`,
`public/build/`, `.env`) are not in git — back them up too:

```cmd
tools\demo\backup.cmd
```

Writes `%USERPROFILE%\EventPro-Backup\eventpro-demo-<timestamp>.zip` (the whole project,
~70 MB). **Copy it to a USB stick.** To restore an artifact, extract the zip with
**Windows Explorer → Extract All** or PowerShell `Expand-Archive` (the archive is a `.zip`,
not a tarball). Rebuilding from scratch *online* instead: `composer install`,
`npm install && npm run build`, `php artisan migrate:fresh --seed`.

---

## What was changed to make this work

- Self-hosted the Inter + Fraunces web fonts and removed the external font CDN (the only
  render-time internet dependency); tightened the CSP to `'self'`.
- Added the git-free integrity tool: `dev:doctor` / `dev:baseline` / `dev:restore`,
  engine `tools/demo/lib/Integrity.php`, standalone `tools/demo/integrity.php`, `dev.cmd`.
- Fixed a footgun: `phpunit.xml` had the test DB commented out, so `php artisan test` ran
  `RefreshDatabase` against the real demo database and wiped it — now isolated to in-memory.

Verified: `dev doctor` HEALTHY (392/392 files unchanged, 310 tests pass, demo DB intact);
break → `dev doctor` pinpoints file + lines → `dev restore` → HEALTHY; backup zip restores
`vendor` correctly.
