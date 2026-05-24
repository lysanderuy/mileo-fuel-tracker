# Mileo Implementation Guide

This document describes the actual architecture and setup of the Mileo application.

---

## Stack

| Layer | Technology |
|---|---|
| Server | Apache (XAMPP) |
| Language | PHP 8.2 |
| Database | MariaDB 10.4 (MySQLi extension) |
| Frontend | Vanilla JS + CSS (no build step) |
| Auth | PHP sessions |

No frameworks, no package managers, no transpilation.

---

## Directory Layout

```
public/           → Apache document root (index.php router, CSS, JS)
app/api/          → JSON API endpoints
app/pages/        → Server-rendered page templates
app/includes/     → Shared layout (header, footer) and reusable components
config/           → Database connection
database/         → Schema and migrations
```

Only `public/` is exposed to the web. Everything else sits outside the web root.

---

## Setup

1. Install XAMPP with Apache, MySQL/MariaDB, and PHP 8.2+.
2. Set `DocumentRoot` to the `public/` folder.
3. Create the `mileo` database.
4. Import `database/schema.sql`.
5. Update `config/db.php` if your local credentials differ from the defaults (`root` / empty password / `localhost`).

---

## Routing

`public/index.php` handles all requests.

- `?api=<resource>/<action>` → includes `app/api/<resource>/<action>.php`
- `?page=<name>` → includes `app/pages/<name>.php` (defaults to `landing`)

Both `api` and `page` values are validated against explicit allowlists. Unknown values return 404.

Protected pages (`dashboard`, `vehicles`, `history`, `fuel-log-detail`) redirect to login if no session exists. Auth pages (`login`, `signup`) redirect to dashboard if a session already exists.

---

## API Layer

Each endpoint is a standalone PHP file. Common conventions:

- **Session check first.** All JSON endpoints check `$_SESSION['user_id']` and return `401` if missing.
- **JSON request bodies.** POST endpoints read `php://input` via `json_decode`, not `$_POST`.
- **Validation returns 422.** Missing/invalid fields return HTTP 422 with `{ "error": "..." }`.
- **No shared response helper.** Each file sets its own headers and calls `json_encode` directly.
- `config/db.php` is required via `require_once` and exposes `$conn` (MySQLi connection).

Auth endpoints (`auth/login`, `auth/signup`, `auth/logout`) are the exception: they use `$_POST` and redirect rather than returning JSON.

---

## Frontend Layer

Each page has a matching JS file in `public/js/` and CSS file in `public/css/`. Pages include the shared `header.php` and `footer.php`. The fill-up modal is a reusable component (`app/includes/components/fillup_modal.php`) with its own JS and CSS.

JS files communicate with the API via `fetch`, sending JSON bodies and reading JSON responses. There are no shared utilities or framework imports — each file is self-contained.

---

## Database Connection

`config/db.php` opens a MySQLi connection and stores it in `$conn`. A `$db_connected` flag is set on success. All API files use `require_once` to load this file.

---

## Fuel Log Calculations

- **`trip_distance`** is auto-computed from the odometer delta between consecutive logs for the same vehicle. It is overrideable via `manual_trip_override: true` in the API.
- **`cost_per_liter`** and **`cost_per_km`** are MySQL generated (stored) columns — never written directly.
- **Efficiency (`km/L`)** is computed at query time using a window over full fill-ups only. Partial fills (`is_full_tank = false`) are included in the liters sum but do not reset the efficiency baseline.
- **Downstream recomputation:** when a log is updated or deleted, the successor log's `trip_distance` is recalculated to stay consistent with the new odometer ordering.

---

## Security Notes

1. All database queries use MySQLi prepared statements.
2. User input is validated at the API layer before touching the database.
3. `config/db.php` is outside the web root and should not be committed with production credentials.
4. CSRF protection is not implemented — a future enhancement for form submissions.
