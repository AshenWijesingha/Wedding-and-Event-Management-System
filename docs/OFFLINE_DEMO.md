# Running EventPro Completely Offline

This guide gets EventPro running for a live demonstration **with no internet
connection at all**, on the machine it is already installed on.

> TL;DR — everything is pre-installed and pre-built. Turn the network off,
> double-click **`Start-EventPro.exe`**, and demo at <http://127.0.0.1:8000>.

---

## 1. Why it works offline

EventPro has been deliberately configured so that nothing it needs at runtime
lives on the internet:

| Concern | Setting / state | Effect offline |
| --- | --- | --- |
| Database | `DB_CONNECTION=sqlite` → `database/database.sqlite` | Local file, no DB server |
| Cache | `CACHE_STORE=file` | Local filesystem |
| Queue | `QUEUE_CONNECTION=sync` | Jobs run inline, no worker/broker |
| Sessions | `SESSION_DRIVER=database` (SQLite) | Local |
| Mail | `MAIL_MAILER=log` | Written to `storage/logs/laravel.log`, never sent |
| Search | `SCOUT_DRIVER=null` | No Meilisearch |
| Broadcasting | `BROADCAST_CONNECTION=log` | No websocket service |
| Fonts | **Self-hosted** Inter + Fraunces (`resources/css/fonts.css`, bundled into `public/build/`) | Loaded from the app itself — *this was the only thing that used to need the internet* |
| Front-end assets | Pre-built into `public/build/` | No `npm run dev`, no Vite server |
| PHP / Node deps | `vendor/` and `node_modules/` already installed | No `composer`/`npm` download |

Features that *can* reach the internet only do so on an explicit click and fail
gracefully offline:

- **Online card payments (PayHere)** — only triggered by clicking *Pay Now* in
  the client portal. With no credentials configured it shows
  *"Online payment is not available"*. **Manual payments (cash / bank transfer /
  cheque / card) are fully offline** — use those in the demo.
- **PDF receipts / quotations** — dompdf renders with bundled DejaVu fonts, offline.
- **Seeded social / vendor links** — only open if you click them.
- **Avatars** — fall back to coloured initials when no image is uploaded.

---

## 2. What's already on this PC

- **PHP 8.4** via Laravel Herd-lite (`%USERPROFILE%\.config\herd-lite\bin\php.exe`)
- **Node.js 24** (only needed to *rebuild* assets; not needed to run the demo)
- The project, with `vendor/`, `node_modules/`, `public/build/`, a seeded
  `database/database.sqlite`, and a valid `.env` (`APP_KEY` set).

Nothing else needs to be installed for the demo.

> `setup.bat` (a winget-based installer) and the root `install.cmd` (an unrelated
> Claude Code bootstrap) both require the internet. **Do not run them for the
> offline demo** — they are only for first-time online provisioning.

---

## 3. Start the demo

### Easiest — the launcher
Double-click **`Start-EventPro.exe`** in the project root. It:
1. finds PHP (PATH → Herd-lite),
2. sees that deps/assets/database already exist and **skips** all install steps,
3. starts `php artisan serve` on `127.0.0.1:8000`, and
4. opens your browser and prints the demo logins.

Press `Ctrl+C` or close the window to stop.

### Manual alternative
```cmd
:: from the project root
"%USERPROFILE%\.config\herd-lite\bin\php.exe" artisan serve --host=127.0.0.1 --port=8000
```
Then open <http://127.0.0.1:8000>. (No `npm run dev` — the built assets in
`public/build/` are served as-is.)

> If you ever see an **unstyled** page, make sure `public/hot` does **not** exist
> (it forces Vite dev-server mode). Delete it and reload. A production build is
> already in place, so it should not be there.

---

## 4. Demo logins

All passwords are `password`.

| Role | Email |
| --- | --- |
| Super admin | `admin@eventpro.io` |
| Tenant admin | `nuwan@mangala.lk` (or `admin@demo.eventpro.test`) |
| Staff | `sanduni@mangala.lk` |
| Data-rich tenant | `admin@showcase.eventpro.test` |

The **client portal** is reached by registering a new account at `/register`
(there is no seeded client login). See `docs/ADMIN-LOGINS.md` and
`docs/DEMONSTRATION_GUIDE.md` for the full walkthrough.

---

## 5. Verify it is truly offline (do this once before the demo)

1. Disconnect Wi-Fi / unplug the cable.
2. Start the app (section 3) and open <http://127.0.0.1:8000>.
3. Open the browser dev-tools **Network** tab and reload `/`, `/login`, the
   public site, and an admin page.
4. Confirm: pages are styled with **Inter / Fraunces**, there are **no failed
   requests to `fonts.bunny.net`** (or any external host), and nothing hangs.

If you change the front-end and need to rebuild, do it **while online**:
```cmd
npm run build
```
The fonts are part of the build, so a rebuild keeps the offline behaviour.

---

## 6. Reset the demo data

To return to a clean, freshly-seeded database:
```cmd
"%USERPROFILE%\.config\herd-lite\bin\php.exe" artisan migrate:fresh --seed
```

---

## 7. If something breaks during evaluation

Run **`dev doctor`** — it finds exactly which files/lines changed or were deleted
(git-free) and verifies every CRUD flow. (In PowerShell use `.\dev doctor`; in cmd.exe the
bare `dev doctor` works; `php artisan dev:doctor` works in any shell.) See
**[`docs/EVALUATION_RECOVERY.md`](EVALUATION_RECOVERY.md)** for the full playbook and
the `tools\demo\` scripts (`baseline`, `doctor`, `diff`, `restore`, `backup`).

> Capture the baseline once, while the code is healthy: **`dev baseline`**.
