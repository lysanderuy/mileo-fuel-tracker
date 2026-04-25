# Mileo Implementation Guide

> Status note: this document is a legacy implementation plan. The current repository uses a simpler PHP structure with `app/pages`, `app/includes`, `app/api`, and `public/` assets. Treat the current code and README as the source of truth.

This guide explains how to implement the Mileo fuel tracking application with the new professional project structure.

---

## Project Structure Overview

The application is organized into several key layers:

```
public/           → Browser-accessible files only (CSS, JS, HTML, assets)
app/              → Core application logic (pages, API, models, helpers)
config/           → Database connection and configuration
database/         → SQL schemas and migration files
```

**Security Note:** Only the `public/` folder is exposed to the browser. All database logic, configuration, and models are protected outside the web root.

---

## Implementation Roadmap

### Phase 1: Setup

1. **Configure XAMPP**
   - Set DocumentRoot to `mileo-fuel-tracker/public`
   - Or copy only `public/` folder to htdocs

2. **Create Database**
   - Create `mileo_db` in phpMyAdmin
   - Import `/database/schema.sql`

3. **Configure Database Connection**
   - Edit `/config/db.php` with your credentials
   - Test connection from the command line or via browser

---

### Phase 2: Frontend Foundation

**File:** `public/index.php`

Create the main entry point and router:
```php
<?php
// Router that directs requests to appropriate pages
$requested_page = $_GET['page'] ?? 'dashboard';

switch($requested_page) {
  case 'dashboard':
    include '../app/pages/dashboard.php';
    break;
  case 'quick-log':
    include '../app/pages/quick-log.php';
    break;
  case 'history':
    include '../app/pages/history.php';
    break;
  case 'vehicles':
    include '../app/pages/vehicles.php';
    break;
  default:
    include '../app/pages/dashboard.php';
}
?>
```

**Files to create:**
- `public/index.php` (router)
- `public/css/styles.css` (main styles)
- `public/css/responsive.css` (mobile styles)
- `public/css/design-tokens.css` (variables)
- `public/js/app.js` (initialization)
- `public/js/api-client.js` (API communication)
- `public/js/utils.js` (helpers)

---

### Phase 3: UI Components (Includes)

**Directory:** `app/includes/`

Create reusable components:

**`app/includes/header.php`**
```php
<?php
// Header with navigation, user info, etc.
?>
<header class="app-header">
  <div class="container">
    <h1>Mileo</h1>
    <nav><?php include 'navbar.php'; ?></nav>
  </div>
</header>
```

**`app/includes/navbar.php`**
```php
<?php
// Main navigation
?>
<nav class="navbar">
  <ul>
    <li><a href="?page=dashboard">Dashboard</a></li>
    <li><a href="?page=quick-log">Quick Log</a></li>
    <li><a href="?page=history">History</a></li>
    <li><a href="?page=vehicles">Vehicles</a></li>
  </ul>
</nav>
```

**`app/includes/footer.php`**
```php
<?php
// Footer with links, copyright, etc.
?>
<footer class="app-footer">
  <p>&copy; 2026 Mileo. All rights reserved.</p>
</footer>
```

---

### Phase 4: Page Templates (Views)

**Directory:** `app/pages/`

Each page includes the header, footer, and unique content.

**`app/pages/dashboard.php`** (Template)
```php
<?php
include '../includes/header.php';
?>

<main class="dashboard">
  <div class="container">
    <h2>Dashboard</h2>
    <!-- Dashboard content here -->
  </div>
</main>

<?php
include '../includes/footer.php';
?>
```

Create similar templates for:
- `quick-log.php` (fuel logging form)
- `history.php` (logs list with sorting)
- `vehicles.php` (vehicle management)

---

### Phase 5: Database Models

**Directory:** `app/models/`

Create models to handle database operations.

