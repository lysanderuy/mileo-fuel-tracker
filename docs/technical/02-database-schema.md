# Mileo Database Schema

This document matches the current `database/schema.sql` file in the repository.

## Database

- Database name: `mileo`

## Tables

### `users`

Stores account information.

| Column | Type | Notes |
|---|---|---|
| `id` | `INT` | Primary key, auto-increment |
| `name` | `VARCHAR(100)` | Required display name |
| `email` | `VARCHAR(255)` | Unique, required |
| `password` | `VARCHAR(255)` | Hashed password |
| `created_at` | `TIMESTAMP` | Defaults to current timestamp |
| `updated_at` | `TIMESTAMP` | Auto-updates on change |

### `vehicles`

Stores vehicles owned by a user.

| Column | Type | Notes |
|---|---|---|
| `id` | `INT` | Primary key, auto-increment |
| `user_id` | `INT` | Foreign key to `users.id` |
| `name` | `VARCHAR(100)` | Required vehicle label |
| `make` | `VARCHAR(50)` | Optional |
| `model` | `VARCHAR(50)` | Optional |
| `year` | `INT` | Optional |
| `fuel_type` | `VARCHAR(20)` | Optional |
| `color` | `VARCHAR(50)` | Optional |
| `plate_number` | `VARCHAR(20)` | Optional |
| `is_archived` | `TINYINT(1)` | `0` for active, `1` for archived |
| `created_at` | `TIMESTAMP` | Defaults to current timestamp |
| `updated_at` | `TIMESTAMP` | Auto-updates on change |

Index:
- `idx_user_id (user_id)`

### `fuel_logs`

Stores individual fill-ups and computed metrics.

| Column | Type | Notes |
|---|---|---|
| `id` | `INT` | Primary key, auto-increment |
| `user_id` | `INT` | Foreign key to `users.id` |
| `vehicle_id` | `INT` | Foreign key to `vehicles.id` |
| `log_date` | `DATE` | Fill-up date |
| `odometer` | `INT` | Odometer reading at fill-up |
| `trip_distance` | `DECIMAL(8,2)` | Distance since last fill-up |
| `liters_filled` | `DECIMAL(8,2)` | Fuel volume added |
| `fuel_price` | `DECIMAL(10,2)` | Total fuel cost |
| `cost_per_liter` | `DECIMAL(10,2)` | Generated: `fuel_price / liters_filled` |
| `cost_per_km` | `DECIMAL(10,4)` | Generated: `fuel_price / trip_distance` |
| `efficiency_l100km` | `DECIMAL(8,2)` | Generated: `(liters_filled / trip_distance) * 100` |
| `notes` | `TEXT` | Optional |
| `created_at` | `TIMESTAMP` | Defaults to current timestamp |
| `updated_at` | `TIMESTAMP` | Auto-updates on change |

Indexes:
- `idx_user_id (user_id)`
- `idx_vehicle_id (vehicle_id)`
- `idx_log_date (log_date)`
- `idx_efficiency (efficiency_l100km)`

## Notes

- The dashboard reads from `fuel_logs` for stats and recent fill-ups.
- The current UI only exposes vehicle CRUD and dashboard overview views.
- Fuel log creation and history pages are planned work, not current UI routes.
