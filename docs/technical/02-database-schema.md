# Mileo Database Schema

This document matches the current `database/schema.sql` file in the repository.

## Database

- Database name: `mileo`
- Server: MariaDB 10.4+ / MySQL 8+
- Charset: `utf8mb4`

## Tables

### `users`

Stores account information.

| Column | Type | Notes |
|---|---|---|
| `id` | `INT` | Primary key, auto-increment |
| `name` | `VARCHAR(100)` | Required display name |
| `email` | `VARCHAR(255)` | Unique, required |
| `password` | `VARCHAR(255)` | Bcrypt-hashed password |
| `created_at` | `TIMESTAMP` | Defaults to current timestamp |
| `updated_at` | `TIMESTAMP` | Auto-updates on change |

Indexes:
- `PRIMARY (id)`
- `UNIQUE email`

Indexes:
- `PRIMARY (id)`
- `UNIQUE email`

---

### `vehicles`

Stores vehicles owned by a user.

| Column | Type | Notes |
|---|---|---|
| `id` | `INT` | Primary key, auto-increment |
| `user_id` | `INT` | Foreign key to `users.id` (cascade delete) |
| `name` | `VARCHAR(100)` | Required vehicle label |
| `make` | `VARCHAR(50)` | Optional |
| `model` | `VARCHAR(50)` | Optional |
| `year` | `INT` | Optional |
| `fuel_type` | `VARCHAR(20)` | Optional |
| `color` | `VARCHAR(50)` | Optional |
| `plate_number` | `VARCHAR(20)` | Optional; unique per user |
| `tank_capacity` | `DECIMAL(6,2)` | Full tank size in liters; optional |
| `odometer` | `INT` | Synced from latest fuel log; optional |
| `is_archived` | `TINYINT(1)` | `0` active, `1` archived; default `0` |
| `is_default` | `TINYINT(1)` | `1` = user's default vehicle; default `0` |
| `created_at` | `TIMESTAMP` | Defaults to current timestamp |
| `updated_at` | `TIMESTAMP` | Auto-updates on change |

Indexes:
- `PRIMARY (id)`
- `KEY idx_user_id (user_id)`
- `UNIQUE KEY uk_user_plate (user_id, plate_number)`

---

### `fuel_logs`

Stores individual fill-ups. `cost_per_liter` and `cost_per_km` are generated (stored) columns — never set manually.

| Column | Type | Notes |
|---|---|---|
| `id` | `INT` | Primary key, auto-increment |
| `user_id` | `INT` | Foreign key to `users.id` (cascade delete) |
| `vehicle_id` | `INT` | Foreign key to `vehicles.id` (cascade delete) |
| `log_date` | `DATE` | Fill-up date (`YYYY-MM-DD`) |
| `odometer` | `INT` | Odometer reading at fill-up (km) |
| `trip_distance` | `DECIMAL(8,2)` | Distance since last fill-up (km); auto-computed from odometer delta; nullable for first log |
| `liters_filled` | `DECIMAL(8,2)` | Fuel volume added (L); required |
| `fuel_price` | `DECIMAL(10,2)` | Total cost of this fill-up; required |
| `is_full_tank` | `TINYINT(1)` | `1` = full fill-up; default `1` |
| `cost_per_liter` | `DECIMAL(10,2)` | **Generated:** `fuel_price / liters_filled` |
| `cost_per_km` | `DECIMAL(10,4)` | **Generated:** `fuel_price / trip_distance` (NULL when trip_distance is NULL or 0) |
| `notes` | `TEXT` | Optional; max 200 characters enforced at API layer |
| `created_at` | `TIMESTAMP` | Defaults to current timestamp |
| `updated_at` | `TIMESTAMP` | Auto-updates on change |

Indexes:
- `PRIMARY (id)`
- `KEY idx_user_id (user_id)`
- `KEY idx_vehicle_id_log_date (vehicle_id, log_date)`
- `KEY idx_log_date (log_date)`

## Notes

- `vehicles.odometer` is kept in sync by the API: it is updated to the latest log's odometer on create, update, and delete of a fuel log.
- Efficiency (`km/L` and `L/100km`) is computed at query time, not stored. The `drop_efficiency_l100km_column.sql` migration removed the old stored column.
- `trip_distance` is automatically computed from odometer delta when a previous log exists. It can be overridden via `manual_trip_override` in the API.
- Downstream `trip_distance` recomputation cascades to the successor log on update and delete.
