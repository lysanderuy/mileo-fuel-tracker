-- Migration: Add is_default to vehicles table
-- Created: 2026-04-30

ALTER TABLE `vehicles` ADD `is_default` TINYINT(1) NOT NULL DEFAULT 0;

-- Optional: Set the first vehicle as default for existing users who have vehicles
-- UPDATE vehicles v1
-- JOIN (SELECT MIN(id) as id, user_id FROM vehicles GROUP BY user_id) v2 ON v1.id = v2.id
-- SET v1.is_default = 1;
