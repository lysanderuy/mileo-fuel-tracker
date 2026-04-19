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
| `log_date` | DATE | NOT NULL | Date of fill-up |
| `odometer` | INT | NOT NULL | Odometer reading (km) |
| `trip_distance` | DECIMAL(8,2) | NOT NULL | Distance traveled since last log |
| `liters_filled` | DECIMAL(8,2) | NOT NULL | Liters of fuel filled |
| `fuel_price` | DECIMAL(10,2) | NOT NULL | Price paid (₱) |
| `cost_per_liter` | DECIMAL(10,2) | GENERATED (fuel_price / liters_filled) | Calculated cost per liter |
| `cost_per_km` | DECIMAL(10,4) | GENERATED (fuel_price / trip_distance) | Calculated cost per kilometer |
| `efficiency_l100km` | DECIMAL(8,2) | GENERATED ((liters_filled / trip_distance) * 100) | Fuel efficiency |
| `notes` | TEXT | | Optional user notes |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Creation time |
| `updated_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP ON UPDATE | Last update |

**Indexes:**
- `user_id` (for user's logs)
- `vehicle_id` (for vehicle's logs)
- `log_date` (for sorting by date)
- `efficiency_l100km` (for sorting by efficiency) ⭐

---

## Sorting Columns

### 1. Sort by Date
**Column:** `fuel_logs.log_date` (DATE)

**Query Example:**
```sql
SELECT * FROM fuel_logs 
WHERE user_id = ? 
ORDER BY log_date DESC 
LIMIT 100;
```

**Sort Options:**
- `DESC` (newest first) – Default
- `ASC` (oldest first)

---

### 2. Sort by Efficiency (L/100km)
**Column:** `fuel_logs.efficiency_l100km` (DECIMAL(8,2))

**Calculated as:** `(liters_filled / trip_distance) * 100`

**Query Example:**
```sql
SELECT * FROM fuel_logs 
WHERE user_id = ? 
ORDER BY efficiency_l100km DESC 
LIMIT 100;
```

**Sort Options:**
- `DESC` (best efficiency first) – Most efficient
- `ASC` (worst efficiency first) – Least efficient

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
$sort_column = ($sort_by === 'efficiency') ? 'efficiency_l100km' : 'log_date';
```

### Building Dynamic Queries
```php
$query = "SELECT * FROM fuel_logs 
          WHERE user_id = ? 
          ORDER BY $sort_column $order 
          LIMIT ? OFFSET ?";

$stmt = $mysqli->prepare($query);
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
  log_date DATE NOT NULL,
  odometer INT NOT NULL,
  trip_distance DECIMAL(8,2) NOT NULL,
  liters_filled DECIMAL(8,2) NOT NULL,
  fuel_price DECIMAL(10,2) NOT NULL,
  cost_per_liter DECIMAL(10,2) GENERATED ALWAYS AS (fuel_price / liters_filled) STORED,
  cost_per_km DECIMAL(10,4) GENERATED ALWAYS AS (fuel_price / trip_distance) STORED,
  efficiency_l100km DECIMAL(8,2) GENERATED ALWAYS AS ((liters_filled / trip_distance) * 100) STORED,
  notes TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE,
  INDEX idx_user_id (user_id),
  INDEX idx_vehicle_id (vehicle_id),
  INDEX idx_log_date (log_date),
  INDEX idx_efficiency (efficiency_l100km)
);
```

---

## Performance Considerations

### Indexes
The schema includes indexes on:
- `fuel_logs.log_date` – For date sorting
- `fuel_logs.efficiency_l100km` – For efficiency sorting
- `fuel_logs.user_id` – For filtering by user
- `fuel_logs.vehicle_id` – For filtering by vehicle

These indexes significantly speed up sorting and filtering operations.

### Generated Columns
Calculated fields (`cost_per_liter`, `cost_per_km`, `efficiency_l100km`) are stored as **generated columns**. This means:
- ✓ Values are automatically calculated and stored
- ✓ No need to calculate in PHP/JavaScript
- ✓ Can be indexed for fast sorting
- ✓ Always in sync with source data

---

## Sample Data

```sql
INSERT INTO users (email, password, first_name, last_name) VALUES
('lysander.uy@gmail.com', 'hashed_password_here', 'Lysander', 'Uy');

INSERT INTO vehicles (user_id, name, make, model, year, fuel_type, plate_number) VALUES
(1, 'Honda Civic', 'Honda', 'Civic', 2020, 'Gasoline', 'ABC-1234');

INSERT INTO fuel_logs (user_id, vehicle_id, log_date, odometer, trip_distance, liters_filled, fuel_price, notes) VALUES
(1, 1, '2026-04-20', 45200, 150.50, 25.0, 60.50, 'Highway driving'),
(1, 1, '2026-04-15', 45050, 200.0, 35.0, 59.00, 'Mixed driving'),
(1, 1, '2026-04-10', 44850, 175.25, 30.0, 61.00, 'City driving');
```

After insertion, the calculated fields will automatically populate:
- `cost_per_liter` = 2.42 (60.50 / 25.0)
- `cost_per_km` = 0.402 (60.50 / 150.50)
- `efficiency_l100km` = 16.61 ((25.0 / 150.50) * 100)
