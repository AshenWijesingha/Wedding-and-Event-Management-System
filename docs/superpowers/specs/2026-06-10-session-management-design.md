# Secure Session Management — Design Spec

_Date: 2026-06-10_

## Context

EventPro (Laravel 11 + Inertia/Vue, multi-tenant) currently uses the `file` session driver
in dev (`redis` in `.env.example`), with `http_only` cookies but **no `Secure` flag, no
session encryption, idle-only expiry (120 min), and no per-user session visibility or
revocation**. Login regenerates the session ID (fixation handled) and logout invalidates;
beyond that there is no session-security tooling. This spec adds production-grade session
management: hardened cookies, idle + absolute timeouts, active-session/device management
with remote revocation, and re-authentication controls.

"Perfectly secure" is not a claim any system can make; this design hardens sessions to
current industry best practice (OWASP ASVS session-management controls).

### Decisions (from brainstorming)
- **Driver:** switch to `database` (adds a `sessions` table) to enable per-user session
  enumeration and remote revocation.
- **Build all four feature groups:** cookie/config hardening, idle + absolute timeout,
  active-sessions/device management, and re-auth + logout-others on password change.
- **Timeouts:** idle 60 min, absolute 12 h.

## Goals / Non-Goals

**Goals:** hardened session cookies; enforced idle + absolute timeouts with clear re-login;
a self-service "active sessions / devices" view with single + bulk revocation; password
changes invalidate other sessions; password-confirmation gate on sensitive actions.

**Non-Goals:** two-factor authentication (separate feature); SSO/OAuth; anomaly detection /
geo-IP alerting; switching the cache/queue backends. Redis-as-session-store remains a
supported alternative but device management assumes the `database` driver.

## Architecture

### 1. Session store + config hardening
- New migration `create_sessions_table` (standard Laravel schema): `id` (string PK),
  `user_id` (nullable, indexed), `ip_address` (45), `user_agent` (text), `payload`
  (longText), `last_activity` (int, indexed).
- `config/session.php` defaults hardened:
  - `'driver' => env('SESSION_DRIVER', 'database')`
  - `'encrypt' => env('SESSION_ENCRYPT', true)`
  - `secure`: `env('SESSION_SECURE_COOKIE', App::environment('production'))` — Secure flag
    on by default in production, overridable per env (off in local HTTP dev).
  - `'http_only' => true`, `'same_site' => 'lax'`, `'lifetime' => env('SESSION_LIFETIME', 60)`.
- `.env.example`: `SESSION_DRIVER=database`, `SESSION_ENCRYPT=true`,
  `SESSION_SECURE_COOKIE=true`, `SESSION_LIFETIME=60`, `SESSION_SAME_SITE=lax`.
- Local `.env`: `SESSION_DRIVER=database`, keep `SESSION_SECURE_COOKIE` unset/false (HTTP dev),
  `SESSION_LIFETIME=60`.
- `.env.testing`: keep `array` driver for most tests; device-management tests override to
  `database` at runtime.

### 2. Idle + absolute timeout — `EnforceSessionTimeout` middleware
- Applied to the authenticated `web` groups (admin + portal).
- On each authenticated request:
  - If `login_at` missing (legacy session), set it to now.
  - **Absolute:** `now - login_at > 12h` → logout, invalidate, redirect `login` with
    `status = "Your session expired. Please sign in again."`
  - **Idle:** `now - last_activity_at > 60m` → same logout/redirect.
  - Else refresh `last_activity_at = now`.
- Durations come from `config('session.idle_timeout')` (3600) and
  `config('session.absolute_timeout')` (43200), added to `config/session.php` so they are
  tunable and testable. `SESSION_LIFETIME=60` is the cookie/GC backstop.

### 3. Active sessions / device management — `SessionManagementController`
- Routes (named, under the authenticated web middleware) reachable by **every** logged-in
  user; surfaced in the admin Profile area and the portal:
  - `GET  …/sessions` → list
  - `DELETE …/sessions/{id}` → revoke one (not the current one) — `password.confirm`
  - `DELETE …/sessions` → revoke all others ("log out everywhere else") — `password.confirm`
- List = `DB::table('sessions')->where('user_id', $id)->orderByDesc('last_activity')`, each
  row mapped to `{ id, ip, device: parsed(user_agent), last_active, is_current }` where
  `is_current = (id === session()->getId())`. A tiny `UserAgentParser` helper derives
  browser + platform (no external dependency; simple string matching).
- Revoke = delete the row(s); deleting the current session is disallowed (use logout).
- Inertia page `Pages/Settings/Sessions.vue` (admin) and a portal entry reusing the same
  component where practical. Degrades to a notice if `config('session.driver') !== 'database'`.

### 4. Re-auth + password change
- Add `Illuminate\Session\Middleware\AuthenticateSession` to the authenticated web groups so
  that a password change (which rotates the auth password hash) invalidates **all other**
  sessions automatically.
- `ProfileController::updatePassword` calls `Auth::logoutOtherDevices($newPassword)` after a
  successful change, and the controller reports how many sessions were ended.
- Password-confirmation: register Laravel's `password.confirm` routes + a
  `Pages/Auth/ConfirmPassword.vue` screen; gate session-revocation routes, platform-settings
  update, and user deletion with `password.confirm`.

### Cross-cutting
- Middleware order: `AuthenticateSession` must run after `StartSession`/`Authenticate`;
  `EnforceSessionTimeout` after authentication. Register via aliases in `bootstrap/app.php`
  and attach to the `admin`/`portal` route groups (not globally, to avoid affecting guest +
  public site).
- All revocations and "logout everywhere" are written to the existing audit log.

## Data flow
Login → session row created with `user_id`, `login_at` stamped. Each request →
`EnforceSessionTimeout` checks/refreshes activity. Device page → reads `sessions` rows.
Revoke → deletes rows. Password change → `logoutOtherDevices` + `AuthenticateSession` evict
every other session on their next request.

## Error handling
- Timeout/expired session → graceful redirect to login with a flash message, never a raw 419/500.
- `password.confirm` failure → re-prompt; throttled.
- Revoking a non-existent/foreign session id → 404 (scoped to `where user_id = me`).
- Driver ≠ database on the device page → friendly "session listing unavailable" notice.

## Testing
- `SessionTimeoutTest`: idle expiry (>60m) and absolute expiry (>12h) both redirect to login
  and clear auth; activity within limits keeps the session.
- `SessionManagementTest` (force `database` driver): list returns only the actor's rows with
  correct `is_current`; revoke-one deletes that row; revoke-others keeps current; foreign id → 404.
- `PasswordChangeSessionTest`: changing the password ends other sessions.
- `SessionConfigTest`: `secure` + `encrypt` true when `APP_ENV != local`.
- Run full existing suite; verify `AuthenticateSession` doesn't break current login/logout
  (`tests/Feature/Auth/*`).

## Rollout / sequencing
1. Store + config hardening (migration, config, env). 2. Timeout middleware. 3. Device
management. 4. Re-auth + password-change invalidation. 5. Tests. Each step ends green.

## Open risks
- `AuthenticateSession` can log users out unexpectedly if the password hash isn't carried in
  the session; mitigated by adding it to the web group consistently and covering login/logout
  with tests before wiring sensitive routes.
- Switching dev to `database` driver requires running the new migration; documented in the
  step and guarded by `Schema::hasTable`.
