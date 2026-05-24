# Mileo API Documentation

All API requests are routed through `public/index.php` via the `?api=` query parameter.

## Routing

```
GET/POST http://localhost/?api=<resource>/<action>
```

All JSON endpoints require an active session (set by the auth flow). Unauthenticated requests return `401`. All error responses follow this shape:

```json
{ "error": "Descriptive message" }
```

---

## Auth

Auth endpoints are **not** JSON APIs. They use form POST and redirect on completion.

### POST `?api=auth/signup`

Creates a new account. Expects `multipart/form-data` or `application/x-www-form-urlencoded`.

| Field | Required | Notes |
|---|---|---|
| `name` | Yes | Display name |
| `email` | Yes | Must be unique |
| `password` | Yes | Plaintext; bcrypt-hashed on server |

On success: redirects to `?page=dashboard`.  
On failure: redirects to `?page=signup` with a flash error in the session.

---

### POST `?api=auth/login`

| Field | Required |
|---|---|
| `email` | Yes |
| `password` | Yes |

On success: redirects to `?page=dashboard`.  
On failure: redirects to `?page=login` with a flash error and the submitted email pre-filled.

---

### GET `?api=auth/logout`

Destroys the session. Redirects to `?page=landing`.

---

## Vehicles

All vehicle endpoints require session auth and return JSON.

---

### GET `?api=vehicles/list`

Returns all vehicles for the authenticated user (active and archived).

**Response:**
```json
{
  "vehicles": [
    {
      "id": 1,
      "name": "Daily",
      "make": "Toyota",
      "model": "Innova",
      "year": 2014,
      "fuel_type": "Diesel",
      "color": "Silver",
      "plate_number": "AAZ 7574",
      "tank_capacity": 65.0,
      "odometer": 45200,
      "is_archived": false,
      "is_default": true
    }
  ]
}
```

`odometer` reflects the latest logged odometer reading from `fuel_logs`, falling back to the value stored on `vehicles`. `is_default` indicates the user's pinned vehicle. Vehicles are ordered: active first, then by default descending, then name ascending.

---

### POST `?api=vehicles/create`

Creates a new vehicle. JSON body.

| Field | Required | Notes |
|---|---|---|
| `name` | Yes | |
| `make` | No | |
| `model` | No | |
| `year` | No | Integer |
| `fuel_type` | No | |
| `color` | No | |
| `plate_number` | No | Must be unique per user |
| `tank_capacity` | No | Liters |

---

### POST `?api=vehicles/update`

Updates an existing vehicle. JSON body. Requires `id`.

Same fields as create plus `id` (required).

---

### POST `?api=vehicles/archive`

Toggles a vehicle's archived state. JSON body.

| Field | Required |
|---|---|
| `id` | Yes |

---

### POST `?api=vehicles/delete`

Permanently deletes a vehicle and all its fuel logs. JSON body.

| Field | Required |
|---|---|
| `id` | Yes |

---

### POST `?api=vehicles/set-default`

Sets a vehicle as the user's default (pins it for the log form). JSON body.

| Field | Required |
|---|---|
| `id` | Yes |

---

## Fuel Logs

All fuel-log endpoints require session auth and return JSON.

---

### GET `?api=fuel-logs/list`

Returns a paginated list of fuel logs for the authenticated user.

**Query parameters:**

| Parameter | Required | Default | Notes |
|---|---|---|---|
| `vehicle_id` | No | all | Filter to a specific vehicle |
| `page` | No | 1 | Page number (20 records per page) |

**Response:**
```json
{
  "logs": [
    {
      "id": 1,
      "log_date": "2026-04-25",
      "fuel_price": 1800.00,
      "cost_per_liter": 72.00,
      "liters_filled": 25.0,
      "trip_distance": 350.0,
      "odometer": 45200,
      "is_full_tank": true,
      "efficiency_kml": 14.0,
      "cost_per_km": 5.14,
      "prior_efficiency_kml": 13.5,
      "prior_cost_per_km": 5.30,
      "notes": null,
      "vehicle_name": "Daily"
    }
  ],
  "total": 42,
  "page": 1,
  "per_page": 20,
  "has_more": true
}
```

`efficiency_kml` and `prior_efficiency_kml` are `null` for partial fills (`is_full_tank = false`) or when there is no prior full fill-up to compute against.

---

### GET `?api=fuel-logs/get`

Returns a single log with its neighboring odometer values (used by the edit modal).

**Query parameters:**

| Parameter | Required |
|---|---|
| `id` | Yes |

**Response:**
```json
{
  "log": {
    "id": 1,
    "vehicle_id": 1,
    "vehicle_name": "Daily",
    "log_date": "2026-04-25",
    "odometer": 45200,
    "trip_distance": 350.0,
    "liters_filled": 25.0,
    "fuel_price": 1800.00,
    "is_full_tank": true,
    "notes": null
  },
  "prev_odometer": 44850,
  "next_odometer": null
}
```

