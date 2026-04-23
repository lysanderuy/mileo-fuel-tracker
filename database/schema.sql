CREATE DATABASE IF NOT EXISTS mileo;
USE mileo;

CREATE TABLE users (
  id         INT          PRIMARY KEY AUTO_INCREMENT,
  name       VARCHAR(100) NOT NULL,
  email      VARCHAR(255) UNIQUE NOT NULL,
  password   VARCHAR(255) NOT NULL,
  created_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE vehicles (
  id           INT          PRIMARY KEY AUTO_INCREMENT,
  user_id      INT          NOT NULL,
  name         VARCHAR(100) NOT NULL,
  make         VARCHAR(50),
  model        VARCHAR(50),
  year         INT,
  fuel_type    VARCHAR(20),
  color        VARCHAR(50),
  plate_number VARCHAR(20),
  created_at   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  updated_at   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_user_id (user_id)
);

CREATE TABLE fuel_logs (
  id                INT            PRIMARY KEY AUTO_INCREMENT,
  user_id           INT            NOT NULL,
  vehicle_id        INT            NOT NULL,
  log_date          DATE           NOT NULL,
  odometer          INT            NOT NULL,
  trip_distance     DECIMAL(8,2)   NOT NULL,
  liters_filled     DECIMAL(8,2)   NOT NULL,
  fuel_price        DECIMAL(10,2)  NOT NULL,
  cost_per_liter    DECIMAL(10,2)  GENERATED ALWAYS AS (fuel_price / liters_filled) STORED,
  cost_per_km       DECIMAL(10,4)  GENERATED ALWAYS AS (fuel_price / trip_distance) STORED,
  efficiency_l100km DECIMAL(8,2)   GENERATED ALWAYS AS ((liters_filled / trip_distance) * 100) STORED,
  notes             TEXT,
  created_at        TIMESTAMP      DEFAULT CURRENT_TIMESTAMP,
  updated_at        TIMESTAMP      DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id)   REFERENCES users(id)    ON DELETE CASCADE,
  FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE,
  INDEX idx_user_id    (user_id),
  INDEX idx_vehicle_id (vehicle_id),
  INDEX idx_log_date   (log_date),
  INDEX idx_efficiency (efficiency_l100km)
);
