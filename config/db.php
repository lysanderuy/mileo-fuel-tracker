<?php
/**
 * ============================================================
 * MILEO — Database Configuration
 * ============================================================
 * Configure your database connection here.
 * These are the default XAMPP credentials.
 */

// Database connection parameters
$db_host = 'localhost';
$db_name = 'mileo_db';
$db_user = 'root';
$db_pass = ''; // XAMPP default is empty

// Create connection
try {
  $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

  // Check connection
  if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
  }

  // Set charset to UTF-8
  $conn->set_charset('utf8mb4');

  // Define a flag for whether the database is connected
  $db_connected = true;
} catch (Exception $e) {
  $db_connected = false;
  // In production, log this error instead of displaying it
  error_log('Database connection error: ' . $e->getMessage());
}
