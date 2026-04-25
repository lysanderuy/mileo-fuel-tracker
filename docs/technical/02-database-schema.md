# Mileo Database Schema

This document outlines the database structure for the Mileo fuel tracking application.

---

## Database Name
`mileo_db`

---

## Tables

### 1. `users`
Stores user account information.

| Column | Type | Constraints | Description |
|--------|------|-----------|-------------|
| `id` | INT | PRIMARY KEY, AUTO_INCREMENT | User ID |
| `email` | VARCHAR(255) | UNIQUE, NOT NULL | Email address (login) |
| `password` | VARCHAR(255) | NOT NULL | Hashed password |
| `first_name` | VARCHAR(100) | | First name |
| `last_name` | VARCHAR(100) | | Last name |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Account creation time |
| `updated_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP ON UPDATE | Last update |

---

### 2. `vehicles`
Stores vehicle information for multi-vehicle support.

| Column | Type | Constraints | Description |
|--------|------|-----------|-------------|
| `id` | INT | PRIMARY KEY, AUTO_INCREMENT | Vehicle ID |
| `user_id` | INT | FOREIGN KEY (users.id), NOT NULL | Owner of vehicle |
| `name` | VARCHAR(100) | NOT NULL | Vehicle name (e.g., "Honda Civic") |
| `make` | VARCHAR(50) | | Vehicle make (e.g., "Honda") |
| `model` | VARCHAR(50) | | Vehicle model (e.g., "Civic") |
| `year` | INT | | Year manufactured |
| `fuel_type` | VARCHAR(20) | | Fuel type (e.g., "Gasoline", "Diesel") |
| `color` | VARCHAR(50) | | Vehicle color |
| `plate_number` | VARCHAR(20) | | License plate |
| `is_default` | TINYINT(1) | NOT NULL, DEFAULT 0 | Pre-selected vehicle in Quick Log |
| `status` | ENUM | NOT NULL, DEFAULT 'active' | `active` or `archived` |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Creation time |
| `updated_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP ON UPDATE | Last update |

**Index:** `user_id` (for faster queries by user)

---

### 3. `fuel_logs` ⭐ (Primary Table for Sorting)
Stores individual fuel log entries.

| Column | Type | Constraints | Description |
|--------|------|-----------|-------------|
| `id` | INT | PRIMARY KEY, AUTO_INCREMENT | Log ID |
| `user_id` | INT | FOREIGN KEY (users.id), NOT NULL | User who logged |
| `vehicle_id` | INT | FOREIGN KEY (vehicles.id), NOT NULL | Vehicle for this log |
| `logged_at` | TIMESTAMP | NOT NULL, DEFAULT CURRENT_TIMESTAMP | Date and time of fill-up (user-settable) |
| `odometer_reading` | DECIMAL(10,2) | NOT NULL | Odometer reading (km) |
| `trip_distance` | DECIMAL(8,2) | NOT NULL | Distance traveled since last log |
| `fuel_price_per_unit` | DECIMAL(10,2) | NOT NULL | Price paid per liter (₱) |
| `volume_filled` | DECIMAL(8,2) | NOT NULL | Liters of fuel filled |
| `is_full_tank` | TINYINT(1) | NOT NULL, DEFAULT 1 | Whether the tank was fully filled |
| `total_cost` | DECIMAL(10,2) | GENERATED (fuel_price_per_unit × volume_filled) | Calculated total cost |
| `cost_per_distance_unit` | DECIMAL(10,4) | GENERATED (total_cost / trip_distance) | Calculated cost per km |
| `efficiency_km_l` | DECIMAL(8,4) | GENERATED (trip_distance / volume_filled) | Fuel efficiency (km/L) ⭐ |
| `notes` | TEXT | | Optional user notes (max 200 chars) |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Creation time |
| `updated_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP ON UPDATE | Last update |

**Indexes:**
- `user_id` (for user's logs)
- `vehicle_id` (for vehicle's logs)
- `logged_at` (for sorting by date)
- `efficiency_km_l` (for sorting by efficiency) ⭐

---

## Sorting Columns

### 1. Sort by Date
**Column:** `fuel_logs.logged_at` (TIMESTAMP)

**Query Example:**
```sql
SELECT * FROM fuel_logs 
WHERE user_id = ? 
ORDER BY logged_at DESC 
LIMIT 100;
```

**Sort Options:**
- `DESC` (newest first) – Default
- `ASC` (oldest first)

---

### 2. Sort by Efficiency (km/L)
**Column:** `fuel_logs.efficiency_km_l` (DECIMAL(8,4))

**Calculated as:** `trip_distance / volume_filled`

**Query Example:**
```sql
SELECT * FROM fuel_logs 
WHERE user_id = ? 
ORDER BY efficiency_km_l DESC 
LIMIT 100;
```

**Sort Options:**
- `DESC` (best efficiency first) – Most km per liter
- `ASC` (worst efficiency first) – Least km per liter

---

## PHP Implementation Notes

### Validating Sort Parameters
```php
$valid_sort_fields = ['date', 'efficiency'];
$valid_orders = ['ASC', 'DESC'];

