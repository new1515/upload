-- Update script for password reset feature
-- Run this if you already have the database without reset columns

ALTER TABLE admins ADD COLUMN IF NOT EXISTS email VARCHAR(100);
ALTER TABLE admins ADD COLUMN IF NOT EXISTS reset_token VARCHAR(255);
ALTER TABLE admins ADD COLUMN IF NOT EXISTS reset_expiry DATETIME;

UPDATE admins SET email = 'admin@school.edu' WHERE username = 'admin';
UPDATE admins SET email = 'superadmin@school.edu' WHERE username = 'superadmin';