**`app/models/Log.php`** (Template)
```php
<?php
class Log {
  private $conn;
  private $table = 'fuel_logs';
  
  public function __construct($db_connection) {
    $this->conn = $db_connection;
  }
  
  // Create a fuel log
  public function create($user_id, $vehicle_id, $logged_at, $odometer_reading,
                         $trip_distance, $fuel_price_per_unit, $volume_filled,
                         $is_full_tank, $notes = null) {
    $query = "INSERT INTO " . $this->table . " 
              SET user_id = ?, vehicle_id = ?, logged_at = ?,
                  odometer_reading = ?, trip_distance = ?,
                  fuel_price_per_unit = ?, volume_filled = ?,
                  is_full_tank = ?, notes = ?";
    
    $stmt = $this->conn->prepare($query);
    return $stmt->execute([$user_id, $vehicle_id, $logged_at, $odometer_reading,
                           $trip_distance, $fuel_price_per_unit, $volume_filled,
                           $is_full_tank, $notes]);
  }
  
  // Read logs with sorting
  public function getByUserId($user_id, $sort_by = 'date', $order = 'DESC', 
                              $limit = 100, $offset = 0) {
    $sort_column = ($sort_by === 'efficiency') ? 'efficiency_km_l' : 'logged_at';
    
    $query = "SELECT * FROM " . $this->table . " 
              WHERE user_id = ? 
              ORDER BY " . $sort_column . " " . $order . " 
              LIMIT ? OFFSET ?";
    
    $stmt = $this->conn->prepare($query);
    $stmt->execute([$user_id, $limit, $offset]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }
  
  // Update a fuel log
  public function update($id, $logged_at, $odometer_reading, $trip_distance,
                         $fuel_price_per_unit, $volume_filled, $is_full_tank, $notes = null) {
    $query = "UPDATE " . $this->table . " 
              SET logged_at = ?, odometer_reading = ?, trip_distance = ?,
                  fuel_price_per_unit = ?, volume_filled = ?,
                  is_full_tank = ?, notes = ?
              WHERE id = ?";
    
    $stmt = $this->conn->prepare($query);
    return $stmt->execute([$logged_at, $odometer_reading, $trip_distance,
                           $fuel_price_per_unit, $volume_filled, $is_full_tank, $notes, $id]);
  }
  
  // Delete a fuel log
  public function delete($id) {
    $query = "DELETE FROM " . $this->table . " WHERE id = ?";
    $stmt = $this->conn->prepare($query);
    return $stmt->execute([$id]);
  }
}
?>
```

Create similar models for:
- `Vehicle.php` (vehicle operations)
- `User.php` (user operations)

---

### Phase 6: Backend API Endpoints

**Directory:** `app/api/logs/` (organized by resource)

Each endpoint is a separate file that:
1. Validates input
2. Uses models to perform database operations
3. Returns JSON responses

**`app/api/logs/create.php`** (Template)
```php
<?php
header('Content-Type: application/json');

// Include configuration and models
require '../../config/db.php';
require '../../models/Log.php';
require '../../helpers/validation.php';
require '../../helpers/response.php';

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  sendResponse(false, 'Invalid request method', 400);
}

// Validate input
$user_id = validateInput($_POST['user_id'] ?? null, 'int');
$vehicle_id = validateInput($_POST['vehicle_id'] ?? null, 'int');
$logged_at = validateInput($_POST['logged_at'] ?? null, 'datetime');
$odometer_reading = validateInput($_POST['odometer_reading'] ?? null, 'float');
$trip_distance = validateInput($_POST['trip_distance'] ?? null, 'float');
$fuel_price_per_unit = validateInput($_POST['fuel_price_per_unit'] ?? null, 'float');
$volume_filled = validateInput($_POST['volume_filled'] ?? null, 'float');
$is_full_tank = isset($_POST['is_full_tank']) ? (int)(bool)$_POST['is_full_tank'] : 1;
$notes = $_POST['notes'] ?? null;

if (!$user_id || !$vehicle_id || !$odometer_reading || !$trip_distance || 
    !$fuel_price_per_unit || !$volume_filled) {
  sendResponse(false, 'Missing required fields', 400);
}

// Create log
$log = new Log($pdo);
if ($log->create($user_id, $vehicle_id, $logged_at ?? date('Y-m-d H:i:s'),
                 $odometer_reading, $trip_distance, $fuel_price_per_unit,
                 $volume_filled, $is_full_tank, $notes)) {
  sendResponse(true, 'Fuel log created successfully', 200, ['log_id' => $pdo->lastInsertId()]);
} else {
  sendResponse(false, 'Failed to create log', 500);
}
?>
```

Create endpoints for:
- `create.php` (POST - create log)
- `read.php` (GET - fetch logs with sorting)
- `update.php` (PUT - update log)
- `delete.php` (DELETE - delete log)
- `stats.php` (GET - dashboard stats)

---

### Phase 7: Helper Functions

**Directory:** `app/helpers/`

**`app/helpers/response.php`** (Standardized API responses)
```php
<?php
function sendResponse($success, $message, $status_code = 200, $data = null) {
  http_response_code($status_code);
  
  $response = [
    'success' => $success,
    'message' => $message
  ];
  
  if ($data) {
    $response = array_merge($response, $data);
  }
  
  echo json_encode($response);
  exit;
}
?>
```