---

### GET `?api=fuel-logs/detail`

Returns a single log with computed efficiency, comparison to the prior fill-up, and fill-up number (used by the detail page).

**Query parameters:**

| Parameter | Required |
|---|---|
| `id` | Yes |

**Response:**
```json
{
  "log": {
    "id": 1,
    "vehicle_id": 1,
    "vehicle_name": "Daily",
    "log_date": "2026-04-25",
    "odometer": 45200,
    "trip_distance": 350.0,
    "liters_filled": 25.0,
    "fuel_price": 1800.00,
    "is_full_tank": true,
    "notes": null,
    "efficiency_kml": 14.0,
    "cost_per_liter": 72.00,
    "cost_per_km": 5.14
  },
  "prior": {
    "id": 0,
    "log_date": "2026-04-10",
    "efficiency_kml": 13.5,
    "cost_per_km": 5.30
  },
  "fillup_number": 7
}
```

`prior` is `null` when there is no previous full fill-up for that vehicle. `efficiency_kml` in both `log` and `prior` is `null` for partial fills or when a baseline does not exist.

---

### POST `?api=fuel-logs/create`

Creates a new fuel log. JSON body.

| Field | Required | Notes |
|---|---|---|
| `vehicle_id` | Yes | Must belong to the user and not be archived |
| `log_date` | Yes | `YYYY-MM-DD` |
| `odometer` | Yes | Must be ≥ last logged odometer for that vehicle |
| `liters_filled` | Yes | Must be > 0 |
| `fuel_price` | Yes | Total cost; must be > 0 |
| `is_full_tank` | No | Boolean; defaults to `true` |
| `notes` | No | Max 200 characters |
| `manual_trip_override` | No | Boolean; if `true`, `trip_distance` must be supplied |
| `trip_distance` | Conditional | Required when `manual_trip_override` is `true` or when no previous log exists |

`trip_distance` is auto-computed from the odometer delta when a prior log exists and `manual_trip_override` is not set.

**Response (201):**
```json
{
  "fuel_log": {
    "id": 42,
    "vehicle_id": 1,
    "log_date": "2026-04-25",
    "odometer": 45200,
    "trip_distance": 350.0,
    "liters_filled": 25.0,
    "fuel_price": 1800.00,
    "is_full_tank": true,
    "efficiency_note": null,
    "notes": null
  }
}
```

`efficiency_note` is a string warning when `is_full_tank` is `false`.

---

### POST `?api=fuel-logs/update`

Updates an existing log. JSON body. Same fields as create plus `id` (required). The API recomputes `trip_distance` for the updated log and the successor log after the change.

**Response (200):** Same shape as create response.

---

### POST `?api=fuel-logs/delete`

Deletes a log. The successor log's `trip_distance` is recomputed, and the vehicle's odometer is synced to the previous log's odometer. JSON body.

| Field | Required |
|---|---|
| `id` | Yes |

**Response (200):**
```json
{ "success": true }
```

---

## Dashboard

### GET `?api=dashboard/summary`

Returns aggregate stats, recent fill-ups, active vehicles, and fleet overview for the authenticated user.

**Query parameters:**

| Parameter | Required | Notes |
|---|---|---|
| `vehicle_id` | No | Scopes stats and fill-ups to a single vehicle |

**Response:**
```json
{
  "stats": {
    "total_fillups": 42,
    "month_fillups": 5,
    "total_spent": 75000.00,
    "month_spent": 9000.00,
    "total_distance": 14490.0,
    "month_total_distance": 1750.0
  },
  "vehicles": [
    { "id": 1, "name": "Daily", "is_default": true }
  ],
  "active_vehicle_name": "Daily",
  "has_vehicles": true,
  "fillups": [
    {
      "date": "2026-04-25",
      "vehicle_name": "Daily",
      "is_full_tank": true,
      "liters_filled": 25.0,
      "cost_per_liter": 72.00,
      "fuel_price": 1800.00,
      "efficiency_kml": 14.0,
      "cost_per_km": 5.14,
      "prior_efficiency_kml": 13.5,
      "prior_cost_per_km": 5.30,
      "trip_distance": 350.0,
      "notes": null
    }
  ],
  "fleet": [
    {
      "id": 1,
      "name": "Daily",
      "avg_kml": 14.0,
      "avg_cost_km": 5.14,
      "total_spent": 75000.00,
      "total_fillups": 42
    }
  ]
}
```

`fleet` is only populated when the user has more than one active vehicle. `fillups` returns the 5 most recent logs. All numeric efficiency and cost fields may be `null` when there is insufficient data. Note: `stats` does not include `avg_kml`, `avg_cost_km`, or `avg_cost_per_liter` fields — only totals and counts.
