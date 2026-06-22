# EventPro demo launcher

A single, dependency-free Windows executable that boots the whole app for a
demonstration: it ensures dependencies/assets/database exist (first run only),
starts the Laravel server, opens the browser, and prints the demo logins.

## Build

```cmd
launcher\build.cmd
```

This compiles `EventProLauncher.cs` with the .NET Framework C# compiler that
ships with Windows and writes `Start-EventPro.exe` to the project root. The
produced `.exe` is gitignored (it is a build artifact).

## Run

Double-click `Start-EventPro.exe` (it must sit in the project root, next to
`artisan`), or run it from a terminal. It serves the app at
`http://127.0.0.1:8000`.

## What it does

1. Locates PHP (PATH, falling back to Laravel Herd).
2. First run only: creates `.env` + app key, runs `composer install`,
   `npm install`, `npm run build`, and creates + seeds the sqlite database.
3. Starts `php artisan serve` and opens the browser.

No queue worker or mail server is required — assets are pre-built,
`QUEUE_CONNECTION=sync`, and `MAIL_MAILER=log` (mail is written to
`storage/logs`).

## Files

- `EventProLauncher.cs` — launcher source.
- `build.cmd` — compiles the source into `Start-EventPro.exe`.