**`app/helpers/validation.php`** (Input validation)
```php
<?php
function validateInput($value, $type) {
  if ($value === null || $value === '') {
    return null;
  }
  
  switch($type) {
    case 'int':
      return filter_var($value, FILTER_VALIDATE_INT);
    case 'float':
      return filter_var($value, FILTER_VALIDATE_FLOAT);
    case 'date':
      // Validate date format YYYY-MM-DD
      return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : null;
    case 'email':
      return filter_var($value, FILTER_VALIDATE_EMAIL);
    default:
      return $value;
  }
}

function validateSort($sort_by) {
  $valid = ['date', 'efficiency'];
  return in_array($sort_by, $valid) ? $sort_by : 'date';
}

function validateOrder($order) {
  return ($order === 'ASC') ? 'ASC' : 'DESC';
}
?>
```

Create additional helpers:
- `utils.php` (general utilities)
- `auth.php` (authentication/authorization)

---

### Phase 8: Frontend Functionality

**File:** `public/js/app.js`

Initialize the application and wire up event listeners:
```javascript
document.addEventListener('DOMContentLoaded', function() {
  // Initialize sorting controls
  setupSorting();
  
  // Initialize form handlers
  setupFuelLogForm();
  setupEditForm();
});
```

**Files to update:**
- `public/js/api-client.js` (API calls)
- `public/js/fuel-log.js` (logging functionality)
- `public/js/dashboard.js` (stats display)

---

## Implementation Checklist

### Setup
- [ ] Clone repository
- [ ] Configure XAMPP with public/ folder
- [ ] Create `mileo_db` database
- [ ] Import `/database/schema.sql`
- [ ] Update `/config/db.php` credentials
- [ ] Test database connection

### Backend
- [ ] Create `/app/models/Log.php`
- [ ] Create `/app/models/Vehicle.php`
- [ ] Create `/app/helpers/response.php`
- [ ] Create `/app/helpers/validation.php`
- [ ] Create `/app/api/logs/create.php`
- [ ] Create `/app/api/logs/read.php`
- [ ] Create `/app/api/logs/update.php`
- [ ] Create `/app/api/logs/delete.php`
- [ ] Create `/app/api/logs/stats.php`

### Frontend
- [ ] Create `/public/index.php` (router)
- [ ] Create `/public/css/styles.css`
- [ ] Create `/public/css/responsive.css`
- [ ] Create `/public/css/design-tokens.css`
- [ ] Create `/app/includes/header.php`
- [ ] Create `/app/includes/navbar.php`
- [ ] Create `/app/includes/footer.php`
- [ ] Create `/app/pages/dashboard.php`
- [ ] Create `/app/pages/quick-log.php`
- [ ] Create `/app/pages/history.php`
- [ ] Create `/app/pages/vehicles.php`
- [ ] Create `/public/js/app.js`
- [ ] Create `/public/js/api-client.js`
- [ ] Create `/public/js/utils.js`

### Testing
- [ ] Test database connection
- [ ] Test Create Log API
- [ ] Test Read Logs API (with sorting)
- [ ] Test Update Log API
- [ ] Test Delete Log API
- [ ] Test Stats API
- [ ] Test Dashboard page
- [ ] Test Quick Log page
- [ ] Test History page with sorting
- [ ] Test Mobile responsiveness

---

## Development Tips

### API Testing
Use tools like Postman or curl to test API endpoints:
```bash
# Test Create Log
curl -X POST http://localhost/api/logs/create.php \
  -d "user_id=1&vehicle_id=1&log_date=2026-04-20&odometer=45200&trip_distance=150.5&liters_filled=25&fuel_price=60.50"

# Test Read Logs (sorted by efficiency)
curl "http://localhost/api/logs/read.php?sort_by=efficiency&order=DESC"
```

### Database Debugging
Check your queries in phpMyAdmin before implementing in PHP:
```sql
SELECT * FROM fuel_logs WHERE user_id = 1 ORDER BY efficiency_km_l DESC;
```

### Frontend Debugging
Use browser console to debug API calls:
```javascript
fetch('/api/logs/read.php?sort_by=date&order=DESC')
  .then(r => r.json())
  .then(d => console.log(d))
```

---

## Security Considerations

1. **Database Credentials** – Never commit `/config/db.php` with real credentials
2. **Input Validation** – Always validate and sanitize user input
3. **SQL Injection** – Use prepared statements (parameterized queries)
4. **CSRF Protection** – Add CSRF tokens to forms (future enhancement)
5. **Authentication** – Implement user login before accessing data (future phase)

---

## Performance Tips

1. **Indexes** – The database schema includes indexes on sort columns (log_date, efficiency_l100km)
2. **Pagination** – Use LIMIT and OFFSET in API calls to avoid loading all data
3. **Caching** – Consider caching dashboard stats if data doesn't change frequently
4. **Lazy Loading** – Load images and data on-demand in the UI
