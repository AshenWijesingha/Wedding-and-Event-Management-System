# EventPro demo recovery kit (`tools/demo/`)

Four double-click Windows scripts that make the live evaluation safe. They are a
**recovery + study aid**: they show you exactly what an evaluator changed or
deleted so you can re-implement it yourself, with a one-click restore as the
fallback. Full playbook: [`docs/EVALUATION_RECOVERY.md`](../../docs/EVALUATION_RECOVERY.md).

| Script | What it does | When to run |
| --- | --- | --- |
| `doctor.cmd`  | Health check: critical files present, app boots, route table, and the 22-check demo-readiness smoke test. Prints `HEALTHY` or lists every `[FAIL]`. | **First**, whenever something might be broken. |
| `diff.cmd`    | Shows `git status` + the full diff **versus the `demo-baseline` tag** — every changed/deleted line. | After `doctor` finds a problem, to see *what* was removed. |
| `restore.cmd` | `restore.cmd path\to\File.php` recovers one file from `demo-baseline`; `restore.cmd` with no argument restores **all** tracked source. | To re-implement by hand (study the diff) or recover instantly (fallback). |
| `backup.cmd`  | Zips the **entire** project — including the git-ignored `vendor\`, `node_modules\`, `public\build\`, `.env` — to `%USERPROFILE%\EventPro-Backup\`. (The seeded sqlite DB is tracked in git, so the baseline already covers it.) | **Once before the evaluation.** Copy the zip to a USB too. |

## One-time setup (already done if you followed the plan)

```cmd
:: from the project root, on a known-good checkout
git tag -a demo-baseline -m "known-good offline demo state"
tools\demo\backup.cmd
```

`diff.cmd` and `restore.cmd` depend on the `demo-baseline` tag. `backup.cmd`
covers the artifacts that git does not track.

## The live-evaluation loop

1. Evaluator deletes/edits some code.
2. `tools\demo\doctor.cmd` → see *that* something broke and roughly where.
3. `tools\demo\diff.cmd` → see *exactly* which lines/files were removed.
4. Re-implement the missing code yourself (use the diff as the answer key), **or**
   `tools\demo\restore.cmd <file>` to recover it.
5. `tools\demo\doctor.cmd` again → confirm `HEALTHY`.

All scripts self-locate PHP (PATH, then Laravel Herd-lite) and need no internet.