$sort_by = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'date';
$order = isset($_GET['order']) ? strtoupper($_GET['order']) : 'DESC';

// Validate to prevent SQL injection
if (!in_array($sort_by, $valid_sort_fields)) {
    $sort_by = 'date';
}
if (!in_array($order, $valid_orders)) {
    $order = 'DESC';
}

// Map frontend field names to database columns
$sort_column = ($sort_by === 'efficiency') ? 'efficiency_km_l' : 'logged_at';
```

### Building Dynamic Queries
```php
$query = "SELECT * FROM fuel_logs 
          WHERE user_id = ? 
          ORDER BY $sort_column $order 
          LIMIT ? OFFSET ?";

$stmt = $pdo->prepare($query);
$stmt->bind_param('iii', $user_id, $limit, $offset);
$stmt->execute();
$result = $stmt->get_result();
```

---

## Database Creation Script

```sql
CREATE DATABASE IF NOT EXISTS mileo_db;
USE mileo_db;

-- Users table
CREATE TABLE users (
  id INT PRIMARY KEY AUTO_INCREMENT,
  email VARCHAR(255) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL,
  first_name VARCHAR(100),
  last_name VARCHAR(100),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Vehicles table
CREATE TABLE vehicles (
  id INT PRIMARY KEY AUTO_INCREMENT,
  user_id INT NOT NULL,
  name VARCHAR(100) NOT NULL,
  make VARCHAR(50),
  model VARCHAR(50),
  year INT,
  fuel_type VARCHAR(20),
  color VARCHAR(50),
  plate_number VARCHAR(20),
  is_default TINYINT(1) NOT NULL DEFAULT 0,
  status ENUM('active', 'archived') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_user_id (user_id)
);

-- Fuel logs table
CREATE TABLE fuel_logs (
  id INT PRIMARY KEY AUTO_INCREMENT,
  user_id INT NOT NULL,
  vehicle_id INT NOT NULL,
  logged_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  odometer_reading DECIMAL(10,2) NOT NULL,
  trip_distance DECIMAL(8,2) NOT NULL,
  fuel_price_per_unit DECIMAL(10,2) NOT NULL,
  volume_filled DECIMAL(8,2) NOT NULL,
  is_full_tank TINYINT(1) NOT NULL DEFAULT 1,
  total_cost DECIMAL(10,2) GENERATED ALWAYS AS (fuel_price_per_unit * volume_filled) STORED,
  cost_per_distance_unit DECIMAL(10,4) GENERATED ALWAYS AS ((fuel_price_per_unit * volume_filled) / trip_distance) STORED,
  efficiency_km_l DECIMAL(8,4) GENERATED ALWAYS AS (trip_distance / volume_filled) STORED,
  notes TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE,
  INDEX idx_user_id (user_id),
  INDEX idx_vehicle_id (vehicle_id),
  INDEX idx_logged_at (logged_at),
  INDEX idx_efficiency (efficiency_km_l)
);
```

---

## Performance Considerations

### Indexes
The schema includes indexes on:
- `fuel_logs.logged_at` – For date sorting
- `fuel_logs.efficiency_km_l` – For efficiency sorting
- `fuel_logs.user_id` – For filtering by user
- `fuel_logs.vehicle_id` – For filtering by vehicle

These indexes significantly speed up sorting and filtering operations.

### Generated Columns
Calculated fields (`total_cost`, `cost_per_distance_unit`, `efficiency_km_l`) are stored as **generated columns**. This means:
- ✓ Values are automatically calculated and stored
- ✓ No need to calculate in PHP/JavaScript
- ✓ Can be indexed for fast sorting
- ✓ Always in sync with source data

---

## Sample Data

```sql
INSERT INTO users (email, password, first_name, last_name) VALUES
('lysander.uy@gmail.com', 'hashed_password_here', 'Lysander', 'Uy');

INSERT INTO vehicles (user_id, name, make, model, year, fuel_type, plate_number, is_default, status) VALUES
(1, 'Honda Civic', 'Honda', 'Civic', 2020, 'Gasoline', 'ABC-1234', 1, 'active');

INSERT INTO fuel_logs (user_id, vehicle_id, logged_at, odometer_reading, trip_distance, fuel_price_per_unit, volume_filled, is_full_tank, notes) VALUES
(1, 1, '2026-04-20 14:30:00', 45200.00, 150.50, 60.50, 25.0, 1, 'Highway driving'),
(1, 1, '2026-04-15 09:15:00', 45049.50, 200.0, 59.00, 35.0, 1, 'Mixed driving'),
(1, 1, '2026-04-10 17:45:00', 44849.50, 175.25, 61.00, 30.0, 1, 'City driving');

-- After insertion, generated columns auto-populate:
-- total_cost = 1512.50 (60.50 × 25.0)
-- cost_per_distance_unit = 10.05 (1512.50 / 150.50)
-- efficiency_km_l = 6.02 (150.50 / 25.0)
```

After insertion, the calculated fields will automatically populate:
- `cost_per_liter` = 2.42 (60.50 / 25.0)
- `cost_per_km` = 0.402 (60.50 / 150.50)
- `efficiency_l100km` = 16.61 ((25.0 / 150.50) * 100)
