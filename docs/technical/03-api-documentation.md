# Mileo API Documentation

> Status note: this document describes the older planned fuel-log API surface. The current repository only exposes auth and vehicle endpoints through `public/index.php` (`?api=auth/*` and `?api=vehicles/*`). Use the live code and README for current behavior.

This document outlines all API endpoints for the Mileo fuel tracking application.

---

## Base URL
```
http://localhost/mileo-fuel-tracker-public/api/logs/
```
(or your configured DocumentRoot)

---

## Endpoints

### 1. Create Fuel Log
**POST** `/logs/create.php`

Creates a new fuel log entry for a vehicle.

**Request Parameters:**
```json
{
  "vehicle_id": 1,
  "logged_at": "2026-04-20T14:30:00",
  "odometer_reading": 45200.00,
  "trip_distance": 150.50,
  "fuel_price_per_unit": 60.50,
  "volume_filled": 25.0,
  "is_full_tank": true,
  "notes": "Highway driving"
}
```

**Response (Success):**
```json
{
  "success": true,
  "message": "Fuel log created successfully",
  "log_id": 42
}
```

---

### 2. Get Fuel Logs
**GET** `/logs/read.php`

Fetches fuel logs with optional sorting and filtering.

**Query Parameters:**
| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `vehicle_id` | integer | No | All | Filter by vehicle |
| `sort_by` | string | No | `date` | Sort field: `date` or `efficiency` |
| `order` | string | No | `DESC` | Sort order: `ASC` or `DESC` |
| `limit` | integer | No | 100 | Number of records to return |
| `offset` | integer | No | 0 | Pagination offset |

**Example Requests:**
```
GET /logs/read.php?sort_by=date&order=DESC
(Newest logs first)

GET /logs/read.php?sort_by=efficiency&order=DESC
(Best fuel efficiency first)

GET /logs/read.php?vehicle_id=1&sort_by=efficiency&order=ASC
(Worst fuel efficiency first for vehicle 1)
```

**Response (Success):**
```json
{
  "success": true,
  "logs": [
    {
      "id": 42,
      "vehicle_id": 1,
      "logged_at": "2026-04-20T14:30:00",
      "odometer_reading": 45200.00,
      "trip_distance": 150.50,
      "fuel_price_per_unit": 60.50,
      "volume_filled": 25.0,
      "is_full_tank": true,
      "total_cost": 1512.50,
      "cost_per_distance_unit": 10.05,
      "efficiency_km_l": 6.02,
      "notes": "Highway driving"
    }
  ],
  "total_records": 24
}
```

---

### 3. Update Fuel Log
**PUT** `/logs/update.php`

Updates an existing fuel log.

**Request Parameters:**
```json
{
  "log_id": 42,
  "logged_at": "2026-04-20T14:30:00",
  "odometer_reading": 45200.00,
  "trip_distance": 150.50,
  "fuel_price_per_unit": 60.50,
  "volume_filled": 25.0,
  "is_full_tank": true,
  "notes": "Highway driving"
}
```

**Response (Success):**
```json
{
  "success": true,
  "message": "Fuel log updated successfully"
}
```

---

### 4. Delete Fuel Log
**DELETE** `/logs/delete.php`

Deletes a fuel log entry.

**Request Parameters:**
```json
{
  "log_id": 42
}
```

**Response (Success):**
```json
{
  "success": true,
  "message": "Fuel log deleted successfully"
}
```

---

### 5. Get Dashboard Stats
**GET** `/logs/stats.php`

Fetches aggregated statistics for the dashboard.

**Query Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `vehicle_id` | integer | No | Filter by vehicle |
| `month` | integer | No | Month (1-12) |
| `year` | integer | No | Year (YYYY) |

**Response (Success):**
```json
{
  "success": true,
  "stats": {
    "total_logs": 24,
    "total_spent": 1500.50,
    "average_cost_per_liter": 59.75,
    "average_cost_per_km": 9.85,
    "average_efficiency": 10.15,
    "best_efficiency": 8.2,
    "worst_efficiency": 12.5,
    "current_month_spent": 350.25,
    "previous_month_spent": 315.75
  }
}
```

---

## Sorting Guide

### Sort by Date
Arranges logs chronologically.

```
sort_by=date&order=DESC  → Newest first
sort_by=date&order=ASC   → Oldest first
```

**Use case:** View recent fill-ups, track changes over time.

---

### Sort by Efficiency (km/L)
Arranges logs by fuel efficiency from best to worst or vice versa.

```
sort_by=efficiency&order=DESC  → Best efficiency first (most km per liter)
sort_by=efficiency&order=ASC   → Worst efficiency first (least km per liter)
```

**Use case:** Identify driving patterns, spot efficiency drops, celebrate improvements.

---

## Error Responses

All endpoints return error responses in this format:

```json
{
  "success": false,
  "message": "Descriptive error message",
  "error_code": "INVALID_PARAM"
}
```

### Common Error Codes
| Code | HTTP Status | Meaning |
|------|-------------|---------|
| `INVALID_PARAM` | 400 | Missing or invalid parameter |
| `NOT_FOUND` | 404 | Resource not found |
| `DB_ERROR` | 500 | Database operation failed |
| `UNAUTHORIZED` | 401 | User not authenticated |

---

## Notes for Frontend Implementation

### Sorting UI
The history page (`pages/history.php`) should include:
1. A dropdown or button group to select sort field
   - "Sort by Date"
   - "Sort by Efficiency"
2. A toggle button for sort order
   - Ascending / Descending

### Example API Call with JavaScript
```javascript
// Fetch logs sorted by efficiency (best first)
fetch('/api/logs/read.php?sort_by=efficiency&order=DESC')
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      displayLogs(data.logs);
    } else {
      showError(data.message);
    }
  });
```

---

## Database Fields Used for Sorting

### Sorting by Date
- Column: `logged_at` (TIMESTAMP)
- Ordered by `logged_at` ASC/DESC

### Sorting by Efficiency
- Column: `efficiency_km_l` = `trip_distance / volume_filled`
- Sorted by `efficiency_km_l` ASC/DESC
