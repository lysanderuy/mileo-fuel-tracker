-- Migration: Add tank_capacity and odometer to vehicles
-- Run this against your existing `mileo` database.

ALTER TABLE `vehicles`
  ADD COLUMN IF NOT EXISTS `tank_capacity` decimal(6,2) DEFAULT NULL COMMENT 'Full tank size in liters'
    AFTER `plate_number`,
  ADD COLUMN IF NOT EXISTS `odometer` int(11) DEFAULT NULL COMMENT 'Current odometer reading in km'
    AFTER `tank_capacity`;
